<?php

    class Tickets_Page extends Page {
    
        public function getInputs() {
            return array(
                        "actionComplete" => array("custom" => "post"),
                        "actionCheckcomplete" => array("pending_id" => "post", "attempts" => "post"),
                        "actionCheckout" => array("member_amount" => "post", "nonmember_amount" => "post")
                        );
        }
        
        public function actionIndex() {
        
        	$this->parent->template->setSubTitle("Tickets");
                        
            //Prepare data
            $data["is_member"] = $this->parent->auth->isMember();
            $data["is_signed_in"] = $this->parent->auth->isLoggedIn();
            $data["member_sold_out"] = $this->parent->settings->getSetting("member_ticket_sold_out");
            $data["member_available"] = $this->parent->settings->getSetting("member_ticket_available");
            $data["nonmember_sold_out"] = $this->parent->settings->getSetting("nonmember_ticket_sold_out");
            $data["nonmember_available"] = $this->parent->settings->getSetting("nonmember_ticket_available");
            $data["member_price"] = $this->parent->settings->getSetting("member_ticket_price");
            $data["nonmember_price"] = $this->parent->settings->getSetting("nonmember_ticket_price");
            $data["member_date"] = date("D jS F", strtotime($this->parent->settings->getSetting("member_ticket_available_date")));
            $data["nonmember_date"] = date("D jS F", strtotime($this->parent->settings->getSetting("nonmember_ticket_available_date")));
            $data["paypal_email"] = $this->parent->settings->getSetting("paypal_email");
            $data["paypal_return_url"] = $this->parent->settings->getSetting("paypal_return_url");
            $data["paypal_ipn_url"] = $this->parent->settings->getSetting("paypal_ipn_url");
            $data["paypal_url"] = $this->parent->settings->getSetting("paypal_url");
            
            $this->parent->template->outputTemplate(array("template" => "tickets", "data" => $data));
        
        }
        
        public function actionComplete() {
            $this->parent->auth->requireLogin();
            $this->parent->template->setSubtitle("order progress");
            $data["pending_id"] = $this->inputs["custom"];
            $this->parent->template->outputTemplate(array("template" => "order-progress", "styles" => "order-progress.css", "scripts" => "order-progress.js", "data" => $data));
        }
        
        public function actionCheckcomplete() {
        
            $this->parent->auth->requireLogin();
            
            //Order success
            $purchase = $this->parent->db->query("SELECT * FROM `pending_purchases` WHERE pending_purchase_id = '%s'", $this->inputs["pending_id"])->fetch_assoc();
            $purchase_error = $this->parent->db->query("SELECT * FROM `purchase_errors` WHERE pending_purchase_id = '%s'", $this->inputs["pending_id"])->fetch_assoc();
            if (!$purchase) {
                echo json_encode(array("status" => "complete"));
            }
            //Retry
            else if ($purchase && !$purchase_error && $this->inputs["attempts"] <= $this->parent->settings->getSetting("max_order_lookup_attempts")) {
                echo json_encode(array("status" => "retry"));
            }
            //Order failed
            else {
                $title = "Order Failed";
                echo json_encode(array("status" => "failed"));
            }
            
        }
        
        public function actionCheckout() {
            
            $this->parent->auth->requireLogin();
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Validate
            if (($this->inputs["member_amount"] != "" && !is_numeric($this->inputs["member_amount"])) || ($this->inputs["nonmember_amount"] != "" && !is_numeric($this->inputs["nonmember_amount"])) || $this->inputs["member_amount"] + $this->inputs["nonmember_amount"] == 0) $this->errorJSON("You must select at least one product");
            if ($this->inputs["member_amount"] > 0 && !$this->parent->auth->isMember()) $this->errorJSON("Non-members cannot buy member tickets");
            if ($userdata["lan"]["real_name"] == "") $this->errorJSON('You need to fill in your real name in your <a href="index.php?page=account">Account Details</a> before you can buy a ticket');
            
            
            /********************/
            // CHECK AVAILABILITY
            /********************/
            $fields = array("api_key" => $this->parent->settings->getSetting("api_key"),
                            "lan" => $this->parent->settings->getSetting("lan_number"));   
            $fields_string = "";
            foreach($fields as $key=>$value) $fields_string .= $key.'='.$value.'&';
            rtrim($fields_string, '&');
            
            //Set up cURL and request                       
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $this->parent->settings->getSetting("receipt_api_availability_url"));
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            $result = json_decode(curl_exec($ch), true);
            
            //If error
            if (isset($result["error"])) {
                $this->errorJSON($result["error"]);
            } else if (!isset($result["availability"])) {
                $this->errorJSON($result);
            }
            
            //If no tickets available, set tickets available to false and abort
            if ($result["availability"] < $this->inputs["member_amount"] + $this->inputs["nonmember_amount"]) {
                if ($result["availability"] == 0) {
                    $this->parent->settings->changeSetting("member_ticket_available", false);
                    $this->parent->settings->changeSetting("nonmember_ticket_available", false);
                    $this->errorJSON("Tickets are now sold out for LAN" . $this->parent->settings->getSetting("lan_number"));
                } else {
                    $this->errorJSON("Only " . $result["availability"] . " ticket(s) available for LAN35");
                }
            }
                
                
            //Calculate total
            $total = ($this->parent->settings->getSetting("member_ticket_price") * $this->inputs["member_amount"]) + ($this->parent->settings->getSetting("nonmember_ticket_price") * $this->inputs["nonmember_amount"]);
            
            //Insert and return purchase id
            $this->parent->db->query("INSERT INTO `pending_purchases` (num_member_tickets, num_nonmember_tickets, user_id, total) VALUES ('%s', '%s', '%s', '%s')", $this->inputs["member_amount"], $this->inputs["nonmember_amount"], $userdata["xenforo"]["user_id"], $total);
            echo json_encode(array("pending_id" => $this->parent->db->getLink()->insert_id));
            
        }
        
        public function actionIpn() {
        
            //Instantiate the IPN listener
            include('ipnlistener.php');
            $listener = new IpnListener();

            //Tell the IPN listener to use the PayPal test sandbox
            $listener->use_sandbox = false;

            //Try to process the IPN POST
            try {
                $listener->requirePostMethod();
                $verified = $listener->processIpn();
            } catch (Exception $e) {
                $this->error_log($e->getMessage());
                return;
            }
            
            //Verified?
            if ($verified) {
            
                //If payment has expired or failed, remove the pending purchase
                if (in_array($_POST['payment_status'], array("Expired", "Failed", "Voided"))) {
                    $this->parent->db->query("DELETE * FROM `pending_purchases` WHERE pending_purchase_id = '%s'", $_POST["custom"]);
                }
                
                //If the request is for anything but completed, don't care
                if ($_POST["payment_status"] != "Completed") {
                    return;
                }
                
                /********************/
                // FRAUD CHECKS
                /********************/
                $errmsg = "";
                $pending_purchase = $this->parent->db->query("SELECT * FROM `pending_purchases` WHERE pending_purchase_id = '%s'", $_POST["custom"])->fetch_assoc();
                if (!$pending_purchase) {
                    $errmsg .= "Purchase doesn't exist\n";
                }
                if ($pending_purchase && $_POST["mc_gross"] != $pending_purchase["total"]) {
                    $errmsg .= "Received total does not match stored total\n";
                }
                if ($_POST["mc_currency"] != "GBP") {
                    $errmsg .= "Invalid current code\n";
                }
                if ($this->parent->db->query("SELECT * FROM `paypal_purchases` WHERE txn_id = '%s'", $_POST["txn_id"])->fetch_assoc()) {
                    $errmsg .= "Transaction ID has already been processed and used\n";
                }
                
                //Calculate member amounts
                $memberAmount = 0;
                $nonmemberAmount = 0;
                if ($_POST["num_cart_items"] == 2) {
                    $memberAmount = $_POST["quantity1"];
                    $nonmemberAmount = $_POST["quantity2"];
                } else if ($_POST["item_number1"] == "member") {
                    $memberAmount = $_POST["quantity1"];
                } else {
                    $nonmemberAmount = $_POST["quantity1"];
                }
                
                //Check products match
                if ($pending_purchase && $pending_purchase["num_member_tickets"] != $memberAmount) {
                    $errmsg .= "Number of member tickets does not match\n";
                }
                if ($pending_purchase && $pending_purchase["num_nonmember_tickets"] != $nonmemberAmount) {
                    $errmsg .= "Number of non-member tickets does not match\n";
                }
                
                //Check if non-member buying member tickets
                $userdata = $this->parent->auth->getUserById($pending_purchase["user_id"]);
                $userdata = $userdata["xenforo"];
                $group = $this->parent->settings->getSetting("xenforo_member_group_id");
                if ($userdata["user_group_id"] != $group && !in_array($group, explode(",", $userdata["secondary_group_ids"])) && $memberAmount > 0) {
                    $errmsg .= "Non-member attempting to purchase member tickets";
                }
                
                /********************/
                // CHECK AVAILABILITY
                /********************/
                $fields = array("api_key" => $this->parent->settings->getSetting("api_key"),
                                "lan" => $this->parent->settings->getSetting("lan_number"));   
                $fields_string = "";
                foreach($fields as $key=>$value) $fields_string .= $key.'='.$value.'&';
                rtrim($fields_string, '&');
                
                //Set up cURL and request                       
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $this->parent->settings->getSetting("receipt_api_availability_url"));
                curl_setopt($ch,CURLOPT_POST, count($fields));
                curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                $result = json_decode(curl_exec($ch), true);
                
                //If no tickets available, set tickets available to false and abort
                if (isset($result["availability"]) && $result["availability"] < $memberAmount + $nonmemberAmount) {
                    if ($result["availability"] == 0) {
                        $this->parent->settings->changeSetting("member_ticket_available", false);
                        $this->parent->settings->changeSetting("nonmember_ticket_available", false);
                        $errmsg = "Tickets sold out for LAN" . $this->parent->settings->getSetting("lan_number");
                    } else {
                        $errmsg = "Only " . $result["availability"] . " ticket(s) available for LAN35";
                    }
                }
                
                
                if (empty($errmsg)) {
                    /********************/
                    // ISSUE RECEIPT
                    /********************/
                    $landata = $this->parent->auth->getUserLanData($pending_purchase["user_id"]);
                    $fields = array("api_key" => $this->parent->settings->getSetting("api_key"),
                                    "lan" => $this->parent->settings->getSetting("lan_number"),
                                    "member_amount" => $memberAmount,
                                    "nonmember_amount" => $nonmemberAmount,
                                    "name" => $landata["real_name"],
                                    "email" => $userdata["email"],
                                    "customer_forum_name" => $userdata["username"],
                                    "student_id" => isset($userdata["customFields"]["student_id"])?$userdata["customFields"]["student_id"]:"");   
                    $fields_string = "";
                    foreach($fields as $key=>$value) $fields_string .= $key.'='.$value.'&';
                    rtrim($fields_string, '&');
                    
                    //Set up cURL and request                       
                    $ch = curl_init();
                    curl_setopt($ch,CURLOPT_URL, $this->parent->settings->getSetting("receipt_api_issue_url"));
                    curl_setopt($ch,CURLOPT_POST, count($fields));
                    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
                    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                    $result = json_decode(curl_exec($ch), true);
                    
                    //Error
                    if (isset($result["error"])) {
                        $errmsg = "Unable to issue receipt: " . $result["error"] . "\n";
                    } else if (!isset($result["success"])) {
                        $errmsg = "Unable to issue receipt: " . $result . "\n";
                    }
                }
                
                
                /********************/
                // ERROR
                /********************/
                if (!empty($errmsg)) {
                
                    //Log error to file
                    $this->error_log("IPN FAILED FRAUD CHECKS: \n" . $errmsg . "\n\n" . $listener->getTextReport());
                    
                    //Send fraud email to committee
                    $email = new EmailWrapper($this->parent);
                    $email->setTo("committee@lsucs.org.uk");
                    $email->setSubject("IPN Fraud Warning");
                    $email->setBody("IPN FAILED FRAUD CHECKS: \n" . $errmsg . "\n\n\n" . $listener->getTextReport());
                    $email->getMessage()->setContentType("text/plain");
                    $email->send();
                    
                    //Add to database as error
                    $this->parent->db->query("INSERT INTO `purchase_errors` (pending_purchase_id, error_message, text_report) VALUES ('%s', '%s', '%s')", (isset($_POST["custom"])?$_POST["custom"]:""), $errmsg, $listener->getTextReport());
                    
                    return;
                }
                
                /********************/
                // GOOD TO GO
                /********************/
                //If we get here, we are good to say the purchase was completely valid
                $this->parent->db->query("INSERT INTO `paypal_purchases` (old_pending_purchase_id, txn_id, payer_email, user_id) VALUES ('%s', '%s', '%s', '%s')", $pending_purchase["pending_purchase_id"], $_POST["txn_id"], $_POST["payer_email"], $pending_purchase["user_id"]);
                $this->parent->db->query("DELETE FROM `pending_purchases` WHERE pending_purchase_id = '%s'", $pending_purchase["pending_purchase_id"]);
                
            } else {
                $this->error_log("INVALID IPN:: " . $listener->getTextReport());
            }
            
        }
        
        private function error_log($message) {
            file_put_contents(".error.log", $message . "\n", FILE_APPEND);
        }
    
    }

?>
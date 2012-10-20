<?php

    class Api_Page extends Page {
    
        public function getInputs() {
            return array(
                        "actionDeletetickets" => array("purchases" => "post"),
                        "actionIssuetickets" => array("purchases" => "post", "lan" => "post", "member_tickets" => "post", "non_member_tickets" => "post", "forum_name" => "post", "email" => "post", "name" => "post"));
        }
    
        public function actionIndex() {
            $this->authenticate();
            echo $this->errorJSON("Invalid API Method");
        }
        
        public function actionDeletetickets() {
            
            $this->authenticate();
            
            //Validate
            $purchases = json_decode($this->inputs["purchases"], true);
            if (count($purchases) == 0) $this->errorJSON("Invalid purchases supplied");
            
            //Loop purchases
            foreach ($purchases as $purchase) {
                $this->parent->db->query("DELETE FROM `tickets` WHERE purchase_id = '%s'", $purchase);
                $this->parent->db->query("DELETE FROM `unclaimed_tickets` WHERE purchase_id = '%s'", $purchase);
            }
            
            echo json_encode(array("success" => true));
            
        }
        
        public function actionIssuetickets() {
        
            $this->authenticate();
        
            $customerforumdata = $this->parent->auth->getUserByName($this->inputs["forum_name"]);
            $customerforumdata = $customerforumdata["xenforo"];
            
            //Validate
            if ($this->inputs["lan"] != $this->parent->settings->getSetting("lan_number")) $this->errorJSON("Invalid LAN supplied");
            if ($this->inputs["name"] == "") $this->errorJSON("Invalid name");
            if ($this->inputs["email"] == "") $this->errorJSON("Invalid email");
            if ($this->inputs["forum_name"] != "" && $customerforumdata == null) $this->errorJSON("Invalid forum name");
            $purchases = json_decode($this->inputs["purchases"], true);
            if (count($purchases) == 0) $this->errorJSON("No purchases supplied");
            
            //If there isn't a forum name, then we need to input as an unclaimed tickets
            if ($customerforumdata == null) {
            
                $keys = array();
                foreach ($purchases as $purchase) {
                
                    $memberTicket = true;
                    if ($purchase["type"] == "non_member") $memberTicket = false;
                
                    //Generate unique claim key
                    $key = substr(strtolower(md5(uniqid(rand(), true))), 0, 12);
                    $keys[] = array("type" => ($memberTicket?"member":"non_member"), "key" => $key);
                    
                    //Insert
                    $this->parent->db->query("INSERT INTO `unclaimed_tickets` (`purchase_id`, `lan_number`, `name`, `email`, `key`, `member_ticket`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $purchase["purchase_id"], $this->inputs["lan"], $this->inputs["name"], $this->inputs["email"], $key, $memberTicket);
                    
                }
                
                //Return claim keys
                echo json_encode(array("success" => true, "keys" => $keys));
                
                return;
                
            }
            
            //Otherwise we need to input a normal ticket
            else {
                
                foreach ($purchases as $purchase) {
                
                    $memberTicket = true;
                    if ($purchase["type"] == "non_member") $memberTicket = false;
            
                    //Check if user is member
                    $member = false;
                    $memberGroup = $this->parent->settings->getSetting("xenforo_member_group_id");
                    if ($customerforumdata["is_moderator"] || $customerforumdata["is_admin"] || $customerforumdata["user_group_id"] == $memberGroup || in_array($memberGroup, explode(",", $customerforumdata["secondary_group_ids"]))) {
                        $member = true;
                    }

                    //If user is not a member and they have bought a member ticket and supplied a forum name, error
                    if ($memberTicket && !$member) {
                        $this->errorJSON("Member tickets cannot be issued to non-member forum accounts " . $customerforumdata["user_group_id"]);
                    }
                    
                    //Work out who to assign ticket to, if anyone
                    $assignID = "";
                    $prevTicket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $customerforumdata["user_id"], $this->inputs["lan"])->fetch_assoc();
                    //If purchasing member ticket and user has non-member assigned, unassign that one and assign the new one
                    if ($prevTicket && $member && $prevTicket["member_ticket"] == 0 && $memberTicket) {
                        $this->parent->db->query("UPDATE `tickets` SET assigned_forum_id = '' WHERE ticket_id = '%s'", $prevTicket["ticket_id"]);
                        $assignID = $customerforumdata["user_id"];
                    }
                    //No previous ticket so assign anyway
                    else if (!$prevTicket) {
                        $assignID = $customerforumdata["user_id"];
                    }
                    
                    //Insert time
                    $this->parent->db->query("INSERT INTO `tickets` (purchase_id, lan_number, member_ticket, purchased_forum_id, assigned_forum_id) VALUES ('%s', '%s', '%s', '%s', '%s')", $purchase["purchase_id"], $this->inputs["lan"], $memberTicket, $customerforumdata["user_id"], $assignID);
                
                }
                
                //Return
                echo json_encode(array("success" => true));
                
                return;
                
            }
            
        }
        
        private function authenticate() {
            $this->parent->auth->requireNotLoggedIn();
            if (!isset($_POST["api_key"]) || sha1($_POST["api_key"]) != sha1($this->parent->settings->getSetting("api_key"))) {
                $this->errorJSON("Invalid API Key");
            }
        }
    
    }

?>
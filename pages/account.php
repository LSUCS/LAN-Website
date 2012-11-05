<?php

    class Account_Page extends Page {
        
        public function getInputs() {
            return array(
                        "actionEditvandetails" => array("phone" => "post", "address" => "post", "postcode" => "post", "collection" => "post", "dropoff" => "post", "availability" => "post"),
                        "actionEditgamedetails" => array("steam" => "post", "currently_playing" => "post", "favourite_games" => "post"),
                        "actionSuggestgame" => array("term" => "get"),
                        "actionAssignticket" => array("name" => "post", "ticket_id" => "post"),
                        "actionAutocomplete" => array("term" => "get"),
                        "actionClaimticket" => array("code" => "post", "email" => "post"),
                        "actionEditaccountdetails" => array("name" => "post", "emergency_contact_name" => "post", "emergency_contact_number" => "post"),
                        "actionLogin" => array("returnurl" => "get", "username" => "post"),
                        "actionAuthlogin" => array("password" => "post", "username" => "post", "returnurl" => "post")
                        );
        }
        
        public function actionDate() {
            echo $this->parent->settings->getSetting("lan_start_date");
        }
    
        public function actionIndex() {
            $this->parent->auth->requireLogin("index.php?page=account");
            $this->parent->template->setSubTitle("Account Details");
            $data["member"] = $this->parent->auth->isMember();
            $this->parent->template->outputTemplate(array("template" => "account", "data" => $data));
        }
        
        public function actionDeletevan() {
        
            $this->parent->auth->requireLogin();
            
            //Check if van is enabled
            if ($this->parent->settings->getSetting("disable_lan_van")) $this->errorJSON("LAN Van is currently disabled - if you need to change your request, contact a committee member ASAP");
            
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Get van
            $van = $this->parent->db->query("SELECT * FROM `lan_van` WHERE user_id = '%s' AND lan = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"));
            if (!$van) $this->errorJSON("No van requests exist to delete");
            
            $this->parent->db->query("DELETE FROM `lan_van` WHERE user_id = '%s' AND lan = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"));
        
        }
        
        public function actionEditvandetails() {
        
            $this->parent->auth->requireLogin();
            
            //Check if van is enabled
            if ($this->parent->settings->getSetting("disable_lan_van")) $this->errorJSON("LAN Van is currently disabled - if you need to change your request, contact a committee member ASAP");
            
            if ($this->inputs["collection"] == "false") $this->inputs["collection"] = 0;
            else $this->inputs["collection"] = 1;
            if ($this->inputs["dropoff"] == "false") $this->inputs["dropoff"] = 0;
            else $this->inputs["dropoff"] = 1;
            
            $userdata = $this->parent->auth->getActiveUserData();
            $userID = $userdata["xenforo"]["user_id"];
            $lan = $this->parent->settings->getSetting("lan_number");
            
            //Validation
            if ($userdata["lan"]["real_name"] == "") $this->errorJSON("Please make sure you set your Real Name at the top of this page before requesting the LAN Van!");
            if ($this->inputs["phone"] == "" || !is_numeric($this->inputs["phone"])) $this->errorJSON("Please supply a phone number");
            if ($this->inputs["address"] == "") $this->errorJSON("Please supply an address");
            if ($this->inputs["postcode"] == "") $this->errorJSON("Invalid postcode");
            if ($this->inputs["availability"] == "") $this->errorJSON("Please specify a time you are available");
            if ($this->inputs["collection"] == 0 && $this->inputs["dropoff"] == 0) $this->errorJSON("You must select at least dropoff or collection, if not both");
            
            //Check for ticket
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userID, $lan)->fetch_assoc();
            if (!$ticket) $this->errorJSON("You may only request the LAN Van if you have a ticket assigned to your account");
            
            //Check if already exists
            $van = $this->parent->db->query("SELECT * FROM `lan_van` WHERE user_id = '%s' AND lan = '%s'", $userID, $lan)->fetch_assoc();
            
            //Update
            if ($van) $this->parent->db->query("UPDATE `lan_van` SET phone_number = '%s', address = '%s', postcode = '%s', collection = '%s', dropoff = '%s', available = '%s' WHERE user_id = '%s' AND lan = '%s'", $this->inputs["phone"], $this->inputs["address"], $this->inputs["postcode"], $this->inputs["collection"], $this->inputs["dropoff"], $this->inputs["availability"], $userID, $lan);
            
            //Insert
            else $this->parent->db->query("INSERT INTO `lan_van` (user_id, lan, phone_number, address, postcode, collection, dropoff, available) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", $userID, $lan, $this->inputs["phone"], $this->inputs["address"], $this->inputs["postcode"], $this->inputs["collection"], $this->inputs["dropoff"], $this->inputs["availability"]);
            
        }
        
        public function actionEditgamedetails() {
        
            $this->parent->auth->requireLogin();
            
            //If steam name is supplied, validate it
            if ($this->inputs["steam"] != "") {
            
                //Get data from steam community
                $page = file_get_contents("http://steamcommunity.com/id/" . $this->inputs["steam"]. "/?xml=1");
                $steam = new SimpleXMLElement($page, LIBXML_NOCDATA);
                
                //Invalid?
                if ($steam->error) {
                    $this->errorJSON("Invalid Steam Community Name");
                }
            }
            
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Update
            $this->parent->db->query("UPDATE `user_data` SET steam_name = '%s', currently_playing = '%s' WHERE user_id = '%s'", $this->inputs["steam"], $this->inputs["currently_playing"], $userdata["xenforo"]["user_id"]);
            $this->parent->db->query("DELETE FROM `user_games` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"]);
            if (is_array($this->inputs["favourite_games"])) {
                foreach ($this->inputs["favourite_games"] as $game) {
                    $this->parent->db->query("INSERT INTO `user_games` (user_id, game) VALUES ('%s', '%s')", $userdata["xenforo"]["user_id"], $game);
                }
            }
        
        }
        
        public function actionSuggestgame() {
            $data = file_get_contents("http://store.steampowered.com/search/suggest?term=" . urlencode($this->inputs["term"]));
            $return = array();
            if (strpos($data, "<li>") > -1) {
                $results = explode("<li>", substr(str_replace(array("</li>", "<ul>", "</ul>"), "", $data), 4));
                foreach ($results as $key => $result) {
                    if (stripos($result, "DLC") < 1) {
                        $return[] = $result;
                    }
                }
                echo json_encode($return);
            }
        }
        
        public function actionCheckdetails() {
            if (!$this->parent->auth->isLoggedIn()) return;
            if (!$this->parent->auth->isPhysicallyAtLan()) return;
            
            $userdata = $this->parent->auth->getActiveUserData();
            $message = null;
            
            //Ticket checks
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
            if (!$ticket) $message = "Your account does not have any tickets assigned to it. Please visit the registration desk";
            else if ($ticket["activated"] == 0) $message = "You may not enter the LAN until you have signed in and had your ticket activated. Please visit the registration desk";
            
            //Detail checks
            else if ($userdata["lan"]["real_name"] == "") $message = "Full name is required";
            elseif ($userdata["lan"]["emergency_contact_name"] == "" || $userdata["lan"]["emergency_contact_number"] == "") $message = "Emergency contact is required";
            
            if ($message != null) echo json_encode(array("incomplete" => true, "message" => $message));
        }
        
        public function actionAssignticket() {
        
            $this->parent->auth->requireLogin();
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Validate
            if (empty($this->inputs["name"])) $this->errorJSON("Please supply a forum name to assign to");
            
            //Check ticket
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s' AND assigned_forum_id = '' AND purchased_forum_id = '%s'", $this->inputs["ticket_id"], $userdata["xenforo"]["user_id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket - either you did not buy it or it doesn't exist");
            
            //Check name
            $assigneduserdata = $this->parent->auth->getUserByName($this->inputs["name"]);
            $assigneduserdata = $assigneduserdata["xenforo"];
            if (!$assigneduserdata) $this->errorJSON("Username not found");
            
            //Check valid account
            if ($assigneduserdata["user_id"] == $userdata["xenforo"]["user_id"]) $this->errorJSON("You cannot assign multiple tickets to yourself");
            $memberGroup = $this->parent->settings->getSetting("xenforo_member_group_id");
            if (!$assigneduserdata["is_moderator"] && !$assigneduserdata["is_admin"] && $assigneduserdata["user_group_id"] != $memberGroup && !in_array($memberGroup, explode(",", $assigneduserdata["secondary_group_ids"]))) {
                if ($ticket["member_ticket"] == 1) $this->errorJSON("Cannot assign member ticket to non-member forum account");
                $assignedmember = false;
            } else {
                $assignedmember = true;
            }
            
            //Check if account already has assigned ticket
            $previousTicket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $this->parent->settings->getSetting("lan_number"), $assigneduserdata["user_id"])->fetch_assoc();
            if ($previousTicket && $previousTicket["member_ticket"] == 0 && $assignedmember) {
                $this->parent->db->query("UPDATE `tickets` SET assigned_forum_id = '' WHERE ticket_id = '%s'", $previousTicket["ticket_id"]);
            }
            else if ($previousTicket) {
                $this->errorJSON("Account already has a ticket assigned to it");
            }
            
            //Change id
            $this->parent->db->query("UPDATE `tickets` SET assigned_forum_id = '%s' WHERE ticket_id = '%s'", $assigneduserdata["user_id"], $ticket["ticket_id"]);
            
        }
        
        function actionAutocomplete() {
            
            $this->parent->auth->requireLogin();
            $userdata = $this->parent->auth->getActiveUserData();
            $users = $this->parent->auth->getUsersByName($this->inputs["term"]);
            
            //Loop
            $return = array();
            if (!$users) die(json_encode($return));
            foreach ($users as $user) {
                if ($user["xenforo"]["user_id"] != $userdata["xenforo"]["user_id"]) {
                    $return[] = $user["xenforo"]["username"];
                }
            }
            echo json_encode($return);
            
        }
        
        function actionClaimticket() {
        
            $this->parent->auth->requireLogin();
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Check if valid code
            if ($this->inputs["code"] == "") $this->errorJSON("Invalid code length");
            
            //Attempt to retrieve
            $unclaimed_ticket = $this->parent->db->query("SELECT * FROM `unclaimed_tickets` WHERE `key`='%s'", $this->inputs["code"])->fetch_assoc();
            if (!$unclaimed_ticket) $this->errorJSON("Invalid code - if this is a mistake, please contact committee@lsucs.org.uk");
            
            //Check email
            if ($this->inputs["email"] != $unclaimed_ticket["email"]) $this->errorJSON("Email address supplied does not match ticket");
            
            //Check member status
            if ($unclaimed_ticket["member_ticket"] == 1 && !$this->parent->auth->isMember()) $this->errorJSON("Cannot claim Member Ticket on a Non-Member account. If this is a mistake, please contact committee@lsucs.org.uk");
            
            //Work out who to assign ticket to, if anyone
            $assignID = "";
            $prevTicket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $unclaimed_ticket["lan_number"])->fetch_assoc();
            //If purchasing member ticket and user has non-member assigned, unassign that one and assign the new one
            if ($prevTicket && $this->parent->auth->isMember() && $prevTicket["member_ticket"] == 0 && $unclaimed_ticket["member_ticket"] == 1) {
                $this->parent->db->query("UPDATE `tickets` SET assigned_forum_id = '' WHERE ticket_id = '%s'", $prevTicket["ticket_id"]);
                $assignID = $userdata["xenforo"]["user_id"];
            }
            //No previous ticket so assign anyway
            else if (!$prevTicket) {
                $assignID = $userdata["xenforo"]["user_id"];
            }
            
            //Insert new ticket
            $this->parent->db->query("INSERT INTO `tickets` (purchase_id, lan_number, member_ticket, purchased_forum_id, assigned_forum_id) VALUES ('%s', '%s', '%s', '%s', '%s')", $unclaimed_ticket["purchase_id"], $unclaimed_ticket["lan_number"], $unclaimed_ticket["member_ticket"], $userdata["xenforo"]["user_id"], $assignID);
            
            //Delete claim
            $this->parent->db->query("DELETE FROM `unclaimed_tickets` WHERE unclaimed_id = '%s'", $unclaimed_ticket["unclaimed_id"]);
        }
        
        function actionEditaccountdetails() {
            $this->parent->auth->requireLogin();
            
            //Validate
            if ($this->inputs["name"] == "") $this->errorJSON("Invalid name supplied");
            if ($this->inputs["emergency_contact_name"] == "") $this->errorJSON("Invalid emergency contact name supplied");
            if ($this->inputs["emergency_contact_number"] == "") $this->errorJSON("Invalid emergency contact number supplied");
            
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Update
            $this->parent->db->query("UPDATE `user_data` SET real_name = '%s', emergency_contact_name = '%s', emergency_contact_number = '%s' WHERE user_id = '%s'", $this->inputs["name"], $this->inputs["emergency_contact_name"], $this->inputs["emergency_contact_number"], $userdata["xenforo"]["user_id"]);
            
        }
        
        public function actionGetdetails() {
        
            $this->parent->auth->requireLogin();
            $userdata = $this->parent->auth->getActiveUserData();
            $return = $userdata["lan"];
            
            //Get games
            $res = $this->parent->db->query("SELECT * FROM `user_games` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"]);
            while ($row = $res->fetch_assoc()) $return["games"][] = $row["game"];
            
            //Get van
            $van = $this->parent->db->query("SELECT * FROM `lan_van` WHERE user_id = '%s' AND lan = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
            if ($van) $return["van"] = $van;
            $return["van_enabled"] = !$this->parent->settings->getSetting("disable_lan_van");
            
            echo json_encode($return);
        }
        
        public function actionGettickets() {
        
            $this->parent->auth->requireLogin();
            
            $userdata = $this->parent->auth->getActiveUserData();
            
            $res = $this->parent->db->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND (purchased_forum_id = '%s' OR assigned_forum_id = '%s')", $this->parent->settings->getSetting("lan_number"), $userdata["xenforo"]["user_id"], $userdata["xenforo"]["user_id"]);
            $tickets = array();
            while ($ticket = $res->fetch_assoc()) {
                $purchaser = $this->parent->auth->getUserById($ticket["purchased_forum_id"]);
                $ticket["purchased_forum_name"] = $purchaser["xenforo"]["username"];
                if ($ticket["assigned_forum_id"] != null && $ticket["assigned_forum_id"] != 0) {
                    $assigned  = $this->parent->auth->getUserById($ticket["assigned_forum_id"]);
                    $ticket["assigned_forum_name"] = $assigned["xenforo"]["username"];
                } else $ticket["assigned_forum_name"] = "";
                $tickets[] = $ticket;
            }
            
            echo json_encode($tickets);
        }
        
        public function actionLogin($invalid = false) {
            $this->parent->auth->requireNotLoggedIn();
            $this->parent->template->setSubTitle("Login");
            
            //Set up data and output template
            $DataBag = array();
            $DataBag["username"] = $this->inputs["username"];
            $DataBag["invalid"] = $invalid;
            $DataBag["returnurl"] = $this->inputs["returnurl"];
            $this->parent->template->outputTemplate(array("template" => "login", "data" => $DataBag, "styles" => "login.css"));
        }
        
        public function actionLogout() {
            $this->parent->auth->requireLogin();
            $this->parent->auth->logoutUser();
            header("location:index.php");
        }
        
        public function actionAuthlogin() {
            $this->parent->auth->requireNotLoggedIn();
            if ($this->parent->auth->loginUser($this->inputs["username"], $this->inputs["password"])) {
                header("location:" . $this->inputs["returnurl"]);
            } else {
                $this->actionLogin(true);
            }
        }
    
    }

?>
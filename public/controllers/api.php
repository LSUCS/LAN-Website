<?php

    class Api_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "updategameservers": return array("data" => "notnull"); break;
                case "lanauth": return array("username" => "notnull", "password" => "notnull", "seat" => "notnull"); break;
                case "deletetickets": return array("purchases" => "notnull"); break;
                case "issuetickets": return array("purchases" => "notnull", "lan" => array("notnull", "int"), "member_tickets" => "notnull", "non_member_tickets" => "notnull", "forum_name" => "notnull", "email" => array("email", "notnull"), "name" => "notnull"); break;
            }
        }
    
        public function get_Index() {
            $this->authenticate();
            echo $this->errorJSON("Invalid API Method");
        }
		
		public function get_Getgameservers() {
			$this->authenticate();
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `game_servers` WHERE local = 1 AND source = 1");
			$output = array();
			while ($row = $res->fetch_assoc()) {
				$output[] = array("server_id" => $row["server_id"], "hostname" => $row["hostname"], "port" => $row["port"]);
			}
			echo json_encode($output);
		}
		
		public function post_Updategameservers($inputs) {
			$this->authenticate();
			$servers = json_decode($inputs["data"], true);
			if (!is_array($servers)) $this->errorJSON("Inputted data is not an array");
			
			foreach ($servers as $server) {
				//Check if valid server
				$entry = LanWebsite_Main::getDb()->query("SELECT * FROM `game_servers` WHERE server_id = '%s'", $server["server_id"])->fetch_assoc();
				if (!$entry) continue;
				
				//Add to db
				LanWebsite_Main::getDb()->query("UPDATE `game_servers` SET name='%s', game='%s', game_icon='%s', num_players='%s', max_players='%s', password_protected='%s', map='%s', players='%s' WHERE server_id = '%s'", $server["name"], $server["game"], $server["game_icon"], $server["num_players"], $server["max_players"], $server["password_protected"], $server["map"], $server["players"], $server["server_id"]);
			}
		}
        
        public function post_Lanauth($inputs) {
        
            $this->authenticate();
            $inputs["seat"] = strtoupper($inputs["seat"]);
            $lan = LanWebsite_Main::getSettings()->getSetting("lan_number");
            
            //Check login details
            if (!LanWebsite_Main::getAuth()->validateCredentials($inputs["username"], $inputs["password"])) $this->errorJSON("Invalid login credentials");
            
            $user = LanWebsite_Main::getUserManager()->getUserByName($inputs["username"]);
            
            //Check activated ticket
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $user->getUserId(), $lan)->fetch_assoc();
            if (!$ticket) $this->errorJSON("You do not have a ticket for the LAN, please visit the front desk");
            if ($ticket["activated"] == 0) $this->errorJSON("Your ticket has not been activated. Go to the front desk");
            
            //Check seat
            $seats = explode("\n", file_get_contents("data/seats.txt"));
            if ($inputs["seat"] == "" || !in_array($inputs["seat"], $seats)) $this->errorJSON("Invalid seat");
            $occupied = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE seat = '%s' AND lan_number = '%s'", $inputs["seat"], $lan)->fetch_assoc();
            if ($occupied && $occupied["assigned_forum_id"] != $user->getUserId()) $this->errorJSON("That seat is already occupied");
            
            
            //Update ticket with seat
            LanWebsite_Main::getDb()->query("UPDATE `tickets` SET seat = '%s' WHERE ticket_id = '%s'", $inputs["seat"], $ticket["ticket_id"]);
            
            //Everything went ok
            echo true;
            
        }
        
        public function post_Deletetickets($inputs) {
            
            $this->authenticate();
            
            //Validate
            $purchases = json_decode($inputs["purchases"], true);
            if (count($purchases) == 0) $this->errorJSON("Invalid purchases supplied");
            
            //Loop purchases
            foreach ($purchases as $purchase) {
                LanWebsite_Main::getDb()->query("DELETE FROM `tickets` WHERE purchase_id = '%s'", $purchase);
                LanWebsite_Main::getDb()->query("DELETE FROM `unclaimed_tickets` WHERE purchase_id = '%s'", $purchase);
            }
            
            echo json_encode(array("success" => true));
            
        }
        
        public function post_Issuetickets($inputs) {
        
            $this->authenticate();
        
            $customer = LanWebsite_Main::getUserManager()->getUserByName($inputs["forum_name"]);
            
            //Validate
            if ($inputs["lan"] != LanWebsite_Main::getSettings()->getSetting("lan_number")) $this->errorJSON("Invalid LAN supplied");
            if ($this->isInvalid("name")) $this->errorJSON("Invalid name");
            if ($this->isInvalid("email")) $this->errorJSON("Invalid email");
            if (strlen($inputs["forum_name"]) > 0 && !$customer) $this->errorJSON("Invalid forum name");
            $purchases = json_decode($inputs["purchases"], true);
            if (count($purchases) == 0) $this->errorJSON("No purchases supplied");
            
            //If there isn't a forum name, then we need to input as an unclaimed tickets
            if (!$customer) {
            
                $keys = array();
                foreach ($purchases as $purchase) {
                
                    $memberTicket = true;
                    if ($purchase["type"] == "non_member") $memberTicket = false;
                
                    //Generate unique claim key
                    $key = substr(strtolower(md5(uniqid(rand(), true))), 0, 12);
                    $keys[] = array("type" => ($memberTicket?"member":"non_member"), "key" => $key);
                    
                    //Insert
                    LanWebsite_Main::getDb()->query("INSERT INTO `unclaimed_tickets` (`purchase_id`, `lan_number`, `name`, `email`, `key`, `member_ticket`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $purchase["purchase_id"], $inputs["lan"], $inputs["name"], $inputs["email"], $key, $memberTicket);
                    
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
                    $member = $customer->isMember();

                    //If user is not a member and they have bought a member ticket and supplied a forum name, error
                    if ($memberTicket && !$member) {
                        $this->errorJSON("Member tickets cannot be issued to non-member forum accounts");
                    }
                    
                    //Work out who to assign ticket to, if anyone
                    $assignID = "";
                    $prevTicket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $customer->getUserId(), $inputs["lan"])->fetch_assoc();
                    //If purchasing member ticket and user has non-member assigned, unassign that one and assign the new one
                    if ($prevTicket && $member && $prevTicket["member_ticket"] == 0 && $memberTicket) {
                        LanWebsite_Main::getDb()->query("UPDATE `tickets` SET assigned_forum_id = '' WHERE ticket_id = '%s'", $prevTicket["ticket_id"]);
                        $assignID = $customer->getUserId();
                    }
                    //No previous ticket so assign anyway
                    else if (!$prevTicket) {
                        $assignID = $customer->getUserId();
                    }
                    
                    //Insert time
                    LanWebsite_Main::getDb()->query("INSERT INTO `tickets` (purchase_id, lan_number, member_ticket, purchased_forum_id, purchased_name, assigned_forum_id) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $purchase["purchase_id"], $inputs["lan"], $memberTicket, $customer->getUserId(), $inputs["name"], $assignID);
                
                }
                
                //Return
                echo json_encode(array("success" => true));
                
            }
            
        }
        
        private function authenticate() {
            LanWebsite_Main::getAuth()->requireNotLoggedIn();
            if (!isset($_POST["api_key"]) || sha1($_POST["api_key"]) != sha1(LanWebsite_Main::getSettings()->getSetting("api_key"))) {
                $this->errorJSON("Invalid API Key");
            }
        }
    
    }

?>
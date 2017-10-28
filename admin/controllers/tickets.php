<?php

    class Tickets_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "deleteraffle": return array("ticket_number" => array("notnull", "int")); break;
                case "addraffle": return array("ticket_id" => array("notnull", "int"), "reason" => "notnull"); break;
                case "seat": return array("ticket_id" => array("notnull", "int"), "seat" => ""); break;
                case "assign": return array("name" => "notnull", "ticket_id" => array("notnull", "int")); break;
                case "activate": return array("id" => array("notnull", "int")); break;
                case "deactivate": return array("id" => array("notnull", "int")); break;
                case "claim": return array("ticket_id" => array("notnull", "int"), "name" => "notnull"); break;
            }
        }
    
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Ticket Management");
            $tmpl->enablePlugin('datatables');
            $tmpl->addTemplate('tickets');
            $tmpl->output();
        }

        public function post_Deleteraffle($inputs) {
            if ($this->isInvalid("ticket_number")) $this->errorJSON("Invalid ticket number");
            LanWebsite_Raffle::deleteTicket($inputs["ticket_number"]);
        }
        
        public function post_Addraffle($inputs) {
            //Validate
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $inputs["ticket_id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket ID");
            if ($this->isInvalid("reason")) $this->errorJSON("You must supply a reason for issuing the raffle ticket");
            if ($ticket["assigned_forum_id"] == "") $this->errorJSON("Cannot issue raffle ticket to unassigned lan ticket");
            
            LanWebsite_Raffle::issueTicket($ticket["assigned_forum_id"], $inputs["reason"]);
        }
        
        public function post_Seat($inputs) {
        
            $inputs["seat"] = strtoupper($inputs["seat"]);
            $lan = LanWebsite_Main::getSettings()->getSetting("lan_number");
        
            //Validate
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $inputs["ticket_id"])->fetch_assoc();
            
            //Check seat
            $seats = explode("\n", file_get_contents("data/seats.txt"));
            if ($inputs["seat"] != "" && !in_array($inputs["seat"], $seats)) $this->errorJSON("Invalid seat");
            
            //Unset seat
            LanWebsite_Main::getDb()->query("UPDATE `tickets` SET seat = '' WHERE seat = '%s' AND lan_number = '%s'", $inputs["seat"], $lan);
            
            //Assign
            LanWebsite_Main::getDb()->query("UPDATE `tickets` SET seat = '%s' WHERE ticket_id = '%s'", $inputs["seat"], $ticket["ticket_id"]);
        
        }
        
        public function get_Loadtables() {
        
            $lan = LanWebsite_Main::getSettings()->getSetting("lan_number");
        
            //Get claimed
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s'", $lan);
            $claimed = array();
            while ($row = $res->fetch_assoc()) {
                $purchased = LanWebsite_Main::getUserManager()->getUserById($row["purchased_forum_id"]);
                $assigned = LanWebsite_Main::getUserManager()->getUserById($row["assigned_forum_id"]);
                $claimed[] = array($row["ticket_id"], $row["member_ticket"] == 1?"Member":"Non-Member", '<a href="' . LanWebsite_Main::buildUrl(false, 'profile', null, array("member" => $purchased->getUsername())) . '">' . $purchased->getUsername() . '</a>', $row["purchased_name"], (!$assigned?"":'<a href="' . LanWebsite_Main::buildUrl(false, 'profile', null, array("member" => $assigned->getUsername())) .'">' . $assigned->getUsername() . '</a>'), $row["activated"] == 1?"Yes":"No", $row["seat"]);
            }
            
            //Get unclaimed
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `unclaimed_tickets` WHERE lan_number = '%s'", $lan);
            $unclaimed = array();
            while ($row = $res->fetch_assoc()) {
                $unclaimed[] = array($row["unclaimed_id"], $row["member_ticket"] == 1?"Member":"Non-Member", $row["name"], $row["email"]);
            }
            
            //Get raffle
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `raffle_tickets` WHERE lan_number = '%s'", $lan);
            $raffle = array();
            while ($row = $res->fetch_assoc()) {
                $user = LanWebsite_Main::getUserManager()->getUserById($row["user_id"]);
                //$raffle[] = array($row["raffle_ticket_number"], $user->getFullName(), $user->getUsername(), $row["reason"]);
            }
            
            //Output
            echo json_encode(array("claimed" => $claimed, "unclaimed" => $unclaimed, "raffle" => $raffle));
            
        }
        
        public function post_Assign($inputs) {
        
            //Validate
            $user = LanWebsite_Main::getUserManager()->getUserByName($inputs["name"]);
            if (!$user) $this->errorJSON("Invalid user name");
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $inputs["ticket_id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            
            $lan = LanWebsite_Main::getSettings()->getSetting("lan_number");
            
            //Unassign
            LanWebsite_Main::getDb()->query("UPDATE `tickets` SET assigned_forum_id = '' WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $user->getUserId(), $lan);
            
            //Assign
            LanWebsite_Main::getDb()->query("UPDATE `tickets` SET assigned_forum_id = '%s' WHERE ticket_id = '%s'", $user->getUserId(), $ticket["ticket_id"]);
        
        }
        
        public function post_Activate($inputs) {
            //Validate
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $inputs["id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            if ($ticket["assigned_forum_id"] == "") $this->errorJSON("Tickets must be assigned before being activated");
            
            //Activate
            LanWebsite_Main::getDb()->query("UPDATE `tickets` SET activated = 1 WHERE ticket_id = '%s'", $inputs["id"]);
            
            //Raffle ticket hook - issue them one if they haven't got one yet
            LanWebsite_Raffle::issueAttendanceTicket($ticket["assigned_forum_id"]);
            
            //Friends of LSUCS Hook
            LanWebsite_Main::getUserManager()->checkFriendsOfLSUCS($ticket["assigned_forum_id"]);
        }
        
        public function post_Deactivate($inputs) {
            //Validate
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $inputs["id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            //Activate
            LanWebsite_Main::getDb()->query("UPDATE `tickets` SET activated = 0 WHERE ticket_id = '%s'", $inputs["id"]);
        }
        
        public function post_Claim($inputs) {
        
            //Validate user
            $user = LanWebsite_Main::getUserManager()->getUserByName($inputs["name"]);
            if (!$user) $this->errorJSON("Invalid user name");
            
            $lan = LanWebsite_Main::getSettings()->getSetting("lan_number");
        
            //Attempt to retrieve
            $unclaimed_ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `unclaimed_tickets` WHERE `unclaimed_id`='%s' AND lan_number = '%s'", $inputs["ticket_id"], $lan)->fetch_assoc();
            if (!$unclaimed_ticket) $this->errorJSON("Invalid claim ticket");
            
            //Check member status
            if ($unclaimed_ticket["member_ticket"] == 1 && !$user->isMember()) {
                $this->errorJSON("Cannot assign member ticket to non-member account");
            } else if ($unclaimed_ticket["member_ticket"] == 1 && $user->isMember()) {
                $member = true;
            } else {
                $member = false;
            }
            
            //Work out who to assign ticket to, if anyone
            $assignID = "";
            $prevTicket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $user->getUserId(), $lan)->fetch_assoc();
            //If purchasing member ticket and user has non-member assigned, unassign that one and assign the new one
            if ($prevTicket && $member && $prevTicket["member_ticket"] == 0 && $unclaimed_ticket["member_ticket"] == 1) {
                LanWebsite_Main::getDb()->query("UPDATE `tickets` SET assigned_forum_id = '' WHERE ticket_id = '%s'", $prevTicket["ticket_id"]);
                $assignID = $user->getUserId();
            }
            //No previous ticket so assign anyway
            else if (!$prevTicket) {
                $assignID = $user->getUserId();
            }
            
            //Insert new ticket
            LanWebsite_Main::getDb()->query("INSERT INTO `tickets` (purchase_id, lan_number, member_ticket, purchased_forum_id, assigned_forum_id) VALUES ('%s', '%s', '%s', '%s', '%s')", $unclaimed_ticket["purchase_id"], $unclaimed_ticket["lan_number"], $unclaimed_ticket["member_ticket"], $user->getUserId(), $assignID);
            
            //Delete claim
            LanWebsite_Main::getDb()->query("DELETE FROM `unclaimed_tickets` WHERE unclaimed_id = '%s'", $unclaimed_ticket["unclaimed_id"]);
            
        }
    
    }
    
?>

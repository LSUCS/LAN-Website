<?php

    class Admintickets_Page extends Page {
    
        public function getInputs() {
            return array(
                        "actionDeleteraffle" => array("ticket_number" => "post"),
                        "actionAddraffle" => array("ticket_id" => "post", "reason" => "post"),
                        "actionSeat" => array("ticket_id" => "post", "seat" => "post"),
                        "actionAssign" => array("name" => "post", "ticket_id" => "post"),
                        "actionActivate" => array("id" => "post"),
                        "actionDeactivate" => array("id" => "post"),
                        "actionClaim" => array("ticket_id" => "post", "name" => "post")
                        );
        }
    
        public function actionIndex() {
            $this->parent->template->setSubTitle("Ticket Management");
            $this->parent->template->outputTemplate('admintickets');
        }
        
        public function actionDeleteraffle() {
            if ($this->inputs["ticket_number"] == "") $this->errorJSON("Invalid ticket number");
            $this->parent->raffle->deleteTicket($this->inputs["ticket_number"]);
        }
        
        public function actionAddraffle() {
            //Validate
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $this->inputs["ticket_id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket ID");
            if ($this->inputs["reason"] == "") $this->errorJSON("You must supply a reason for issuing the raffle ticket");
            if ($ticket["assigned_forum_id"] == "") $this->errorJSON("Cannot issue raffle ticket to unassigned lan ticket");
            
            $this->parent->raffle->issueTicket($ticket["assigned_forum_id"], $this->inputs["reason"]);
        }
        
        public function actionSeat() {
        
            $this->inputs["seat"] = strtoupper($this->inputs["seat"]);
            $lan = $this->parent->settings->getSetting("lan_number");
        
            //Validate
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $this->inputs["ticket_id"])->fetch_assoc();
            
            //Check seat
            $seats = explode("\n", file_get_contents("seats.txt"));
            if ($this->inputs["seat"] != "" && !in_array($this->inputs["seat"], $seats)) $this->errorJSON("Invalid seat");
            
            //Unset seat
            $this->parent->db->query("UPDATE `tickets` SET seat = '' WHERE seat = '%s' AND lan_number = '%s'", $this->inputs["seat"], $lan);
            
            //Assign
            $this->parent->db->query("UPDATE `tickets` SET seat = '%s' WHERE ticket_id = '%s'", $this->inputs["seat"], $ticket["ticket_id"]);
        
        }
        
        public function actionLoadtables() {
        
            $lan = $this->parent->settings->getSetting("lan_number");
        
            //Get claimed
            $res = $this->parent->db->query("SELECT * FROM `tickets` WHERE lan_number = '%s'", $lan);
            $claimed = array();
            while ($row = $res->fetch_assoc()) {
                $purchased = $this->parent->auth->getUserById($row["purchased_forum_id"]);
                $assigned = $this->parent->auth->getUserById($row["assigned_forum_id"]);
                $claimed[] = array($row["ticket_id"], $row["member_ticket"] == 1?"Member":"Non-Member", '<a href="index.php?page=profile&member=' . $purchased["xenforo"]["username"] . '">' . $purchased["xenforo"]["username"] . '</a>', $row["purchased_name"], ($assigned["xenforo"]["username"] == ""?"":'<a href="index.php?page=profile&member=' . $assigned["xenforo"]["username"] . '">' . $assigned["xenforo"]["username"] . '</a>'), $row["activated"] == 1?"Yes":"No", $row["seat"]);
            }
            
            //Get unclaimed
            $res = $this->parent->db->query("SELECT * FROM `unclaimed_tickets` WHERE lan_number = '%s'", $lan);
            $unclaimed = array();
            while ($row = $res->fetch_assoc()) {
                $unclaimed[] = array($row["unclaimed_id"], $row["member_ticket"] == 1?"Member":"Non-Member", $row["name"], $row["email"]);
            }
            
            //Get raffle
            $res = $this->parent->db->query("SELECT * FROM `raffle_tickets` WHERE lan_number = '%s'", $lan);
            $raffle = array();
            while ($row = $res->fetch_assoc()) {
                $userdata = $this->parent->auth->getUserById($row["user_id"]);
                $raffle[] = array($row["raffle_ticket_number"], $userdata["lan"]["real_name"], $userdata["xenforo"]["username"], $row["reason"]);
            }
            
            //Output
            echo json_encode(array("claimed" => $claimed, "unclaimed" => $unclaimed, "raffle" => $raffle));
            
        }
        
        public function actionAssign() {
        
            //Validate
            $userdata = $this->parent->auth->getUserByName($this->inputs["name"]);
            if (!$userdata) $this->errorJSON("Invalid user name");
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $this->inputs["ticket_id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            
            $lan = $this->parent->settings->getSetting("lan_number");
            
            //Unassign
            $this->parent->db->query("UPDATE `tickets` SET assigned_forum_id = '' WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $lan);
            
            //Assign
            $this->parent->db->query("UPDATE `tickets` SET assigned_forum_id = '%s' WHERE ticket_id = '%s'", $userdata["xenforo"]["user_id"], $ticket["ticket_id"]);
        
        }
        
        public function actionActivate() {
            //Validate
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $this->inputs["id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            if ($ticket["assigned_forum_id"] == "") $this->errorJSON("Tickets must be assigned before being activated");
            
            //Activate
            $this->parent->db->query("UPDATE `tickets` SET activated = 1 WHERE ticket_id = '%s'", $this->inputs["id"]);
            
            //Raffle ticket hook - issue them one if they haven't got one yet
            $this->parent->raffle->issueAttendanceTicket($ticket["assigned_forum_id"]);
            
            //Friends of LSUCS Hook
            $this->parent->auth->checkFriendsOfLSUCS($ticket["assigned_forum_id"]);
        }
        
        public function actionDeactivate() {
            //Validate
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $this->inputs["id"])->fetch_assoc();
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            //Activate
            $this->parent->db->query("UPDATE `tickets` SET activated = 0 WHERE ticket_id = '%s'", $this->inputs["id"]);
        }
        
        public function actionClaim() {
        
            //Validate user
            $userdata = $this->parent->auth->getUserByName($this->inputs["name"]);
            if (!$userdata) $this->errorJSON("Invalid user name");
            
            $lan = $this->parent->settings->getSetting("lan_number");
        
            //Attempt to retrieve
            $unclaimed_ticket = $this->parent->db->query("SELECT * FROM `unclaimed_tickets` WHERE `unclaimed_id`='%s' AND lan_number = '%s'", $this->inputs["ticket_id"], $lan)->fetch_assoc();
            if (!$unclaimed_ticket) $this->errorJSON("Invalid claim ticket");
            
            //Check member status
            $member = false;
            if ($unclaimed_ticket["member_ticket"] == 1) {
                $group = $this->parent->settings->getSetting("xenforo_member_group_id");
                if ($userdata["xenforo"]["user_group_id"] != $group && !in_array($group, explode(",", $userdata["xenforo"]["secondary_group_ids"]))) {
                    $this->errorJSON("Cannot assign member ticket to non-member account");
                }
                $member = true;
            }
            
            //Work out who to assign ticket to, if anyone
            $assignID = "";
            $prevTicket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $lan)->fetch_assoc();
            //If purchasing member ticket and user has non-member assigned, unassign that one and assign the new one
            if ($prevTicket && $member && $prevTicket["member_ticket"] == 0 && $unclaimed_ticket["member_ticket"] == 1) {
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
    
    }
    
?>
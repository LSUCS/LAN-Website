<?php

    class Admintickets_Page extends Page {
    
        public function getInputs() {
            return array(
                        "actionActivate" => array("id" => "post"),
                        "actionDeactivate" => array("id" => "post")
                        );
        }
    
        public function actionIndex() {
            $this->parent->template->setSubTitle("Ticket Management");
            $this->parent->template->outputTemplate('admintickets');
        }
        
        public function actionLoadtables() {
        
            //Get claimed
            $res = $this->parent->db->query("SELECT * FROM `tickets` WHERE lan_number = '%s'", $this->parent->settings->getSetting("lan_number"));
            $claimed = array();
            while ($row = $res->fetch_assoc()) {
                $purchased = $this->parent->auth->getUserById($row["purchased_forum_id"]);
                $assigned = $this->parent->auth->getUserById($row["assigned_forum_id"]);
                $claimed[] = array($row["ticket_id"], $row["member_ticket"] == 1?"Member":"Non-Member", $purchased["xenforo"]["username"], $assigned["xenforo"]["username"], $row["activated"] == 1?"Yes":"No");
            }
            
            //Get unclaimed
            $res = $this->parent->db->query("SELECT * FROM `unclaimed_tickets` WHERE lan_number = '%s'", $this->parent->settings->getSetting("lan_number"));
            $unclaimed = array();
            while ($row = $res->fetch_assoc()) {
                $unclaimed[] = array($row["unclaimed_id"], $row["member_ticket"] == 1?"Member":"Non-Member", $row["name"], $row["email"]);
            }
            
            //Output
            echo json_encode(array("claimed" => $claimed, "unclaimed" => $unclaimed));
            
        }
        
        public function actionAssign() {
        
        }
        
        public function actionActivate() {
            //Validate
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $this->inputs["id"]);
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            //Activate
            $this->parent->db->query("UPDATE `tickets` SET activated = 1 WHERE ticket_id = '%s'", $this->inputs["id"]);
        }
        
        public function actionDeactivate() {
            //Validate
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE ticket_id = '%s'", $this->inputs["id"]);
            if (!$ticket) $this->errorJSON("Invalid ticket id");
            //Activate
            $this->parent->db->query("UPDATE `tickets` SET activated = 0 WHERE ticket_id = '%s'", $this->inputs["id"]);
        }
        
        public function actionClaim() {
        
        }
    
    }
    
?>
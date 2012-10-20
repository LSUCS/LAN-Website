<?php

    class Adminlanvan_Page extends Page {
        
        public function actionIndex() {
            $this->parent->template->setSubtitle("Lan Van Admin");
            $this->parent->template->outputTemplate("adminlanvan");
        }
        
        public function actionLoad() {
        
            //Get claimed
            $res = $this->parent->db->query("SELECT * FROM `lan_van` WHERE lan = '%s'", $this->parent->settings->getSetting("lan_number"));
            $return = array();
            while ($row = $res->fetch_assoc()) {
                $user = $this->parent->auth->getUserById($row["user_id"]);
                $return[] = array($user["lan"]["real_name"], $row["phone_number"], $row["address"], $row["postcode"], str_replace(array(1, 0), array("Yes", "No"), $row["collection"]), str_replace(array(1, 0), array("Yes", "No"), $row["dropoff"]), $row["available"]);
            }
            
            //Output
            echo json_encode($return);
            
        }
    
    }
    
?>
<?php
    
    class Admintf2_Controller extends LanWebsite_Controller {
        
        public function actionIndex() {
            $this->parent->template->setSubtitle("TF2 Signups");
            $this->parent->template->outputTemplate("admintf2");
        }
        
        public function actionLoad() {
        
            $res = $this->parent->db->query("SELECT * FROM `tf2`");
            $output = array();
            while ($row = $res->fetch_assoc()) {
            
                $userdata = $this->parent->auth->getUserById($row["user_id"]);
                $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
                $output[] = array($userdata["lan"]["real_name"], '<a href="index.php?page=profile&member=' . $userdata["xenforo"]["username"] . '">' . $userdata["xenforo"]["username"] . '</a>', '<a href="http://steamcommunity.com/id/' . $userdata["lan"]["steam_name"] . '">' . $userdata["lan"]["steam_name"] . '</a>' , $ticket["seat"]);
            
            }
            
            echo json_encode($output);
        
        }
    
    }

?>
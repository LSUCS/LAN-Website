<?php
    
    class Adminhungergames_Controller extends LanWebsite_Controller {
        
        public function actionIndex() {
            $this->parent->template->setSubtitle("Hungergames Signups");
            $this->parent->template->outputTemplate("adminhungergames");
        }
        
        public function actionLoad() {
        
            $res = $this->parent->db->query("SELECT * FROM `hungergames`");
            $output = array();
            while ($row = $res->fetch_assoc()) {
            
                $userdata = $this->parent->auth->getUserById($row["user_id"]);
                $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
                $output[] = array($userdata["lan"]["real_name"], '<a href="index.php?page=profile&member=' . $userdata["xenforo"]["username"] . '">' . $userdata["xenforo"]["username"] . '</a>', $row["minecraft"] , $ticket["seat"]);
            
            }
            
            echo json_encode($output);
        
        }
    
    }

?>
<?php

    class Lanvan_Controller extends LanWebsite_Controller {
        
        public function get_Index() {
			$tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Food Ordering");
            $tmpl->addTemplate('food');
			$tmpl->output();
        }
        
        public function get_Load() {
        
            //Get claimed
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `lan_van` WHERE lan = '%s'", LanWebsite_Main::getSettings()->getSetting("lan_number"));
            $return = array();
            while ($row = $res->fetch_assoc()) {
                $user = LanWebsite_Main::getUserManager()->getUserById($row["user_id"]);
                $return[] = array($user->getFullName(), $row["phone_number"], $row["address"], $row["postcode"], str_replace(array(1, 0), array("Yes", "No"), $row["collection"]), str_replace(array(1, 0), array("Yes", "No"), $row["dropoff"]), $row["available"]);
            }
            
            //Output
            echo json_encode($return);
            
        }
    
    }
    
?>
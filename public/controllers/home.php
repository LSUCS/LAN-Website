<?php

    class Home_Controller extends LanWebsite_Controller {
	
		public function get_Index() {
        
            //Get blog
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `blog` ORDER BY date DESC LIMIT 0,4");
            $data["blog"] = array();
            while ($row = $res->fetch_assoc()) {
                $user = LanWebsite_Main::getUserManager()->getUserById($row["user_id"]);
                $row["username"] = $user->getUsername();
                $row["date"] = date("D jS M g:iA", strtotime($row["date"]));
                $data["blog"][] = $row;
            }
		
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Home");
            $tmpl->addTemplate('home', $data);
            $tmpl->enablePlugin('twitter');
			$tmpl->output();
		
		}
        
        public function get_Getimage() {
            $folders = glob("data/gallery/*");
            $folder = array_pop($folders);
            echo json_encode(array_rand(array_flip(glob($folder . "/*.*")), 10));
        }
        
        public function get_Test() {
            $userManager = LanWebsite_Main::getUserManager();
            
            $u = $userManager->getUserById(870);
            
            var_dump($u);
            die;
        }
    
    }

?>
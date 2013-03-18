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
		
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Home");
            $tmpl->addScript('/js/pages/home.js');
            $tmpl->addStyle('/css/pages/home.css');
            $tmpl->addTemplate('public/home', $data);
            $tmpl->enablePlugin('twitter');
			$tmpl->output();
		
		}
        
        public function get_Getimage() {
            $folders = glob("gallery/*");
            $folder = array_pop($folders);
            echo json_encode(array_rand(array_flip(glob($folder . "/*.*")), 10));
        }
    
    }

?>
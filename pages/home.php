<?php

    class Home_Page extends Page {
	
		public function actionIndex() {
        
            //Get blog
            $res = $this->parent->db->query("SELECT * FROM `blog` ORDER BY date DESC LIMIT 0,4");
            $data["blog"] = array();
            while ($row = $res->fetch_assoc()) {
                $userdata = $this->parent->auth->getUserById($row["user_id"]);
                $row["username"] = $userdata["xenforo"]["username"];
                $row["date"] = date("D jS M g:iA", strtotime($row["date"]));
                $data["blog"][] = $row;
            }
		
			$this->parent->template->setSubTitle("Home");
			$this->parent->template->outputTemplate(array("template" => "home", "data" => $data));
		
		}
        
        public function actionGetimage() {
            $folders = glob("gallery/*");
            $folder = array_pop($folders);
            echo json_encode(array_rand(array_flip(glob($folder . "/*.*")), 10));
        }
    
    }

?>
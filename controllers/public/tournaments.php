<?php

    class Tournaments_Controller extends LanWebsite_Controller {
	
		public function getInputs() {
			return array("actionSignuphungergames" => array("minecraft" => "post"));
		}
        
        public function actionIndex() {
        
        	$this->parent->template->setSubTitle("Tournaments");
			$this->parent->template->outputTemplate("tournaments");
        
        }
		
        public function actionSignuphungergames() {
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Validate
            if ($this->parent->auth->isGuest()) $this->errorJSON("You must be logged in to sign up");
            if (!$this->parent->auth->isPhysicallyAtLan()) $this->errorJSON("You cannot sign up unless you are at the LAN");
            if ($this->parent->settings->getSetting("enable_hungergames") == 0) $this->errorJSON("Sign-ups are disabled");
            if ($this->parent->db->query("SELECT * FROM `hungergames` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"])->fetch_assoc()) $this->errorJSON("You have alread signed up");
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
            if (!$ticket) $this->errorJSON("You do not have a ticket for this LAN");
            if ($ticket["activated"] == 0) $this->errorJSON("Your ticket is not activated, visit the front desk");
            if ($ticket["seat"] == "") $this->errorJSON("You do not have a seat assigned to your ticket, visit the front desk");
			
			//Check minecraft name
			if (file_get_contents("https://minecraft.net/haspaid.jsp?user=" . $this->inputs["minecraft"]) == "false") $this->errorJSON("Invalid Minecraft username!");
            
            //Signup
            $this->parent->db->query("INSERT INTO `hungergames` (user_id, minecraft) VALUES ('%s', '%s')", $userdata["xenforo"]["user_id"], $this->inputs["minecraft"]);
        
        }
        
        public function actionCheckhungergames() {
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Validate
            if ($this->parent->auth->isGuest()) $this->errorJSON("You must be logged in to sign up");
            if (!$this->parent->auth->isPhysicallyAtLan()) $this->errorJSON("You cannot sign up unless you are at the LAN");
            if ($this->parent->settings->getSetting("enable_hungergames") == 0) $this->errorJSON("Sign-ups are disabled");
            if ($this->parent->db->query("SELECT * FROM `hungergames` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"])->fetch_assoc()) $this->errorJSON("You have alread signed up");
            
            echo json_encode(array("not_signed_up" => true));
            
        }
        
        public function actionSignuptf2() {
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Validate
            if ($this->parent->auth->isGuest()) $this->errorJSON("You must be logged in to sign up");
            if (!$this->parent->auth->isPhysicallyAtLan()) $this->errorJSON("You cannot sign up unless you are at the LAN");
            if ($this->parent->settings->getSetting("enable_tf2") == 0) $this->errorJSON("Sign-ups are disabled");
            if ($this->parent->db->query("SELECT * FROM `tf2` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"])->fetch_assoc()) $this->errorJSON("You have alread signed up");
            if ($userdata["lan"]["steam_name"] == "") $this->errorJSON('You must enter your Steam community name in your <a href="index.php?page=account">Account Details</a> before you can sign up to TF2');
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
            if (!$ticket) $this->errorJSON("You do not have a ticket for this LAN");
            if ($ticket["activated"] == 0) $this->errorJSON("Your ticket is not activated, visit the front desk");
            if ($ticket["seat"] == "") $this->errorJSON("You do not have a seat assigned to your ticket, visit the front desk");
            
            //Signup
            $this->parent->db->query("INSERT INTO `tf2` (user_id) VALUES ('%s')", $userdata["xenforo"]["user_id"]);
        
        }
        
        public function actionChecktf2() {
            $userdata = $this->parent->auth->getActiveUserData();
            
            //Validate
            if ($this->parent->auth->isGuest()) $this->errorJSON("You must be logged in to sign up");
            if (!$this->parent->auth->isPhysicallyAtLan()) $this->errorJSON("You cannot sign up unless you are at the LAN");
            if ($this->parent->settings->getSetting("enable_tf2") == 0) $this->errorJSON("Sign-ups are disabled");
            if ($this->parent->db->query("SELECT * FROM `tf2` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"])->fetch_assoc()) $this->errorJSON("You have alread signed up");
            
            echo json_encode(array("not_signed_up" => true));
            
        }
        
        public function actionTf2() {
            $this->parent->template->setSubTitle("left 4 Dead 2");
			$this->parent->template->outputTemplate(array("template" => "tournament-tf2", "styles" => "tournament-tf2.css", "scripts" => "tournament-tf2.js"));
        }
        
        public function actionMinecraft() {
            $this->parent->template->setSubTitle("Minecraft Creative Challenge");
			$this->parent->template->outputTemplate(array("template" => "tournament-minecraft", "styles" => "tournament-minecraft.css"));
        }
        
        public function actionHungergames() {
            $this->parent->template->setSubTitle("Minecraft Hunger Games");
			$this->parent->template->outputTemplate(array("template" => "tournament-hungergames", "styles" => "tournament-hungergames.css", "scripts" => "tournament-hungergames.js"));
        }
        
        public function actionQuiz() {
            $this->parent->template->setSubTitle("Pub Quiz");
			$this->parent->template->outputTemplate(array("template" => "tournament-quiz", "styles" => "tournament-quiz.css"));
        }
                
        public function actionAchievements() {
            $this->parent->template->setSubTitle("LAN Achievements");
			$this->parent->template->outputTemplate(array("template" => "tournament-achievements", "styles" => "tournament-achievements.css"));
        }
    
    }

?>
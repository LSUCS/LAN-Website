<?php

    class Profile_Page extends Page {
    
        public function getInputs() {
            return array("actionIndex" => array("member" => "post"));
        }
    
        public function actionIndex() {
            
            //Validate
            if ($this->inputs["member"] != "") {
                $userdata = $this->parent->auth->getUserByName($this->inputs["member"]);
                if (!$userdata) header("location: index.php");
            } else if ($this->parent->auth->isGuest()) {
                 header("location: index.php");
            } else {
                $userdata = $this->parent->auth->getActiveUserData();
            }
            
            $this->parent->template->setSubtitle("Profile");
            $this->parent->template->outputTemplate("profile");
        }
    
    }
    
?>
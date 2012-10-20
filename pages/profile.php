<?php

    class Profile_Page extends Page {
    
        public function actionIndex() {
            $this->parent->template->setSubtitle("Profile");
            $this->parent->template->outputTemplate("profile");
        }
    
    }
    
?>
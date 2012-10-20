<?php

    class Contact_Page extends Page {
    
        public function actionIndex() {
            $this->parent->template->setSubtitle("contact");
            $this->parent->template->outputTemplate('contact');
        }
    
    }

?>
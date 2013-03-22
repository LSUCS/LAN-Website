<?php

    class Contact_Controller extends LanWebsite_Controller {
    
        public function get_Index() {
			$tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Contact");
            $tmpl->addTemplate('contact');
            $tmpl->output();
        }
    
    }

?>
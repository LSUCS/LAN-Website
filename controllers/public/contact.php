<?php

    class Contact_Controller extends LanWebsite_Controller {
    
        public function get_Index() {
			$tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Contact");
            $tmpl->addTemplate('public/contact');
            $tmpl->output();
        }
    
    }

?>
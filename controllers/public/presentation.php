<?php

	class Presentation_Controller extends LanWebsite_Controller {
		
		public function get_Index() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Lan Map");
            $tmpl->addScript('/js/pages/presentation.js');
            $tmpl->addStyle('/css/pages/presentation.css');
            $tmpl->addTemplate('public/presentation');
            $tmpl->disableMainTemplate();
			$tmpl->output();
		}
		
	}
	
?>
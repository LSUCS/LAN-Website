<?php

	class Presentation_Controller extends LanWebsite_Controller {
		
		public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Lan Map");
            $tmpl->addTemplate('presentation');
            $tmpl->disableMainTemplate();
			$tmpl->output();
		}
		
	}
	
?>
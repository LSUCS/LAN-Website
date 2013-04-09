<?php

	class Presentation_Controller extends LanWebsite_Controller {
		
		public function get_Index() {
            $data["presentation"] = LanWebsite_Main::getSettings()->getSetting("presentation_url");
            $data["interval"] = LanWebsite_Main::getSettings()->getSetting("presentation_refresh_interval");
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Lan Map");
            $tmpl->addTemplate('presentation', $data);
            $tmpl->disableMainTemplate();
			$tmpl->output();
		}
		
	}
	
?>
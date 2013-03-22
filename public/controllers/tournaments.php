<?php

    class Tournaments_Controller extends LanWebsite_Controller {
        
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournaments");
            $tmpl->addTemplate('tournaments');
			$tmpl->output();
        }
        
        public function get_Quiz() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Quiz");
            $tmpl->addTemplate("tournament-quiz");
			$tmpl->output();
        }
    
    }

?>
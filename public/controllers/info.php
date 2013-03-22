<?php

    class Info_Controller extends LanWebsite_Controller {
        
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Info");
            $tmpl->addTemplate('info');
            $tmpl->output();
        }
        
        public function get_Parking() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Location and Parking");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-parking');
            $tmpl->output();
        }
        
        public function get_Arrival() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Arrival Details");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-arrival');
            $tmpl->output();
        }
        
        public function get_Lanvan() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Lan Van");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-lanvan');
            $tmpl->output();
        }
        
        public function get_Rules() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Lan Rules");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-rules');
            $tmpl->output();
        }
        
        public function get_Equipment() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("What to Bring");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-equipment');
            $tmpl->output();
        }
        
        public function get_Tournamentrules() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournament Rules");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-tournamentrules');
            $tmpl->output();
        }
        
        public function get_Food() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Food and Drink");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-food');
            $tmpl->output();
        }
        
        public function get_Dc() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("DC++");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-dc');
            $tmpl->output();
        }
        
        public function get_Raffle() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Raffle");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-raffle');
            $tmpl->output();
        }
        
        public function get_Sleeping() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Sleeping at the Lan");
            $tmpl->addStyle('/public/css/info.css');
            $tmpl->addTemplate('info-sleeping');
            $tmpl->output();
        }
    }

?>
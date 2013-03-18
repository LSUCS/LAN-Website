<?php

    class Info_Controller extends LanWebsite_Controller {
        
        public function get_Index() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Info");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info');
            $tmpl->output();
        }
        
        public function get_Parking() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Location and Parking");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-parking');
            $tmpl->output();
        }
        
        public function get_Arrival() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Arrival Details");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-arrival');
            $tmpl->output();
        }
        
        public function get_Lanvan() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Lan Van");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-lanvan');
            $tmpl->output();
        }
        
        public function get_Rules() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Lan Rules");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-rules');
            $tmpl->output();
        }
        
        public function get_Equipment() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("What to Bring");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-equipment');
            $tmpl->output();
        }
        
        public function get_Tournamentrules() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Tournament Rules");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-tournamentrules');
            $tmpl->output();
        }
        
        public function get_Food() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Food and Drink");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addStyle('/css/pages/info-food.css');
            $tmpl->addTemplate('public/info-food');
            $tmpl->output();
        }
        
        public function get_Dc() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("DC++");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-dc');
            $tmpl->output();
        }
        
        public function get_Raffle() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Raffle");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-raffle');
            $tmpl->output();
        }
        
        public function get_Sleeping() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Sleeping at the Lan");
            $tmpl->addStyle('/css/pages/info.css');
            $tmpl->addTemplate('public/info-sleeping');
            $tmpl->output();
        }
    }

?>
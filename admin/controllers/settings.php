<?php

    class Settings_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "savesettings": return array("settings" => "notnull"); break;
            }
        }
    
        public function get_Index() {
            //Get settings and group
            $settings = LanWebsite_Main::getSettings()->getSettings();
            $groups = array();
            foreach ($settings as $setting) {
                $groups[$setting["setting_group"]][] = $setting;
            }
            
            $data["groups"] = $groups;
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Site Settings");
            $tmpl->enablePlugin('timepicker');
            $tmpl->addTemplate('settings', $data);
            $tmpl->output();
        }
        
        public function get_Hello() {
            echo 'HAI';
            print_r($_GET);
        }
        
        public function post_Savesettings($inputs) {
            $settings = json_decode($inputs["settings"], true);
            if (count($settings) == 0) echo false;
            foreach ($settings as $setting => $value) {
                echo $setting . " " . $value . "\n";
                if (!LanWebsite_Main::getSettings()->settingIsReal($setting)) echo false;
                if (!LanWebsite_Main::getSettings()->changeSetting($setting, $value)) echo false;
            }
            echo true;
        }
    
    }

?>
<?php

    class Adminsettings_Page extends Page {
    
        public function getInputs() {
            return array("actionSavesettings" => array("settings" => "post"));
        }
    
        public function actionIndex() {
            $this->parent->template->setSubTitle("Admin Settings");
            
            //Get settings and output template
            $data["settings"] = $this->parent->settings->getSettings();
            $this->parent->template->outputTemplate(array("template" => "adminsettings", "data" => $data));
        }
        
        public function actionSavesettings() {
            $settings = json_decode($this->inputs["settings"], true);
            if (count($settings) == 0) echo false;
            foreach ($settings as $setting => $value) {
                echo $setting . " " . $value . "\n";
                if (!$this->parent->settings->settingIsReal($setting)) echo false;
                if (!$this->parent->settings->changeSetting($setting, $value)) echo false;
            }
            echo true;
        }
    
    }

?>
<?php

    class Info_Page extends Page {
        
        public function actionIndex() {
        
        	$this->parent->template->setSubTitle("Info");
			$this->parent->template->outputTemplate('info');
        
        }
        
        public function actionParking() {
            $this->parent->template->setSubTitle("Location and Parking");
            $this->parent->template->outputTemplate(array("template" => "info-parking"));
        }
        
        public function actionArrival() {
            $this->parent->template->setSubTitle("Arrival Details");
            $this->parent->template->outputTemplate(array("template" => "info-arrival"));
        }
        
        public function actionLanvan() {
            $this->parent->template->setSubTitle("Lan Van");
            $this->parent->template->outputTemplate(array("template" => "info-lanvan"));
        }
        
        public function actionRules() {
            $this->parent->template->setSubTitle("Lan Rules");
            $this->parent->template->outputTemplate(array("template" => "info-rules"));
        }
        
        public function actionEquipment() {
            $this->parent->template->setSubTitle("What to bring");
            $this->parent->template->outputTemplate(array("template" => "info-equipment"));
        }
        
        public function actionTournamentrules() {
            $this->parent->template->setSubTitle("Tournament Rules");
            $this->parent->template->outputTemplate(array("template" => "info-tournamentrules"));
        }
        
        public function actionFood() {
            $this->parent->template->setSubTitle("Food and Drink");
            $this->parent->template->outputTemplate(array("template" => "info-food", "styles" => "info-food.css"));
        }
        
        public function actionDc() {
            $this->parent->template->setSubTitle("DC++");
            $this->parent->template->outputTemplate(array("template" => "info-dc"));
        }
        
        public function actionRaffle() {
            $this->parent->template->setSubTitle("Raffle");
            $this->parent->template->outputTemplate(array("template" => "info-raffle"));
        }
        
        public function actionSleeping() {
            $this->parent->template->setSubTitle("Sleeping at the lan");
            $this->parent->template->outputTemplate(array("template" => "info-sleeping"));
        }
    }

?>
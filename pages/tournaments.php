<?php

    class Tournaments_Page extends Page {
        
        public function actionIndex() {
        
        	$this->parent->template->setSubTitle("Tournaments");
			$this->parent->template->outputTemplate("tournaments");
        
        }
    
    }

?>
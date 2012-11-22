<?php

	class Presentation_Page extends Page {
		
		public function actionIndex() {
			$this->parent->template->outputTemplate(array("template" => "presentation", "wrapper" => false));
		}
		
		public function actionGetsong() {
		}
		
	}
	
?>
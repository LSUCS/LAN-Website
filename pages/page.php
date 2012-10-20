<?php

	class Page {
	
		public $parent;
        public $inputs = array();
		
		public function __construct($parent) {
			$this->parent = $parent;
		}
        
        public function getInputs() {
            return array();
        }
        
        public function errorJson($error) {
            $json["error"] = $error;
            die(json_encode($json));
        }
        
        public function error($message) {
            $this->parent->template->outputTemplate(array("content" => '<div class="error-box">' . $message . '</div>'));
            die();
        }
	
	}
	
?>
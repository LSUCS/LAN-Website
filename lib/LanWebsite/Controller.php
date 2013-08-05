<?php

	abstract class LanWebsite_Controller {
   
        private $valid = array();
        private $invalid = array();
        
        final public function handleRequest() {
            
            //See if action is being called and determine http method required for inputs
            $action = 'index';
            $method = 'get';
			if (isset($_GET["action"])) {
                $a = strtolower($_GET["action"]);
                if (method_exists($this, "post_" . ucwords($a))) {
                    $method = 'post';
                    $action = $a;
                } else if (method_exists($this, "get_" . ucwords($a))) {
                    $method = 'get';
                    $action = $a;
                }
			}
            
            //Validate inputs against running method
            $inputs = $this->getInputFilters($action);
            if (!is_array($inputs)) $inputs = array();
            $validInputs = array();
            $invalidInputs = array();
            $values = array();
            foreach ($inputs as $name => $filters) {
            
                if (!is_array($filters)) $filters = (array)$filters;
            
                //Check if it exists
                if ($method == "post" && isset($_POST[$name])) {
                    $value = $_POST[$name];
                } else if ($method == "get" && isset($_GET[$name])) {
                    $value = $_GET[$name];
                } else {
                    $value = "";
                }
                
                //If notnull filter doesn't exist and input is null, accept input as valid
                if (!in_array("notnull", $filters) && $value == "") {
                    $validInputs[] = $name;
                    $values[$name] = $value;
                    continue;
                }
                
                //Process filter validation
                $invalid = array();
                foreach ($filters as $filter) {
                    
                    switch ($filter) {
                        
                        case 'notnull':
                            if ($value == "") $invalid[] = 'null';
                            break;
                            
                        case 'int':
                            if (filter_var($value, FILTER_VALIDATE_INT) == false) $invalid[] = 'int';
                            break;
                            
                        case 'bool':
                            if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) $invalid[] = 'bool';
                            break;
                            
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) $invalid[] = 'email';
                            break;
                            
                        case 'ip':
                            if (!filter_var($value, FILTER_VALIDATE_IP)) $invalid[] = 'ip';
                            break;
                            
                        case 'url':
                            if (!filter_var($value, FILTER_VALIDATE_URL)) $invalid[] = 'url';
                            break;
                        
                    }
                        
                }
                
                //If invalid
                if (count($invalid) > 0) $invalidInputs[$name] = $invalid;
                else $validInputs[] = $name;
                $values[$name] = $value;
                
            }
			
			//Run child page action
            $this->valid = $validInputs;
            $this->invalid = $invalidInputs;
			call_user_func(array($this, $method . '_' . ucwords($action)), $values);
            
        }
        
        final public function isInvalid($input, $filter = "") {
            if ($filter == "" && isset($this->invalid[$input])) return true;
            else if (isset($this->invalid[$input]) && in_array($filter, $this->invalid[$input])) return true;
            return false;
        }
        
        public function errorJson($error) {
            $json["error"] = $error;
            die(json_encode($json));
        }
        
        public function error($message) {
            $this->parent->template->outputTemplate(array("content" => '<div class="error-box">' . $message . '</div>'));
            die();
        }
        
        
        abstract public function get_Index();
        
        public function getInputFilters($action) { return array(); }
	
	}
	
?>
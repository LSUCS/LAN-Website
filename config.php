
<?php

	class Config {
		
		public $database;
		public $auth;
		
		function __construct() {
        
            require_once("../password.php");
			
			/**
			 * Database
			 */
			$this->database["host"] = "localhost";
			$this->database["user"] = "lanwebsite";
			$this->database["pass"] = $password;
			$this->database["db"]   = "dev-lanwebsite";
			
			/**
			 *Auth
			 */
            $this->auth["xenforoDir"] = "/home/soc_lsucs/lsucs.org.uk/htdocs";
			
		}
		
	}

?>
<?php

	//Database class
	class Db {
		
		private $config, $db;
		
		public function __construct($parent) {
			$this->config = $parent->config;
			$this->db = new mysqli($this->config->database["host"], $this->config->database["user"], $this->config->database["pass"], $this->config->database["db"]);
			if (mysqli_connect_errno()) die("Unable to connect to SQL Database: " . mysqli_connect_error());
			$this->createTables();
		}
        
        /**
         * Selects a new database
         */
		public function select_db($db) {
            $db->select_db($db) or die("Unable to select new datbase: " . mysqli_error());
        }
        
        /**
         *  Returns database link
         */
        public function getLink() {
            return $this->db;
        }
        
		/**
		 * Base MySQL query function. Cleans all parameters to prevent injection
		 */
		public function query() {
			$args = func_get_args();
			$sql = array_shift($args);
			foreach ($args as $key => $value) $args[$key] = $this->clean($value);
			$res = $this->db->query(vsprintf($sql, $args));
            if (!$res) die("MySQLi Error: " . mysqli_error($this->db));
            else return $res;
		}
        
		/**
		 * Stops MySQL injection
		 */
		public function clean($string) {
			return $this->db->real_escape_string(trim($string));
		}
		
		/**
		 * Creates default tables if they don't exist
		 */
		private function createTables() {
            $this->query("CREATE TABLE IF NOT EXISTS `settings` (
                          `setting_name` varchar(100) NOT NULL,
                          `setting_type` varchar(20) NOT NULL,
                          `setting_value` varchar(200) NOT NULL,
                          PRIMARY KEY (`setting_name`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;");
		}
		
	}
	
?>
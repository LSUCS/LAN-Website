<?php

	//Database class
	class LanWebsite_Db {
		
		private $db;
		
		public function __construct() {
            $config = LanWebsite_Main::getConfig();
			$this->db = new mysqli($config['database']["host"], $config['database']["user"], $config['database']["pass"], $config['database']["db"]);
			if (mysqli_connect_errno()) die("Unable to connect to SQL Database: " . mysqli_connect_error());
			$this->createTables();
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
		}
		
	}
	
?>
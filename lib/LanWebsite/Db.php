<?php

	//Database class
	class LanWebsite_Db {
		
		private $db;
		
		public function __construct() {
            $this->connect();
        }
        
        private function connect() {
            $config = LanWebsite_Main::getConfig();
			$this->db = new mysqli($config['database']["host"], $config['database']["user"], $config['database']["pass"], $config['database']["db"]);
			if (mysqli_connect_errno()) die("Unable to connect to SQL Database: " . mysqli_connect_error());
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
            $query = vsprintf($sql, $args);
			$res = $this->db->query($query);
            if (!$res) {
                if (strstr($this->db->error, "MySQL server has gone away")) {
                    echo "MySQL server timeout, reconnecting\n";
                    $this->connect();
                    $res = $this->db->query($query);
                } else {
                    print_r(debug_backtrace());
                    echo $query;
                    die("MySQLi Error: " . $this->db->error);
                }
            }
            return $res;
		}
        
		/**
		 * Stops MySQL injection
		 */
		public function clean($string) {
			return $this->db->real_escape_string(trim($string));
		}
		
	}
	
?>

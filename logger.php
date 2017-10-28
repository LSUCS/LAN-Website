<?
    Logger::init();

    class Logger {
    
        private static $db = null;
        private static $init = false;
        private static $destructor = null;
        private static $start_time = null;        
        private static $cache = array();
        private static $sessid = null;
    
        public static function init() {
            self::$start_time = microtime(true);
            
            if (self::$destructor == null) self::$destructor = new LoggerDestructor();
        }
        
        public static function log($type, $data) {
            self::$cache[] = array("data" => $data, "time" => microtime(true), "type" => $type);
        }
        
		private static function query() {
			$args = func_get_args();
			$sql = array_shift($args);
			foreach ($args as $key => $value) $args[$key] = self::clean($value);
			$res = self::$db->query(vsprintf($sql, $args));
            if (!$res) {
                if (strstr(self::$db->error, "MySQL server has gone away")) {
                    echo "MySQL server timeout, reconnecting\n";
                    self::$db = null;
                    self::connect();
                    $res = self::$db->query(vsprintf($sql, $args));
                } else die("Logger: MySQLi Error: " . self::$db->error);
            }
            return $res;
		}
        
		private static function clean($string) {
			return self::$db->real_escape_string(trim($string));
		}
        
        private static function connect() {
            if (self::$db == null) {
                include(dirname(__FILE__) . '/config.php');
                self::$db = new mysqli($config["database"]["host"], $config["database"]["user"], $config["database"]["pass"], $config["database"]["db"]);
                if (mysqli_connect_errno()) die("Unable to connect to Logging SQL Database: " . mysqli_connect_error());
            }
        }
        
        public static function destruct() {
            self::store();
            $user = LanWebsite_Main::getUserManager()->getActiveUser();
            if (self::$sessid != null) {
                self::query("UPDATE logger_sessions SET user_id = '%s', end_time = '%s' WHERE logger_session_id = '%s'", $user->getUserId(), microtime(true), self::$sessid);
            }
        }
        
        public static function store() {
        
            if (count(self::$cache) > 0) {
        
                //Connect
                self::connect();
                
                if (self::$sessid == null) {
                    if (isset($_SERVER["REQUEST_URI"])) {
                        $url = $_SERVER["REQUEST_URI"];
                    } else $url = "";
                    self::query("INSERT INTO `logger_sessions` (url, lan_number, start_time) VALUES ('%s', '%s', '%s')", $url, LanWebsite_Main::getSettings()->getSetting("lan_number"), self::$start_time);
                    self::$sessid = self::$db->insert_id;
                }
                
                foreach (self::$cache as $log) {
                    self::query("INSERT INTO logger_entries (logger_session_id, type, time, data) VALUES ('%s', '%s', '%s', '%s')", self::$sessid, $log["type"], $log["time"], $log["data"]);
                }
                self::$cache = array();
                
                self::init();
            
            }
            
        }
    
    }
    
    class LoggerDestructor {
        public function __destruct() {
            Logger::destruct();
        }
    }

?>

<?php
	
	class LanWebsite_Main {
		
        private static $init = false;
		
		private static $db;
        private static $settings;
        private static $auth;
        private static $usermanager;
        
        
        /**
         * Loads base dependencies for LAN website core
         */
		public static function initialize() {
		
            //Initiation check
            if (self::$init == true) return false;
            self::$init = true;
    
            //Load base config
            include 'config.php';
			
            //Load base objects
			self::$db       = new LanWebsite_Db();
            self::$settings = new LanWebsite_Settings();
            self::$auth     = new $config['auth'];
            self::$usermanager = new $config['usermanager'];
            
            //Init auth
            self::$auth->init();
        
		}
        
        
        /**
         * Public routing engine
         */
        public static function routePublic() {
        
            //Validate route
            $page = self::validateRequestRoutes(array("api", "home", "tickets", "whatson", "tournaments", "info", "account", "profile", "gallery", "map", "contact", "servers", "orderfood", "presentation", "chat"));
            if (!$page) $page = self::$settings->getSetting("default_page");
            
            //Force data requirement if at LAN
            if (self::isAtLan() && $page != "account") {
            
                $userdata = self::$auth->getActiveUserData();
                $message = null;
                
                //Ticket checks
                $ticket = self::$db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], self::$settings->getSetting("lan_number"))->fetch_assoc();
                if (!$ticket || $ticket["activated"] == 0 || $userdata["lan"]["real_name"] == "" || $userdata["lan"]["emergency_contact_name"] == "" || $userdata["lan"]["emergency_contact_number"] == "") {
                    header("location: index.php?page=account");
                }
            }
            
            //Load controller and handle request
            require_once "controllers/public/" . $page . ".php";
            $class = ucwords($page) . "_Controller";
            $controller = new $class;
            $controller->handleRequest();
            
        }
        
        
        /**
         * Public routing engine
         */
        public static function routeAdmin() {

            self::$auth->requireAdmin();
            
            //Validate route
            $page = self::validateRequestRoutes(array("adminsettings", "admintournaments", "adminwhatson", "adminblog", "admingallery", "admintickets", "adminlanvan", "admintf2", "adminhungergames", "adminfood"));
            if (!$page) $page = self::$settings->getSetting("default_admin_page");
            
            //Load controller and handle request
            require_once "controllers/admin/" . $page . ".php";
            $class = ucwords($page) . "_Controller";
            $controller = new $class($page . "_Admin_Controller");
            $controller->handleRequest();
        
        }
        
        
        /**
         *  Private function to validate current http request against a lit of routes
         */
        private static function validateRequestRoutes($routes) {
            
            //Initialisation check
            if (self::$init == false) throw new Exception('Attempting to route before core initialization');
            
            //Instant IE ban
            if (strpos(getenv("HTTP_USER_AGENT"), "MSIE") > -1) {
                preg_match("/MSIE (\d\d?)\./", getenv("HTTP_USER_AGENT"), $matches);
                if ($matches[1] < 8) Header("Location: no-ie.html");
            }
            
            //Parse page - if invalid, load 'not found' template
            if (!isset($_GET["page"]) || $_GET["page"] == "") {
                $page = false;
            } else if (in_array(strtolower($_GET["page"]), $routes)) {
                $page = strtolower($_GET["page"]);
            } else {
                self::pageNotFound();
                return;
            }
            
            return $page;
            
        }
        
        
        /**
         *  Page not found
         */
        public static function pageNotFound() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("page not found");
            $tmpl->addTemplate('public/notfound');
			$tmpl->output();
        }
        
        
        /**
         *  Url Builder
         */
        public static function buildUrl($admin, $controller = null, $action = null, $args = array()) {
            
            //SEO-friendly URLs?
            include 'config.php';
            if ($config['seo_enabled']) {
                
                //Routing engine
                if ($admin) $url = "/admin/";
                else $url = "/";
                //Controller
                if ($controller != null) $url .= $controller . "/";
                //Action
                if ($action != null) $url .= $action . "/";
                //Args
                if (count($args) > 0) $url .= '?';
                foreach ($args as $arg => $value) $url .= urlencode($arg) . "=" . urlencode($value) . "&";
                
                return trim($url, "&");
            
            }
            
            //Standard URLs
            else {
                $params = array();
                
                //Routing engine
                if ($admin) $url = "/admin.php?";
                else $url = "/index.php?";
                //Controller
                if ($controller != null) $params[] = 'page' . "=" . $controller;
                //Action
                if ($action != null) $params[] = 'action' . "=" . $action;
                //Args
                foreach ($args as $arg => $value) $params[] = urlencode($arg) . "=" . urlencode($value);
                
                return $url . implode("&", $params);
            }
            
        }
        
        
        /**
         *  Getters
         */
        public static function getDb() {
            return self::$db;
        }
        
        public static function getSettings() {
            return self::$settings;
        }
        
        public static function getAuth() {
            return self::$auth;
        }
        
        public static function getUserManager() {
            return self::$usermanager;
        }
        
        
        /**
         *  Utility
         */
        public static function isAtLan() {
            $ips = explode(",", str_replace(" ", "", self::$settings->getSetting("lan_ip_addresses")));
            if (in_array(self::getIp(), $ips)) return true;
            else return false;
        }
        
        public static function getIp() {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
            else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            else $ip = $_SERVER['REMOTE_ADDR'];
            return $ip;
        }
		
	}
	
?>
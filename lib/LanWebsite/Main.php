<?php
    
    class LanWebsite_Main {
        
        private static $init = false;
        
        private static $controllerdir = '';
        
        private static $db;
        private static $settings;
        private static $auth;
        private static $usermanager;
        private static $templatemanager;
        
        
        /**
         * Loads base dependencies for LAN website core
         */
        public static function initialize() {
        
            //include $_SERVER['DOCUMENT_ROOT'] . 'logger.php';
            require '/srv/http/soc_lsucs/lan.lsucs.org.uk/htdocs/logger.php';
        
            //Initiation check
            if (self::$init == true) return false;
            self::$init = true;
    
            //Load base config
            $config = self::getConfig();
            
            //Load controller location
            self::$controllerdir = $config['controllerdir'];            
            
            //Load base objects
            self::$db       = new LanWebsite_Db();
            self::$settings = new LanWebsite_Settings();
            self::$auth     = new $config['auth'];
            self::$usermanager = new $config['usermanager'];
            self::$templatemanager = new LanWebsite_TemplateManager();
            
            //Init auth
            self::$auth->init();
            
        }
        
        
        /**
         *  Sets the base controller dir
         */
        public static function setControllerDir($dir) {
            self::$controllerdir = $dir;
        }
        
        /**
         *  Route the current request according to inputted valid controllers
         */
        public static function route($validControllers, $default) {
            
            //Initialisation check
            if (self::$init == false) throw new Exception('Attempting to route before core initialization');
            
            //Instant IE ban
            if (strpos(getenv("HTTP_USER_AGENT"), "MSIE") > -1) {
                preg_match("/MSIE (\d\d?)\./", getenv("HTTP_USER_AGENT"), $matches);
                if ($matches[1] < 8) Header("Location: /no-ie.html");
            }
            
            //Parse page - if invalid, load 'not found' template
            if (isset($_GET["page"]) && in_array(strtolower($_GET["page"]), $validControllers)) {
                $page = strtolower($_GET["page"]);
            } else if (isset($_GET["page"])) {
                return self::pageNotFound();
            } else {
                $page = $default;
            }
            
            
            //Check controller file exists
            if (!file_exists(trim(self::$controllerdir, "/") . "/" . $page . ".php")) return self::pageNotFound();
            
            //Load controller and handle request
            require_once trim(self::$controllerdir, "/") . "/" . $page . ".php";
            $class = ucwords($page) . "_Controller";
            $controller = new $class;
            $controller->handleRequest();
            
        }
        
        /**
         *  Page not found
         */
        public static function pageNotFound() {
            $tmpl = self::$templatemanager;
            $tmpl->setSubTitle("page not found");
            $tmpl->addTemplate('notfound');
            $tmpl->output();
        }
        
        
        /**
         *  Get Base Config
         */
        public static function getConfig() {
            include dirname(__FILE__) . '/../../config.php';
            return $config;
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
        
        public static function getTemplateManager() {
            return self::$templatemanager;
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
            /*$log_file_path = "/home/soc_lsucs/websites/lan.lsucs.org.uk/htdocs/ip-file.txt";
            if(file_exists($log_file_path))
            {
                file_put_contents($log_file_path, $ip);
            }*/
            return $ip;
        }
        
    }
    
?>


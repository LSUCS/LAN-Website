<?php

    ini_set('display_errors','On');
    error_reporting(E_ALL);
    setlocale(LC_MONETARY, 'en_GB');
    session_start();
    


	//Includes
	include("config.php");
	include("db.php");
    include("settings.php");
    include("auth.php");
    include("template.php");
	include("pages/page.php");
    include("email.php");
    include("simpleimage.php");
    include("raffle.php");

	$main = new Main();
	
	class Main {
		
		//Setup some variables
		public $config;
		public $db;
        public $settings;
        public $auth;
        public $template;
        public $raffle;
		public $pages;
		public $page;
		
		function __construct() {
			
            //Load base classes
			$this->config   = new Config();
			$this->db       = new Db($this);
            $this->settings = new Settings($this);
            $this->auth     = new Auth($this);
            $this->raffle   = new Raffle($this);
            
            //Load template manager
            $this->template = new Template($this);
    
            //Instant IE ban
            if (strpos(getenv("HTTP_USER_AGENT"), "MSIE") > -1) {
                preg_match("/MSIE (\d\d?)\./", getenv("HTTP_USER_AGENT"), $matches);
                if ($matches[1] < 8) Header("Location: no-ie.html");
            }
            
            //Admin routing
            if (isset($_GET["route"]) && strtolower($_GET["route"] == "admin")) {
            
                $this->auth->requireAdmin();
            
                //Set valid pages
                $this->pages = array("adminsettings", "admintournaments", "adminwhatson", "adminblog", "admingallery", "admintickets", "adminlanvan", "admintf2", "adminhungergames", "adminfood");
                
                //Parse page - if invalid, load 'not found' template
                if (!isset($_GET["page"]) || $_GET["page"] == "") {
                    $this->page = $this->settings->getSetting("default_admin_page");
                } else if (in_array(strtolower($_GET["page"]), $this->pages)) {
                    $this->page = strtolower($_GET["page"]);
                } else {
                    $this->template->setSubtitle("Page not found");
                    $this->template->outputTemplate();
                    return;
                }
            
            }
            
            //Regular public routing
            else {
            
                //Set valid pages
                $this->pages = array("api", "home", "tickets", "whatson", "tournaments", "info", "account", "profile", "gallery", "map", "contact", "servers", "orderfood", "presentation", "chat");
            
                //Parse page - if invalid, load 'not found' template
                if (!isset($_GET["page"]) || $_GET["page"] == "") {
                    $this->page = $this->settings->getSetting("default_page");
                } else if (in_array(strtolower($_GET["page"]), $this->pages)) {
                    $this->page = strtolower($_GET["page"]);
                } else {
                    $this->template->setSubtitle("Page not found");
                    $this->template->outputTemplate();
                    return;
                }
                
                if ($this->auth->isLoggedIn() && $this->auth->isPhysicallyAtLan() && $this->page != "account") {
                
                    $userdata = $this->auth->getActiveUserData();
                    $message = null;
                    
                    //Ticket checks
                    $ticket = $this->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $this->settings->getSetting("lan_number"))->fetch_assoc();
                    if (!$ticket || $ticket["activated"] == 0 || $userdata["lan"]["real_name"] == "" || $userdata["lan"]["emergency_contact_name"] == "" || $userdata["lan"]["emergency_contact_number"] == "") {
                        header("location: index.php?page=account");
                    }
                }
                
            }
            
            //Include requested page
            include("pages/" . $this->page . ".php");
            $class = $this->page . "_Page";
            $child = new $class($this);
        
			//See if there is a specified action to run or if we are running default
			$method = 'actionIndex';
			if (isset($_GET["action"]) && method_exists($child, "action" . ucwords(strtolower($_GET["action"])))) {
				$method = 'action' . ucwords(strtolower($_GET["action"]));
			}
            
            //Validate inputs against running method and store in child
            $inputarr = $child->getInputs();
            foreach ($inputarr as $page => $inputs) {
                if ($method == $page) {
                    foreach ($inputs as $input => $type) {
                        if ($type == "post" && isset($_POST[$input])) {
                            $child->inputs[$input] = $_POST[$input];
                        } else if ($type == "get" && isset($_GET[$input])) {
                            $child->inputs[$input] = $_GET[$input];
                        } else {
                            $child->inputs[$input] = "";
                        }
                    }
                }
            }
			
			//Run child page action
			call_user_func(array($child, $method));
			
		}
		
	}
	
?>
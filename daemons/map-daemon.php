<?php
    
    echo "### LSUCS LAN Website Map Daemon ###\n";
    echo "Initiating...\n";

    ini_set('display_errors','On');
    error_reporting(E_ALL);
    
    echo "Checking client...\n";
    
    //This can't run via the browser
    if(!php_sapi_name() == 'cli' || !empty($_SERVER['remove_addr'])) die("Cannot execute daemon via browser");
    
    echo "Including required files...\n";
    
    //Include necessaries
	include("config.php");
	include("db.php");
    include("settings.php");
    
    echo "Launching main class...\n";
    
    $main = new Main();
    
    class Main {
    
		public $config;
		public $db;
        public $settings;
        
        function __construct() {
        
            echo "Initiating base classes...\n";
        
            //Load base classes
			$this->config   = new Config();
			$this->db       = new Db($this);
            $this->settings = new Settings($this);
            
            echo "Retrieving sleep period...\n";
            
            //Sleep interval
            $sleep = $this->settings->getSetting("map_daemon_sleep_period");
            
            echo "Starting loop...\n";
            
            //Start daemon
            while (1) {
            
                echo "Processing...\n";
            
                //Start time
                $mtime = explode(" ",microtime());
                $starttime = $mtime[1] + $mtime[0];
                
                //Process
                file_get_contents($this->settings->getSetting("map_update_url"));
                
                //End time
                $mtime = explode(" ",microtime());
                $endtime = $mtime[1] + $mtime[0];
                $exectime = $endtime - $starttime;
                
                echo "Processing done. Execution time: " . ceil($exectime) . " seconds\n";
                
                //Change browser update period
                $this->settings->changeSetting("map_browser_update_interval", ceil($exectime + $sleep));
                
                echo "Sleeping for " . $sleep . " seconds...\n";
                
                sleep($sleep);
                
            }
            
        }
        

    }

?>
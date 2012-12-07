<?php

    class Main {
    
		public $settings;
		public $db;
		public $auth;
		public $config;
        
        function init() {
        
            echo "Initiating base classes...\n";
        
            //Load base classes
			$this->config   = new Config();
			$this->db       = new Db($this);
            $this->settings = new Settings($this);
			$this->auth     = new Auth($this);
			
			//Websocket server
			echo "Instantiating websocket server...\n";
			new ChatServer($this->settings->getSetting("chat_address"), $this->settings->getSetting("chat_port"));
            
        }
		        
    }
	
?>
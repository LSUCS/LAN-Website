<?php

    ini_set('display_errors','On');
    error_reporting(E_ALL);

    //Include necessaries
	include("config.php");
	include("db.php");
    include("settings.php");
    
    $main = new Main();
    
    class Main {
    
		public $config;
		public $db;
        public $settings;
        
        function __construct() {
        
            //Load base classes
			$this->config   = new Config();
			$this->db       = new Db($this);
            $this->settings = new Settings($this);
            
            echo "Getting tickets<br />";
            $res = $this->db->query("SELECT * FROM `tickets`");
            while ($ticket = $res->fetch_assoc()) {
                
                $purchase = $this->db->query("SELECT * FROM `receipts2`.`purchase` WHERE purchase_id = '%s'", $ticket["purchase_id"])->fetch_assoc();
                $receipt = $this->db->query("SELECT * FROM `receipts2`.`receipt` WHERE receipt_id = '%s'", $purchase["receipt_id"])->fetch_assoc();
                $this->db->query("UPDATE `tickets` SET purchased_name = '%s' WHERE ticket_id = '%s'", $receipt["name"], $ticket["ticket_id"]);
                
                echo "Processed " . $receipt["name"] . "<br />";
            
            }
            
            echo "Done";
            
        }
        
    }

?>
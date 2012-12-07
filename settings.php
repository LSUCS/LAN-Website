<?php

    class Settings {
    
        private $parent;
        
        private $defaults = array(
                                "lan_start_date" => array("date", "2012-10-26 20:00:00"),
                                "lan_end_date" => array("date", "2012-10-28 18:00:00"),
                                "lan_number" => array("int", "35"),
                                "enable_timer" => array("bool", true),
                                "member_ticket_sold_out" => array("bool", false),
                                "member_ticket_price" => array("int", 10),
                                "member_ticket_available" => array("bool", true),
                                "member_ticket_available_date" => array("date", "2012-10-01 00:00:00"),
                                "nonmember_ticket_sold_out" => array("bool", false),
                                "nonmember_ticket_price" => array("int", 15),
                                "nonmember_ticket_available" => array("bool", true),
                                "nonmember_ticket_available_date" => array("date", "2012-10-17 00:00:00"),
                                "xenforo_member_group_id" => array("int", 4),
                                "xenforo_fol_group_id" => array("int", 20),
                                "default_page" => array("text", "home"),
                                "default_title" => array("text", "LSUCS"),
                                "default_admin_page" => array("text", "adminsettings"),
                                "api_key" => array("pass", ''),
                                "paypal_email" => array("text", "paypal@lsucs.org.uk"),
                                "paypal_return_url" => array("text", "http://lan.lsucs.org.uk/index.php?page=tickets&action=complete"),
                                "paypal_ipn_url" => array("text", "http://lan.lsucs.org.uk/index.php?page=tickets&action=ipn"),
                                "paypal_url" => array("text", "https://www.paypal.com/cgi-bin/webscr"),
                                "email_user" => array("text", "receipts@lsucs.org.uk"),
                                "email_pass" => array("pass", ""),
                                "email_host" => array("text", "ssl://smtp.gmail.com"),
                                "email_port" => array("text", "465"),
                                "receipt_api_availability_url" => array("text", "http://receipts2.lsucs.org.uk/index.php?page=api&action=lanavailability"),
                                "receipt_api_issue_url" => array("text", "http://receipts2.lsucs.org.uk/index.php?page=api&action=issuelanreceipt"),
                                "max_order_lookup_attempts" => array("int", 60),
                                "steam_api_key" => array("pass", ""),
                                "lan_ip_addresses" => array("text", "0.0.0.0,0.0.0.0"),
                                "disable_lan_van" => array("bool", false),
                                "map_cron_lock" => array("bool", false),
                                "map_update_url" => array("text", "http://lans.lsucs.org.uk/index.php?page=map&action=process"),
                                "map_browser_update_interval" => array('int', 30),
                                "map_daemon_sleep_period" => array('int', 10),
                                "server_cron_lock" => array("bool", false),
                                "server_update_url" => array("text", "http://lans.lsucs.org.uk/index.php?page=servers&action=process"),
                                "server_browser_update_interval" => array('int', 10),
                                "server_daemon_sleep_period" => array('int', 5),
								"enable_tf2" => array('bool', false),
								"enable_hungergames" => array('bool', false),
								"chat_address" => array("text", "localhost"),
								"chat_port" => array("int", 8081),
								"chat_history_length" => array("int", 10)
                                );
    
        public function __construct($parent) {
            $this->parent = $parent;
            $this->checkDefaults();
        }
        
        public function getSetting($setting) {
            if (!$this->settingIsReal($setting)) return false;
            $res = $this->parent->db->query("SELECT * FROM `settings` WHERE setting_name = '%s'", $setting);
            $arr = $res->fetch_array();
            return $arr[2];
        }
        
        public function getSettings() {
            $res = $this->parent->db->query("SELECT * FROM `settings`");
            $arr = array();
            while ($row = $res->fetch_assoc()) $arr[] = $row;
            return $arr;
        }
        
        public function changeSetting($setting, $value) {
        
            if (!$this->settingIsReal($setting)) die("Invalid setting name: " . $setting);
            
            //Type validation
            switch ($this->defaults[$setting][0]) {
                case "date":
                    if (!preg_match("/^\d\d\d\d-\d\d-\d\d\s\d\d:\d\d:\d\d$/", $value)) return false;
                    break;
                case "int":
                    if (!is_numeric($value)) return false;
                    break;
                case "bool":
                    if ($value && $value !== 1 && $value !== 0) return false;
                    if ($value) $value = true;
                    else $value = false;
                    break;
                case "text":
                case "pass":
                    break;
            }
            
            //Update value
            if ($this->parent->db->query("UPDATE `settings` SET setting_value='%s' WHERE setting_name = '%s'", $value, $setting)) return true;
            return false;
            
        }
        
        
        public function settingIsReal($setting) {
            return array_key_exists($setting, $this->defaults);
        }
        
        private function settingIsStored($setting) {
            $res = $this->parent->db->query("SELECT * FROM `settings` WHERE setting_name = '%s'", $setting);
            if (mysqli_num_rows($res) > 0) return true;
            return false;
        }
        private function checkDefaults() {
            foreach ($this->defaults as $setting => $properties) {
                if (!$this->settingIsStored($setting)) {
                    $this->parent->db->query("INSERT INTO `settings` (setting_name, setting_type, setting_value) VALUES ('%s', '%s', '%s')", $setting, $properties[0], $properties[1]);
                }
            }
        }
    
    }

?>
<?php

    class LanWebsite_Settings {
    
        //TODO: groups, cache
        
        private $defaults = array(
                                "lan_start_date" => array("date", "2012-10-26 20:00:00", SettingsGroup::Lan),
                                "lan_end_date" => array("date", "2012-10-28 18:00:00", SettingsGroup::Lan),
                                "lan_number" => array("int", "35", SettingsGroup::Lan),
                                "enable_timer" => array("bool", true, SettingsGroup::Lan),
                                "member_ticket_sold_out" => array("bool", false, SettingsGroup::Tickets),
                                "member_ticket_price" => array("int", 10, SettingsGroup::Tickets),
                                "member_ticket_available" => array("bool", true, SettingsGroup::Tickets),
                                "member_ticket_available_date" => array("date", "2012-10-01 00:00:00", SettingsGroup::Tickets),
                                "nonmember_ticket_sold_out" => array("bool", false, SettingsGroup::Tickets),
                                "nonmember_ticket_price" => array("int", 15, SettingsGroup::Tickets),
                                "nonmember_ticket_available" => array("bool", true, SettingsGroup::Tickets),
                                "nonmember_ticket_available_date" => array("date", "2012-10-17 00:00:00", SettingsGroup::Tickets),
                                "xenforo_member_group_id" => array("int", 4, SettingsGroup::Auth),
                                "xenforo_fol_group_id" => array("int", 20, SettingsGroup::Auth),
                                "default_page" => array("text", "home", SettingsGroup::General),
                                "default_title" => array("text", "LSUCS", SettingsGroup::General),
                                "default_admin_page" => array("text", "adminsettings", SettingsGroup::General),
                                "api_key" => array("pass", '', SettingsGroup::General),
                                "paypal_email" => array("text", "paypal@lsucs.org.uk", SettingsGroup::PayPal),
                                "paypal_return_url" => array("text", "http://lan.lsucs.org.uk/index.php?page=tickets&action=complete", SettingsGroup::PayPal),
                                "paypal_ipn_url" => array("text", "http://lan.lsucs.org.uk/index.php?page=tickets&action=ipn", SettingsGroup::PayPal),
                                "paypal_url" => array("text", "https://www.paypal.com/cgi-bin/webscr", SettingsGroup::PayPal),
                                "email_user" => array("text", "receipts@lsucs.org.uk", SettingsGroup::Email),
                                "email_pass" => array("pass", "", SettingsGroup::Email),
                                "email_host" => array("text", "ssl://smtp.gmail.com", SettingsGroup::Email),
                                "email_port" => array("text", "465", SettingsGroup::Email),
                                "receipt_api_availability_url" => array("text", "http://receipts2.lsucs.org.uk/index.php?page=api&action=lanavailability", SettingsGroup::ReceiptSystem),
                                "receipt_api_issue_url" => array("text", "http://receipts2.lsucs.org.uk/index.php?page=api&action=issuelanreceipt", SettingsGroup::ReceiptSystem),
                                "max_order_lookup_attempts" => array("int", 60, SettingsGroup::General),
                                "steam_api_key" => array("pass", "", SettingsGroup::Steam),
                                "lan_ip_addresses" => array("text", "0.0.0.0,0.0.0.0", SettingsGroup::General),
                                "disable_lan_van" => array("bool", false, SettingsGroup::LanVan),
                                "map_cron_lock" => array("bool", false, SettingsGroup::Map),
                                "map_update_url" => array("text", "http://lans.lsucs.org.uk/index.php?page=map&action=process", SettingsGroup::Map),
                                "map_browser_update_interval" => array('int', 30, SettingsGroup::Map),
                                "map_daemon_sleep_period" => array('int', 10, SettingsGroup::Map),
                                "server_cron_lock" => array("bool", false, SettingsGroup::GameServer),
                                "server_update_url" => array("text", "http://lans.lsucs.org.uk/index.php?page=servers&action=process", SettingsGroup::GameServer),
                                "server_browser_update_interval" => array('int', 10, SettingsGroup::GameServer),
                                "server_daemon_sleep_period" => array('int', 5, SettingsGroup::GameServer),
								"enable_tf2" => array('bool', false, SettingsGroup::General),
								"enable_hungergames" => array('bool', false, SettingsGroup::General),
								"chat_address" => array("text", "localhost", SettingsGroup::Chat),
								"chat_port" => array("int", 8081, SettingsGroup::Chat),
								"chat_history_length" => array("int", 10, SettingsGroup::Chat),
								"chat_enabled" => array("bool", 0, SettingsGroup::Chat),
								"chat_url" => array("text", "ws://lan.lsucs.org.uk:8080", SettingsGroup::Chat),
                                "lsucs_auth_url" => array("text", "http://auth.lsucs.org.uk/", SettingsGroup::Auth)
                                );
    
        public function __construct() {
            $this->checkDefaults();
        }
        
        public function getSetting($setting) {
            if (!$this->settingIsReal($setting)) return false;
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `settings` WHERE setting_name = '%s'", $setting);
            $arr = $res->fetch_array();
            return $arr[2];
        }
        
        public function getSettings() {
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `settings`");
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
            if (LanWebsite_Main::getDb()->query("UPDATE `settings` SET setting_value='%s' WHERE setting_name = '%s'", $value, $setting)) return true;
            return false;
            
        }
        
        public function settingIsReal($setting) {
            return array_key_exists($setting, $this->defaults);
        }
        
        private function settingIsStored($setting) {
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `settings` WHERE setting_name = '%s'", $setting);
            if (mysqli_num_rows($res) > 0) return true;
            return false;
        }
        private function checkDefaults() {
            foreach ($this->defaults as $setting => $properties) {
                if (!$this->settingIsStored($setting)) {
                    LanWebsite_Main::getDb()->query("INSERT INTO `settings` (setting_name, setting_type, setting_value) VALUES ('%s', '%s', '%s')", $setting, $properties[0], $properties[1]);
                }
            }
        }
    
    }
    
    abstract class SettingsGroup {
        const General = "General";
        const Lan = "LAN";
        const Tickets = "Tickets";
        const Auth = "Auth";
        const PayPal = "PayPal";
        const Email = "Email";
        const ReceiptSystem = "Receipt System";
        const Steam = "Steam";
        const LanVan = "LAN Van";
        const Map = "Live Map";
        const GameServer = "Server List";
        const Chat = "Chat";
    }

?>
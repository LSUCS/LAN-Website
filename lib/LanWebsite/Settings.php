<?php

    class LanWebsite_Settings {
    
        private $cache = array();
        
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
                                "api_key" => array("pass", '', SettingsGroup::General),
                                "paypal_email" => array("email", "paypal@lsucs.org.uk", SettingsGroup::PayPal),
                                "paypal_return_url" => array("url", "http://lan.lsucs.org.uk/index.php?page=tickets&action=complete", SettingsGroup::PayPal),
                                "paypal_ipn_url" => array("url", "http://lan.lsucs.org.uk/index.php?page=tickets&action=ipn", SettingsGroup::PayPal),
                                "paypal_url" => array("url", "https://www.paypal.com/cgi-bin/webscr", SettingsGroup::PayPal),
                                "email_user" => array("email", "receipts@lsucs.org.uk", SettingsGroup::Email),
                                "email_pass" => array("pass", "", SettingsGroup::Email),
                                "email_host" => array("text", "ssl://smtp.gmail.com", SettingsGroup::Email),
                                "email_port" => array("text", "465", SettingsGroup::Email),
                                "receipt_api_availability_url" => array("url", "http://receipts2.lsucs.org.uk/index.php?page=api&action=lanavailability", SettingsGroup::ReceiptSystem),
                                "receipt_api_issue_url" => array("url", "http://receipts2.lsucs.org.uk/index.php?page=api&action=issuelanreceipt", SettingsGroup::ReceiptSystem),
                                "max_order_lookup_attempts" => array("int", 60, SettingsGroup::General),
                                "lan_ip_addresses" => array("text", "0.0.0.0,0.0.0.0", SettingsGroup::General),
                                "disable_lan_van" => array("bool", false, SettingsGroup::LanVan),
                                "map_cron_lock" => array("bool", false, SettingsGroup::Map),
                                "map_process_url" => array("url", "http://dev.lan.lsucs.org.uk/map/processseat/?ticket=", SettingsGroup::Map),
                                "map_browser_update_interval" => array('int', 30, SettingsGroup::Map),
                                "map_daemon_sleep_period" => array('int', 10, SettingsGroup::Map),
								"chat_address" => array("text", "localhost", SettingsGroup::Chat),
								"chat_port" => array("int", 8081, SettingsGroup::Chat),
								"chat_history_length" => array("int", 10, SettingsGroup::Chat),
								"chat_enabled" => array("bool", false, SettingsGroup::Chat),
                                "chat_daemon_online" => array("bool", false, SettingsGroup::Chat),
								"chat_url" => array("text", "ws://lan.lsucs.org.uk:8087", SettingsGroup::Chat),
                                "require_ticket_for_chat" => array("bool", false, SettingsGroup::Chat),
                                "chat_message_max_length" => array("int", 500, SettingsGroup::Chat),
                                "chat_buffer_size" => array("int", 548576, SettingsGroup::Chat),
                                "lsucs_auth_url" => array("url", "http://auth.lsucs.org.uk/", SettingsGroup::Auth),
                                "presentation_url" => array("url", "https://docs.google.com/presentation/embed?id=1xSQaCi9f6lRTms75rQqLbYCPX88z6JegMQkTBNYAths&start=true&loop=true&delayms=15000", SettingsGroup::Presentation),
                                "presentation_refresh_interval" => array("int", 60000, SettingsGroup::Presentation),
                                "committee_rota_url" => array("url", "https://docs.google.com/spreadsheet/pub?key=0Ar_sSX-R25mQdEF4ekVBektsV0o1U0JoOEZuZ3NhUXc&output=html&widget=true", SettingsGroup::CommitteeRota),
                                "committee_rota_width" => array("int", 700, SettingsGroup::CommitteeRota),
                                "committee_rota_height" => array("int", 500, SettingsGroup::CommitteeRota),
								"lobby_address" => array("text", "localhost", SettingsGroup::Lobbies),
								"lobby_port" => array("int", 8088, SettingsGroup::Lobbies),
								"lobby_history_length" => array("int", 20, SettingsGroup::Lobbies),
								"lobby_enabled" => array("bool", false, SettingsGroup::Lobbies),
                                "lobby_daemon_online" => array("bool", false, SettingsGroup::Lobbies),
								"lobby_url" => array("text", "ws://lan.lsucs.org.uk:8088", SettingsGroup::Lobbies),
                                "lobby_show_connecting" => array("bool", true, SettingsGroup::Lobbies),
                                "lobby_message_max_length" => array("int", 500, SettingsGroup::Lobbies),
                                "lobby_buffer_size" => array("int", 548576, SettingsGroup::Lobbies),
                                );
        
        public function getSetting($setting) {
            
            //If invalid, return false and delete
            if (!$this->settingIsReal($setting)) {
                $this->deleteSetting($setting);
                return false;
            }
            
            //If not cached, load from db
            if (!isset($this->cache[$setting])) {
                $res = LanWebsite_Main::getDb()->query("SELECT * FROM `settings` WHERE setting_name = '%s'", $setting);
                
                //If no result, load default
                if (mysqli_num_rows($res) < 1) {
                    LanWebsite_Main::getDb()->query("INSERT INTO `settings` (setting_name, setting_value) VALUES ('%s', '%s')", $setting, $this->defaults[$setting][1]);
                    $this->cache[$setting] = $this->defaults[$setting][1];
                } 
                //Otherwise use DB value
                else {
                    $arr = $res->fetch_array();
                    $this->cache[$setting] = $arr[1];
                }
            }
            
            return $this->cache[$setting];
        }
        
        public function getSettings() {
            $arr = array();
            foreach ($this->defaults as $setting => $properties) {
                $row["setting_value"] = $this->getSetting($setting);
                $row["setting_name"] = $setting;
                $row["setting_group"] = $this->defaults[$row["setting_name"]][2];
                $row["setting_default"] = $this->defaults[$row["setting_name"]][1];
                $row["setting_type"] = $this->defaults[$row["setting_name"]][0];
                $arr[] = $row;
            }
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
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) return false;
                    break;
                case "email":
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) return false;
                    break;
                case "url":
                    if (filter_var($value, FILTER_VALIDATE_URL) === false) return false;
                    break;
                case "ip":
                    if (!filter_var($value, FILTER_VALIDATE_IP)) return false;
                    break;
                case "text":
                case "pass":
                    break;
            }
            
            //Update value
            if (LanWebsite_Main::getDb()->query("UPDATE `settings` SET setting_value='%s' WHERE setting_name = '%s'", $value, $setting)) {
                $this->cache[$setting] = $value;
                return true;
            }
            return false;
            
        }
        
        public function settingIsReal($setting) {
            return array_key_exists($setting, $this->defaults);
        }
        
        private function deleteSetting($setting) {
            LanWebsite_Main::getDb()->query("DELETE FROM `settings` WHERE setting_name = '%s'", $setting);
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
        const LanVan = "LAN Van";
        const Map = "Live Map";
        const GameServer = "Server List";
        const Chat = "Chat";
        const Presentation = "LAN Presentation";
        const CommitteeRota = "Committee Rota";
        const Lobbies = "Lobbies";
    }

?>
<?

    class LanWebsite_Raffle {
        
        public static function issueAttendanceTicket($userid) {
            $lan = LanWebsite_Main::getSettings()->getSetting('lan_number');
            
            //Check if attendance ticket already issued
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `raffle_tickets` WHERE user_id = '%s' AND lan_number = '%s' AND reason = 'attendance'", $userid, $lan);
            if ($res->num_rows == 0) {
                LanWebsite_Main::getDb()->query("INSERT INTO `raffle_tickets` (lan_number, raffle_ticket_number, user_id, reason) VALUES ('%s', '%s', '%s', '%s')", $lan, self::getTicketNumber(), $userid, "attendance");
            }
        }
        
        public static function issueTicket($userid, $reason) {
            LanWebsite_Main::getDb()->query("INSERT INTO `raffle_tickets` (lan_number, raffle_ticket_number, user_id, reason) VALUES ('%s', '%s', '%s', '%s')", LanWebsite_Main::getSettings()->getSetting("lan_number"), self::getTicketNumber(), $userid, $reason);
        }
        
        public static function deleteTicket($number) {
            LanWebsite_Main::getDb()->query("DELETE FROM `raffle_tickets` WHERE raffle_ticket_number = '%s' AND lan_number = '%s'", $number, LanWebsite_Main::getSettings()->getSetting("lan_number"));
        }
        
        private static function getTicketNumber() {
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `raffle_tickets` WHERE lan_number = '%s' ORDER BY raffle_ticket_number ASC", LanWebsite_Main::getSettings()->getSetting('lan_number'));
            $i = 1;
            while ($row = $res->fetch_assoc()) {
                if ($row["raffle_ticket_number"] > $i) break;
                $i++;
            }
            return $i;
        }
    
    }
    
?>
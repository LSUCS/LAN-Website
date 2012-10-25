<?

    class Raffle {
        
        private $parent;
    
        public function __construct($parent) {
            $this->parent = $parent;
        }
        
        public function issueAttendanceTicket($userid) {
            $lan = $this->parent->settings->getSetting('lan_number');
            
            //Check if attendance ticket already issued
            $res = $this->parent->db->query("SELECT * FROM `raffle_tickets` WHERE user_id = '%s' AND lan_number = '%s' AND reason = 'attendance'", $userid, $lan);
            if ($res->num_rows == 0) {
                $this->parent->db->query("INSERT INTO `raffle_tickets` (lan_number, raffle_ticket_number, user_id, reason) VALUES ('%s', '%s', '%s', '%s')", $lan, $this->getTicketNumber(), $userid, "attendance");
            }
        }
        
        public function issueTicket($userid, $reason) {
            $this->parent->db->query("INSERT INTO `raffle_tickets` (lan_number, raffle_ticket_number, user_id, reason) VALUES ('%s', '%s', '%s', '%s')", $this->parent->settings->getSetting("lan_number"), $this->getTicketNumber(), $userid, $reason);
        }
        
        public function deleteTicket($number) {
            $this->parent->db->query("DELETE FROM `raffle_tickets` WHERE raffle_ticket_number = '%s' AND lan_number = '%s'", $number, $this->parent->settings->getSetting("lan_number"));
        }
        
        private function getTicketNumber() {
            $res = $this->parent->db->query("SELECT * FROM `raffle_tickets` WHERE lan_number = '%s' ORDER BY raffle_ticket_number ASC", $this->parent->settings->getSetting('lan_number'));
            $i = 1;
            while ($row = $res->fetch_assoc()) {
                if ($row["raffle_ticket_number"] > $i) break;
                $i++;
            }
            return $i;
        }
    
    }
    
?>
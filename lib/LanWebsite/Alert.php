<?php

    
	class LanWebsite_Alert {
	   
        const ALERT_IMPORTANT = 'important';
        const ALERT_NOTICE = 'notice';
        const ALERT_MESSAGE = 'message';
        const ALERT_SUCCESS = 'success';
	   
        private $id = null;
        private $level = null;        
        private $message = null;
        private $link = null;
        
        public function __construct($level, $message, $link=null) {
            $this->level = $level;
            $this->message = $message;
            $this->link = $link;
        }
        
        public function __toString() {
            $string = '';
            if(!empty($this->link)) $string .= "<a href='" . $this->link . "'>";
            $string .= "<div class='alert " . $this->level . "' id='alert-" . $this->id . "'>";
            $string .= "<span class='alert-message'>" . $this->message . "</span>";
            $string .= "<span class='alert-close'></span>";
            $string .= "</div>";
            if(!empty($this->link)) $string .= "</a>";
            return $string;
        }
        
	
	}
    
?>
<?php

    class EmailWrapper {
    
        private $template = "";
        private $message;
        private $transport;
        private $mailer;
        private $config;
        private $parent;
        
        public function __construct($parent) {
        
            $this->parent = $parent;
        
            //Require Swift Mailer
            require_once 'lib/SwiftMailer/swift_required.php';
           
            //Set up message
            $this->message = Swift_Message::newInstance();
            $this->message->setContentType("text/html");
            $this->message->setCharset("iso-8859-1");
            $this->message->setFrom(array($this->parent->settings->getSetting("email_user") => "LSU Computer Society"));

        }
        
        //Template management
        public function loadTemplate($template) {
            $this->template = file_get_contents("emails/" . $template . ".html");
        }
        public function replaceKey($key, $value) {
            $this->template = str_replace($key, $value, $this->template);
        }
        
        //Settings
        public function setTo($value) {
            $this->message->setTo($value);
        }
        public function setSubject($value) {
            $this->message->setSubject($value);
        }
        public function setBody($message) {
            $this->template = $message;
        }
        
        //Get message object
        public function getMessage() {
            return $this->message;
        }
        
        //Send message
        public function send() {
        
            //Set up transport
            $this->transport = Swift_SmtpTransport::newInstance($this->parent->settings->getSetting("email_host"), $this->parent->settings->getSetting("email_port"));
            $this->transport->setUsername($this->parent->settings->getSetting("email_user"));
            $this->transport->setPassword($this->parent->settings->getSetting("email_pass"));
            
            //Set up mailer
            $mailer = Swift_Mailer::newInstance($this->transport);
            
            //Set message template
            $this->message->setBody($this->template);
            
            //Send and return
            return $result = $mailer->send($this->message);
        
        }
    
    }

?>
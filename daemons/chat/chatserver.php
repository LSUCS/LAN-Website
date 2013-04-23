<?php

	class ChatServer extends WebSocketServer {
		
		protected $maxBufferSize = 1048576;
		protected $userClass = 'ChatUser';
        
        private $conversationContacts = array();
        private $contactDetails = array();
        private $conversations = array();
        private $messages = array();

		//////////////////////////
		// CONNECTION FUNCTIONS //
		//////////////////////////
		/**
		 * Handles incoming protocol messages
		 */
		protected function process($user, $message) {
			
			$this->stdout("Received message from " . $user->id . ": " . $message);
			
			//Extract protocol command and payload
			if (strpos($message, ":") === false) return $this->error($user, "Invalid message structure");
			$command = strtolower(substr($message, 0, strpos($message, ":")));
			$payload = json_decode(substr($message, strpos($message, ":")+1), true);
			if ($payload === NULL && strlen($payload) > 0) return $this->error($user, "Invalid JSON payload");
			
			//Select protocol command action
			switch ($command) {
			
				//INIT - Returns all initial chat information for user
				//params - { }
				//return - JSON array of open conversations and user list
				case "init":
                    $conversations = array();
                    foreach ($this->getConversations($user->data->getUserId()) as $conv) {
                        if ($conv->contacts[$user->data->getUserId()]->open == 1) $conversations[$conv->conversationid] = $conv;
                    }
					$this->sendCommand($user, "init", array("contacts" => $this->getContactList($user), "conversations" => $conversations, "userid" => $user->data->getUserId()));
					break;
				
				//SENDMESSAGE - Sends a message to a conversation
				//params - { convID: , message: }
				//return - null
				case "sendmessage":
                
                    //Check if message is null/invalid
                    if (!isset($payload["message"]) || strlen(trim($payload["message"])) == 0) return $this->error($user, "Invalid message");
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = $this->getConversation($payload["convID"]);
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Get conversation contacts, check if user is part of it
                    $in = false;
                    foreach ($conv->contacts as $contact) {
                        if ($contact->userid == $user->data->getUserId()) $in = true;
                    }
                    if (!$in) return $this->error($user, "User not part of conversation");
        
                    //Create and save message object
                    $msg = strip_tags(trim($payload["message"]));
                    if (strlen($msg) > LanWebsite_Main::getSettings()->getSetting("chat_message_max_length")) $msg = substr($msg, 0, LanWebsite_Main::getSettings()->getSetting("chat_message_max_length"));
                    $msg = preg_replace("/([^\w\/])(www\.[a-z0-9\-]+\.[a-z0-9\-]+)/i", "$1http://$2", $msg);
                    $msg = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a target=\"_blank\" href=\"$1\">$1</A>", $msg);
                    $message = new Message(time(), $conv->conversationid, $user->data->getUserId(), $this->getContactDetails($user->data->getUserId()), $msg);
                    $this->saveMessage($message);
                    
                    //Loop contacts, updating read status and messaging them if need be
                    foreach ($conv->contacts as $contact) {
                    
                        //Mark as unread
                        if ($contact->userid == $user->data->getUserId()) $contact->read = 1;
                        else $contact->read = 0;
                        
                        //Send out
                        if ($contact->open == 0) {
                            $contact->minimised = 0;
                            $contact->open = 1;
                            $this->saveConversationContact($contact);
                            $this->sendAllUsersCommand($contact->userid, "openconversation", $this->getConversation($conv->conversationid));
                        } else {
                            $this->sendAllUsersCommand($contact->userid, "sendmessage", $message);
                        }
                        
                        //Update contact
                        $contact->open = 1;
                        $this->saveConversationContact($contact);
                        
                    }
                    
                foreach ($conv->contacts as $contact) {
                    if ($contact->userid != $user->data->getUserId()) $d = $contact->userid;
                }  
                Logger::log("chatmessage", json_encode(array("sender" => $user->data->getUserId(), "to" => $d, "message" => $message->message)));
                Logger::store();
                    
					break;
					
				//OPENCONVERSATION - Opens a conversation with a user
				//params - { userID: }
				//return - JSON object of conversation
				case "openconversation":
				
					//Validate user id
					if (!isset($payload["userID"]) || $payload["userID"] == $user->data->getUserId() || !$this->getContactDetails($payload["userID"])) return $this->error($user, "Invalid user id for conversation");
                    
                    //Ticket mode
                    if (LanWebsite_Main::getSettings()->getSetting("require_ticket_for_chat") == 1) {
                        $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND activated = 1 AND assigned_forum_id > 0", LanWebsite_Main::getSettings()->getSetting("lan_number"));
                        //$res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE activated = 1 AND assigned_forum_id = '%s'", $payload["userID"]);
                        if (!$res) return $this->error($user, "Cannot open conversation, recipient has no ticket");
                    }
				
					//Check if a conversation already exists between these two users and return it if it does
                    foreach ($this->getConversations($user->data->getUserId()) as $conv) {
                        foreach ($conv->contacts as $contact) {
                            if ($contact->userid == $payload["userID"]) {
                                $c = $this->getConversationContact($user->data->getUserId(), $conv->conversationid);
                                $c->open = 1;
                                $this->saveConversationContact($c);
                                $this->sendAllUsersCommand($user->data->getUserId(), "openconversation", $this->getConversation($conv->conversationid));
                                return;
                            }
                        }
                    }
                    
					//Create and save conversation
                    $contacts[] = new ConversationContact($user->data->getUserId(), null, 1, 0, 1);
                    $contacts[] = new ConversationContact($payload["userID"], null, 0, 1, 1);
                    $conv = new Conversation(null, $contacts, array());
                    $conv = $this->saveConversation($conv);
                    
					//Reciprocate open conversation
					$this->sendAllUsersCommand($user->data->getUserId(), "openconversation", $conv);
					
					break;
					
				//CLOSECONVERSATION - Marks a conversation as closed (hidden) for the user
				//params - { convID: }
				//return - null
				case "closeconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"]) || !$this->getConversation($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = $this->getConversation($payload["convID"]);
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $contact = $this->getConversationContact($user->data->getUserId(), $payload["convID"]);
                    if (!$contact) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as closed
                    $contact->open = 0;
                    $this->saveConversationContact($contact);
                    
                    //Reciprocate
                    $this->sendAllUsersCommand($user->data->getUserId(), "closeconversation", $conv);
                    
					break;
					
				//MINIMISECONVERSATION - Marks a conversation as minimised for the user
				//params - { convID: }
				//return - null
				case "minimiseconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"]) || !$this->getConversation($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = $this->getConversation($payload["convID"]);
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $contact = $this->getConversationContact($user->data->getUserId(), $payload["convID"]);
                    if (!$contact) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as minimised
                    $contact->minimised = 1;
                    $this->saveConversationContact($contact);
                    
                    //Reciprocate
                    $this->sendAllUsersCommand($user->data->getUserId(), "minimiseconversation", $conv);
                    
					break;
                    
				//MAXIMISECONVERSATION - Marks a conversation as maximised for the user
				//params - { convID: }
				//return - null
				case "maximiseconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"]) || !$this->getConversation($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = $this->getConversation($payload["convID"]);
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $contact = $this->getConversationContact($user->data->getUserId(), $payload["convID"]);
                    if (!$contact) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as maximised
                    $contact->minimised = 0;
                    $this->saveConversationContact($contact);
                    
                    //Reciprocate
                    $this->sendAllUsersCommand($user->data->getUserId(), "maximiseconversation", $conv);
                    
					break;
                    
				//READCONVERSATION - Marks a conversation as read for the user
				//params - { convID: }
				//return - null
				case "readconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"]) || !$this->getConversation($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = $this->getConversation($payload["convID"]);
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $contact = $this->getConversationContact($user->data->getUserId(), $payload["convID"]);
                    if (!$contact) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as closed
                    $contact->read = 1;
                    $this->saveConversationContact($contact);
                    
					break;

				
				//DEFAULT - invalid protocol command
				default:
					$this->error($user, "Invalid protocol command");
					break;
			
			}
		}
		
		/**
		 * Handles authenticating the connecting websocket
		 */
		protected function connected($user) {
			
			//Handle cookie and init auth
			$cookies = explode("; ", $user->headers['cookie']);
			$_COOKIE = array();
			foreach ($cookies as $cookie) {
				$cookie = explode("=", $cookie);
				$_COOKIE[$cookie[0]] = $cookie[1];
			}
            LanWebsite_Main::getAuth()->init();
			
			//If user is not logged in, fatal error
			if (!LanWebsite_Main::getAuth()->isLoggedIn()) return $this->fatalError($user, "Must be logged in to use chat");
			$user->data = LanWebsite_Main::getUserManager()->getActiveUser();
			
			$this->stdout("Authorised connection " . $user->data->getUserId() . " for " . $user->data->getUsername());
            
            //Ticket mode
            if (LanWebsite_Main::getSettings()->getSetting("require_ticket_for_chat") == 1) {
                //$res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND activated = 1 AND assigned_forum_id > 0", LanWebsite_Main::getSettings()->getSetting("lan_number"));
                $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE activated = 1 AND assigned_forum_id = '%s'", $user->data->getUserId());
                if (!$res) return $this->error($user, "Must have lan ticket to use chat");
            }
			
			//Update presence to other sockets, excluding current user
            $this->getContactDetails($user->data->getUserId());
            $this->setContactStatus($user->data->getUserId(), "online");
            $details = $this->getContactDetails($user->data->getUserId());
            foreach ($this->users as $u) {
                if ($user->data->getUserId() != $u->data->getUserId()) {
                    $this->sendCommand($u, "updatecontact", $details);
                }
            }
			
		}
		
		/**
		 * Shuts down the connection
		 */
		protected function closed($user) {
			$this->stdout("Connection " . $user->id . " closed by user");
            
            //Check if other sockets exist for user
            foreach ($this->users as $u) {
                if ($u->id != $user->id && $u->data->getUserId() == $user->data->getUserId()) return;
            }
            
			//Update presence to other sockets, excluding current user
            $this->setContactStatus($user->data->getUserId(), "offline");
            $details = $this->getContactDetails($user->data->getUserId());
            foreach ($this->users as $u) {
                if ($user->data->getUserId() != $u->data->getUserId()) {
                    $this->sendCommand($u, "updatecontact", $details);
                }
            }
		}
		
		
		//////////////////////
		//  SEND FUNCTIONS  //
		//////////////////////
		/**
		 * Sends a command to a socket
		 */
		protected function sendCommand($user, $command, $payload) {
			$this->stdout("SEND - " . $command . " - TO - "  . $user->id);
			$this->send($user, $command . ":" . json_encode($payload));
		}
        
		/**
		 * Sends a command to all sockets for inputted user id
		 */
		protected function sendAllUsersCommand($userid, $command, $payload) {
            foreach ($this->users as $user) {
                if ($user->data->getUserId() == $userid) {
                    $this->stdout("SEND - " . $command . " - TO - "  . $user->id);
                    $this->send($user, $command . ":" . json_encode($payload));
                }
            }
		}
		
		/**
		 * Sends an error to the client
		 */
		protected function error($user, $message) {
			$this->stdout("ERROR: " . $message);
			$this->sendCommand($user, "error", array("error" => $message));
		}
		
		/**
		 * Sends an error to the client then closes the connection
		 */
		protected function fatalError($user, $message) {
			$this->error($user, $message);
			$this->disconnect($user->socket);
		}	
        
        
        ////////////////////////////
		// CONVERSATION FUNCTIONS //
		////////////////////////////
        /**
         *  Returns a conversation by its ID
         */
        protected function getConversation($conversationId) {
            if (isset($this->conversations[$conversationId])) {
                $conv = $this->conversations[$conversationId];
                $conv->contacts = $this->getConversationContacts($conversationId);
                $conv->history = $this->getConversationHistory($conversationId);
                return $conv;
            }
            else return false;
        }
        
        /**
         *  Get conversations for a user
         */
        protected function getConversations($userId) {
            $return = array();
            foreach ($this->conversationContacts as $conv) {
                foreach ($conv as $contact) {
                    if ($contact->userid == $userId) {
                        $return[$contact->conversationid] = $this->getConversation($contact->conversationid);
                        break;
                    }
                }
            }
            return $return;
        }
        
        /**
         *  Save a conversation, creates a new entry if it doesn't exist/id is null
         */
        protected function saveConversation($conversation) {
            //If new conversation, generate ID
            if ($conversation->conversationid == null) {
                $conversation->conversationid = uniqid('');
                foreach ($conversation->contacts as $key => $contact) {
                    $conversation->contacts[$key]->conversationid = $conversation->conversationid;
                }
            }
            //Store
            $this->conversations[$conversation->conversationid] = $conversation;
            foreach ($conversation->contacts as $contact) {
                $this->saveConversationContact($contact);
            }
            
            return $this->getConversation($conversation->conversationid);
        }
        
        /**
         *  Removes a conversation
         */
        protected function deleteConversation($conversationId) {
            $this->conversations = array_diff_key($this->conversations, array($conversationId => ""));
            $this->conversationContacts = array_diff_key($this->conversationContacts, array($conversationId => ""));
            $this->messages = array_diff_key($this->messages, array($conversationId => ""));
        }
        
        
        //////////////////////
		// MESSAGE FUNCTION //
		//////////////////////
        /**
         *  Gets history for a conversation
         */
        protected function getConversationHistory($conversationId) {
            $return = array();
            if (isset($this->messages[$conversationId])) {
                foreach ($this->messages[$conversationId] as $message) {
                    $message->contact = $this->getContactDetails($message->userid);
                    $return[] = $message;
                }
            }
            return $return;
        }
        
        /**
         *  Saves a message
         */
        protected function saveMessage($message) {
            $this->messages[$message->conversationid][] = $message;
        }
        
        
        ////////////////////////////////////
		// CONVERSATION CONTACT FUNCTIONS //
		////////////////////////////////////
        /**
         *  Get a conversation contact object by user id and conversation id
         */
        protected function getConversationContact($userId, $conversationId) {
            if (isset($this->conversationContacts[$conversationId][$userId])) {
                $contact = $this->conversationContacts[$conversationId][$userId];
                $contact->details = $this->getContactDetails($userId);
                return $contact;
            }
            else return false;
        }
        
        /**
         *  Get all contacts for a conversation
         */
        protected function getConversationContacts($conversationId) {
            $return = array();
            foreach ($this->conversationContacts[$conversationId] as $userId => $contact) {
                $return[$contact->userid] = $this->getConversationContact($userId, $conversationId);
            }
            return $return;
        }
        
        /**
         *  Save a conversation contact
         */
        protected function saveConversationContact($contact) {
            $this->conversationContacts[$contact->conversationid][$contact->userid] = $contact;
        }
        
        
		//////////////////////////////
		// CONTACT DETAIL FUNCTIONS //
		//////////////////////////////
        /**
         *  Returns a contact details object by user id
         */
        protected function getContactDetails($userId) {
            //Check if details exist
            if (!isset($this->contactDetails[$userId])) {
                $data = LanWebsite_Main::getUserManager()->getUserById($userId);
                if (!$data) return false;
                $this->contactDetails[$userId] = new ContactDetails($userId, $data->getUsername(), "offline", $data->getAvatar());
            }
            return $this->contactDetails[$userId];
        }
        
        /**
         *  Set the contact's status in their details
         */
        protected function setContactStatus($userId, $status) {
            $this->contactDetails[$userId]->status = $status;
        }
        
        /**
         *  Get contact list
         */
        protected function getContactList($exclude=false) {
            $contacts = array();
            
            //No ticket mode
            if (LanWebsite_Main::getSettings()->getSetting("require_ticket_for_chat") == 0) {
                foreach ($this->users as $u) {
                    if (!isset($contacts[$u->data->getUserId()]) && (($exclude != false && $u->data->getUserId() != $exclude->data->getUserId()) || $exclude == false) ) $contacts[$u->data->getUserId()] = $this->getContactDetails($u->data->getUserId());
                }
            }
            
            //Ticket mode
            else {
                $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND activated = 1 AND assigned_forum_id > 0", LanWebsite_Main::getSettings()->getSetting("lan_number"));
                //$res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE activated = 1 AND assigned_forum_id > 0");
                while ($row = $res->fetch_assoc()) {
                    if (!isset($contacts[$row["assigned_forum_id"]]) && (($exclude != false && $row["assigned_forum_id"] != $exclude->data->getUserId()) || $exclude == false)) {
                        $contacts[$row["assigned_forum_id"]] = $this->getContactDetails($row["assigned_forum_id"]);
                    }
                }
            }
            
            return $contacts;
        }
        
		
	}
	
	class Message {
		public $time;
		public $conversationid;
        public $userid;
		public $message;
		public $contact;
		
		public function __construct($time=null, $conversationid=null, $userid=null, $contact=null, $message=null) {
			$this->time = $time;
            $this->userid = $userid;
			$this->conversationid = $conversationid;
			$this->contact = $contact;
			$this->message = $message;
		}
	}
	
	class Conversation {
		public $conversationid;
		public $contacts;
		public $history;
		
		public function __construct($conversationid=null, $contacts=array(), $history=array()) {
			$this->conversationid = $conversationid;
			$this->contacts = $contacts;
			$this->history = $history;
		}
	}
    
    class ConversationContact {
        public $userid;
        public $conversationid;
        public $open;
        public $minimised;
        public $read;
        public $details;
        
        public function __construct($userid=null, $conversationid=null, $open=null, $minimised=null, $read=null, $details=null) {
            $this->userid = $userid;
            $this->conversationid = $conversationid;
            $this->open = $open;
            $this->minimised = $minimised;
            $this->read = $read;
            $this->details = $details;
        }
    }
	
	class ContactDetails {
		public $userid;
		public $name;
		public $status;
		public $avatar;
		
		public function __construct($userid=null, $name=null, $status=null, $avatar=null) {
			$this->userid = $userid;
			$this->name = $name;
			$this->status = $status;
			$this->avatar = $avatar;
		}
	}

?>
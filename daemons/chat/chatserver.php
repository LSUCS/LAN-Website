<?php

	class ChatServer extends WebSocketServer {
		
		protected $maxBufferSize = 1048576;
		protected $userClass = 'ChatUser';

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
                    $res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE user_id = '%s' AND open = 1", $user->data->getUserId());
                    while ($conv = $res->fetch_assoc()) {
                        $c = $this->getConversation($conv["conversation_id"]);
                        $c->minimised = $conv["minimised"];
                        $c->read = $conv["read"];
                        $conversations[] = $c;
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
                    $conv = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_conversations` WHERE conversation_id = '%s'", $payload["convID"])->fetch_assoc();
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Get conversation participants, check if user is part of it
                    $participants = array();
                    $in = false;
                    $res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s'", $payload["convID"]);
                    while ($row = $res->fetch_assoc()) {
                        $participants[] = $row;
                        if ($row["user_id"] == $user->data->getUserId()) $in = true;
                    }
                    if (!$in) return $this->error($user, "User not part of conversation");
                
                    //Insert into db
                    LanWebsite_Main::getDb()->query("INSERT INTO `chat_messages` (conversation_id, user_id, message) VALUES ('%s', '%s', '%s')", $payload['convID'], $user->data->getUserId(), $payload['message']);
                    
                    //Get message object
                    $message = $this->getMessage(LanWebsite_Main::getDb()->getLink()->insert_id);
                    
                    //Loop participants, updating read status and messaging them if need be
                    foreach ($participants as $participant) {
                        //Mark as unread
                        if ($participant["user_id"] == $user->data->getUserId()) LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET `read` = 1 WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $participant["user_id"]);
                        else LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET `read` = '0' WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $participant["user_id"]);
                        
                        //If conversation is closed, open it
                        if ($participant["open"] == 0) LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET open = 1 WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $participant["user_id"]);
                                                
                        //Check for online users for participant
                        foreach ($this->users as $chatuser) {
                            if ($chatuser->data->getUserId() == $participant["user_id"]) {
                                if ($participant["open"] == 0) {
                                    $this->sendCommand($chatuser, "openconversation", $this->getConversation($payload["convID"], $chatuser));
                                    $participant["open"] = 1;
                                }
                                $this->sendCommand($chatuser, "sendmessage", $message);
                                break;
                            }
                        }
                    }
                    
					break;
					
				//OPENCONVERSATION - Opens a conversation with a user
				//params - { userID: }
				//return - JSON object of conversation
				case "openconversation":
				
					//Validate user id
					if (!isset($payload["userid"]) || $payload["userid"] == $user->data->getUserId()) return $this->error($user, "Invalid user id for conversation");
					/*$ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND assigned_forum_id = '%s' AND activated = 1", LanWebsite_Main::getSettings()->getSetting("lan_number"), $user->data->getUserId())->fetch_assoc();
					if (!$ticket) return $this->error($user, "Cannot open conversation with user not at LAN");*/
				
					//Check if a conversation already exists between these two users exclusively and return it if it does
					$res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE user_id = '%s'", $user->data->getUserId());
					while ($row = $res->fetch_assoc()) {
						$res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s'", $row["conversation_id"]);
                        if (mysqli_num_rows($res) > 2) continue;
                        $exists = false;
                        while ($row2 = $res->fetch_assoc()) if ($row2["user_id"] == $payload["userid"]) $exists = true;
						if ($exists) {
                            LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET open = 1 WHERE conversation_id = '%s' AND user_id = '%s'", $row["conversation_id"], $user->data->getUserId());
							$this->sendCommand($user, "openconversation", $this->getConversation($row["conversation_id"], $user));
							return;
						}
					}
					
					//Create conversation
					LanWebsite_Main::getDb()->query("INSERT INTO `chat_conversations` () VALUES ()");
					$convid = LanWebsite_Main::getDb()->getLink()->insert_id;
					LanWebsite_Main::getDb()->query("INSERT INTO `chat_users` (conversation_id, user_id, open, minimised, `read`) VALUES ('%s', '%s', 1, 0, 1)", $convid, $user->data->getUserId());
					LanWebsite_Main::getDb()->query("INSERT INTO `chat_users` (conversation_id, user_id, open, minimised, `read`) VALUES ('%s', '%s', 0, 0, 1)", $convid, $payload["userid"]);
					
					//Reciprocate open conversation
					$this->sendCommand($user, "openconversation", $this->getConversation($convid, $user));
					
					break;
					
				//CLOSECONVERSATION - Marks a conversation as closed (hidden) for the user
				//params - { convID: }
				//return - null
				case "closeconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_conversations` WHERE conversation_id = '%s'", $payload["convID"])->fetch_assoc();
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId())->fetch_assoc();
                    if (!$res) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as closed
                    LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET open = 0 WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId());
                    
					break;
					
				//MINIMISECONVERSATION - Marks a conversation as minimised for the user
				//params - { convID: }
				//return - null
				case "minimiseconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_conversations` WHERE conversation_id = '%s'", $payload["convID"])->fetch_assoc();
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId())->fetch_assoc();
                    if (!$res) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as minimised
                    LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET minimised = 1 WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId());
                    
					break;
                    
				//MAXIMISECONVERSATION - Marks a conversation as maximised for the user
				//params - { convID: }
				//return - null
				case "maximiseconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_conversations` WHERE conversation_id = '%s'", $payload["convID"])->fetch_assoc();
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId())->fetch_assoc();
                    if (!$res) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as maximimised
                    LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET minimised = 0 WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId());
                    
					break;
                    
				//READCONVERSATION - Marks a conversation as read for the user
				//params - { convID: }
				//return - null
				case "readconversation":
                
                    //Check if conv id is valid
                    if (!isset($payload["convID"])) return $this->error($user, "Conversation ID not provided");
                    $conv = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_conversations` WHERE conversation_id = '%s'", $payload["convID"])->fetch_assoc();
                    if (!$conv) return $this->error($user, "Invalid conversation ID");
                    
                    //Check if user is part of it
                    $res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId())->fetch_assoc();
                    if (!$res) return $this->error($user, "User is not part of conversation, cannot close");
                    
                    //Mark as read
                    LanWebsite_Main::getDb()->query("UPDATE `chat_users` SET `read` = 1 WHERE conversation_id = '%s' AND user_id = '%s'", $payload["convID"], $user->data->getUserId());
                    
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
			
			//Update presence to other sockets, excluding current user
			$this->updateContactLists($user);
			
		}
		
		/**
		 * Shuts down the connection
		 */
		protected function closed($user) {
			$this->stdout("Connection " . $user->id . " closed by user");
			//Update presence to other sockets
			$this->updateContactLists();
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
		
		
		
		///////////////////////
		// UTILITY FUNCTIONS //
		///////////////////////
        
        /**
         *  Updates contact list on all sockets
         */
        private function updateContactLists($excludeUser = false) {
            foreach ($this->users as $user) {
                if (($excludeUser != false && $user->data->getUserId() != $excludeUser->data->getUserId()) || $excludeUser == false) {
                    $this->sendCommand($user, "updatecontactlist", $this->getContactList($user));
                }
            }
        }
		
		/**
		 *	Returns a conversation object
		 */
		private function getConversation($conversationid, $excludeUser=false) {
		
			//Check convo exists
			$conv = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_conversations` WHERE conversation_id = '%s'", $conversationid)->fetch_assoc();
			if (!$conv) return false;
			
			//New object
			$conversation = new Conversation($conversationid);
			
			//Load contacts
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s'", $conversationid);
			while ($row = $res->fetch_assoc()) {
				if (($excludeUser != false && $row["user_id"] != $excludeUser->data->getUserId()) || $excludeUser == false) {
					$conversation->contacts[] = $this->getContact($row["user_id"]);
				}
			}
			
			//Load history
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_messages` WHERE conversation_id = '%s' ORDER BY time desc LIMIT 0,10", $conversationid);
			while ($row = $res->fetch_assoc()) {
				$conversation->history[] = $this->getMessage($row["message_id"]);
			}
            $conversation->history = array_reverse($conversation->history);
			
			return $conversation;
		}
		
		/**
		 *	Returns a message object
		 */
		private function getMessage($messageid) {
			$msg = LanWebsite_Main::getDb()->query("SELECT * FROM `chat_messages` WHERE message_id = '%s'", $messageid)->fetch_assoc();
			return new Message($messageid, $msg["time"], $msg["conversation_id"], $this->getContact($msg["user_id"]), $msg["message"]);
		}
		
		/**
		 *	Returns a contact object
		 */
		private function getContact($userid) {
			$user = LanWebsite_Main::getUserManager()->getUserById($userid);
			$contact = new Contact($userid, $user->getUsername(), "offline", $user->getAvatar());
			foreach ($this->users as $user) {
				if ($user->data->getUserId() == $userid) $contact->status = "online";
			}
			return $contact;
		}
		
		/**
		 * Returns contact list
		 */
		private function getContactList($excludeUser = false) {
			
			$online = array();
			$ingame = array();
			$offline = array();
            
            $ids = array();
			
			//Get activated tickets for LAN
			/*$res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND activated = 1 AND assigned_forum_id > 0", LanWebsite_Main::getSettings()->getSetting("lan_number"));
			while ($row = $res->fetch_assoc()) {
				if (($excludeUser != false && $row["assigned_forum_id"] != $excludeUser->data->getUserId()) || $excludeUser == false) {
					$contact = $this->getContact($row["assigned_forum_id"]);
					switch ($contact->status) {
						case "online": $online[] = $contact; break;
						case "in-game": $ingame[] = $contact; break;
						default: $offline[] = $contact; break;
					}
				}
			}*/
            
            foreach ($this->users as $user) {
                if ((($excludeUser != false && $user->data->getUserId() != $excludeUser->data->getUserId()) || $excludeUser == false) && !in_array($user->data->getUserId(), $ids)) {
					$contact = $this->getContact($user->data->getUserId());
                    $ids[] = $user->data->getUserId();
					switch ($contact->status) {
						case "online": $online[] = $contact; break;
						case "in-game": $ingame[] = $contact; break;
						default: $offline[] = $contact; break;
					}
                }
            }
			
			//Sort
			usort($online, function ($a, $b) { if ($a->name == $b->name) return 0; else return ($a->name > $b->name) ? +1:-1; });
			usort($ingame, function ($a, $b) { if ($a->name == $b->name) return 0; else return ($a->name > $b->name) ? +1:-1; });
			usort($offline, function ($a, $b) { if ($a->name == $b->name) return 0; else return ($a->name > $b->name) ? +1:-1; });
			
			return array_merge($ingame, $online, $offline);
			
		}
		
	}
	
	class Message {
		public $messageid;
		public $time;
		public $conversationid;
		public $contact;
		public $message;
		
		public function __construct($messageid=null, $time=null, $conversationid=null, $contact=null, $message=null) {
			$this->messageid = $messageid;
			$this->time = $time;
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
	
	class Contact {
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
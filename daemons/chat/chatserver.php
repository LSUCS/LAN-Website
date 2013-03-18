<?php

	class ChatServer extends WebSocketServer {
		
		protected $maxBufferSize = 1048576;
		protected $userClass = 'LanChatUser';

		//////////////////////////
		// CONNECTION FUNCTIONS //
		//////////////////////////
		/**
		 * Handles incoming protocol messages
		 */
		protected function process($user, $message) {
		
			global $_MAIN;
			
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
					$this->sendCommand($user, "init", array("contacts" => $this->getContactList(), "conversations" => ""));
					break;
				
				//SENDMESSAGE - Sends a message to a conversation
				//params - { convID: , message: }
				//return - null
				case "sendmessage":
					break;
					
				//OPENCONVERSATION - Opens a conversation with a user
				//params - { userID: }
				//return - JSON object of conversation
				case "openconversation":
				
					//Validate user id
					if (!isset($payload["userid"]) || $payload["userid"] == $user->userID) return $this->error($user, "Invalid user id for conversation");
					$ticket = $_MAIN->db->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND assigned_forum_id = '%s' AND activated = 1", $_MAIN->settings->getSetting("lan_number"), $user->userID)->fetch_assoc();
					if (!$ticket) return $this->error($user, "Cannot open conversation with user not at LAN");
				
					//Check if a conversation already exists between these two users and return it if it does
					$res = $_MAIN->db->query("SELECT * FROM `chat_users` WHERE user_id = '%s'", $user->userID);
					while ($row = $res->fetch_assoc()) {
						$exists = $_MAIN->db->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s' AND user_id = '%s'", $row["conversation_id"], $payload["userid"])->fetch_assoc();
						if ($exists) {
							$this->sendCommand($user, "openconversation", $this->getConversation($row["conversation_id"], $user));
							return;
						}
					}
					
					//Create conversation
					$_MAIN->db->query("INSERT INTO `chat_conversations` (public, addable) VALUES (0, 1)");
					$convid = $_MAIN->db->getLink()->insert_id;
					$_MAIN->db->query("INSERT INTO `chat_users` (conversation_id, user_id, open, minimised) VALUES ('%s', '%s', 1, 0)", $convid, $user->userID);
					$_MAIN->db->query("INSERT INTO `chat_users` (conversation_id, user_id, open, minimised) VALUES ('%s', '%s', 0, 0)", $convid, $payload["userid"]);
					
					//Reciprocate open conversation
					$this->sendCommand($user, "openconversation", $this->getConversation($convid, $user));
					
					break;
					
				//CLOSECONVERSATION - Marks a conversation as closed (hidden) for the user
				//params - { conversationID: }
				//return - null
				case "closeconversation":
					break;
					
				//MINIMISECONVERSATION - Marks a conversation as minimised for the user
				//params - { conversationID: }
				//return - null
				case "minimiseconversation":
					break;
					
				//JOINCONVERSATION - Join a conversation (only if conversation is marked as public)
				//params - { conversationID: }
				//return - JSON object of conversation
				case "joinconversation":
					break;
					
				//LEAVECONVERSATION - Removes the user from the conversation
				//params - { conversationID: }
				//return - null
				case "leaveconversation":
					break;
					
				//ADDTOCONVERSATION - Adds a user to a conversation (only if conversation is marked as addable)
				//params - { conversationID: , userID }
				//return - null
				case "addtoconversation":
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
			global $_MAIN;
			
			//Handle cookie and get user ID
			$cookies = explode("; ", $user->headers['cookie']);
			$_COOKIE = array();
			foreach ($cookies as $cookie) {
				$cookie = explode("=", $cookie);
				$_COOKIE[$cookie[0]] = $cookie[1];
			}
			print_r($_COOKIE);
			$user->userID = $_MAIN->auth->getIdFromSession();
			
			//If user is not logged in, fatal error
			if ($user->userID == null) return $this->fatalError($user, "Must be logged in to use chat");
			$userdata = $_MAIN->auth->getUserById($user->userID);
			
			//Store userID
			$user->userID = $userdata["xenforo"]["user_id"];
			
			$this->stdout("Authorised connection " . $user->id . " for " . $userdata["xenforo"]["username"]);
			
			//Update presence to other sockets
			$this->allSendCommand($user, "updatecontactlist", $this->getContactList());
			
		}
		
		/**
		 * Shuts down the connection
		 */
		protected function closed($user) {
			$this->stdout("Connection " . $user->id . " closed by user");
			//Update presence to other sockets
			$this->allSendCommand($user, "updatecontactlist", $this->getContactList());
		}
		
		
		
		//////////////////////
		//  SEND FUNCTIONS  //
		//////////////////////
		/**
		 *	Sends a command to all sockets
		 */
		protected function allSendCommand($userExclude = null, $command, $payload) {
			$this->multiSendCommand(array_map(function ($o) { return $o->userID; }, $this->users), $userExclude, $command, $payload);
		}
		
		/**
		 * Sends a command to all sockets with a userID in $userIDs, excluding the optional $user
		 * Returns an array of IDs that don't have sockets so can't be sent
		 */
		protected function multiSendCommand($userIDs, $userExclude = null, $command, $payload) {
	
			$sent = array();
			
			foreach ($this->users as $user) {
				if (in_array($user->userID, $userIDs) && $userExclude != $user) {
					$this->sendCommand($user, $command, $payload);
					if (!in_array($user->userID, $sent)) $sent[] = $user->userID;
				} else if ($userExclude == $user && !in_array($user->userID, $sent)) {
					$sent[] = $user->userID;
				}
			}
			
			return array_diff($userIDs, $sent);
		}
		
		/**
		 * Sends a command to a socket
		 */
		protected function sendCommand($user, $command, $payload) {
			$this->stdout("SEND - " . $command . " - TO - " . $user->userID . " " . $user->id);
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
		 *	Returns a conversation object
		 */
		private function getConversation($conversationid, $excludeUser=false) {
			global $_MAIN;
		
			//Check convo exists
			$conv = $_MAIN->db->query("SELECT * FROM `chat_conversations` WHERE conversation_id = '%s'", $conversationid)->fetch_assoc();
			if (!$conv) return false;
			
			//New object
			$conversation = new Conversation($conversationid);
			
			//Load contacts
			$res = $_MAIN->db->query("SELECT * FROM `chat_users` WHERE conversation_id = '%s'", $conversationid);
			while ($row = $res->fetch_assoc()) {
				if (($excludeUser != false && $row["user_id"] != $excludeUser->userID) || $excludeUser == false) {
					$conversation->contacts[] = $this->getContact($row["user_id"]);
				}
			}
			
			//Load history
			$res = $_MAIN->db->query("SELECT * FROM `chat_messages` WHERE conversation_id = '%s' ORDER BY time asc LIMIT 0,10", $conversationid);
			while ($row = $res->fetch_assoc()) {
				$conversation->history[] = $this->getMessage($row["message_id"]);
			}
			
			return $conversation;
		}
		
		/**
		 *	Returns a message object
		 */
		private function getMessage($messageid) {
			global $_MAIN;
			
			$msg = $_MAIN->db->query("SELECT * FROM `chat_messages` WHRE message_id = '%s'", $messageid)->fetch_assoc();
			return new Message($messageid, $msg["time"], $msg["conversationid"], $this->getContact($msg["user_id"]), $msg["message"]);
		}
		
		/**
		 *	Returns a contact object
		 */
		private function getContact($userid) {
			global $_MAIN;
			$userdata = $_MAIN->auth->getUserById($userid);
			$contact = new Contact($userid, $userdata["xenforo"]["username"], "offline", $_MAIN->auth->getAvatarById($userid));
			foreach ($this->users as $user) {
				if ($user->userID == $userid) $contact->status = "online";
			}
			return $contact;
		}
		
		/**
		 * Returns contact list
		 */
		private function getContactList($excludeUser = false) {
		
			global $_MAIN;
			
			$online = array();
			$ingame = array();
			$offline = array();
			
			//Get activated tickets for LAN
			$res = $_MAIN->db->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND activated = 1 AND assigned_forum_id > 0", $_MAIN->settings->getSetting("lan_number"));
			while ($row = $res->fetch_assoc()) {
				if (($excludeUser != false && $row["assigned_forum_id"] != $excludeUser->userID) || $excludeUser == false) {
					$contact = $this->getContact($row["assigned_forum_id"]);
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
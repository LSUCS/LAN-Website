<?php

	class LobbyServer extends WebSocketServer {
		
		protected $maxBufferSize = 1048576;
		protected $userClass = 'LobbyUser';
        
        private $lobbies;
        private $lobbyPasswords;
        private $contacts;

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
			
				//INIT - Returns all initial lobby information for user
				//params - { }
				//return - JSON object of active lobby, list of lobbies (empty if active lobby) and global chat history
				case "init":
                    $return = array("activelobby" => null, "lobbies" => array(), "globalchat" => array());
                    
                    //Active lobby
                    $return["activelobby"] = $this->getActiveLobby($user->data->getUserId());
                    
                    //Lobby list if active lobby is null
                    if ($return["activelobby"] == null) {
                        $return["lobbies"] = $this->getLobbyList();
                    }
                    
                    //Global chat history
                    $return["globalchat"] = $this->getGlobalChatHistory();
                    
					$this->sendCommand($user, "init", $return);
					break;
                    
                    
                //CREATELOBBY - Create a new lobby
				//params - { title:, game:, icon:, maxplayers:, password:, description: }
                case "createlobby":
                
                    //Validate
                    $error = false;
                    if (!isset($payload["title"]) || strlen(trim($payload["title"])) == 0) $error = "Invalid title, must be at least 1 character in length";
                    if (!isset($payload["game"]) || strlen(trim($payload["game"])) == 0) $error = "Invalid game, must be at least 1 character in length";
                    if (isset($payload["password"]) && strlen(trim($payload["password"])) < 3 && strlen(trim($payload["password"])) > 0) $error = "Invalid password - must be at least 3 characters in length";
                    if ($error) {
                        return $this->sendCommand($user, "createlobby", array("error" => $error));
                    }
                    
                    //Prepare values
                    if (!isset($payload["icon"]) || strlen(trim($payload["icon"])) == 0) $icon = "derp";
                    else $icon = $payload["icon"];
                    if (!isset($payload["maxplayers"]) || $payload["maxplayers"] < 2) $playerlimit = 0;
                    else $playerlimit = $payload["maxplayers"];
                    if (isset($payload["password"]) && strlen(trim($payload["password"])) > 3) {
                        $locked = true;
                        $password = $payload["password"];
                    } else {
                        $locked = false;
                        $password = null;
                    }
                    if (isset($payload["description"])) $description = $payload["description"];
                    else $description = "";
                    
                    //Create lobby object and save
                    $lobby = new Lobby(null, array($this->getContact($user->data->getUserId())), array(), $payload["game"], $icon, $playerlimit, $locked, $description, $this->getContact($user->data->getUserId()));
                    $lobby = $this->saveLobby($lobby, $password);
                    
                    //Set contact lobby
                    $this->setContactLobby($user->data->getUserId(), $lobby->lobbyid);
                    
                    //Reciprocate empty create lobby to signify success
                    $this->sendCommand($user, "createlobby", array());
                    
                    //Send join lobby
                    $this->sendCommand($user, "joinlobby", $lobby);
                    
                    //Update lobbies to relevant users
                    $this->sendLobbyUpdate($lobby);
                
                    break;
                   
                   
                //EDITLOBBY - Create a new lobby
				//params - { lobbyID:, title:, game:, icon:, maxplayers:, password:, description: }
                case "editlobby":
                    break;
                    
                    
				//JOINLOBBY - Join specified lobby ID
				//params - { lobbyID: }
                case "joinlobby":
                    break;
                    
                    
				//LEAVELOBBY - Leave lobby user is in
				//params - { }
                case "leavelobby":
                    break;
                    
                    
				//SENDLOBBYCHAT - Send message to user's lobby
				//params - { message: }
                case "sendlobbychat":
                    break;
                    
                    
				//SENDGLOBALCHAT - Send message to global chat
				//params - { lobbyID: }
                case "sendglobalchat":
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
			if (!LanWebsite_Main::getAuth()->isLoggedIn()) return $this->fatalError($user, "Must be logged in to use lobby server");
			$user->data = LanWebsite_Main::getUserManager()->getActiveUser();
			
			$this->stdout("Authorised connection " . $user->data->getUserId() . " for " . $user->data->getUsername());
			
		}
		
		/**
		 * Shuts down the connection
		 */
		protected function closed($user) {
			$this->stdout("Connection " . $user->id . " closed by user");
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
		
		
		/////////////////////
		// LOBBY FUNCTIONS //
		/////////////////////
        /**
         *  Send lobby update to all sockets that aren't in a lobby or are in the specified lobby
         */
        protected function sendLobbyUpdate($lobby) {
            foreach ($this->users as $user) {
                $contact = $this->getContact($user->data->getUserId());
                if ($contact->activelobbyid == null || $contact->activelobbyid == $lobby->lobbyid) {
                    $this->sendCommand($user, "updatelobby", $lobby);
                }
            }
        }
        
        /**
         *  Returns active lobby for user if it exists, null if not
         */
		protected function getActiveLobby($userId) {
            $contact = $this->getContact($userId);
            return $this->getLobby($contact->activelobbyid);
        }
        
        /**
         *  Returns a lobby by its ID
         */
        protected function getLobby($lobbyId) {
            if (isset($this->lobbies[$lobbyId])) return $this->lobbies[$lobbyId];
            else return false;
        }
        
        /**
         *  Save a lobby object, creates a new entry if it doesn't exist/id is null
         */
        protected function saveLobby($lobby, $password=null) {
            //If new lobby, generate ID
            if ($lobby->lobbyid == null) {
                $lobby->lobbyid = uniqid('', true);
            }
            //Store
            $this->lobbies[$lobby->lobbyid] = $lobby;
            //Store password if applicable
            if ($password != null) $this->lobbyPasswords = sha2($password);
            
            return $lobby;
        }
        
        /**
         *  Returns array of lobbies
         */
        protected function getLobbyList() {
            return $this->lobbies;
        }
        
        
		///////////////////////
		// CONTACT FUNCTIONS //
		///////////////////////
        /**
         *  Returns a contact object from user id
         */
        protected function getContact($userId) {
            if (!isset($this->contacts[$userId])) {
                $data = LanWebsite_Main::getUserManager()->getUserById($userId);
                $this->contacts[$userId] = new Contact($userId, $data->getUsername(), $data->getAvatar(), $data->getSteam(), null);
            }
            return $this->contacts[$userId];
        }
        
        /**
         *  Sets the active lobby of a contact
         */
        protected function setContactLobby($userId, $lobbyId) {
            $contact = $this->getContact($userId);
            $contact->activelobbyid = $lobbyId;
            $this->contacts[$userId] = $contact;
        }
        
        
        ///////////////////////////
		// GLOBAL CHAT FUNCTIONS //
		///////////////////////////
        /**
         *  Gets the global chat history
         */
        protected function getGlobalChatHistory() {
        
        }
        
		
	}
	
	class Message {
		public $time;
		public $contact;
		public $message;
		
		public function __construct($time=null, $contact=null, $message=null) {
			$this->time = $time;
			$this->contact = $contact;
			$this->message = $message;
		}
	}
	
	class Lobby {
		public $lobbyid;
		public $contacts;
		public $history;
        public $game;
        public $icon;
        public $playerlimit;
        public $locked;
        public $description;
        public $leader;
		
		public function __construct($lobbyid=null, $contacts=array(), $history=array(), $game=null, $icon=null, $playerlimit=null, $locked=null, $description=null, $leader=null) {
			$this->lobbyid = $lobbyid;
			$this->contacts = $contacts;
			$this->history = $history;
            $this->game = $game;
            $this->icon = $icon;
            $this->playerlimit = $playerlimit;
            $this->locked = $locked;
            $this->description = $description;
            $this->leader = $leader;
		}
	}
	
	class Contact {
		public $userid;
		public $name;
		public $avatar;
        public $steam;
        public $activelobbyid;
		
		public function __construct($userid=null, $name=null, $avatar=null, $steam=null, $activelobbyid=null) {
			$this->userid = $userid;
			$this->name = $name;
			$this->avatar = $avatar;
            $this->steam = $steam;
            $this->activelobbyid = $activelobbyid;
		}
	}

?>
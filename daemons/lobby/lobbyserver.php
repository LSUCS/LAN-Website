<?php

	class LobbyServer extends WebSocketServer {
		
		protected $userClass = 'LobbyUser';
        
        private $lobbies = array();
        private $lobbyPasswords = array();
        private $contacts;
        private $globalHistory = array();

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
                    
                    //User id
                    $return["contact"] = $this->getContact($user->data->getUserId());
                    
                    //Global chat history
                    $return["globalchat"] = $this->globalHistory;
                    
					$this->sendCommand($user, "init", $return);
					break;
                    
                    
                //CREATELOBBY - Create a new lobby
				//params - { title:, game:, icon:, maxplayers:, password:, description:, steam: }
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
                    if (!isset($payload["icon"]) || strlen(trim($payload["icon"])) == 0) $icon = "/images/no-game.png";
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
                    if (!isset($payload["steam"]) || $payload["steam"] == 0) $steam = false;
                    else $steam = true;
                    
                    //Check icon
                    $ch = curl_init($icon);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    curl_exec($ch);
                    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    $this->stdout($retcode . " hi");
                    if ($retcode != 200) $icon = "/images/no-game.png";
                    
                    //Create lobby object and save
                    $lobby = new Lobby(null, array($this->getContact($user->data->getUserId())), array(), $payload["title"], $payload["game"], $icon, $playerlimit, $locked, $description, $this->getContact($user->data->getUserId()), $steam);
                    $lobby = $this->saveLobby($lobby);
                    $this->setLobbyPassword($lobby->lobbyid, $password);
                    
                    //Set contact lobby
                    $this->setContactLobby($user->data->getUserId(), $lobby->lobbyid);
                    
                    //Reciprocate empty create lobby to signify success
                    $this->sendCommand($user, "createlobby", array());
                    
                    //Send join lobby to all sockets for user
                    foreach ($this->users as $u) {
                        if ($u->data->getUserId() == $user->data->getUserId()) {
                            $this->sendCommand($u, "joinlobby", $lobby);
                        }
                    }
                    
                    //Update lobbies to relevant users
                    $this->sendLobbyUpdate($lobby);
                    
                    //Send notification
                    $notification = new Notification('<span style="color: #333;"><span style="color: red;">>>></span> New lobby: <span style="color: #FFCC66;">' . $lobby->title . '</span> for <span style="color: #FFCC66;">' . $lobby->game . '</span> created by <span class="lanwebsite-contact" style="color: ' . $lobby->leader->color . ';" value="' . $lobby->leader->userid . '">' . $lobby->leader->name . '</i></span>');
                    $this->sendGlobalNotification($notification);
                    
                Logger::log("createlobby", json_encode(array("userid" => $user->data->getUserId(), "lobby" => $lobby->title, "game" => $payload["game"], "locked" => $locked)));
                Logger::store();
                    
                    break;
                   
                   
                //EDITLOBBY - Create a new lobby
				//params - { lobbyID:, title:, game:, icon:, maxplayers:, password:, description:, steam: }
                case "editlobby":
                
                    //Validate
                    $error = false;
                    if (!isset($payload["lobbyID"]) || !$this->getLobby($payload["lobbyID"])) $error = "Invalid lobby ID";
                    if (!isset($payload["title"]) || strlen(trim($payload["title"])) == 0) $error = "Invalid title, must be at least 1 character in length";
                    if (!isset($payload["game"]) || strlen(trim($payload["game"])) == 0) $error = "Invalid game, must be at least 1 character in length";
                    if (isset($payload["password"]) && strlen(trim($payload["password"])) < 3 && strlen(trim($payload["password"])) > 0) $error = "Invalid password - must be at least 3 characters in length";
                    if ($error) {
                        return $this->sendCommand($user, "createlobby", array("error" => $error));
                    }
                    
                    //Prepare values
                    if (!isset($payload["icon"]) || strlen(trim($payload["icon"])) == 0) $icon = "/images/no-game.png";
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
                    if (!isset($payload["steam"]) || $payload["steam"] == 0) $steam = false;
                    else $steam = true;
                    
                    //Get old lobby object, check leader and playerlimit
                    $oldLobby = $this->getLobby($payload["lobbyID"]);
                    $contact = $this->getContact($user->data->getUserId());
                    if ($contact->userid != $oldLobby->leader->userid) return $this->sendCommand($user, "createlobby", array("error" => "Only leader can edit the lobby"));
                    if ($playerlimit > 0 && count($oldLobby->contacts) > $playerlimit) return $this->sendCommand($user, "createlobby", array("error" => "Cannot set player limit to less than current players"));
                    
                    //Check icon
                    $ch = curl_init($icon);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    curl_exec($ch);
                    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($retcode == 400) $icon = "/images/no-game.png";
                    
                    //Create lobby object and save
                    $lobby = new Lobby($payload["lobbyID"], $oldLobby->contacts, $oldLobby->history, $payload["title"], $payload["game"], $icon, $playerlimit, $locked, $description, $contact, $steam);
                    $lobby = $this->saveLobby($lobby);
                    $this->setLobbyPassword($lobby->lobbyid, $password);
                    
                    //Reciprocate empty create lobby to signify success
                    $this->sendCommand($user, "createlobby", array());
                    
                    //Update lobby to relevant users
                    $this->sendLobbyUpdate($lobby);
                
                    break;
                    
                    
				//JOINLOBBY - Join specified lobby ID
				//params - { lobbyID:, password: }
                case "joinlobby":
                
                    //Validate
                    $error = false;
                    if (!isset($payload["lobbyID"]) || !$this->getLobby($payload["lobbyID"])) $error = "Invalid lobby ID";
                    $lobby = $this->getLobby($payload["lobbyID"]);
                    if ($lobby->locked == 1 && (!isset($payload["password"]) || $payload["password"] != $this->getLobbyPassword($lobby->lobbyid))) $error = "Invalid password";
                    if ($lobby->playerlimit > 0 && count($lobby->contacts) == $lobby->playerlimit) $error = "Lobby is full";
                    $contact = $this->getContact($user->data->getUserId());
                    if ($contact->activelobbyid != null) $error = "Contact already in lobby";
                    if ($error) {
                        return $this->sendCommand($user, "joinlobby", array("error" => $error));
                    }
                    
                    //Update contact and lobby
                    $contact->activelobbyid = $lobby->lobbyid;
                    $lobby->contacts[] = $contact;
                    $this->saveLobby($lobby);
                    $this->setContactLobby($contact->userid, $lobby->lobbyid);
                    
                    //Reciprocate join lobby command to all sockets for user
                    foreach ($this->users as $u) {
                        if ($u->data->getUserId() == $user->data->getUserId()) {
                            $this->sendCommand($u, "joinlobby", $lobby);
                        }
                    }
                    
                    //Update lobby to relevant users
                    $this->sendLobbyUpdate($lobby);
                    
                Logger::log("joinlobby", json_encode(array("userid" => $user->data->getUserId(), "lobby" => $lobby->title, "game" => $lobby->game, "locked" => $lobby->locked, "playercount" => count($lobby->contacts))));
                Logger::store();
                
                    break;
                    
                    
				//LEAVELOBBY - Leave lobby user is in
				//params - { }
                case "leavelobby":
                
                    //Check if in lobby
                    $contact = $this->getContact($user->data->getUserId());
                    if ($contact->activelobbyid == null) return $this->error($user, "Not in lobby, unable to leave");
                    
                    //Get lobby
                    $lobby = $this->getLobby($contact->activelobbyid);
                    
                    //Check if contact is only member of lobby, if so remove entire lobby
                    if (count($lobby->contacts) == 1) {
                        $this->deleteLobby($lobby->lobbyid);
                        $this->sendLobbyDelete($lobby);
                    }
                    //Otherwise remove from lobby
                    else {
                        
                        //Remove from contacts
                        foreach ($lobby->contacts as $key => $c) {
                            if ($c->userid == $contact->userid) {
                                $lobby->contacts = array_diff($lobby->contacts, array($c));
                                break;
                            }
                        }
                        
                        //If contact is leader of lobby, set next contact to be leader and update
                        if ($lobby->leader->userid == $contact->userid) {
                            $lobby->leader = reset($lobby->contacts);
                        }
                            
                        //Save lobby
                        $this->saveLobby($lobby);
                        
                        //Reciprocate leave lobby
                        $this->sendCommand($user, "leavelobby", array());
                        
                        //Send lobby updates
                        $this->sendLobbyUpdate($lobby);
                    }
                    
                    //Update contact status
                    $this->setContactLobby($contact->userid, null);
                    
                    break;
                    
                    
				//SENDLOBBYCHAT - Send message to user's lobby
				//params - { message: }
                case "sendlobbychat":
                
                    //Check message
                    if (!isset($payload["message"]) || strlen(trim($payload["message"])) == 0) return $this->error($user, "No message provided");
                
                    //Get contact and check for active lobby
                    $contact = $this->getContact($user->data->getUserId());
                    if ($contact->activelobbyid == null) return $this->error($user, "Unable to send chat message, user not in lobby");
                    
                    $lobby = $this->getLobby($contact->activelobbyid);
                    
                    //Form message
                    $msg = strip_tags(trim($payload["message"]));
                    if (strlen($msg) > LanWebsite_Main::getSettings()->getSetting("lobby_message_max_length")) $msg = substr($msg, 0, LanWebsite_Main::getSettings()->getSetting("lobby_message_max_length"));
                    $msg = preg_replace("/([^\w\/])(www\.[a-z0-9\-]+\.[a-z0-9\-]+)/i", "$1http://$2", $msg);
                    $msg = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a target=\"_blank\" href=\"$1\">$1</A>", $msg);
                    $message = new Message(time(), $contact, $msg);
                    
                    //Send message out to sockets in lobby
                    foreach ($this->users as $u) {
                        if ($this->getContact($u->data->getUserId())->activelobbyid == $lobby->lobbyid) {
                            $this->sendCommand($u, "sendlobbychat", $message);
                        }
                    }
                    
                    //Save the message to the lobby
                    $max = LanWebsite_Main::getSettings()->getSetting("lobby_history_length");
                    $lobby->history[] = $message;
                    if (count($lobby->history) > $max) {
                        $lobby->history = array_values(array_slice($lobby->history, -1 * $max));
                    }
                    $this->saveLobby($lobby);
                    
                Logger::log("lobbychat", json_encode(array("userid" => $user->data->getUserId(), "lobby" => $lobby->title, "message" => $message->message)));
                Logger::store();
                    
                    break;
                    
                    
				//SENDGLOBALCHAT - Send message to global chat
				//params - { lobbyID: }
                case "sendglobalchat":
                
                    //Check message
                    if (!isset($payload["message"]) || strlen(trim($payload["message"])) == 0) return $this->error($user, "No message provided");
                    
                    //Get contact
                    $contact = $this->getContact($user->data->getUserId());
                   
                    //Form message
                    $msg = strip_tags(trim($payload["message"]));
                    if (strlen($msg) > LanWebsite_Main::getSettings()->getSetting("lobby_message_max_length")) $msg = substr($msg, 0, LanWebsite_Main::getSettings()->getSetting("lobby_message_max_length"));
                    $msg = preg_replace("/([^\w\/])(www\.[a-z0-9\-]+\.[a-z0-9\-]+)/i", "$1http://$2", $msg);
                    $msg = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a target=\"_blank\" href=\"$1\">$1</A>", $msg);
                    $message = new Message(time(), $contact, $msg);
                    
                    //Send message out to sockets in lobby
                    foreach ($this->users as $u) {
                        $this->sendCommand($u, "sendglobalchat", $message);
                    }
                    
                    //Add to queue
                    $this->addGlobalHistory($message);
                    
                Logger::log("globalchat", json_encode(array("userid" => $user->data->getUserId(), "message" => $message->message)));
                Logger::store();
                    
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
        
        
        //////////////////////
        // GLOBAL FUNCTIONS //
        //////////////////////
        /**
         *  Send global notification
         */
        protected function sendGlobalNotification($notification) {
            $this->addGlobalHistory($notification);
            foreach ($this->users as $user) {
                $this->sendCommand($user, "sendglobalnotification", $notification);
            }
        }
        protected function addGlobalHistory($elem) {
            $max = LanWebsite_Main::getSettings()->getSetting("lobby_history_length");
            $this->globalHistory[] = $elem;
            if (count($this->globalHistory) > $max) {
                $this->globalHistory = array_values(array_slice($this->globalHistory, -1 * $max));
            }
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
         *  Sends a lobby delete to all sockets that aren't in a lobby and a lobby leave to all sockets in the specified lobby
         */
        protected function sendLobbyDelete($lobby) {
            foreach ($this->users as $user) {
                $contact = $this->getContact($user->data->getUserId());
                if ($contact->activelobbyid == null) {
                    $this->sendCommand($user, "deletelobby", $lobby);
                } else if ($contact->activelobbyid == $lobby->lobbyid) {
                    $this->sendCommand($user, "leavelobby", array());
                }
            }
        }
        
        /**
         *  Removes a lobby
         */
        protected function deleteLobby($lobbyId) {
            $this->lobbies = array_diff_key($this->lobbies, array($lobbyId => ""));
            $this->lobbyPasswords = array_diff_key($this->lobbyPasswords, array($lobbyId => ""));
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
         *  Returns a lobby password
         */
        protected function getLobbyPassword($lobbyId) {
            if (isset($this->lobbyPasswords[$lobbyId])) return $this->lobbyPasswords[$lobbyId];
            else return null;
        }
        
        /**
         *  Save a lobby object, creates a new entry if it doesn't exist/id is null
         */
        protected function saveLobby($lobby) {
            //If new lobby, generate ID
            if ($lobby->lobbyid == null) {
                $lobby->lobbyid = uniqid('');
            }
            //Store
            $this->lobbies[$lobby->lobbyid] = $lobby;
            
            return $lobby;
        }
        
        /**
         *  Set the password for a lobby, null or false = no password
         */
        protected function setLobbyPassword($lobbyId, $password) {
            if ($password) $this->lobbyPasswords[$lobbyId] = $password;
            else $this->lobbyPasswords = array_diff_key($this->lobbyPasswords, array($lobbyId => ""));
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
                $letters = str_split('0123456789ABCDEF');
                $color = "#";
                for ($i = 0; $i < 6; $i++) $color .= $letters[rand(0,15)];
                $this->contacts[$userId] = new Contact($userId, $data->getUsername(), $data->getAvatar(), $data->getSteam(), null, $color);
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
		
	}
    
    class Notification {
        public $notification;
        
        public function __construct($notification=null) {
            $this->notification = $notification;
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
        public $title;
        public $game;
        public $icon;
        public $playerlimit;
        public $locked;
        public $description;
        public $leader;
        public $steam;
		
		public function __construct($lobbyid=null, $contacts=array(), $history=array(), $title=null, $game=null, $icon=null, $playerlimit=null, $locked=null, $description=null, $leader=null, $steam=null) {
			$this->lobbyid = $lobbyid;
			$this->contacts = $contacts;
			$this->history = $history;
            $this->title = $title;
            $this->game = $game;
            $this->icon = $icon;
            $this->playerlimit = $playerlimit;
            $this->locked = $locked;
            $this->description = $description;
            $this->leader = $leader;
            $this->steam = $steam;
		}
	}
	
	class Contact {
		public $userid;
		public $name;
		public $avatar;
        public $steam;
        public $activelobbyid;
        public $color;
		
		public function __construct($userid=null, $name=null, $avatar=null, $steam=null, $activelobbyid=null, $color=null) {
			$this->userid = $userid;
			$this->name = $name;
			$this->avatar = $avatar;
            $this->steam = $steam;
            $this->activelobbyid = $activelobbyid;
            $this->color = $color;
		}
        
        public function __toString() {
            return serialize($this);
        }
	}

?>
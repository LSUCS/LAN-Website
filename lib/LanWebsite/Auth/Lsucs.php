<?php

	class LanWebsite_Auth_Lsucs implements LanWebsite_Auth {
	
		private $parent;
		private $activeId = null;
		private $sessionId;
		
		public function init() {
        
			//Check if logged in
			if (isset($_COOKIE['lan_session']) && $_COOKIE['lan_session'] != '') {
				$cookie = $_COOKIE['lan_session'];
				
				//Lookup cookie in db
				$session = LanWebsite_Main::getDb()->query("SELECT * FROM `sessions` WHERE session_id = '%s'", $cookie)->fetch_assoc();
				
				//If invalid session or session has expired, clear cookie and set active user to null
				if (!$session || $session["expires"] < time()) {
					setcookie('lan_session', '', time() - 3600, '/', '.lsucs.org.uk');

					//Create a copy of the cookie for the new LSUVGS url
					setcookie('lan_session', '', time() - 3600, '/', '.lsuvgs.org.uk');

					$this->activeId = null;
				}
				//Else load user object for session
				else {
					$this->activeId = $session["user_id"];
					$this->sessionId = $session['session_id'];
				}
			
			}
		
		}
		
		public function login($username, $password) {
			//Validate
            if(strpos($username, '@') !== false) return "Please use your Username to login, not your email.";
			$valid = $this->validateCredentials($username, $password);
			if (!$valid) return false;
			
			//Load user
            $user = LanWebsite_Main::getUserManager()->getUserByName($username);
			$this->activeId = $user->getUserId();
			
			//Update session
			$this->sessionId = uniqid($user->getUserId() . '.', true);
            $expires = time()+60*60*24*30;
			LanWebsite_Main::getDb()->query("INSERT INTO `sessions` (session_id, user_id, expires) VALUES ('%s', '%s', '%s')", $this->sessionId, $user->getUserId(), $expires);
            setcookie('lan_session', $this->sessionId, $expires, '/', ".lsucs.org.uk");

			//Creates a second cookie for lsuvgs urls
			setcookie('lan_session', $this->sessionId, $expires, '/', ".lsuvgs.org.uk");

			
			return true;
		}
		
		public function logout() {
			//Remove session
			LanWebsite_Main::getDb()->query("DELETE FROM `sessions` WHERE session_id = '%s'", $this->sessionId);
			$this->sessionId = null;
			setcookie('lan_session', '', time() - 3600, '/');
			//Guest user
			$this->activeId = null;
		}
		
		public function validateCredentials($username, $password) {
			$data = LanWebsite_Main::getUserManager()->getLsucsAuthResponse('validatecredentials', array("username" => $username, "password" => $password));
			if ($data === true) {
				return true;
            }
  		    return false;
		}
		
		
		public function requireLogin() {
            if (!$this->isLoggedIn()) header('Location: ' . LanWebsite_Main::buildUrl(false, 'account', 'login'));
        }
        
        public function requireMember() {
            if (!$this->isMember()) header('Location: ' . LanWebsite_Main::buildUrl(false, 'home'));
        }
		
		public function requireAdmin() {
            if (!$this->isAdmin()) header('Location: ' . LanWebsite_Main::buildUrl(false, 'home'));
        }
		
		public function requireNotLoggedIn() {
            if ($this->isLoggedIn()) header('Location: ' . LanWebsite_Main::buildUrl(false, 'home'));
        }
		
		
		public function getActiveUserId() {
			return $this->activeId;
		}
		
		public function isLoggedIn() {
            return $this->activeId != null;
        }
        
        public function isMember() {
            return LanWebsite_Main::getUserManager()->getActiveUser()->isMember();
        }
        
        public function isAdmin() {
            return LanWebsite_Main::getUserManager()->getActiveUser()->isAdmin();
        }
	
	}

?>

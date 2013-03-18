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
					setcookie('lan_session', '', time() - 3600, '/');
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
			$valid = $this->validateCredentials($username, $password);
			if (!$valid) return false;
			
			//Load user
            $user = LanWebsite_Main::getUserManager()->getUserByName($username);
			$this->activeId = $user->getUserId();
			
			//Update session
			$this->sessionId = uniqid($user->getUserId() . '.', true);
            $expires = time()+60*60*24*30;
			LanWebsite_Main::getDb()->query("INSERT INTO `sessions` (session_id, user_id, expires) VALUES ('%s', '%s', '%s')", $this->sessionId, $user->getUserId(), $expires);
            setcookie('lan_session', $this->sessionId, $expires, '/');
			
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
            return $data;
		}
		
		
		public function requireLogin() {
            if (!$this->isLoggedIn()) header('Location: ' . LanWebsite_Main::buildUrl(false, 'account', 'login'));
        }
		
		public function requireAdmin() {
            if (!LanWebsite_Main::getUserManager()->getActiveUser()->isAdmin()) header('Location: ' . LanWebsite_Main::buildUrl(false, 'home'));
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
	
	}

?>
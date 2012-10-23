<?php

    class Auth {
    
        private $parent;
        private $userModel;
        private $lanData = array();
        private $userLevel = UserLevels::Guest;
        
        /**
         * Constructor - loads session and logs user in if possible
         */
        public function __construct($parent) {
            $this->parent = $parent;
            
            //Initiate XenForo
            $this->xfDir = $this->parent->config->auth["xenforoDir"];
            $this->startTime = microtime(true);
            require($this->xfDir . '/library/XenForo/Autoloader.php');
            require("/home/soc_lsucs/lsucs.org.uk/htdocs/library/XenForo/Session.php");
            XenForo_Autoloader::getInstance()->setupAutoloader($this->xfDir. '/library');
            XenForo_Application::initialize($this->xfDir . '/library', $this->xfDir);
            XenForo_Application::set('page_start_time', $this->startTime);
            $this->userModel = XenForo_Model::create('XenForo_Model_User');
            
            //Initiate XenForo sessions
            $session = new Xenforo_Session();
            $session->startPublicSession();
            
            //Calculate user level
            $this->storeUserLevel();
            
            //Load LAN data
            $this->loadLanData();
        }
        
        /**
         * Logs the user in
         */
        public function loginUser($username, $password) {
            //If no cookies, return false
            if (count($_COOKIE) == 0) return false;
            
            //Check credentials
            $userId = $this->userModel->validateAuthentication($username, $password, $error);
            if (!$userId) return false;
            
            //Setup session
            $this->userModel->setUserRememberCookie($userId);
            $this->userModel->deleteSessionActivity(0, $this->getIp());
            $session = XenForo_Application::get('session');
            $session->changeUserId($userId);
            XenForo_Visitor::setup($userId);
            
            //Calculate user level
            $this->storeUserLevel();
            
            //Load LAN data
            $this->loadLanData();
            
            return true;
        }
        
        /**
         * Logs the user out
         */
        public function logoutUser() {
            //Remove an admin session if we're logged in as the same person
			if (XenForo_Visitor::getInstance()->get('is_admin'))
			{
				$adminSession = new XenForo_Session(array('admin' => true));
				$adminSession->start();
				if ($adminSession->get('user_id') == Xenforo_Visitor::getInstance()->getUserId())
				{
					$adminSession->delete();
				}
			}
            
            //Clear down normal sessions
            $sessionModel = XenForo_Model::create("XenForo_Model_Session");
			$sessionModel->processLastActivityUpdateForLogOut(XenForo_Visitor::getUserId());
			XenForo_Application::get('session')->delete();
			XenForo_Helper_Cookie::deleteAllCookies(
				array('session'),
				array('user' => array('httpOnly' => false))
			);
            
            //Setup guest user
			XenForo_Visitor::setup(0);
            $this->storeUserLevel();
            $this->loadLanData();
        }
        
        /**
         * Get Active User Data
         */
        public function getActiveUserData() {
            return array("xenforo" => Xenforo_Visitor::getInstance()->toArray(),
                         "lan" => $this->lanData);
        }
        
        /**
         * Gets the data for inputted user
         */
        public function getUserByName($username) {
            $user = $this->userModel->getUserByName($username);
            if (!$user) return false;
            return array("xenforo" => $user,
                         "lan" => $this->getUserLanData($user["user_id"]));
        }
        
        /**
         * Gets data for users matching inputted name
         */
        public function getUsersByName($match) {
            $users = $this->userModel->getUsers(array("username" => $match));
            if (!$users) return false;
            $results = array();
            foreach ($users as $user) {
                $results[] = array("xenforo" => $user,
                                   "lan" => $this->getUserLanData($user["user_id"]));
            }
            return $results;
        }

        /**
         * Gets the data for inputted user
         */
        public function getUserById($id) {
            $user = $this->userModel->getUserById($id);
            if (!$user) return false;
            return array("xenforo" => $user,
                         "lan" => $this->getUserLanData($id));
        }
        
        /**
         * Gets the avatar for the inputted user
         */
        public function getAvatarById($id) {
            $userdata = $this->getUserById($id);
            
            //Gravatar
            if ($userdata["xenforo"]["gravatar"] != "") {
                return XenForo_Template_Helper_Core::getAvatarUrl($userdata["xenforo"], "l");
            }
            
            //Other
            if ($userdata["xenforo"]["avatar_date"] != "") {
                return "http://lsucs.org.uk/" . XenForo_Template_Helper_Core::getAvatarUrl($userdata["xenforo"], "l", "content");
            }
            
            //Default
            return "http://lsucs.org.uk/" . XenForo_Template_Helper_Core::getAvatarUrl($userdata["xenforo"], "l", "default");
        }
        
        /**
         *  Validates inputted user and password
         */
        public function validateCredentials($username, $password) {
            $userId = $this->userModel->validateAuthentication($username, $password, $error);
            if (!$userId) return false;
            return true;
        }
        
        /**
         * User Level Access Requirements
         */
        public function requireLogin() {
            if ($this->userLevel == UserLevels::Guest) {
                header("location:index.php?page=account&action=login&returnurl=" . urlencode($_SERVER['REQUEST_URI']));
                return;
            }
        }
        public function requireNotLoggedIn() {
            if ($this->isLoggedIn()) {
                header("location:index.php");
            }
        }
        public function requireAdmin() {
            if (!$this->userLevel == UserLevels::Admin) {
                header("location:index.php?page=account&action=login&returnurl=" . urlencode($_SERVER['REQUEST_URI']));
            }
        }
        
        /**
         * User Level Utilities
         */
        public function isMember() {
            return ($this->userLevel == UserLevels::Member || $this->userLevel == UserLevels::Admin);
        }
        public function isAdmin() {
            return ($this->userLevel == UserLevels::Admin);
        }
        public function isGuest() {
            return ($this->userLevel == UserLevels::Guest);
        }
        public function isLoggedIn() {
            return !($this->userLevel == UserLevels::Guest);
        }
        
        /**
         * Checks whether the user is physically at the LAN (by ip)
         */
        public function isPhysicallyAtLan() {
            $ips = explode(",", str_replace(" ", "", $this->parent->settings->getSetting("lan_ip_addresses")));
            if (in_array($this->getIp(), $ips)) return true;
            else return false;
        }
        
        /**
         * Returns LAN data for specified user id
         */
        public function getUserLanData($userID) {
            $data = $this->parent->db->query("SELECT * FROM `user_data` WHERE user_id='%s'", $userID)->fetch_assoc();
            if (!$data) {
                $this->parent->db->query("INSERT INTO `user_data` (user_id) VALUES ('%s')", $userID);
                return $this->parent->db->query("SELECT * FROM `user_data` WHERE user_id='%s'", $userID)->fetch_assoc();
            }
            else {
                return $data;
            }
        }
        
        /**
         * Retrieves user LAN data from the database. If there is no entry in LAN database, it attempts to import from XenForo
         */
        private function loadLanData() {
        
            //Guests, go away
            if ($this->userLevel == UserLevels::Guest) {
                $this->lanData = array();
                return;
            }
            
            //Query
            $userId = Xenforo_Visitor::getInstance()->getUserId();
            $this->lanData = $this->getUserLanData($userId);
            
        }
        
        /**
         * Works out and stores the current user level according to the user signed in
         */
        private function storeUserLevel() {
            $group = $this->parent->settings->getSetting("xenforo_member_group_id");
            if (XenForo_Visitor::getInstance()->get("is_moderator")) {
                $this->userLevel = UserLevels::Admin;
            } else if (XenForo_Visitor::getInstance()->get("user_group_id") == $group || in_array($group, explode(",", XenForo_Visitor::getInstance()->get("secondary_group_ids")))) {
                $this->userLevel = UserLevels::Member;
            } else if (Xenforo_Visitor::getInstance()->getUserId() > 0) {
                $this->userLevel = UserLevels::Regular;
            } else {
                $this->userLevel = UserLevels::Guest;
            }
        }
        
        /**
         * Utility function to get the most accurate IP
         */
        private function getIp() {
          if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
          else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
          else $ip= $_SERVER['REMOTE_ADDR'];
          return $ip;
        }
    
    }
    
    abstract class UserLevels {
        const Admin = 0;
        const Member = 1;
        const Regular = 2;
        const Guest = 3;
    }

?>
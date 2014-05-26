<?php

    class LanWebsite_UserManager_Lsucs implements LanWebsite_UserManager {
    
        private $userCache = array();
    
		public function getActiveUser() {
            $userid = LanWebsite_Main::getAuth()->getActiveUserId();
            if ($userid == null) return new LanWebsite_User();
            return $this->getUserById($userid);
        }
		
		public function getUserById($userId) {
            //Get user data from LSUCS auth system
            if($userId == 0) return false;
            $auth = $this->getLsucsAuthResponse('getuserbyid', array("userid" => $userId));
            if (isset($auth['error'])) return false;
            
            //Get lan data
            $data = $this->getLanData($auth['userid']);
            
            //Return user obj from fill function
            return $this->fillUserObj($auth, $data);
        }
		
		public function getUserByName($name) {
            //Get user data from LSUCS auth system
            $auth = $this->getLsucsAuthResponse('getuserbyusername', array("username" => $name));
            if (isset($auth['error'])) return false;
            
            //Get lan data
            $data = $this->getLanData($auth['userid']);
            
            //Return user obj from fill function
            return $this->fillUserObj($auth, $data);
        }
		
		public function getUsersByName($name) {
            //Get user data from LSUCS auth system
            $users = $this->getLsucsAuthResponse('getusersbyusername', array("username" => $name));
            if (isset($users['error'])) return false;
            
            $output = array();
            foreach ($users as $user) {
                //Get data from LAN website
                $data = $this->getLanData($user['userid']);
                //Return user obj from fill function
                $output[] = $this->fillUserObj($user, $data);
            }
            
            //Return
            return $output;
        }
        
        public function saveUser(LanWebsite_User $user) {
            LanWebsite_Main::getDb()->query("UPDATE `user_data` SET real_name = '%s', emergency_contact_name = '%s', emergency_contact_number = '%s', steam_name = '%s', currently_playing = '%s' WHERE user_id = '%s'", $user->getFullName(), $user->getEmergencyContact(), $user->getEmergencyNumber(), $user->getSteam(), $user->getCurrentlyPlaying(), $user->getUserId());
            LanWebsite_Main::getDb()->query("DELETE FROM `user_games` WHERE user_id = '%s'", $user->getUserId());
            foreach ($user->getFavouriteGames() as $game) {
                LanWebsite_Main::getDb()->query("INSERT INTO `user_games` (user_id, game) VALUES ('%s', '%s')", $user->getUserId(), $game);
            }
        }
        
        public function getLsucsAuthResponse($method, $params) {
            //Check cache
            $cachekey = md5(LanWebsite_Main::getSettings()->getSetting("api_key") . $method . serialize($params));
            if (!LanWebsite_Cache::get("authapi", $cachekey, $result)) {
            
                //Prepare fields
                $fields = array("key" => LanWebsite_Main::getSettings()->getSetting("api_key"));
                $fields = array_merge($fields, $params);
                foreach($fields as $key=>$value) $fields[$key] = $key.'='.$value; 
                
                //Prepare cURL
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, rtrim(LanWebsite_Main::getSettings()->getSetting("lsucs_auth_url"), "/") . '/' . $method );
                curl_setopt($ch,CURLOPT_POST, 2);
                curl_setopt($ch,CURLOPT_POSTFIELDS, implode("&", $fields));
                curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                
                //Decode response and store
                $result = json_decode(curl_exec($ch), true);

                if(isset($result['error'])) throw new Exception('Fatal Auth Error: ' . $result['error'] . " ID: " . $params['userid']); 
                LanWebsite_Cache::set("authapi", $cachekey, $result, 30000);
            }
            return $result;
        }
        
        public function checkFriendsOfLsucs($userID) {
            $this->getLsucsAuthResponse("checkfol", array("userid" => $userID));
        }
        
        private function getLanData($userid) {
            //Get data from LAN website
            $data = LanWebsite_Main::getDb()->query("SELECT * FROM `user_data` WHERE user_id = '%s'", $userid)->fetch_assoc();
            if (!$data) {
                LanWebsite_Main::getDb()->query("INSERT INTO `user_data` (user_id) VALUES (%s)", $userid);
                $data = LanWebsite_Main::getDb()->query("SELECT * FROM `user_data` WHERE user_id = '%s'", $userid)->fetch_assoc();
            }
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `user_games` WHERE user_id = '%s'", $userid);
            $data["favourite_games"] = array();
            while ($row = $res->fetch_assoc()) $data["favourite_games"][] = $row["game"];
            return $data;
        }
        
        private function fillUserObj($auth, $data) {
            //Fill new user object
            $user = new LanWebsite_User();
            $user->setUserId($auth['userid']);
            $user->setUsername($auth['username']);
            $user->setFullName($data['real_name']);
            $user->setEmail($auth['email']);
            $user->setAvatar($auth['avatar']);
            $user->setGroups($auth['groups']);
            $user->setSteam($data['steam_name']);
            $user->setFavouriteGames($data["favourite_games"]);
            $user->setCurrentlyPlaying($data["currently_playing"]);
            $user->setEmergencyContact($data["emergency_contact_name"]);
            $user->setEmergencyNumber($data["emergency_contact_number"]);
            
            //Work out user level
            $membergroup = LanWebsite_Main::getSettings()->getSetting("xenforo_member_group_id");
            if ($auth['moderator'] == true || $auth['admin'] == true) $user->setUserLevel(UserLevel::Admin);
            else if (in_array($membergroup, $user->getGroups())) $user->setUserLevel(UserLevel::Member);
            else $user->setUserLevel(UserLevel::Regular);
            
            //Return object
            return $user;
        }
    
    }

?>

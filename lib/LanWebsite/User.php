<?php

	class LanWebsite_User {
	
		private $userId = null;
		private $userLevel = UserLevel::Guest;
		private $username = 'Guest';
		private $fullname = 'Guest Account';
		private $email = '';
        private $avatar = 'http://lsucs.org.uk/styles/default/xenforo/avatars/avatar_l.png';
        private $groups = array();
        private $steam = '';
        private $emergency_contact;
        private $emergency_number;
        private $currently_playing;
        private $favourite_games = array();
		
        
        public function isAdmin() {
            return ($this->userLevel == UserLevel::Admin);        
        }
        
        public function isMember() {
            return ($this->userLevel == UserLevel::Member || $this->userLevel == UserLevel::Admin);
        }
        
        public function getUserId() {
            return $this->userId;
        }
        public function setUserId($userId) {
            $this->userId = $userId;
        }
        
        public function getUsername() {
            return $this->username;
        }
        public function setUsername($username) {
            $this->username = $username;
        }
        
        public function getFullName() {
            return $this->fullname;
        }
        public function setFullName($fullname) {
            $this->fullname = $fullname;
        }
        
        public function getEmail() {
            return $this->email;
        }
        public function setEmail($email) {
            $this->email = $email;
        }
        
        public function getUserLevel() {
            return $this->userLevel();
        }
        public function setUserLevel($level) {
            $this->userLevel = $level;
        }
        
        public function getAvatar() {
            return $this->avatar;
        }
        public function setAvatar($avatar) {
            $this->avatar = $avatar;
        }
        
        public function getGroups() {
            return $this->groups;
        }
        public function setGroups($groups) {
            if (!is_array($groups)) $groups = (array)$groups;
            $this->groups = $groups;
        }
        
        public function getSteam() {
            return $this->steam;
        }
        public function setSteam($steam) {
            $this->steam = $steam;
        }
        
        public function getEmergencyContact() {
            return $this->emergency_contact;
        }
        public function setEmergencyContact($contact) {
            $this->emergency_contact = $contact;
        }
        
        public function getEmergencyNumber() {
            return $this->emergency_number;
        }
        public function setEmergencyNumber($number) {
            $this->emergency_number = $number;
        }
        
        public function getCurrentlyPlaying() {
            return $this->currently_playing;
        }
        public function setCurrentlyPlaying($current) {
            $this->currently_playing = $current;
        }
        
        public function getFavouriteGames() {
            return $this->favourite_games;
        }
        public function setFavouriteGames($games) {
            if (!is_array($games)) $games = (array)$games;
            $this->favourite_games = $games;
        }
	
	}
    
?>
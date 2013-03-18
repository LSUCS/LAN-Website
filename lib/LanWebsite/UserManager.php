<?php

    interface LanWebsite_UserManager {
    
		public function getActiveUser();
		
		public function getUserById($userId);
		
		public function getUserByName($name);
		
		public function getUsersByName($name);
        
        public function saveUser(LanWebsite_User $user);
    
    }
    
	abstract class UserLevel {
        const Admin = 0;
        const Member = 1;
        const Regular = 2;
        const Guest = 3;
    }
    
?>
<?php

	interface LanWebsite_Auth {
		
		public function init();
        
		
		public function login($username, $password);
		
		public function logout();
		
		public function validateCredentials($username, $password);
		
		
		public function requireLogin();
		
		public function requireAdmin();
		
		public function requireNotLoggedIn();
		
        
		public function getActiveUserId();
		
		public function isLoggedIn();
	
	}

?>
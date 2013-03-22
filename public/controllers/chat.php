<?

	class Chat_Controller extends LanWebsite_Controller {
	
		public function get_Index() {
		}
		
		public function get_Getdetails() {
			//Check if chat is enabled in settings
			if (!LanWebsite_Main::getAuth()->isLoggedIn() || LanWebsite_Main::getSettings()->getSetting("chat_enabled") == 0 || !LanWebsite_Main::getUserManager()->getActiveUser()->isAdmin()) {
				echo json_encode(array("disabled" => true));
				return;
			}
			
			echo json_encode(array("url" => LanWebsite_Main::getSettings()->getSetting("chat_url")));
		}
	
	}

?>
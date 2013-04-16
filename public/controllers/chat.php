<?

	class Chat_Controller extends LanWebsite_Controller {
	
		public function get_Index() {
		}
		
		public function get_Getdetails() {
        
			//Check if chat is enabled
            //$res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND activated = 1 AND assigned_forum_id = '%s'", LanWebsite_Main::getSettings()->getSetting("lan_number"), LanWebsite_Main::getUserManager()->getActiveUser()->getUserId())->fetch_assoc();
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s'", LanWebsite_Main::getUserManager()->getActiveUser()->getUserId())->fetch_assoc();
       
			if (!LanWebsite_Main::getAuth()->isLoggedIn() || LanWebsite_Main::getSettings()->getSetting("chat_enabled") == 0 || (!$res && LanWebsite_Main::getSettings()->getSetting("require_ticket_for_chat") == 1)) {
				echo json_encode(array("disabled" => true));
				return;
			}
			
			echo json_encode(array("url" => LanWebsite_Main::getSettings()->getSetting("chat_url")));
		}
	
	}

?>
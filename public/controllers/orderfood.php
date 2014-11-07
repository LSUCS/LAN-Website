<?

	class Orderfood_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "order": return array("options" => "notnull"); break;
            }
        }
	
		public function get_Index() {
			
			//Get shop data
			$data["shops"] = array();
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `food_shops`");
			while ($row = $res->fetch_assoc()) {
				$row["options"] = array();
				$res2 = LanWebsite_Main::getDb()->query("SELECT * FROM `food_options` WHERE shop_id = '%s' ORDER BY option_name ASC", $row["shop_id"]);
				while ($row2 = $res2->fetch_assoc()) $row["options"][] = $row2;
				$data["shops"][] = $row;
			}
			
			//Get unpaid data
			$data["unpaid"] = 0;
			$user = LanWebsite_Main::getUserManager()->getActiveUser();
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `food_orders` WHERE user_id = '%s' AND paid = 0 AND lan_number = '%s'",
                $user->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"));
			while ($row = $res->fetch_assoc()) {
				$data["unpaid"] += $row["price"];
			}
		
			//Output
            $tmpl = LanWebsite_Main::getTemplateManager();
            $tmpl->setSubTitle("Order Food");
            $tmpl->addTemplate('orderfood', $data);
			$tmpl->output();
			
		}
		
		public function post_Order($inputs) {
		
			$user = LanWebsite_Main::getUserManager()->getActiveUser();
		
			//Validate few things
			if (!LanWebsite_Main::isAtLan()) $this->errorJSON("You need to be at the LAN to order food");
			if ($user->getFullName() == "") $this->errorJSON("You must fill in your real name in your account details before ordering food");
			$ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $user->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"))->fetch_assoc();
			if (!$ticket) $this->errorJSON("You do not have a ticket");
			if ($ticket["seat"] == "") $this->errorJSON("Your seat is not set");
		
			$opts = json_decode($inputs["options"], true);
			$totalOptions = 0;
			$totalCost = 0;
			$options = array();
			$time = 0;
			
			//Validation json
			if (!is_array($opts) || count($opts) == 0) $this->errorJSON("Invalid options");
			
			//Get shops
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `food_shops`");
			$shops = array();
			while ($row = $res->fetch_assoc()) $shops[$row["shop_id"]] = $row;
			
			//Loop options
			foreach ($opts as $opt) {
			
				//Validate
				$option = LanWebsite_Main::getDb()->query("SELECT * FROM `food_options` WHERE option_id = '%s'", $opt["option_id"])->fetch_assoc();
				if (!$option) $this->errorJSON("Invalid option ID");
				if ((!is_numeric($opt["amount"]) && strlen($opt["amount"]) > 0) || $opt["amount"] < 0 || $opt["amount"] > 9) $this->errorJSON("Invalid option amount");
				if ($shops[$option["shop_id"]]["enabled"] == 0) $this->errorJSON("Shop is closed");
				
				$totalOptions += $opt["amount"];
				$totalCost += $opt["amount"] * $option["price"];
				if ($opt["amount"] > 0) {
					if (strtotime($shops[$option["shop_id"]]["order_by"]) < $time || $time == 0) $time = strtotime($shops[$option["shop_id"]]["order_by"]);
					$options[] = array("option" => $option, "amount" => $opt["amount"]);
				}
			}
			
			if ($totalOptions < 1) $this->errorJSON("You must select at least one option to order");
			
			//Add to database
			foreach ($options as $option) {
				for ($i = 0; $i < $option["amount"]; $i++) {
					LanWebsite_Main::getDb()->query("INSERT INTO `food_orders` (shop_id, user_id, lan_number, option_id, option_name, price) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $option["option"]["shop_id"], $user->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"), $option["option"]["option_id"], $option["option"]["option_name"], $option["option"]["price"]);
				}
			}

			//Return
			echo json_encode(array("cost" => $totalCost, "order_by" => date("l gA", $time)));
		
		}
	
	}
	
?>
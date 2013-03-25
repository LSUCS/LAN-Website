<?php
	
	class Food_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "paid": return array("order_id" => array("int", "notnull")); break;
            }
        }
	
		public function get_Index() {
			$tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Food Ordering");
            $tmpl->enablePlugin('datatables');
            $tmpl->addTemplate('food');
			$tmpl->output();
		}
		
		public function get_Loadtables() {
		
			$return["paid"] = array();
			$return["unpaid"] = array();
		
			//Get shops
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `food_shops`");
			$shops = array();
			while ($row = $res->fetch_assoc()) $shops[$row["shop_id"]] = $row;
			
			//Get orders
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `food_orders` WHERE lan_number = '%s'", LanWebsite_Main::getSettings()->getSetting("lan_number"));
			while ($order = $res->fetch_assoc()) {
				$shop = $shops[$order["shop_id"]];
				$user = LanWebsite_Main::getUserManager()->getUserById($order["user_id"]);
				$ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $user->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"))->fetch_assoc();
				$out = array();
				$out[] = $order["order_id"];
				$out[] = $user->getFullName();
				$out[] = $ticket["seat"];
				$out[] = $shop["shop_name"];
				$out[] = $order["option_name"];
				$out[] = "&pound;" . $order["price"];
				
				//Store
				if ($order["paid"] == 1) $return["paid"][] = $out;
				else $return["unpaid"][] = $out;
			}
			
			echo json_encode($return);
			
		}
		
		public function post_Paid($inputs) {
			
			//Validate
			$order = LanWebsite_Main::getDb()->query("SELECT * FROM `food_orders` WHERE order_id = '%s'", $inputs["order_id"])->fetch_assoc();
			if (!$order) $this->errorJSON("Invalid order ID");
			
			//Mark is paid
			LanWebsite_Main::getDb()->query("UPDATE `food_orders` SET paid = 1 WHERE order_id = '%s'", $order["order_id"]);
            
            echo true;
			
		}
	
	}

?>
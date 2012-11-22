<?php
	
	class Adminfood_Page extends Page {
	
		public function getInputs() {
			return array("actionPaid" => array("order_id" => "post"));
		}
	
		public function actionIndex() {
			$this->parent->template->setSubtitle("food ordering");
			$this->parent->template->outputTemplate("adminfood");
		}
		
		public function actionLoadtables() {
		
			$return["paid"] = array();
			$return["unpaid"] = array();
		
			//Get shops
			$res = $this->parent->db->query("SELECT * FROM `food_shops`");
			$shops = array();
			while ($row = $res->fetch_assoc()) $shops[$row["shop_id"]] = $row;
			
			//Get orders
			$res = $this->parent->db->query("SELECT * FROM `food_orders` WHERE lan_number = '%s'", $this->parent->settings->getSetting("lan_number"));
			while ($order = $res->fetch_assoc()) {
				$shop = $shops[$order["shop_id"]];
				$userdata = $this->parent->auth->getUserById($order["user_id"]);
				$ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s'", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
				$out = array();
				$out[] = $order["order_id"];
				$out[] = $userdata["lan"]["real_name"];
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
		
		public function actionPaid() {
			
			//Validate
			$order = $this->parent->db->query("SELECT * FROM `food_orders` WHERE order_id = '%s'", $this->inputs["order_id"])->fetch_assoc();
			if (!$order) $this->errorJSON("Invalid order ID");
			
			//Mark is paid
			$this->parent->db->query("UPDATE `food_orders` SET paid = 1 WHERE order_id = '%s'", $order["order_id"]);
			
		}
	
	}

?>
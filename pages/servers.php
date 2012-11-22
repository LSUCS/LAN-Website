<?php

	class Servers_Page extends Page {
		
		public function actionIndex() {
		
			$this->parent->template->setSubtitle("Game Servers");
			$this->parent->template->outputTemplate("servers");
		
		}
		
		public function actionLoadservers() {
			
			$res = $this->parent->db->query("SELECT * FROM `game_servers` WHERE name != ''");
			$return["servers"] = array();
			while ($row = $res->fetch_assoc()) {
				$row["players"] = json_decode($row["players"], true);
				$return["servers"][] = $row;
			}
			$return["interval"] = $this->parent->settings->getSetting("server_browser_update_interval");
			
			echo json_encode($return);
		
		}
		
        public function actionProcess() {
        
            //Check lock
            if ($this->parent->settings->getSetting("server_cron_lock") == 1) return;
            
            //Lock
            $this->parent->settings->changeSetting("server_cron_lock", true);
			
			//Get source servers
			$res = $this->parent->db->query("SELECT * FROM `game_servers` WHERE source = 1 AND local = 0");
			
			require "lib/SourceQuery/SourceQuery.class.php";
			$query = new SourceQuery();
			
			//Loop, updating info
			while ($server = $res->fetch_assoc()) {
				
				try	{
				
					//Connect and grab info
					$query->connect($server["hostname"], $server["port"], 1, SourceQuery::SOURCE);
					$info = $query->getInfo();
					$players = $query->getPlayers();
					$app = file_get_contents("http://steamcommunity.com/app/" . $info["AppID"]);
					preg_match('/<div class="apphub_AppIcon"><img src="(.*?)">/im', $app, $matches);
					
					//Put info into array
					$server["name"] = $info["HostName"];
					$server["game"] = $info["ModDesc"];
					if (isset($matches[1])) $server["game_icon"] = $matches[1];
					else $server["game_icon"] = "";
					$server["num_players"] = $info["Players"];
					$server["max_players"] = $info["MaxPlayers"];
					$server["password_protected"] = $info["Password"];
					$server["map"] = $info["Map"];
					$server["players"] = json_encode($players);
					
					print_r($server);
					
					//Store
					$this->parent->db->query("UPDATE `game_servers` SET name = '%s', game = '%s', game_icon = '%s', num_players = '%s', max_players = '%s', password_protected = '%s', map = '%s', players = '%s' WHERE server_id = '%s'", $server["name"], $server["game"], $server["game_icon"], $server["num_players"], $server["max_players"], $server["password_protected"], $server["map"], $server["players"], $server["server_id"]);
					
				} catch (Exception $e) {
					echo "Error processing server " . $server["hostname"] . ": " . $e->getMessage() . "<br />";
				}
				
			}
			
			//Unlock
            $this->parent->settings->changeSetting("map_cron_lock", true);
        
        }
	
	}

?>
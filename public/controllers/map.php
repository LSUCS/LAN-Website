<?php

    class Map_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "processseat": return array("ticket" => array("notnull", "int")); break;
            }
        }
    
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Lan Map");
            $tmpl->addTemplate('map');
			$tmpl->output();
        }
        
        public function get_Load() {
            
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `map_cache`");
            $return["data"] = array();
            while($row = $res->fetch_assoc()) $return["data"][] = $row;
            //$return["interval"] = LanWebsite_Main::getSettings()->getSetting("map_browser_update_interval");
            
            echo json_encode($return);
        
        }
        
        function get_Processseat($inputs) {
        
            //Ticket
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND  ticket_id = '%s'", LanWebsite_Main::getSettings()->getSetting("lan_number"), $inputs["ticket"])->fetch_assoc();
            if (!$ticket) return;
        
            //Basic details
            $user = LanWebsite_Main::getUserManager()->getUserById($ticket["assigned_forum_id"]);
            $seat["seat"] = $ticket["seat"];
            $seat["user_id"] = $ticket["assigned_forum_id"];
            $seat["username"] = $user->getUsername();
            $seat["name"] = $user->getFullName();
            $seat["steam"] = $user->getSteam();
			
            //Steam
            $steam = false;
            if ($user->getSteam() != "") {
				$error = "";
                
                //Set up cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                
                //Attempt to get data from Steam, retry up to 4 times
				for ($i = 0; $i < 4; $i++) {
					try {
                        //Make cURL request
                        curl_setopt($ch, CURLOPT_URL, "http://steamcommunity.com/id/" . urlencode($user->getSteam()) . "/?xml=1");
						$page = curl_exec($ch);
                        if ($page === FALSE) throw new Exception();
						$steam = true;
						break;
					} catch (Exception $e) {
						$steam = false;
						$error = $e->getMessage();
					}
				}
				
				if ($steam) $steam = new SimpleXMLElement($page, LIBXML_NOCDATA);
            }
            
			
            //Avatar
            if ($steam) $seat["avatar"] = (string)$steam->avatarFull;
            else $seat["avatar"] = $user->getAvatar();
            
            //Game
            if ($steam && $steam->privacyState == "public" && $steam->onlineState == "in-game") {
                $seat["game"] = (string)$steam->inGameInfo->gameName;
                $seat["game_icon"] = (string)$steam->inGameInfo->gameIcon;
                $seat["ingame"] = true;
            } else if ($user->getCurrentlyPlaying() != "") {
                $seat["ingame"] = true;
                $seat["game_icon"] = "";
                $seat["game"] = $user->getCurrentlyPlaying();
            } else {
                $seat["ingame"] = false;
                $seat["game_icon"] = "";
                $seat["game"] = "";
            }
            
            //Favourites
            $favs = array();
            foreach ($user->getFavouriteGames() as $fav) $favs[] = '<li>' . $fav . '</li>';
            if (count($favs) > 6) $favs = array_rand(array_flip($favs), 6);
            if (count($favs) > 0) $seat["favourite"] = "<ul>" . implode("", $favs) . "</ul>";
            else $seat["favourite"] = "";
            
            //Most played
            $seat["mostplayed"] = "";
            if ($steam && $steam->privacyState == "public" && isset($steam->mostPlayedGames)) {
                $games = array();
                $i = 0;
                foreach ($steam->mostPlayedGames->mostPlayedGame as $game) {
                    if ($i == 4) break;
                    $games[] = '<li><img src="' . $game->gameIcon . '" />' . $game->gameName . ' - ' . $game->hoursOnRecord . ' hours</li>';
                    $i++;
                }
                if (count($games) > 0) $seat["mostplayed"] = "<ul>" . implode("", $games) . "</ul>";
            }
            
            //LOGGING
            //not currently in game and no previous game = nothing
            //not currently in game and previous game = stop
            //currently in game and not in previous game = start
            //currently in game and in previous different game = start + stop
            //currently in game and in previous same game = nothing
            $cache = LanWebsite_Main::getDb()->query("SELECT * FROM map_cache WHERE user_id = '%s' AND seat = '%s'", $user->getUserId(), $seat["seat"])->fetch_assoc();
            $start = false;
            $stop = false;
            if ($seat["game"] == "" && ($cache && $cache["game"] != "")) $stop = true;
            if ($seat["game"] != "" && ($cache && $cache["game"] == "")) $start = true;
            if ($seat["game"] != "" && ($cache && $cache["game"] != $seat["game"] && $cache["game"] != null)) {
                $start = true;
                $stop = true;
            }
            if ($stop) Logger::log("stopgame", json_encode(array("game" => $cache["game"], "userid" => $seat["user_id"])));
            if ($start && $steam && $steam->privacyState == "public" && $steam->onlineState == "in-game") Logger::log("startgame", json_encode(array("game" => $seat["game"], "userid" => $seat["user_id"])));
            

            
            
            echo json_encode($seat);
            
        }
    
    }

?>
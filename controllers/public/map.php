<?php

    class Map_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "processseat" => return array("ticket" => array("notnull", "int")); break;
            }
        }
    
        public function get_Index() {
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Lan Map");
            $tmpl->addScript('/js/pages/map.js');
            $tmpl->addStyle('/css/pages/map.css');
            $tmpl->addTemplate('public/map');
			$tmpl->output();
        }
        
        public function get_Load() {
            
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `map_cache`");
            $return["data"] = array();
            while($row = $res->fetch_assoc()) $return["data"][] = $row;
            $return["interval"] = LanWebsite_Main::getSettings()->getSetting("map_browser_update_interval");
            
            echo json_encode($return);
        
        }
        
        public function get_Process() {
        
            //Check lock
            if (LanWebsite_Main::getSettings()->getSetting("map_cron_lock") == 1) return;
            
            //Lock
            LanWebsite_Main::getSettings()->changeSetting("map_cron_lock", true);
                    
            //Get tickets with seats
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND seat != ''", LanWebsite_Main::getSettings()->getSetting("lan_number"));
            
            //Spawn asynchronous jobs
            $jobs = array();
            while ($ticket = $res->fetch_assoc()) {
                $jobs[] = $this->JobStartAsync($_SERVER['SERVER_NAME'],'/index.php?page=map&action=processseat&ticket=' . $ticket["ticket_id"]);
            }
            
            //Wait until all complete
            $cache = array();
            while (true) {
                sleep(1);
                
                $done = true;
                foreach ($jobs as $key => $job) {
                    $r = $this->JobPollAsync($job);
                    if ($r !== false) {
                        $done = false;
                        if (strpos($r, '{"seat"')) {
                            $cache[] = json_decode(substr($r, strpos($r, '{"seat"')), true);
                        }
                    }
                    else unset($jobs[$key]);
                }
                
                if ($done) break;
            }
            
            //Clear table
            LanWebsite_Main::getDb()->query("TRUNCATE TABLE `map_cache`");
            
            //Store
            foreach ($cache as $seat) {
                LanWebsite_Main::getDb()->query("INSERT INTO `map_cache` (seat, user_id, username, name, steam, avatar, ingame, game, mostplayed, favourite, game_icon)
                                          VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                                          $seat["seat"], $seat["user_id"], $seat["username"], $seat["name"], $seat["steam"], $seat["avatar"], $seat["ingame"], $seat["game"], $seat["mostplayed"], $seat["favourite"], $seat["game_icon"]);
            }
            
            //Unlock
            LanWebsite_Main::getSettings()->changeSetting("map_cron_lock", true);
        
        }
        
        function get_Processseat($inputs) {
		
        
            //Ticket
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND  ticket_id = '%s'", LanWebsite_Main::getSettings()->getSetting("lan_number"), $inputs["ticket"])->fetch_assoc();
            if (!$ticket) return;
        
            //Basic details
            $userda = LanWebsite_Main::getUserManager()->getUserById($ticket["assigned_forum_id"]);
            $seat["seat"] = $ticket["seat"];
            $seat["user_id"] = $ticket["assigned_forum_id"];
            $seat["username"] = $user->getUsername();
            $seat["name"] = $user->getFullName();
            $seat["steam"] = $user->getSteam();
			
            //Steam
            $steam = false;
            if ($user->getSteam() != "") {
				$error = "";
				for ($i = 0; $i < 4; $i++) {
					try {
						$page = file_get_contents("http://steamcommunity.com/id/" . urlencode($user->getSteam()) . "/?xml=1");
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
            
            echo json_encode($seat);
            
        }        
        
        private function JobStartAsync($server, $url) {
            $errno = '';
            $errstr = '';
            
            set_time_limit(0);
            
            $fp = fsockopen($server, 80, $errno, $errstr, 30);
            if (!$fp) {
               echo "$errstr ($errno)<br />\n";
               return false;
            }
            $out = "GET $url HTTP/1.1\r\n";
            $out .= "Host: $server\r\n";
            $out .= "Connection: Close\r\n\r\n";
            
            stream_set_blocking($fp, false);
            stream_set_timeout($fp, 86400);
            fwrite($fp, $out);
            
            return $fp;
        }

        private function JobPollAsync(&$fp) {
            if ($fp === false) return false;
            
            if (feof($fp)) {
                fclose($fp);
                $fp = false;
                return false;
            }
            
            return fread($fp, 10000);
        }
    
    }

?>
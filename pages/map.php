<?php

    class Map_Page extends Page {
    
        public function getInputs() {
            return array("actionProcessseat" => array("ticket" => "get"));
        }
    
        public function actionIndex() {
            $this->parent->template->setSubtitle("lan map");
            $this->parent->template->outputTemplate('map');
        }
        
        public function actionLoad() {
            
            $res = $this->parent->db->query("SELECT * FROM `map_cache`");
            $return = array();
            while($row = $res->fetch_assoc()) $return[] = $row;
            
            echo json_encode($return);
        
        }
        
        public function actionProcess() {
        
            //Check lock
            if ($this->parent->settings->getSetting("map_cron_lock") == 1) return;
            
            //Lock
            $this->parent->settings->changeSetting("map_cron_lock", true);
                    
            //Get tickets with seats
            $res = $this->parent->db->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND seat != '' AND activated = 1", $this->parent->settings->getSetting("lan_number"));
            
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
                        if (strlen($r) > 1) {
                            $cache[] = json_decode(substr($r, strpos($r, '{"seat"')), true);
                        }
                    }
                    else unset($jobs[$key]);
                }
                
                if ($done) break;
            }
            
            //Clear table
            $this->parent->db->query("TRUNCATE TABLE `map_cache`");
            
            //Store
            foreach ($cache as $seat) {
                $this->parent->db->query("INSERT INTO `map_cache` (seat, user_id, username, name, steam, avatar, ingame, game, mostplayed, favourite, game_icon)
                                          VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                                          $seat["seat"], $seat["user_id"], $seat["username"], $seat["name"], $seat["steam"], $seat["avatar"], $seat["ingame"], $seat["game"], $seat["mostplayed"], $seat["favourite"], $seat["game_icon"]);
            }
            
            //Unlock
            $this->parent->settings->changeSetting("map_cron_lock", true);
        
        }
        
        function actionProcessseat() {
        
            //Ticket
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE lan_number = '%s' AND activated = 1 AND  ticket_id = '%s'", $this->parent->settings->getSetting("lan_number"), $this->inputs["ticket"])->fetch_assoc();
            if (!$ticket) return;
        
            //Basic details
            $userdata = $this->parent->auth->getUserById($ticket["assigned_forum_id"]);
            $seat["seat"] = $ticket["seat"];
            $seat["user_id"] = $ticket["assigned_forum_id"];
            $seat["username"] = $userdata["xenforo"]["username"];
            $seat["name"] = $userdata["lan"]["real_name"];
            $seat["steam"] = $userdata["lan"]["steam_name"];
            
            //Steam
            $steam = false;
            if ($userdata["lan"]["steam_name"] != "") {
                $page = file_get_contents("http://steamcommunity.com/id/" . $userdata["lan"]["steam_name"] . "/?xml=1");
                $steam = new SimpleXMLElement($page, LIBXML_NOCDATA);                
            }
            
            //Avatar
            if ($steam) $seat["avatar"] = (string)$steam->avatarFull;
            else $seat["avatar"] = "images/avatar_blank.png";
            
            //Game
            if ($steam && $steam->privacyState == "public" && $steam->onlineState == "in-game") {
                $seat["game"] = (string)$steam->inGameInfo->gameName;
                $seat["game_icon"] = (string)$steam->inGameInfo->gameIcon;
                $seat["ingame"] = true;
            } else if ($userdata["lan"]["currently_playing"] != "") {
                $seat["ingame"] = true;
                $seat["game_icon"] = "";
                $seat["game"] = $userdata["lan"]["currently_playing"];
            } else {
                $seat["ingame"] = false;
                $seat["game_icon"] = "";
                $seat["game"] = "";
            }
            
            //Favourites
            $res2 = $this->parent->db->query("SELECT * FROM `user_games` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"]);
            $favs = array();
            $seat["favourite"] = "";
            while ($fav = $res2->fetch_assoc()) $favs[] = '<li>' . $fav["game"] . '</li>';
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
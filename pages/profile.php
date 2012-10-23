<?php

    class Profile_Page extends Page {
    
        public function getInputs() {
            return array("actionLoadprofile" => array("name" => "post"));
        }
    
        public function actionIndex() {
            
            $this->parent->template->setSubtitle("Profile");
            $this->parent->template->outputTemplate("profile");
        }
        
        public function actionLoadprofile() {
        
            //Validate
            $this->inputs["name"] = urldecode($this->inputs["name"]);
            $userdata = $this->parent->auth->getUserByName($this->inputs["name"]);
            if (!$userdata) return;
            
            $profile = array();
            
            //Basic details
            $profile["user_id"] = $userdata["xenforo"]["user_id"];
            $profile["username"] = $userdata["xenforo"]["username"];
            $profile["name"] = $userdata["lan"]["real_name"];
            $profile["steam"] = $userdata["lan"]["steam_name"];
            
            //Steam
            $steam = false;
            if ($userdata["lan"]["steam_name"] != "") {
                $page = @file_get_contents("http://steamcommunity.com/id/" . $userdata["lan"]["steam_name"] . "/?xml=1");
                if ($page === false) return;
                $steam = new SimpleXMLElement($page, LIBXML_NOCDATA);                
            }
            
            //Game
            if ($steam && $steam->privacyState == "public" && $steam->onlineState == "in-game") {
                $profile["game"] = (string)$steam->inGameInfo->gameName;
                $profile["game_link"] = (string)$steam->inGameInfo->gameLink;
                $profile["game_icon"] = (string)$steam->inGameInfo->gameIcon;
                $profile["ingame"] = true;
            } else if ($userdata["lan"]["currently_playing"] != "") {
                $profile["ingame"] = true;
                $profile["game_link"] = "";
                $profile["game_icon"] = "";
                $profile["game"] = $userdata["lan"]["currently_playing"];
            } else {
                $profile["ingame"] = false;
                $profile["game_link"] = "";
                $profile["game_icon"] = "";
                $profile["game"] = "";
            }
            
            //Ticket
            $ticket = $this->parent->db->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s' AND seat != ''", $userdata["xenforo"]["user_id"], $this->parent->settings->getSetting("lan_number"))->fetch_assoc();
            if ($ticket) $profile["seat"] = $ticket["seat"];
            else $profile["seat"] = "";
           
            //Avatar
            if ($steam) $profile["avatar"] = (string)$steam->avatarFull;
            else $profile["avatar"] = $this->parent->auth->getAvatarById($userdata["xenforo"]["user_id"]);
            
            //Favourites
            $res2 = $this->parent->db->query("SELECT * FROM `user_games` WHERE user_id = '%s'", $userdata["xenforo"]["user_id"]);
            $favs = array();
            $profile["favourite"] = "";
            while ($fav = $res2->fetch_assoc()) $favs[] = '<li>' . $fav["game"] . '</li>';
            if (count($favs) > 0) $profile["favourite"] = "<ul>" . implode("", $favs) . "</ul>";
            
            //Most played
            $profile["mostplayed"] = "";
            if ($steam && $steam->privacyState == "public" && isset($steam->mostPlayedGames)) {
                $games = array();
                $i = 0;
                foreach ($steam->mostPlayedGames->mostPlayedGame as $game) {
                    if ($i == 4) break;
                    $games[] = '<li><img src="' . $game->gameIcon . '" />' . $game->gameName . ' - ' . $game->hoursOnRecord . ' hours</li>';
                    $i++;
                }
                if (count($games) > 0) $profile["mostplayed"] = "<ul>" . implode("", $games) . "</ul>";
            }
            
            echo json_encode($profile);
            
        }
    
    }
    
?>
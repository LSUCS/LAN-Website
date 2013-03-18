<?php

    class Profile_Controller extends LanWebsite_Controller {
    
        public function getInputFilters($action) {
            switch ($action) {
                case "loadprofile": return array("name" => "notnull"); break;
            }
        }
    
        public function get_Index() {
            $tmpl = new LanWebsite_Template();
            $tmpl->setSubTitle("Profile");
            $tmpl->addScript('/js/pages/profile.js');
            $tmpl->addStyle('/css/pages/profile.css');
            $tmpl->addTemplate('public/profile');
			$tmpl->output();
        }
        
        public function post_Loadprofile($inputs) {
        
            //Decide whether we are sbowing client-requested profile or not
            $inputs["name"] = urldecode($inputs["name"]);
            if (strlen($inputs["name"]) > 0) {
                $user = LanWebsite_Main::getUserManager()->getUserByName($inputs["name"]);
                if (!$user) die(false);
            } else if (LanWebsite_Main::getAuth()->isLoggedIn()) {
                $user = LanWebsite_Main::getUserManager()->getActiveUser();
            }
            
            $profile = array();
            
            //Basic details
            $profile["user_id"] = $user->getUserId();
            $profile["username"] = $user->getUsername();
            $profile["name"] = $user->getFullName();
            $profile["steam"] = $user->getSteam();
            
            //Steam
            $steam = false;
            if ($user->getSteam() != "") {
                $page = @file_get_contents("http://steamcommunity.com/id/" . $user->getSteam() . "/?xml=1");
                if ($page === false) return;
                $steam = new SimpleXMLElement($page, LIBXML_NOCDATA);                
            }
            
            //Game
            if ($steam && $steam->privacyState == "public" && $steam->onlineState == "in-game") {
                $profile["game"] = (string)$steam->inGameInfo->gameName;
                $profile["game_link"] = (string)$steam->inGameInfo->gameLink;
                $profile["game_icon"] = (string)$steam->inGameInfo->gameIcon;
                $profile["ingame"] = true;
            } else if ($user->getCurrentlyPlaying() != "") {
                $profile["ingame"] = true;
                $profile["game_link"] = "";
                $profile["game_icon"] = "";
                $profile["game"] = $user->getCurrentlyPlaying();
            } else {
                $profile["ingame"] = false;
                $profile["game_link"] = "";
                $profile["game_icon"] = "";
                $profile["game"] = "";
            }
            
            //Ticket
            $ticket = LanWebsite_Main::getDb()->query("SELECT * FROM `tickets` WHERE assigned_forum_id = '%s' AND lan_number = '%s' AND seat != ''", $user->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"))->fetch_assoc();
            if ($ticket) $profile["seat"] = $ticket["seat"];
            else $profile["seat"] = "";
           
            //Avatar
            if ($steam) $profile["avatar"] = (string)$steam->avatarFull;
            else $profile["avatar"] = $user->getAvatar();
            
            //Favourites
            $favs = array();
            $profile["favourite"] = "";
            foreach ($user->getFavouriteGames() as $fav) $favs[] = '<li>' . $fav . '</li>';
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
            
            //Raffle
            $active = LanWebsite_Main::getUserManager()->getActiveUser();
            $profile["raffle"] = "";
            if (LanWebsite_Main::getAuth()->isLoggedIn() && $active->getUserId() == $user->getUserId()) {
                $raffle = array();
                $res = LanWebsite_Main::getDb()->query("SELECT * FROM `raffle_tickets` WHERE user_id = '%s' AND lan_number = '%s'", $active->getUserId(), LanWebsite_Main::getSettings()->getSetting("lan_number"));
                while ($row = $res->fetch_assoc()) $raffle[] = '<li>' . $row["raffle_ticket_number"] . ' - ' . ucwords($row["reason"]) . '</li>';
                if (count($raffle) > 0) $profile["raffle"] = '<ul>' . implode("", $raffle) . '</ul>';
            }
            
            echo json_encode($profile);
            
        }
    
    }
    
?>
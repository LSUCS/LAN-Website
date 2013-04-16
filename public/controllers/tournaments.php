<?php

    class Tournaments_Controller extends LanWebsite_Controller {
    
        public function getInputFilters($action) {
            switch ($action) {
                case "tournament": return array("id" => array("int", "notnull")); break;
                case "leaveteam": return array("tournamentid" => array("int", "notnull")); break;
                case "jointeam": return array("teamid" => array("int", "notnull")); break;
            }
        }
        
        public function get_Index() {
            $data = array();
            
            //Load tournaments
            $ts = $this->getTournaments();
            $data["tournaments"] = array();
            foreach ($ts as $t) {
                $data["tournaments"][date('l', $t["start_time"])][] = $t;
            }
            
            //Get user tournaments
            $ts = $this->getTournamentsForUser(LanWebsite_Main::getUserManager()->getActiveUser()->getUserId());
            $data["usertournaments"] = array();
            foreach ($ts as $t) {
                if ($t["team_id"] > 0) $t["team"] = $this->getTeam($t["team_id"]);
                $data["usertournaments"][] = $t;
            }
            
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournaments");
            $tmpl->addTemplate('tournaments', $data);
			$tmpl->output();
        }
        
        public function get_Tournament($inputs) {
            $data = array();
            
            $id = $inputs["id"];
            
            //Load tournament
            $data["tournament"] = $this->getTournament($id);
            
            //Tournament not found?
            if (!$data["tournament"]) return LanWebsite_Main::pageNotFound();
            
            //Load format
            $data["format"] = $this->getFormat($data["tournament"]["format_id"]);
            
            //Load brackets
            $data["brackets"] = $this->getBrackets($id);
            
            //Load matches
            $data["matches"] = $this->getMatches($id);
            
            //Load teams
            $data["teams"] = $this->getTeams($id);
            
            //Load user signup
            $data["signup"] = $this->getUserSignup(LanWebsite_Main::getUserManager()->getActiveUser()->getUserId(), $id);
            
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournaments");
            $tmpl->addTemplate('tournament', $data);
			$tmpl->output();
        }
        
        public function get_Leaveteam($inputs) {
            LanWebsite_Main::getAuth()->requireLogin();
            
            //Check tournament ID
            $t = $this->getTournament($inputs["tournamentid"]);
            if (!$t) header("Location: " . LanWebsite_Main::buildUrl(false, "tournaments"));
            
            $user = LanWebsite_Main::getUserManager()->getActiveUser();
            
            //Check signup
            $signup = $this->getUserSignup($user->getUserId(), $inputs["tournamentid"]);
            if (!$signup || $signup["team_id"] < 1) header("Location: " . LanWebsite_Main::buildUrl(false, "tournaments", "tournament", array("id" => $inputs["tournamentid"])));
            
            //Get team
            $team = $this->getTeam($signup["team_id"]);
            
            //Leave team
            LanWebsite_Main::getDb()->query("UPDATE tournament_signups SET team_id = null WHERE tournament_id = '%s' AND user_id = '%s'", $inputs["tournamentid"], $user->getUserId());
            
            //If leader
            if ($team["leader_id"] = $user->getUserId()) {
                if (count($team["players"]) > 1) {
                    $repid = null;
                    foreach ($team["players"] as $p) {
                        if ($p["user_id"] != $user->getUserId()) {
                            $repid = $p["user_id"];
                            break;
                        }
                    }
                    LanWebsite_Main::getDb()->query("UPDATE tournament_teams SET leader_id = '%s' WHERE team_id = '%s'", $repid, $team["team_id"]);
                } else {
                    LanWebsite_Main::getDb()->query("DELETE FROM tournament_teams WHERE team_id = '%s'", $team["team_id"]);
                }
            }
            
            header("Location: " . LanWebsite_Main::buildUrl(false, "tournaments", "tournament", array("id" => $inputs["tournamentid"])));
        }
        
        public function get_Jointeam($inputs) {
            LanWebsite_Main::getAuth()->requireLogin();
            
            //Check team ID
            $team = $this->getTeam($inputs["teamid"]);
            if (!$team) header("Location: " . LanWebsite_Main::buildUrl(false, "tournaments"));
            
            $user = LanWebsite_Main::getUserManager()->getActiveUser();
            
            //Check signup
            $signup = $this->getUserSignup($user->getUserId(), $inputs["teamid"]);
            if ($signup && $signup["team_id"] > 0) header("Location: " . LanWebsite_Main::buildUrl(false, "tournaments", "tournament", array("id" => $team["tournament_id"])));
            
            //Update signup for user
            if ($signup) LanWebsite_Main::getDb()->query("UPDATE tournament_signups SET team_id = '%s' WHERE user_id = '%s' AND tournament_id = '%s'", $team["team_id"], $user->getUserId(), $team["tournament_id"]);
            else LanWebsite_Main::getDb()->query("INSERT INTO tournament_signups (user_id, tournament_id, team_id) VALUES ('%s', '%s', '%s')", $user->getUserId(), $team["tournament_id"], $team["team_id"]);
            
            //Update leader if necessary
            if ($team["leader_id"] < 1) LanWebsite_Main::getDb()->query("UPDATE tournament_teams SET leader_id = '%s' WHERE team_id = '%s'", $user->getUserId(), $team["team_id"]);
            
            header("Location: " . LanWebsite_Main::buildUrl(false, "tournaments", "tournament", array("id" => $team["tournament_id"])));
        }
        
        public function get_Bracket($inputs) {
            

        }
        
        //////////////////////////
        // TOURNAMENT FUNCTIONS //
        //////////////////////////
        
        private function getUserSignup($userId, $tournamentId) {
            return LanWebsite_Main::getDb()->query("SELECT * FROM tournament_signups WHERE tournament_id = '%s' AND user_id = '%s'", $tournamentId, $userId)->fetch_assoc();
        }
        
        private function getTeams($tournamentId) {
            $return = array();
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_teams WHERE tournament_id = '%s'", $tournamentId);
            while ($row = $res->fetch_assoc()) {
                $row["players"] = array();
                $res2 = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_signups WHERE tournament_id = '%s' AND team_id = '%s'", $tournamentId, $row["team_id"]);
                while ($row2 = $res2->fetch_assoc()) $row["players"][] = $row2;
                $return[] = $row;
            }
            return $return;
        }
        
        private function getMatches($tournamentId) {
            $return = array();
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_matches WHERE tournament_id = '%s'", $tournamentId);
            while ($row = $res->fetch_assoc()) $return[$row["round"]][] = $row;
            return $return;
        }
        
        private function getBrackets($tournamentId) {
            $return = array();
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_brackets WHERE tournament_id = '%s'", $tournamentId);
            while ($row = $res->fetch_assoc()) $return[] = $row;
            return $return;
        }
        
        private function getFormat($formatId) {
            return LanWebsite_Main::getDb()->query("SELECT * FROM tournament_formats WHERE format_id = '%s'", $formatId)->fetch_assoc();
        }
        
        private function getTournaments() {
            $return = array();
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_tournaments WHERE lan_number = '%s' ORDER BY start_time ASC", LanWebsite_Main::getSettings()->getSetting("lan_number"));
            while ($row = $res->fetch_assoc()) {
                $return[] = $row;
            }
            return $return;
        }
        
        private function getTournamentsForUser($userId) {
            $return = array();
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_tournaments INNER JOIN tournament_signups ON tournament_tournaments.tournament_id = tournament_signups.tournament_id WHERE tournament_signups.user_id = '%s' ORDER BY start_time ASC", $userId);
            while ($row = $res->fetch_assoc()) {
                $return[] = $row;
            }
            return $return;
        }
        
        private function getTournament($tournamentId) {
            return LanWebsite_Main::getDb()->query("SELECT * FROM tournament_tournaments WHERE tournament_id = '%s' AND lan_number = '%s'", $tournamentId, LanWebsite_Main::getSettings()->getSetting("lan_number"))->fetch_assoc();
        }
        
        private function getTeam($teamId) {
            $team = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_teams WHERE team_id = '%s'", $teamId)->fetch_assoc();
            if (!$team) return false;
            $team["players"] = array();
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM tournament_signups WHERE team_id = '%s'", $teamId);
            while ($row = $res->fetch_assoc()) $team["players"][] = $row;
            return $team;
        }
    
    }

?>
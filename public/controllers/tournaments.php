<?php

    class Tournaments_Controller extends LanWebsite_Controller {
        public function getInputFilters($action) {
            switch ($action) {
                case "view": return array("id" => array('notnull', 'int'));
            }
        }
        
        public function get_Index() {
            
            $db = LanWebsite_Main::getDb();
            
            //Get all tournaments
            $res = $db->query("SELECT ID FROM `tournament_tournaments` WHERE lan = '%s' ORDER BY start_time ASC", LanWebsite_Main::getSettings()->getSetting("lan_number"));
            
            $tournaments = array();
            while($row = $res->fetch_assoc()) {
                $tournaments[$row['id']] = Tournament_Main::t($row['id']);
            }
            
            //Get info about tournaments that the user has signed up to
            $res = $db->query("SELECT tournament_id, team_id FROM `tournament_signups WHERE user_id = '%s'", LanWebsite_Main::getAuth()->getActiveUserId());
            $userTournaments = array();
            while($row = $res->fetch_assoc()) {
                $row['tournament'] = Tournament_Main::t($row['tournament_id']);
                
                //The tournament has been deleted, but the signups haven't. This shouldn't happen, but clean up the db.
                if(!$row['tournament']) {
                    $db->query("DELETE FROM `tournament_signups` WHERE tournament_id = '%s'", $row['tournament_id']);
                }
                
                //If the signup was part of a team
                if($row['team_id'] !== 0) {
                    $team = Tournament_Main::team($row['team_id']);
                    if(!$team) continue;

                    $row['team'] = $team;
                }
                
                $userTournaments[] = $row;
            }
            
            
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournaments");
            $tmpl->addTemplate('tournaments2', array('tournaments' => $tournaments, 'usertournaments' => $userTournaments));
			$tmpl->output();
        }
    
        public function get_View($inputs) {
            if($this->isInvalid('id')) $this->error("Invalid Tournament ID");
            
            //Get the tournament
            $tournament = Tournament_Main::tournament($inputs['id']);
            if(!$tournament) $this->error(404);
            if(!$tournament->isVisibleToUser()) $this->error(403);
            
            $signups_list = $tournament->getSignups();

            $tmpl = LanWebsite_Main::getTemplateManager();
            $tmpl->setSubTitle($tournament['name']);
            $tmpl->addTemplate('tournament', array('tournament'=>$tournament, 'signup_list'=>$signups_list, 'brackets' => 0, 'matches' => 0));
            $tmpl->output();
        }
    }

?>
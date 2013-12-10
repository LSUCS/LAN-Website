<?php

class Tournaments_Controller extends LanWebsite_Controller {
    public function getInputFilters($action) {
        switch ($action) {
            case "view": return array("id" => array('notnull', 'int'));
            case "joinsolo": return array("tournament_id" => array('notnull', 'int'));
            case "joinasteam": return array("tournament_id" => array('notnull', 'int'), "team_id" => array('notnull', 'int'));
            case "leave": return array("tournament_id" => array('notnull', 'int'));
            case "clearalert": return array("alert_id" => 'notnull');
        }
    }
    
    public function get_Index2() {
        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->setSubTitle("Tournaments");
        $tmpl->addTemplate('tournaments');
        $tmpl->output();
    }
    
    public function get_Index() {
        
        $db = LanWebsite_Main::getDb();
        
        //Get all tournaments
        $res = $db->query("SELECT id FROM `tournament_tournaments` WHERE lan = '%s' ORDER BY start_time ASC", LanWebsite_Main::getSettings()->getSetting("lan_number"));
        
        $tournaments = array();
        while($row = $res->fetch_assoc()) {
            $tournaments[$row['id']] = Tournament_Main::tournament($row['id']);
        }
        
        //Get info about tournaments that the user has signed up to
        $res = $db->query("SELECT tournament_id, team_id FROM `tournament_signups` WHERE user_id = '%s'", LanWebsite_Main::getAuth()->getActiveUserId());
        $userTournaments = array();
        while($row = $res->fetch_assoc()) {
            $row['tournament'] = Tournament_Main::tournament($row['tournament_id']);
            
            //The tournament has been deleted, but the signups haven't. This shouldn't happen, but clean up the db.
            if(!$row['tournament']) {
                $db->query("DELETE FROM `tournament_signups` WHERE tournament_id = '%s'", $row['tournament_id']);
                continue;
            }
            
            //If the signup was part of a team
            if($row['team_id'] !== '0') {
                $team = Tournament_Main::team($row['team_id']);
                if(!$team) continue;

                $row['team'] = $team;
            }
            
            $userTournaments[] = $row;
        }
        
        
        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Tournaments <sup>(beta)</sup>");
        $tmpl->addTemplate('tournament_header');
        $tmpl->addTemplate('tournaments2', array('tournaments' => $tournaments, 'usertournaments' => $userTournaments));
		$tmpl->output();
    }

    public function get_View($inputs) {
        if($this->isInvalid('id')) $this->error("Invalid Tournament ID");
        
        //Get the tournament
        $tournament = Tournament_Main::tournament($inputs['id']);
        if(!$tournament) $this->error(404);
        if(!$tournament->isVisibleToUser()) $this->error(403);
        
        $tournament->redirectStatic();
        
        $signups_list = $tournament->getSignupList();
        $userTeams = Tournament_Main::getUserTeams();
        
        if($tournament->started) {
            $matches = $tournament->getMatches();
            $bracket = $tournament->getStructure();
        } else {
            $matches = $bracket = false;
        }

        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->setSubTitle($tournament->getName());
        $tmpl->addTemplate('tournament_header');
        $tmpl->addTemplate('tournament', array('tournament'=>$tournament, 'user_teams' => $userTeams, 'signup_list'=>$signups_list, 'bracket' => $bracket, 'matches' => $matches));
        $tmpl->output();
    }
    
    public function post_Joinsolo($inputs) {
        LanWebsite_Main::getAuth()->requireLogin();
        if($this->isInvalid('tournament_id')) $this->errorJson("Invalid Tournament");
        
        $Tournament = Tournament_Main::tournament($inputs["tournament_id"]);
        if(!$Tournament) $this->errorJson("Invalid Tournament (404)");
        
        if(!$Tournament->isVisibleToUser()) $this->errorJson(403);
        if(!$Tournament->signupsOpen()) $this->errorJson("Signups are closed for this tournament");
        if($Tournament->started) $this->errorJson("This tournament has already started!");
        
        $db = LanWebsite_Main::getDb();
        $r = $db->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'",
            $Tournament->ID, LanWebsite_Main::getAuth()->getActiveUserId());
        if($r->num_rows) $this->errorJson("You have already signed up to this tournament!");
        
        $db->query("INSERT INTO `tournament_signups` (tournament_id, user_id, team_id) VALUES ('%s', '%s', 0)",
            $Tournament->ID, LanWebsite_Main::getAuth()->getActiveUserId());
            
        //We could update here, but it feels like a lot of effort for what it's worth
        LanWebsite_Cache::delete('tournament', 'signuplist_' . $Tournament->ID);
    }
    
    public function post_Joinasteam($inputs) {
        LanWebsite_Main::getAuth()->requireLogin();
        if($this->isInvalid('tournament_id')) $this->errorJson("Invalid Tournament");
        if($this->isInvalid('team_id')) $this->errorJson("Invalid Team");
        
        $Tournament = Tournament_Main::tournament($inputs["tournament_id"]);
        if(!$Tournament) $this->errorJson("Invalid Tournament (404)");
        
        $Team = Tournament_Main::team($inputs["team_id"]);
        if(!$Team) $this->errorJson("Team does not exist");
        
        if($Tournament->getTeamSize() < 2) $this->errorJson("This is not a team tournament!");
        if(!$Tournament->isVisibleToUser()) $this->errorJson(403);
        if(!$Tournament->signupsOpen()) $this->errorJson("Signups are closed for this tournament");
        if($Tournament->started) $this->errorJson("This tournament has already started!");
        
        $db = LanWebsite_Main::getDb();
        $r = $db->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'",
            $Tournament->ID, LanWebsite_Main::getAuth()->getActiveUserId());
        if($r->num_rows) $this->errorJson("You have already signed up to this tournament!");
        
        $r = $db->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND team_id = '%s'",
            $Tournament->ID, $Team->ID);
        if($r->num_rows >= $Tournament->getTeamSize()) {
            $this->errorJson("This team is already at the maximum number of players");
        }
        
        $db->query("INSERT INTO `tournament_signups` (tournament_id, user_id, team_id) VALUES ('%s', '%s', '%s')",
            $Tournament->ID, LanWebsite_Main::getAuth()->getActiveUserId(), $Team->ID);
        
        //We could update here, but it feels like a lot of effort for what it's worth
        LanWebsite_Cache::delete('tournament', 'signuplist_' . $Tournament->ID);
    }
    
    public function post_Leave($inputs) {
        LanWebsite_Main::getAuth()->requireLogin();
        if($this->isInvalid('tournament_id')) $this->errorJson("Invalid Tournament");
        
        $db = LanWebsite_Main::getDb();
        
        $t = $db->query("SELECT visible, signups, started FROM `tournament_tournaments` WHERE id = '%s'", $inputs["tournament_id"]);
        if(!$t->num_rows) $this->errorJson("Invalid Tournament (404)");
        
        $r = $db->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'",
            $inputs['tournament_id'], LanWebsite_Main::getAuth()->getActiveUserId());
        if(!$r->num_rows) $this->errorJson("You are not signed up to this tournament!");
        
        $t = $t->fetch_assoc();
        if(!$t["visible"]) $this->errorJson(403);
        if(!$t["signups"]) $this->errorJson("Signups are closed for this tournament, so you cannot leave at this time.");
        if($t["started"]) $this->errorJson("This tournament has already started! Please contact a Committee member if you cannot compete.");
        
        $db->query("DELETE FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'",
            $inputs['tournament_id'], LanWebsite_Main::getAuth()->getActiveUserId());
            
        //We could update here, but it feels like a lot of effort for what it's worth
        LanWebsite_Cache::delete('tournament' ,'signuplist_' . $inputs['tournament_id']);
    }
    
    public function post_Clearalert($inputs) {
        LanWebsite_Main::getAuth()->requireLogin();
        if($this->isInvalid('alert_id')) $this->errorJson("Invalid Alert");
        
        if(array_key_exists('cleared_alerts', $_COOKIE)) $clearedAlerts = unserialize($_COOKIE['cleared_alerts']);
        else $clearedAlerts = array();
        $clearedAlerts[] = $inputs['alert_id'];
        setcookie('cleared_alerts', serialize($clearedAlerts), time() + 604800, '/', '.lsucs.org.uk');
        echo "cleared";
    }
}

?>
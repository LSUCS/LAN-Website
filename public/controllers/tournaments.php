<?php

class Tournaments_Controller extends LanWebsite_Controller {
    public function getInputFilters($action) {
        switch ($action) {
            case "view": return array("id" => array('notnull', 'int'));
            case "createteam": return array("name" => "notnull", "icon" => "url", "description" => "string");
            case "joinsolo": return array("tournament_id" => array('notnull', 'int'));
        }
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
        $tmpl->addTemplate('tournament', array('tournament'=>$tournament, 'user_teams' => $userTeams, 'signup_list'=>$signups_list, 'bracket' => $bracket, 'matches' => $matches));
        $tmpl->output();
    }
    
    public function get_Create() {
        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->setSubTitle("Create Team");
        $tmpl->addTemplate("tournament_create");
        $tmpl->output();
    }
    
    public function post_Createteam($inputs) {
        if($this->isInvalid('name')) $this->errorJson("Invalid Name");
        
        if(strlen($inputs["name"]) > 200 || strlen($inputs["name"]) < 3) $this->errorJson("Invalid Name" . strlen($inputs["name"]));
        
        $db = LanWebsite_Main::getDb();
        
        $r = $db->query("SELECT * FROM tournament_teams WHERE Name LIKE '%s'", $inputs["name"]);
        if($r->num_rows) $this->errorJson("A team with this name already exists!" . $r->num_rows);
        
        $r = $db->query("INSERT INTO tournament_teams (Name, Icon, Description) VALUES ('%s', '%s', '%s')", $inputs["name"], $inputs["icon"], $inputs["description"]);
        echo json_encode(array('id'=>$db->getLink()->insert_id));
    }
    
    public function post_Joinsolo($inputs) {
        if($this->isInvalid('tournament_id')) $this->errorJson("Invalid Tournament");
        
        $db = LanWebsite_Main::getDb();
        
        $r = $db->query("SELECT visible, signups, started FROM `tournament_tournaments` WHERE id = '%s'", $inputs["tournament_id"]);
        if(!$r->num_rows) $this->errorJson("Invalid Tournament (404)");
        
        $r = $r->fetch_assoc();
        if(!$r["visible"]) $this->errorJson(403);
        if(!$r["signups"]) $this->errorJson("Signups are closed for this tournament");
        if($r["started"]) $this->errorJson("This tournament has already started!");
        
        $r = $db->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'",
            $inputs['tournament_id'], LanWebsite_Main::getAuth()->getActiveUserId());
        if($r->num_rows) $this->errorJson("You have already signed up to this tournament!");
        
        $r = $db->query("INSERT INTO `tournament_signups` (tournament_id, user_id, team_id) VALUES ('%s', '%s', 0)",
            $inputs['tournament_id'], LanWebsite_Main::getAuth()->getActiveUserId());
    }
}

?>
<?php

class Tournaments_Controller extends LanWebsite_Controller {
    public function getInputFilters($action) {
        switch ($action) {
            case "add":
                return array(
                    "game" => "int",
                    "name" => "notnull",
                    "teamsize" => "int",
                    "description" => "notnull",
                    "start" => "int",
                    "end" => "int",
                    "signups-close" => "int",
                    "type" => "int",
                    "signups" => "bool",
                    "visible" => "bool"
                );
            case "editmatch":
                return array(
                    "id" => array("int","notnull"),
                    "score1" => "string",
                    "score2" => "string",
                    "winner" => "int"
                );
            case "delete":
            case "empty": 
            case "view":
                return array("tournament_id" => array("int","notnull"));
        }
    }
    
    public function get_index() {
        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Tournaments");
        $tmpl->addTemplate('tournaments');
        $tmpl->enablePlugin('timepicker');
		$tmpl->output();
    }
    
    public function get_Getentries() {
        $res = LanWebsite_Main::getDb()->query("SELECT ID from `tournament_tournaments` WHERE lan = '%s' ORDER BY game ASC",
            LanWebsite_Main::getSettings()->getSetting("lan_number"));
        
        $tournaments = array();
        while(list($ID) = $res->fetch_row()) {
            $tournaments[] = Tournament_Main::tournament($ID);
        }
        //echo json_encode($tournaments);
        
        $json = array();
        foreach($tournaments as $t) {
            $json[] = $t->jsonSerialize();
        }
        echo json_encode(array('tournaments'=>$json, 'games'=>Tournament_Main::getGames(), 'types'=>Tournament_Main::getTypes()));
    }
    
    public function post_Add($inputs) {
        //Validate
        if($this->isInvalid("game")) $this->errorJSON("You must supply a game!");
        if($this->isInvalid("teamsize")) $this->errorJSON("You must a team size!");
        if($this->isInvalid("type")) $this->errorJSON("You must supply a tournament type!");
        if($this->isInvalid("signups")) $this->errorJSON("You must supply a value for open signups!");
        if($this->isInvalid("visible")) $this->errorJSON("You must supply a value for visible!");
        if($this->isInvalid("start")) $this->errorJSON("You must supply a valid Start Time!");
        if($this->isInvalid("end")) $this->errorJSON("You must supply a valid End Time!");
        if($this->isInvalid("signups_close")) $this->errorJSON("You must supply a valid Signup Closing Time!");
        
        if(!in_array($inputs["game"], array_keys(Tournament_Main::getGames()))) $this->errorJson("Invalid Game: " . $inputs["game"]);
        if(!in_array($inputs["type"], array_keys(Tournament_Main::getTypes()))) $this->errorJson("Invalid Type: " . $inputs["type"]);
        
        if(empty($inputs["name"]) || strlen($inputs["name"]) > 100) $this->errorJson("Invalid Tournament Name");
        
        //Validate our dates
        foreach(array('start', 'end', 'signups_close') as $d) {
            $date = $inputs[$d];
            if($date > strtotime('next year')) $this->errorJson('You cannot create an event this far in the future! (' . $d . ')');
            if($date < strtotime('today')) $this->errorJson('You cannot create an event in the past! (' . $d . ')');
        }
        
        if($inputs["teamsize"] > 6 || $inputs["teamsize"] < 1) $this->errorJson("Invalid Team Size: " . $inputs["teamsize"]);
        
        //Let's insert
        LanWebsite_Main::getDb()->query("
            INSERT INTO `tournament_tournaments` (lan, game, name, team_size, type, description, start_time, end_time, signup_close, signups, visible) 
            VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            LanWebsite_Main::getSettings()->getSetting("lan_number"), $inputs["game"], $inputs["name"], $inputs["teamsize"], $inputs["type"], $inputs["description"],
                $inputs["start"], $inputs["end"], $inputs["signups_close"], $inputs["signups"], $inputs["visible"]);
        echo true;
    }
    
    public function post_Delete($inputs) {
        $tournament = Tournament_Main::tournament($inputs['tournament_id']);
        if(!$tournament) $this->error(404);
        echo $tournament->delete();
    }
    
    public function post_Empty($inputs) {
        $tournament = Tournament_Main::tournament($inputs['tournament_id']);
        if(!$tournament) $this->error(404);
        echo $tournament->empty();
    }
    
    public function get_View($inputs) {
        if ($this->isInvalid("tournament_id")) $this->error("Invalid Request");
        
        $tournament = Tournament_Main::tournament($inputs['id']);
        if(!$tournament) $this->error(404);
                  
        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("View Tournament");
        $tmpl->addTemplate('view', $tournament);
        
		$tmpl->output();
    }
    
    public function get_Matches($inputs) {
        if ($this->isInvalid("tournament_id")) $this->error("Invalid Request");
        
        $tournament = Tournament_Main::tournament($inputs['id'])->getStructure();
        if(!$tournament) $this->error(404);
        
        $matches = $tournament->getMatches(false);
       
        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Tournament Matches");
        $tmpl->addTemplate('tournament_matches', array('tournament'=>$tournament, 'matches'=>$matches));        
		$tmpl->output();
    }
    
    public function post_Start($inputs) {
        if ($this->isInvalid("tournament_id")) $this->jsonError("Invalid Request");
        
        $tournament = Tournament_Main::tournament($inputs['id'])->getStructure();
        
        if($tournament->started) $this->jsonError("This tournament has already started");
        
        //Close signups and make it display as started
        //In theory creating matches could take a while, so we wanna close signups first
        LanWebsite_Main::getDb()->query("UPDATE `tournament_tournaments` SET started=1, signups=0 WHERE id = '%s'", $tournament->ID);
        LanWebsite_Cache::delete('tournament', 'tournament_' . $tournament->ID);
        echo $tournament->createMatches();
    }
    
    public function get_Signups($inputs) {
        $tournament = Tournament_Main::tournament($inputs['tournament_id']);
        if(!$tournament) $this->error(404);

        //echo json_encode($tournament->getSignupList());

        $json = array();
        foreach($tournament->getSignupList() as $t) {
            $json[] = $t->jsonSerialize();
        }
        echo json_encode($json);
    }
    
    public function post_Editmatch($inputs) {
        if($this->isInvalid("id")) $this->jsonError("Invalid Match");
        if($this->isInvalid("score1")) $this->jsonError("Invalid Score 1");
        if($this->isInvalid("score2")) $this->jsonError("Invalid Score 2");
        if($this->isInvalid("winner")) $this->jsonError("Invalid Winner");
        
        $played = ($inputs["winner"]) ? 1 : 0;
        LanWebsite_Main::getDb()->query("UPDATE `tournament_matches` SET played_bool = '%s' score1 = '%s', score2 = '%s', winner='%s' WHERE id = '%s'",
            $played, $inputs["score1"], $inputs["score2"], $inputs["winner"], $inputs["id"]);
        LanWebsite_Cache::delete('tournament', 'match_' . $inputs["id"]);
    }
}
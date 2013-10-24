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
                    "signups_close" => "int",
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
            case "start":
            case "view": 
            case "matches":
            case "collate":
                return array("id" => array("int","notnull"));
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
        if ($this->isInvalid("id")) $this->errorJson("Invalid Request");
        $tournament = Tournament_Main::tournament($inputs['id']);
        if(!$tournament) $this->error(404);
        echo $tournament->delete();
    }
    
    public function post_Empty($inputs) {
        if ($this->isInvalid("id")) $this->errorJson("Invalid Request");
        $tournament = Tournament_Main::tournament($inputs['id']);
        if(!$tournament) $this->error(404);
        echo $tournament->empty();
    }
    
    public function get_Edit($inputs) {
        if ($this->isInvalid("id")) $this->errorJson("Invalid Request");
        
        $tournament = Tournament_Main::tournament($inputs['id']);
        if(!$tournament) $this->errorJson(404);
        
		$tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Edit Tournament");
        $tmpl->addTemplate('tournament_matches', array('tournament'=>$tournament, 'matches'=>$matches));        
		$tmpl->output();
    }
    
    public function get_Matches($inputs) {
        if ($this->isInvalid("id")) $this->error("Invalid Request");
        
        $tournament = Tournament_Main::tournament($inputs['id'])->getStructure();
        if(!$tournament) $this->error(404);
        
        $matches = $tournament->getMatches(false);
       
        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Tournament Matches");
        $tmpl->addTemplate('tournament_matches', array('tournament'=>$tournament, 'matches'=>$matches));        
		$tmpl->output();
    }
    
    public function post_Start($inputs) {
        if ($this->isInvalid("id")) $this->errorJson("Invalid Request");
                
        $tournament = Tournament_Main::tournament($inputs['id'], false)->getStructure();
        if(!$tournament) $this->errorJson("Invalid Tournament");
        
        if($tournament->started) $this->errorJson("This tournament has already started");
        
        //Close signups and make it display as started
        //In theory creating matches could take a while, so we wanna close signups first
        LanWebsite_Main::getDb()->query("UPDATE `tournament_tournaments` SET started=1, signups=0 WHERE id = '%s'", $tournament->ID);
        LanWebsite_Cache::delete('tournament', 'tournament_' . $tournament->ID);
        
        $output = $tournament->createMatches();
        if($output !== true) {
            $this->errorJson("A fatal error occured: " . $output);
        }
    }
    
    public function get_View($inputs) {
        if ($this->isInvalid("id")) $this->error("Invalid Request");
        
        $tournament = Tournament_Main::tournament($inputs['id']);
        if(!$tournament) $this->error(404);

        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Tournament Signups");
        $tmpl->addTemplate('tournament_signups', array('tournament'=>$tournament, 'signups'=>$tournament->getSignupList(false)));        
		$tmpl->output();
    }
    
    public function get_Collate($inputs) {
        if ($this->isInvalid("id")) $this->error("Invalid Request");
        
        $db = Tournament_Main::getDb();
        $tournament = Tournament_Main::tournament($inputs['id']);
        if(!$tournament) $this->error(404);
        
        if(!$tournament->signupsOpen()) $this->error("The signups must be closed before you can collate teams");
        $teamSize = $tournament->getTeamSize();
        if($teamSize < 2) $this->error("This is not a team tournament!");
        
        $signups = $tournament->getSignups(false);
        $tSignups = count($signups);
        
        if($tSignups % $teamSize !== 0) { 
            $this->error("This tournament has team sizes of " . $teamSize . ", but there are " . $tSignups . " signups. " . $tSignups . " does not divide by " . $teamSize);
        }
        
        
        
        //Dry run, collect and sort data about the teams and how many members they have in the tournament, and how many solo players we have
        $teams = array();
        $noTeam = array();
        foreach($signups as $player) {
            //Check if the user's team ID is non (unassigned) or they aren't paired with a team (second clause shouldn't happen)
            if($player['team_id'] == 0 || !array_key_exists('team', $player)) {
                $noTeam[] = $player;
            } else {
                //Checks if the team exists in the main array
                if(!array_key_exists($player['team']->ID, $teams)) {
                    $teams[$player['team']->ID] = array();
                }
                $teams[$player['team']->ID][] = $player;
            }
        }
        
        $noTeamRequired = 0;
        foreach($teams as $teamPlayers) {
            $numPlayers = count($teamPlayers);
            if($numPlayers > $teamSize) $this->error("FATAL: Team " . $teamPlayers[0]['team']->getName() . " has more than " . $teamSize . " players!");
            if($numPlayers == 1) {
                $noTeam[] = $teamPlayers[0];
            } else {
                if($numPlayers !== $teamSize) {
                    $neededPlayers = $teamSize - $numPlayers;
                    $noTeamRequired += $neededPlayers;
                } //Else there is no problem, the team has enough players
            }
        }
        
        //Number of players without a team
        $numNoTeam = count($noTeam);
        
        //Allocate unassigned players to teams where possible
        //Make unassigned players into teams
        if($noTeamRequired < $numNoTeam) { //There are less people not in a team than teams needing players. Gonna have to split teams 
            
            //Annoying cases where there aren't enough solo players. Try to pair teams
            $teamNeededPlayers = array();
            $teamPairs = array();
            foreach($teams as $teamPlayers) {
                if(count($teamPlayers) !== $teamSize) {      
                    //Does a team need the amount of players that this team has?
                    if($key = array_search(count($teamPlayers), $teamNeededPlayers)) {
                        unset($teamNeededPlayers[$key]);
                        $teamPairs[] = array($key, $teamPlayers[0]['team']->ID);
                    } else {
                        $neededPlayers = $teamSize - $numPlayers;
                        $teamNeededPlayers[$teamPlayers[0]['team']->ID] = $neededPlayers;
                    }
                }
            }
            
            foreach($teamPairs as $pair) {
                
            }
            
            //TODO: Add additional logic here for left over players
            
        } else { 
            //Assign each team the number of unassigned players that it needs
            foreach($teams as $teamPlayers) {
                if(count($teamPlayers) !== $teamSize) {
                    $neededPlayers = $teamSize - $numPlayers;
                    for($x = 0; $x < $neededPlayers; $x++) {
                        $player = array_shift($noTeam);
                        $db->query("UPDATE `tournament_signups` SET team_id = '%s', team_temporary = 1 WHERE tournament_id = '%s' AND user_id = '%s'", $teamPlayers[0]['team']->ID, $tournament->ID, $player->getId());
                    }
                }
            }
        
            //We still have players unassigned to teams
            if(count($noTeam) !== 0) {
                if(count($noTeam) % $teamSize) {//Oh oh, this isn't good
                    $this->error("FATAL: Remeinder of players (" . count($noTeam) . ") isn't a multiple of the team size: " . $teamSize);
                }
                $teamsToCreate = count($noTeam) / $teamSize;
                for($x = 0; $x < $teamsToCreate; $x++) {
                    $firstPlayer = array_shift(array_values($noTeam));
                    
                    $teamName = $firstPlayer->getUsername();
                    if(substr($teamName, -1) == 's') {
                        $teamName += "' Team";
                    } else {
                        $teamName += "'s Team";
                    }
                    
                    $res = $db->query("INSERT INTO `tournament_teams` (Name, Description, Temporary) VALUES ('%s', 'This team was create automatically by the tournament system', 1)", $teamName);
                    $id = $res->inserted_id;
                    
                    for($y = 0; $y < $teamSize; $y++) {
                        $player = array_shift($noTeam);
                        $permission = ($player == $firstPlayer) ? 1 : 0;
                        $db->query("INSERT INTO `tournament_teams_members` (team_id, user_id, permission) VALUES ('%s', '%s', '%s')", $id, $player->getId(), $permission);
                    }
                }
            }
            
        }
        
    }
    
    public function post_Editmatch($inputs) {
        if($this->isInvalid("id")) $this->errorJson("Invalid Match");
        if($this->isInvalid("score1")) $this->errorJson("Invalid Score 1");
        if($this->isInvalid("score2")) $this->errorJson("Invalid Score 2");
        if($this->isInvalid("winner")) $this->errorJson("Invalid Winner");
        
        $db = LanWebsite_Main::getDb();
        //Check the match exists, and get the tournament id for later
        $res = $db->query("SELECT tournament_id FROM `tournament_matches` WHERE id = '%s'", $inputs["id"]);
        if($res->num_rows < 1) {
            $this->errorJson("Invalid Match (404)");
        }
        list($tournament_id) = $res->fetch_row();
        
        $played = ($inputs["winner"]) ? 1 : 0;
        
        $db->query("UPDATE `tournament_matches` SET played_bool = '%s', score1 = '%s', score2 = '%s', winner='%s' WHERE id = '%s'",
            $played, $inputs["score1"], $inputs["score2"], $inputs["winner"], $inputs["id"]);
        LanWebsite_Cache::delete('tournament', 'match_' . $inputs["id"]);
        
        
        $Structure = Tournament_Main::tournament($tournament_id)->getStructure();
        $Structure->updateMatch($inputs["id"]);
    }
}
<?php

    class Tournaments_Controller extends LanWebsite_Controller {
        public function getInputFilters($action) {
            switch ($action) {
                case "add": return array("game" => "int", "teamsize" => "int", "type" => "int", "signups" => "bool", "visible" => "bool"); break;
            }
        }
        
        public function get_index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournaments");
            $tmpl->addTemplate('tournaments');
			$tmpl->output();
        }
        
        public function get_Getentries() {
            $res = LanWebsite_Main::getDb()->query("SELECT id, game, team_size, type, signups, visible FROM `tournament_tournaments`
                WHERE lan = '%s' ORDER BY game ASC", LanWebsite_Main::getSettings()->getSetting("lan_number"));
            $tournaments = array();
            while($row = $res->fetch_assoc()) {
                $row['type'] = LanWebsite_Tournaments::getType($row['type']);
                $tournaments[] = $row;
            }
            echo json_encode($tournaments);
        }
        
        public function post_Add($inputs) {
            //Validate
            if ($this->isInvalid("game")) $this->errorJSON("You must supply a game!");
            if ($this->isInvalid("teamsize")) $this->errorJSON("You must a team size!");
            if ($this->isInvalid("type")) $this->errorJSON("You must supply a tournament type!");
            if ($this->isInvalid("signups")) $this->errorJSON("You must supply a value for open signups!");
            if ($this->isInvalid("visible")) $this->errorJSON("You must supply a value for visible!");
            
            if(!in_array($inputs["game"], array_keys(LanWebsite_Tournaments::getGames()))) $this->errorJson("Invalid Game: " . $inputs["game"]);
            if(!in_array($inputs["type"], array_keys(LanWebsite_Tournaments::getTypes()))) $this->errorJson("Invalid Type: " . $inputs["type"]);
            
            if($inputs["teamsize"] > 6 || $inputs["teamsize"] < 1) $this->errorJson("Invalid Team Size: " . $inputs["teamsize"]);
            
            //Let's insert
            LanWebsite_Main::getDb()->query("INSERT INTO `tournament_tournaments` (game, team_size, type, signups, visible) VALUES ('%s', '%s', '%s', '%s', '%s')", $inputs["game"], $inputs["teamsize"], $inputs["type"], $inputs["signups"], $inputs["visible"]);
            echo true;
        }
    }
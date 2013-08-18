<?php

    class Tournaments_Controller extends LanWebsite_Controller {
        public function getInputFilters($action) {
            switch ($action) {
                case "add":
                    return array(
                        "game" => "int",
                        //"name" => "string",
                        "teamsize" => "int",
                        //"description" => "string",
                        "start" => "int",
                        "end" => "int",
                        "signups-close" => "int",
                        "type" => "int",
                        "signups" => "bool",
                        "visible" => "bool"
                    );
                case "delete":
                case "empty": 
                case "view":
                    return array("tournament_id" => array("int","notnull"));
            }
        }
        
        //Checks if a tournament exists. Gives an error if it doesn't. JSON if required
        //Can also return the object if it's needed again
        private function checkExists($ID, $JSON, $ret = false) {
            $eMsg = "This tournament does not exist!";
            
            $t = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_tournaments` WHERE id = '%s'", $ID);
            if(!$t->num_rows) {
                if($JSON) $this->errorJSON($eMsg);
                else $this->error($eMsg);
            }
            
            if($ret) return $t;
            else return true;
        }
        
        public function get_index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournaments");
            $tmpl->addTemplate('tournaments');
            $tmpl->enablePlugin('timepicker');
			$tmpl->output();
        }
        
        public function get_Getentries() {
            $res = LanWebsite_Main::getDb()->query("SELECT t.*, COUNT(s.user_id) AS current_signups 
                FROM `tournament_tournaments` AS t
                LEFT JOIN `tournament_signups` AS s
                    ON t.id = s.tournament_id
                WHERE lan = '%s'
                GROUP BY t.id
                ORDER BY game ASC", LanWebsite_Main::getSettings()->getSetting("lan_number"));
            $tournaments = array();
            while($row = $res->fetch_assoc()) {
                $row['type'] = LanWebsite_Tournaments::getType($row['type']);
                $row['game'] = LanWebsite_Tournaments::getGame($row['game']);
                
                $row['start_time'] = date('Y-m-d H:i:s', $row['start_time']);
                $row['end_time'] = date('Y-m-d H:i:s', $row['end_time']);
                $row['signup_end'] = date('Y-m-d H:i:s', $row['signup_end']);
                
                $tournaments[] = $row;
            }
            echo json_encode($tournaments);
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
            if($this->isInvalid("signups-close")) $this->errorJSON("You must supply a valid Signup Closing Time!");
            
            if(!in_array($inputs["game"], array_keys(LanWebsite_Tournaments::getGames()))) $this->errorJson("Invalid Game: " . $inputs["game"]);
            if(!in_array($inputs["type"], array_keys(LanWebsite_Tournaments::getTypes()))) $this->errorJson("Invalid Type: " . $inputs["type"]);
            
            if(empty($inputs["name"]) || strlen($inputs["name"]) > 100) $this->errorJson("Invalid Tournament Name");
            
            //Validate our dates
            foreach(array('start', 'end', 'signups-close') as $d) {
                $date = $inputs[$d];
                if($date > strtotime('next year')) $this->errorJson('You cannot create an event this far in the future! (' . $d . ')');
                if($date < strtotime('today')) $this->errorJson('You cannot create an event in the past! (' . $d . ')');
            }
            
            if($inputs["teamsize"] > 6 || $inputs["teamsize"] < 1) $this->errorJson("Invalid Team Size: " . $inputs["teamsize"]);
            
            //Let's insert
            LanWebsite_Main::getDb()->query("INSERT INTO `tournament_tournaments` (lan, game, name, team_size, type, description, start_time, end_time, signup_close, signups, visible) 
                                            VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                                            LanWebsite_Main::getSettings()->getSetting("lan_number"), $inputs["game"], $inputs["name"], $inputs["teamsize"], $inputs["type"], $inputs["description"],
                                                $inputs["start"], $inputs["end"], $inputs["signups-close"], $inputs["signups"], $inputs["visible"]);
            echo true;
        }
        
        public function post_Delete($inputs) {
            if ($this->isInvalid("tournament_id")) $this->errorJSON("Invalid Request");
            
            $this->checkExists($inputs["tournament_id"], true);
            
            LanWebsite_Main::getDb()->query("DELETE FROM `tournament_tournaments` WHERE id = '%s'", $inputs["tournament_id"]);
            LanWebsite_Main::getDb()->query("DELETE FROM `tournament_signups` WHERE tournament_id = '%s'", $inputs["tournament_id"]);
            echo true;
        }
        
        public function post_Empty($inputs) {
            if ($this->isInvalid("tournament_id")) $this->errorJSON("Invalid Request");

            $this->checkExists($inputs["tournament_id"], true);
            
            LanWebsite_Main::getDb()->query("DELETE FROM `tournament_signups` WHERE tournament_id = '%s'", $inputs["tournament_id"]);
            echo true;
        }
        
        public function get_View($inputs) {
            if ($this->isInvalid("tournament_id")) $this->error("Invalid Request");
            
            $t = $this->checkExists($inputs["tournament_id"], false, true);
                        
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("View Tournament");
            $tmpl->addTemplate('view', $t->fetch_assoc());
            
			$tmpl->output();
        }
        
        public function get_Signups($inputs) {
            $this->checkExists($inputs["tournament_id"], true);
            
            $s = LanWebsite_Main::getDb()->query("SELECT user_id, team_id FROM `tournament_signups` WHERE tournament_id = '%s' ORDER BY signed_up", $inputs["tournament_id"]);
            $signups = array();
            while($row = $s->fetch_assoc()) {
                $user = LanWebsite_Main::getUserManager()->getUserById($row["user_id"]);
                $row["username"] = $user->getUsername();
                $row["signed_up"] = date("D jS M g:iA", strtotime($row["signed_up"]));
                $signups[] = $row;
            }
            echo json_encode($signups);
        }
    }
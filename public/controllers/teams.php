<?php

class Teams_Controller extends LanWebsite_Controller {
    public function getInputFilters($action) {
        switch ($action) {
            case "createteam": return array("name" => "notnull", "icon" => "url", "description" => "string");
            case "invite": return array('teamid'=>array('notnull', 'int'), 'username'=>"string");
            case "view": return array('teamid' => array("notnull", "int"));
            case "inviterespond": return array('teamid' => array("notnull", "int"), 'accept' => array("notnull", "bool"));
        }
    }
    
    public function get_Index() {
        $db = LanWebsite_Main::getDb();
        
        $teams = array();
        
        //Get all teams
        $res = $db->query("SELECT id FROM `tournament_teams` ORDER BY Name ASC");
        
        while($row = $res->fetch_assoc()) {
            $teams[] = Tournament_Main::team($row['id']);
        }
        
        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Teams");
        $tmpl->addTemplate('tournament_header');
        $tmpl->addTemplate('teams', array('teams' => $teams, 'invites' => array(), 'userteams' => false));
		$tmpl->output();
    }
    
    public function get_Self() {
        LanWebsite_Main::getAuth()->requireLogin();
        $db = LanWebsite_Main::getDb();
        
        $invites = array();
        $teams = array();
        
        $res = $db->query("SELECT id FROM `tournament_teams` AS t
                            LEFT JOIN `tournament_teams_members` AS m
                                ON t.ID = m.team_id
                            WHERE m.user_id = '%s' 
                            ORDER BY Name ASC",
                    LanWebsite_Main::getAuth()->getActiveUserId());
        while($row = $res->fetch_assoc()) {
            $teams[] = Tournament_Main::team($row['id']);
        }
        
        $inv = $db->query("SELECT team_id, date FROM `tournament_teams_invites` WHERE user_id = '%s' AND status = 1", LanWebsite_Main::getAuth()->getActiveUserId());
        while($row = $inv->fetch_assoc()) {
            $invites[] = array('team' => Tournament_Main::team($row['team_id']), 'date' => $row['date']);
        }
        
        $tmpl = LanWebsite_Main::getTemplateManager();
		$tmpl->setSubTitle("Teams");
        $tmpl->addTemplate('tournament_header');
        $tmpl->addTemplate('teams', array('teams' => $teams, 'invites' => $invites, 'userteams' => true));
		$tmpl->output();
    }
    
    public function get_View($inputs) {
        //if($this->isInvalid('teamid')) $this->error("Invalid Team ID");
        
        //Get the team
        $team = Tournament_Main::team($inputs['teamid']);
        if(!$team) $this->error(404);
        
        $inv = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_teams_invites` WHERE user_id = '%s' AND status = 1", LanWebsite_Main::getAuth()->getActiveUserId());
        if($inv->num_rows) {
            $invite = true;
        } else {
            $invite = false;
        }
        
        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->setSubTitle($team->getName());
        $tmpl->addTemplate('tournament_header');
        $tmpl->addTemplate('team_view', array('team' => $team, 'invite' => $invite));
        $tmpl->output();
    }
    
    public function get_Create() {
        LanWebsite_Main::getAuth()->requireLogin();
        
        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->setSubTitle("Create Team");
        $tmpl->addTemplate('tournament_header');
        $tmpl->addTemplate("team_create");
        $tmpl->output();
    }
    
    public function post_Createteam($inputs) {
        LanWebsite_Main::getAuth()->requireLogin();
        if($this->isInvalid('name')) $this->errorJson("Invalid Name");
        
        if(strlen($inputs["name"]) > 200 || strlen($inputs["name"]) < 3) $this->errorJson("Invalid Name" . strlen($inputs["name"]));
        
        $db = LanWebsite_Main::getDb();
        
        $r = $db->query("SELECT * FROM tournament_teams WHERE Name LIKE '%s'", $inputs["name"]);
        if($r->num_rows) $this->errorJson("A team with this name already exists!");
        
        $r = $db->query("INSERT INTO tournament_teams (Name, Icon, Description) VALUES ('%s', '%s', '%s')", $inputs["name"], $inputs["icon"], $inputs["description"]);
        $teamid = $db->getLink()->insert_id;
        
        $r = $db->query("INSERT INTO tournament_teams_members (team_id, user_id, permission) VALUES ('%s', '%s', 1)", $teamid, LanWebsite_Main::getAuth()->getActiveUserId());
        
        echo json_encode(array('teamid'=>$teamid));
    }
    
    public function post_Invite($inputs) {
        LanWebsite_Main::getAuth()->requireLogin();
        if($this->isInvalid('username')) $this->errorJson("Invalid Username");
        if($this->isInvalid('teamid')) $this->errorJson("Invalid Team");
        
        $Team = Tournament_Main::team($inputs['teamid']);
        if(!$Team->isTeamMember()) $this->errorJson("You are not in this team! You cannot invite members.");
        
        $User = LanWebsite_Main::getUserManager()->getUserByName($inputs['username']);
        if(!$User) $this->errorJson("User Does Not Exist!");
        
        $db = LanWebsite_Main::getDb();
        
        $res = $db->query("SELECT * FROM `tournament_teams_invites` WHERE team_id = '%s' AND user_id = '%s'", $inputs['teamid'], $User->getUserId());
        if($res->num_rows) $this->errorJson("This user has already been invited to join this team!");
        
        if(array_key_exists($User->getUserId(), $Team->getMembers())) $this->errorJson("This user is already a member of this team!");
        
        $db->query("INSERT INTO `tournament_teams_invites` (team_id, user_id, status, date) VALUES ('%s', '%s', 1, '%s')", $inputs['teamid'], $User->getUserId(), time());
        $db->query("INSERT INTO `tournament_alerts` (user_id, level, message, link) VALUES ('%s', '%s', '%s', '%s')",
            $User->getUserId(), 'ALERT_MESSAGE', "You have been invited to join " . $Team->getName(), LanWebsite_Main::buildUrl(false, 'teams', 'view', array('teamid'=>$Team->ID)));
        echo true;
    }
    
    public function post_Inviterespond($inputs) {
        LanWebsite_Main::getAuth()->requireLogin();
        if($this->isInvalid('teamid')) $this->errorJson("Invalid Team ID");
        if($this->isInvalid('accept')) $this->errorJson("Invalid action");
        
        $Team = Tournament_Main::team($inputs['teamid']);
        if(!$Team) $this->errorJson("Invalid Team");
        
        $db = LanWebsite_Main::getDb();
        
        $inv = $db->query("SELECT * FROM `tournament_teams_invites` WHERE user_id = '%s' AND team_id = '%s' AND status = 1", LanWebsite_Main::getAuth()->getActiveUserId(), $Team->ID);
        if($inv->num_rows < 1) $this->errorJson("No invite found");
        
        $res = $db->query("SELECT * FROM `tournament_teams_members` WHERE user_id = '%s' AND team_id = '%s'", LanWebsite_Main::getAuth()->getActiveUserId(), $Team->ID);
        if($res->num_rows < 1) {
            if($inputs["accept"]) {
                $db->query("INSERT INTO `tournament_teams_members` (team_id, user_id) VALUES ('%s', '%s')", $Team->ID, LanWebsite_Main::getAuth()->getActiveUserId());
                LanWebsite_Cache::delete("tournament", 'team_members_' . $Team->ID);
            }
        }
        $db->query("DELETE FROM `tournament_teams_invites` WHERE user_id = '%s' AND team_id = '%s' AND status = 1", LanWebsite_Main::getAuth()->getActiveUserId(), $Team->ID);
        $db->query("DELETE FROM `tournament_alerts` WHERE user_id = '%s' AND link = '%s'", LanWebsite_Main::getAuth()->getActiveUserId(), LanWebsite_Main::buildUrl(false, 'teams', 'self', array('teamid'=>$Team->ID)));
    }
    
}
<?php

class Tournament_Team {//implements jsonSerializable{
    public $ID = null;
    private $name;
    private $members;
    private $description;
    
    function __construct($ID) {
        if(!LanWebsite_Cache::get('tournament', 'team_' . $ID, $r)) {
            $r = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_teams` WHERE id = '%s'", $ID)->fetch_assoc();
            if(!$r) return false;
            
            LanWebsite_Cache::set('tournament', 'team_' . $ID, $r);
        }
        
        $this->ID =     (int) $ID;
        $this->name =   (string) $r['Name'];
        $this->loadMembers();
    }
    
    function jsonSerialize() {
        return array(
            'id' =>         $this->ID,
            'name' =>       $this->name,
            'members' =>    $this->members
        );
    }
    
    //Getter Functions
    public function getName() {
        if(is_null($this->ID)) return false;
        return $this->name;
    }
    
    public function getMembers() {
        if(is_null($this->ID)) return array();
        return $this->members;
    }
    
    public function getDescription() {
        if(is_null($this->ID)) return false;
        return $this->description;
    }
    
    //Methods
    public function isTeamMember($user = false) {
        if(!$user) $user = LanWebsite_Main::getAuth()->getActiveUserId();
        return array_key_exists($user, $this->members);
    }
    
    public function isCaptain() {
        $user = LanWebsite_Main::getAuth()->getActiveUserId();
        return (array_key_exists($user, $this->members) && $this->members[$user]['permission'] > 0);
    }
    
    public function getCaptain() {
        foreach($this->members as $m) {
            if($m['permission'] > 0) return $m['user'];
        }
    }
    
    private function loadMembers() {
        if(!LanWebsite_Cache::get('tournament', 'team_members_' . $this->ID, $this->members)) {
            $r = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_teams_members` WHERE team_id = '%s'", $this->ID);
            
            $this->members = array();
            if($r->num_rows) {
                while($row = $r->fetch_assoc()) {
                    $this->members[$row['user_id']] = array('user' => LanWebsite_Main::getUserManager()->getUserById($row['user_id']), 'permission' => $row['permission']);
                }
            }
            LanWebsite_Cache::set('tournament', 'team_members_' . $this->ID, $this->members);
        }
    }
    
    public function deleteMember($ID) {
        //Delete Member
        LanWebsite_Main::getDb()->query("DELETE FROM `tournament_teams_members` WHERE user_id = '%s'", $ID);
        
        //Update Cache
        unset($this->members[$ID]);
        LanWebsite_Cache::set('tournament', 'team_members_' . $this->ID, $this->members);
        
        return true;
    }
    
    public function emptyMembers() {
        //Delete all members
        LanWebsite_Main::getDb()->query("DELETE FROM `tournament_teams_members` WHERE team_id = '%s'", $this->ID);
        
        //Update Cache
        $this->members = array();
        LanWebsite_Cache::set('tournament', 'team_members_' . $this->ID, $this->members);
        
        return true;
    }
    
    public function delete() {
        //Remove all member entries
        $this->emptyMembers();
        LanWebsite_Main::getDb()->query("DELETE FROM `tournament_teams` WHERE id = '%s'", $this->ID);
        LanWebsite_Cache::delete('tournament', 'team_' . $this->ID);
        return true;
    }
}
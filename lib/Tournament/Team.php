<?php

class Tournament_Team {//implements jsonSerializable{
    public $ID = null;
    private $name;
    private $members;
    
    function __construct($ID) {
        if(!LanWebsite_Cache::get('tournament', 'team_' . $ID, $r)) {
            $r = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_teams` WHERE id = '%s'", $ID)->fetch_assoc();
            if(!$r) return false;
            
            LanWebsite_Cache::set('tournament', 'team_' . $ID, $r);
        }
        
        $this->ID =     (int) $ID;
        $this->name =   (string) $r['name'];
        $this->members = $this->loadMembers();
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
        if(is_null($this->ID)) return false;
        return $this->members;
    }
    
    //Methods
    public function isTeamMember($user = false) {
        if(!$user) $user = LanWebsite_Main::getAuth()->getActiveUserId();
        return array_key_exists($user, $this->members);
    }
    
    private function loadMembers() {
        if(!LanWebsite_Cache::get('tournament', 'team_members_' . $this->ID, $this->members)) {
            $r = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_teams_members` WHERE team_id = '%s'", $this->ID);
            
            if($r->num_rows) {
                $this->members = array();
                while($row = $r->fetch_assoc()) {
                    $this->members[$row['user_id']] = LanWebsite_Main::getAuth()->getUserById($Row['user_id']);
                }
                LanWebsite_Cache::set('tournament', 'team_members_' . $this->ID, $this->members);
            }
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
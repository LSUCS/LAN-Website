<?php

class Tournament_Tournament implements JsonSerializable{
    public $ID = null;
    private $type;
    private $game;
    private $name;
    private $lan;
    private $team_size;
    private $signups_open;
    private $visible;
    private $signups_close;
    private $start_time;
    private $end_time;
    private $description;
    
    function __construct($ID) {
        if(!LanWebsite_Cache::get('tournament', 'team_' . $ID, $r)) {
            $r = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_tournaments` WHERE id = '%s'". $ID)->fecth_assoc();
            if(!$r) return false;
            
            LanWebsite_Cache::set('tournament', 'team_' . $ID, $r);
        }
        
        $this->ID = (int) $ID;
        $this->type = (int) $r['type'];
        $this->game = (int) $r['game'];
        $this->name = (string) $r['name'];
        $this->lan = (float) $r['lan'];
        $this->team_size = (int) $r['team_size'];
        $this->signups_open = (bool) $r['signups'];
        $this->visible = (bool) $r['visible'];
        $this->signups_close = (int) $r['signups_close'];
        $this->start_time = (int) $r['start_time'];
        $this->end_time = (int) $r['end_time'];
        $this->description = (string) $r['description'];
    }
    
    function jsonSerialize() {
        return array(
            'id' => $this->ID,
            'type' => $this->type,
            'game' => $this->game,
            'name' => $this->name,
            'lan' => $this->lan,
            'team_size' => $this->team_size,
            'signups' => $this->signups_open,
            'visible' => $this->visible,
            'signups_close' => $this->signups_close,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'description' => $this->description
        );
    }
    
    //Utility
    private function niceDate($date) {
        return date('g:ia D', $date);
    }

    //Getter Functions
    public function getType() {
        if(is_null($this->ID)) return false;
        return Tournaments_Main::getType($this->type);
    }
    
    public function getGame() {
        if(is_null($this->ID)) return false;
        return Tournaments_Main::getGame($this->game);
    }
    
    public function getName() {
        if(is_null($this->ID)) return false;
        return $this->name;
    }
    
    public function getLan() {
        if(is_null($this->ID)) return false;
        return $this->lan;
    }
    
    public function getTeamSize() {
        if(is_null($this->ID)) return false;
        return $this->team_size;
    }
    
    public function signupsOpen() {
        if(is_null($this->ID)) return false;
        return $this->signups_open;
    }
    
    public function isVisible() {
        if(is_null($this->ID)) return false;
        return $this->visible;
    }
    
    public function isVisibleToUser() {
        if(is_null($this->ID)) return false;
        return ($this->isVisible() || LanWebsite_Main::getAuth()->getActiveUser()->isAdmin());
    }

    public function getStart($formatDate = true) {
        if(is_null($this->ID)) return false;
        return ($formatDate) ? $this->niceDate($this->start_time) : $this->start_time;
    }

    public function getEnd($formatDate = true) {
        if(is_null($this->ID)) return false;
        return ($formatDate) ? $this->niceDate($this->end_time) : $this->end_time;
    }
    
    public function getSignupClose($formatDate = true) {
        if(is_null($this->ID)) return false;
        return ($formatDate) ? $this->niceDate($this->signups_close) : $this->signups_close;
    }        
    
    public function getDescription() {
        if(is_null($this->ID)) return false;
        return $this->description;
    }
    
    //Methods
    public function isSignedUp($user = false) {
        if(!$user) $user = LanWebsite_Main::getAuth()->getActiveUserId();
        return (bool) LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'", $this->ID, $user)->num_rows;
    }
    
    public function getSignups() {
        $signups_list = array();
        if($this->getTeamSize() > 1) {
            $signups = LabWebsite_Main::getDb()->query("SELECT team_id, COUNT(user_id) AS players FROM `tournament_signups` WHERE tournament_id = '%s' GROUP BY team_id", $this->ID);
            while($Row = $signups->fetch_assoc()) {
			    $signups_list[$Row['user_id']] = array('team' => new Tournament_Team($Row['team_id']), 'players' => $Row['players']);
            }
        } else {
            $signups = $db->query("SELECT user_id FROM `tournament_signups` WHERE tournament_id = '%s'", $this->ID);
            while($Row = $signups->fetch_assoc()) {
			    $signups_list[$Row['user_id']] = LanWebsite_Main::getAuth()->getUserById($Row['user_id']);
            }
        }
        return $signups_list;
    }
    
    public function emptySignups() {
        LanWebsite_Main::getDb()->query("DELETE FROM `tournament_signups` WHERE tournament_id = '%s'", $this->ID);
        return true;
    }
    
    public function delete() {
        $this->emptySignups();
        LanWebsite_Main::getDb()->query("DELETE FROM `tournament_tournaments` WHERE id = '%s'", $this->ID);
        return true;
    }
}
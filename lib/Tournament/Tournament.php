<?php

class Tournament_Tournament {//implements jsonSerializable{
    public $ID = null;
    private $type;
    private $game;
    private $name;
    private $lan;
    private $team_size;
    private $signups_open;
    private $visible;
    private $signups_close;
    public $started;
    private $start_time;
    private $end_time;
    private $description;
    
    private $matches = null;
    private $signups = null;
    private $signupList = null;
    
    function __construct($ID, $useCache = true) {
        if($ID == 0) throw new Tournament_404;
        if(!$useCache || !LanWebsite_Cache::get('tournament', 'tournament_' . $ID, $r)) {
            $r = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_tournaments` WHERE id = '%s'", $ID)->fetch_assoc();
            if(!$r) throw new Tournament_404;
            
            LanWebsite_Cache::set('tournament', 'tournament_' . $ID, $r);
        }
        
        $this->ID =             (int) $ID;
        $this->type =           (int) $r['type'];
        $this->game =           (int) $r['game'];
        $this->name =           (string) $r['name'];
        $this->lan =            (float) $r['lan'];
        $this->team_size =      (int) $r['team_size'];
        $this->signups_open =   (bool) $r['signups'];
        $this->visible =        (bool) $r['visible'];
        $this->signups_close =  (int) $r['signup_close'];
        $this->start_time =     (int) $r['start_time'];
        $this->end_time =       (int) $r['end_time'];
        $this->description =    (string) $r['description'];
        $this->started =        (bool) $r['started'];
    }
    
    function jsonSerialize() {
        return array(
            'id' =>                 $this->ID,
            'type' =>               $this->type,
            'type_name' =>          $this->getType(),
            'game' =>               $this->game,
            'game_name' =>          $this->getGame(), 
            'name' =>               $this->getName(),
            'lan' =>                $this->getLan(),
            'team_size' =>          $this->getTeamSize(),
            'signups' =>            $this->signupsOpen(),
            'visible' =>            $this->isVisible(),
            'signups_close' =>      $this->signups_close,
            'signups_close_nice' => $this->getSignupClose(),
            'start_time' =>         $this->start_time,
            'start_time_nice' =>    $this->getEnd(),
            'end_time' =>           $this->end_time,
            'end_time_nice' =>      $this->getEnd(),
            'description' =>        $this->getDescription(),
            'current_signups' =>    count($this->getSignupList()),
            'started' =>            $this->started
        );
    }
    
    //Utility
    private function niceDate($date) {
        return date('g:ia D', $date);
    }

    //Getter Functions
    public function getType() {
        if(is_null($this->ID)) return false;
        return Tournament_Main::getType($this->type);
    }
    
    public function getGame() {
        if(is_null($this->ID)) return false;
        return Tournament_Main::getGame($this->game);
    }
    
    public function getName() {
        if(is_null($this->ID)) return false;
        return $this->name;
    }
    
    public function getLan() {
        if(is_null($this->ID)) return false;
        return $this->lan;
    }
    
    public function getIcon() {
        if(is_null($this->ID)) return false;
        return '';
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
        return ($this->isVisible() || LanWebsite_Main::getUserManager()->getActiveUser()->isAdmin());
    }

    public function getStart($formatDate = true) {
        if(is_null($this->ID)) return false;
        return ($formatDate) ? $this->niceDate($this->start_time) : $this->start_time;
    }
    
    public function getDay() {
        if(is_null($this->ID)) return false;
        return date('l', $this->start_time);
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
    
    public function getMatches($useCache = true) {
        if(is_null($this->ID)) return false;
        if(!$this->started) return false;
        if(is_null($this->matches) || !$useCache) $this->updateMatches($useCache);
        return $this->matches;
    }
    

    /**
     * Tournament_Tournament::getSignups()
     * Returns a list if team or member objects that are signed up to a tournament
     * Used when working with tournament structure and back end processing/calculations
     * @return array of User or Team objects
     */
    public function getSignups() {
        if(is_null($this->ID)) return false;
        if(is_null($this->signups)) $this->updateSignups();
        return $this->signups;
    }
    
    
    /**
     * Tournament_Tournament::getSigupList()
     * Gets a list of all users signed up, and their teams if they are part of one.
     * This differs from the above function as it always contains as many users as are signed up to a tournament, regardless of teams 
     * @param bool $useCache
     * @return void
     */
    public function getSignupList($useCache = true) {
        if(is_null($this->ID)) return false;
        if(is_null($this->signupList) || $useCache) $this->updateSignupList($useCache);
        return $this->signupList;
    }
    
    public function getStructure() {
        //if(!$this->started) return false;
        //Get the type
        $type = Tournament_Main::getType($this->type);
        //Turn it into nice class format. E.g Round Robin to Roundrobin
        $type = ucfirst(strtolower(str_replace(' ','',$type)));
        $type = 'Tournament_' . $type;
        return new $type($this->ID);
    }
    
    //Methods
    public function isSignedUp($user = false) {
        if(!$user) $user = LanWebsite_Main::getAuth()->getActiveUserId();
        return (bool) LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'", $this->ID, $user)->num_rows;
    }
    
    private function updateSignups() {
        $this->signups = array();
        if($this->getTeamSize() > 1) {
            $signups = LanWebsite_Main::getDb()->query("SELECT team_id, COUNT(user_id) AS players FROM `tournament_signups` WHERE tournament_id = '%s' GROUP BY team_id", $this->ID);
            while($Row = $signups->fetch_assoc()) {
			    $this->signups[$Row['team_id']] = array('team' => new Tournament_Team($Row['team_id']), 'players' => $Row['players']);
            }
        } else {
            $signups = LanWebsite_Main::getDb()->query("SELECT user_id FROM `tournament_signups` WHERE tournament_id = '%s'", $this->ID);
            while($Row = $signups->fetch_assoc()) {
			    $this->signups[$Row['user_id']] = LanWebsite_Main::getUserManager()->getUserById($Row['user_id']);
            }
        }
    }
    
    private function updateSignupList($useCache) {
        if($useCache) {
            LanWebsite_Cache::get("tournament", "signuplist_" . $this->ID, $this->signupList);
        } else {
            $this->signupList = false;
        }
        
        if(!$this->signupList) {
            $this->signupList = array();
            $r = LanWebsite_Main::getDb()->query("SELECT user_id, team_id FROM `tournament_signups` WHERE tournament_id = '%s' ORDER BY team_id ASC", $this->ID);
            while($Row = $r->fetch_assoc()) {
                $this->signupList[$Row['user_id']] = array('user'=>LanWebsite_Main::getUserManager()->getUserById($Row['user_id']));
                if($Row['team_id'] !== '0') $this->signupList[$Row['user_id']]['team'] = Tournament_Main::team($Row['team_id']);
            }
            LanWebsite_Cache::set("tournament", "signuplist_" . $this->ID, $this->signupList);
        }
    }
    
    public function emptySignups() {
        LanWebsite_Main::getDb()->query("DELETE FROM `tournament_signups` WHERE tournament_id = '%s'", $this->ID);
        $this->signups = array();
        return true;
    }
    
    public function delete() {
        $this->emptySignups();
        LanWebsite_Main::getDb()->query("DELETE FROM `tournament_tournaments` WHERE id = '%s'", $this->ID);
        return true;
    }
    
    private function updateMatches($useCache) {
        if(!$this->started) return false;
        if($useCache) {
            LanWebsite_Cache::get("tournament", "matches_" . $this->ID, $this->matches);
        } else {
            $this->matches = false;
        }
        
        if(!$this->matches) {
            $r = LanWebsite_Main::getDb()->query("SELECT id FROM `tournament_matches` WHERE tournament_id = '%s'", $this->ID);
            $this->matches = array();
            while($row = $r->fetch_assoc()) {
                $this->matches[$row['id']] = new Tournament_Match($row['id']);
            }
        }
    }
}

<?php

class Tournament_Main {
    //Array of all tournament objects
    //Handled by this class to prevent multiple instances of same tournament
    private static $tournaments = array();
    
    //Same for tournaments
    private static $teams = array();
    
    //And for Matches
    private static $matches = array();
    
    
    private static $types = array(
        0 => 'Single Elimination',
        1 => 'Double Elimination',
        2 => 'Round Robin',
        3 => 'High Score'
    );
    
    private static $games = array( 
        0 =>    'Counter Strike: Source',
        1 =>    'Team Fortress 2',
        2 =>    'Left 4 Dead 2',
        3 =>    'Chivalry',
        4 =>    'MineCraft - Hunger Games',
        5 =>    'Counter Strike: Global Offensive',
        6 =>    'League of Legends',
        7 =>    'DoTA 2',
        8 =>    'Starcraft 2',
        9 =>    'Smite',
        10 =>   'FIFA'
    );
    
    public static function getIcon($gameID) {
        return '';
    }
    
    public static function getType($typeID) {
        return self::$types[$typeID];
    }
    public static function getTypes() {
        return self::$types;
    }
    
    public static function getGame($gameID) {
        return self::$games[$gameID];
    }
    public static function getGames() {
        return self::$games;
    }
    
    public static function tournament($id, $cache = false) {
        if(!$cache && array_key_exists($id, self::$tournaments)) return self::$tournaments[$id];
        self::$tournaments[$id] = new Tournament_Tournament($id, $cache);
        return self::$tournaments[$id];
    }
    
    public static function team($id) {
        if(array_key_exists($id, self::$teams)) return self::$teams[$id];
        self::$teams[$id] = new Tournament_Team($id);
        return self::$teams[$id];
    }
    
    public static function match($id) {
        if(array_key_exists($id, self::$matches)) return self::$match[$id];
        self::$matches[$id] = new Tournament_Match($id);
        return self::$matches[$id];
    }
    
    public static function getUserTeams($userid = null) {
        if(is_null($userid)) $userid = LanWebsite_Main::getAuth()->getActiveUserId();
        
        //Get an array of user's teams
        if(!LanWebsite_Cache::get('tournament', 'user_teams_' . $userid, $teams)) {
            $teams = array();
            $r = LanWebsite_Main::getDb()->query("SELECT team_id FROM `tournament_teams_members` WHERE user_id = '%s'", $userid);
            while($row = $r->fetch_assoc()) {
                $teams[] = $row['team_id'];
            }
            LanWebsite_Cache::set('tournament', 'user_teams_' . $userid, $teams);
        }
        
        //Turn that array into team models
        $teamObjects = array();
        foreach($teams as $teamID) {
            $teamObjects[$teamID] = self::team($teamID);
        }
        
        return $teamObjects;
    }
    
    public static function getPlayerLink($player) {
        if($player instanceOf LanWebsite_User) {
            $link = LanWebsite_Main::buildUrl(false, 'profile', null, array('member'=>$player->getUsername()));
            $link = "<a href='" . $link . "'>" . $player->getUsername() . "</a>";
        } else {
            $link = LanWebsite_Main::buildUrl(false, 'tournaments', 'viewteam', array('id'=>$player->ID));
            $link = "<a href='" . $link . "'>" . $player->getName() . "</a>";
        }
        return $link;
    }
    
    public static function getPlayerId($player) {
        if($player instanceOf LanWebsite_User) {
            return $player->getUserId();
        } else {
            return $player->ID;
        }
    }
    
    public static function getPlayerName($player) {
        if($player instanceOf LanWebsite_User) {
            return $player->getUsername();
        } else {
            return $player->getName();
        }
    }
}

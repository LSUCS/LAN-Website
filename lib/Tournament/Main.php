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
        } elseif($player instanceOf Tournament_Team) {
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
    
    public static function isGhost($player) {
        return (self::getPlayerId($player) == 0) ? true : false;
    }
    
    public static function timeDiff($time) {
        $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
        $lengths = array("60","60","24","7","4.35","12","10");
        
        $now = time();
        
        $difference = $now - $time;
        $tense = "ago";
        
        for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
           $difference /= $lengths[$j];
        }
        
        $difference = round($difference);
        
        if($difference != 1) {
           $periods[$j].= "s";
        }

        return implode(array($difference, $periods[$j], $tense), ' ');
    }
    
    public static function loadAlerts() {
        $alerts = array();
        $db = LanWebsite_Main::getDb();
        
        if(array_key_exists('cleared_alerts', $_COOKIE)) {
            $clearedAlerts = unserialize($_COOKIE['cleared_alerts']);
        } else {
            $clearedAlerts = array();
        }
        
        //General alerts
        $res = $db->query("SELECT id, message, level, user_id, link FROM `tournament_alerts` WHERE user_id = '%s' OR user_id = 0", LanWebsite_Main::getAuth()->getActiveUserId());
        while($row = $res->fetch_assoc()) {                  
            if(in_array($row['id'], $clearedAlerts)) continue;
            $alerts[] = new LanWebsite_Alert(constant('LanWebsite_Alert::' . $row['level']), $row['message'], $row['link'], $row['id']);
        }
        
        //Tournaments
        $res = $db->query("SELECT t.ID, t.start_time, t.end_time, t.started FROM `tournament_signups` AS s JOIN `tournament_tournaments` AS t ON s.tournament_id = t.ID WHERE s.user_id = '%s'", LanWebsite_Main::getAuth()->getActiveUserId());
        while($row = $res->fetch_assoc()) {
            $time = $row['start_time'] - time();
            $end = $row['end_time'] - time();
            if($time < 3600 && $end > 0) {
                if($row['started']) {
                    $level = LanWebsite_Alert::ALERT_IMPORTANT;
                    $message = 'A tournament that you are playing in is currently in progress';
                } else {
                    $level = LanWebsite_Alert::ALERT_NOTICE;
                    $message = 'A tournament that you are playing in is starting soon';
                }
                if(in_array($row['id'], $clearedAlerts)) continue;
                
                $link = LanWebsite_Main::buildUrl(false, 'tournaments', 'view', array('id'=>$row['ID']));
                $alerts[] = new LanWebsite_Alert($level, $message, $link, 'tournament_' . $level . '_' . $row['id']);
            }
        }
        return $alerts;
    }
}

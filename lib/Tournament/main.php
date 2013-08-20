<?php

class Tournament_Main {
    //Array of all tournament objects
    //Handled by this class to prevent multiple instances of same tournament
    private $tournaments = array();
    
    private static $types = array(
        0 => 'Single Elimination',
        1 => 'Double Elimination',
        2 => 'Round Robin',
        3 => 'High Score'
    );
    
    private static $games = array( 
        0 => 'Counter Strike: Source',
        1 => 'Team Fortress 2',
        2 => 'Left 4 Dead 2',
        3 => 'Chivalry',
        4 => 'MineCraft - Hunger Games',
        5 => 'Counter Strike: Global Offensive',
        6 => 'League of Legends',
        7 => 'DoTA 2',
        8 => 'Starcraft 2',
        9 => 'Smite',
        10 => 'FIFA'
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
    
    public static function tournament($id) {
        if(array_key_exists($id, $this->tournaments)) return $this->tournaments[$id];
        $this->tournaments[$id] = new Tournament_Tournament($id);
        return $this->tournaments[$id];
    }
    
    public static function team($id) {
        if(array_key_exists($id, $this->teams)) return $this->teams[$id];
        $this->teams[$id] = new Tournament_Team($id);
        return $this->teams[$id];
    }
}
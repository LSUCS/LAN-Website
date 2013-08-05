<?php

class LanWebsite_Tournaments {
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
    
}
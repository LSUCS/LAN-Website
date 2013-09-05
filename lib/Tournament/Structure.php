<?php

abstract class Tournament_Structure extends Tournament_Tournament {
    public abstract function createMatches();
    public abstract function display();
    
    protected function createMatch($player1, $player2, $round) {
        if($player1 == 'ghost' || $player2 == 'ghost') return;
        
        if($player1 instanceOf LanWebsite_User) {
            $id1 = $player1->getUserId();
            $id2 = $player2->getUserId();
            $teams = 0;
        } else {
            $id1 = $player1->ID;
            $id2 = $player2->ID;
            $teams = 1;
        }
        LanWebsite_Main::getDb()->query("INSERT INTO `tournament_matches` (tournament_id, player1, player2, teams_bool, round)
            VALUES ('%s', '%s', '%s', '%s', '%s')", $this->ID, $id1, $id2, $teams, $round);
    }
    
    //Quick and ugly style templates
    final protected function showTemplate($name, $data) {
        $dir = '/public/';
        $prefix = 'tournament_';
        
        $file = trim($dir . "templates/" . $prefix . $name . ".tmpl", "/");
        
        if(!file_exists($file)) throw new Exception("File does not exist");

        $DataBag = $data;
        include $file;
    }
}

?>
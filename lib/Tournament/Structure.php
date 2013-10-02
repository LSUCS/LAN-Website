<?php

abstract class Tournament_Structure extends Tournament_Tournament {
    public abstract function createMatches();
    public abstract function display();
    
    protected function createMatch($player1, $player2, $round, $game = 0, $ghostCreate = false) {
        if(!$ghostCreate) {
            if($player1 == 'ghost' || $player2 == 'ghost') return;
        }
        
        $id1 = ($player1 == 'ghost') ? 0 : Tournament_Main::getPlayerId($player1);
        $id2 = ($player2 == 'ghost') ? 0 : Tournament_Main::getPlayerId($player2);
        
        if($this->getTeamSize() > 1){
        //if($player1 instanceOf LanWebsite_User or $player2 instanceOf LanWebsite_User) {
            $teams = 0;
        } else {
            $teams = 1;
        }
        LanWebsite_Main::getDb()->query("INSERT INTO `tournament_matches` (tournament_id, player1, player2, teams_bool, round, game)
            VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $this->ID, $id1, $id2, $teams, $round, $game);
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
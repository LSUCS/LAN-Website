<?php

abstract class Tournament_Structure extends Tournament_Tournament {
    public abstract function createMatches();
    public abstract function display();
    public abstract function updateMatch($info);
    protected $games = array();
    
    protected function createMatch($player1, $player2, $round, $game = 0, $ghostCreate = false) {
        if(!$ghostCreate) {
            //Check for ghosts
            if(!(ctype_digit($player1) && ctype_digit($player2))) return;
        }
        
        //$id1 = (!ctype_digit($player1)) ? $player1 : Tournament_Main::getPlayerId($player1);
        //$id2 = (!ctype_digit($player2)) ? $player2 : Tournament_Main::getPlayerId($player2);
        
        if($this->getTeamSize() == 1) {
        //if($player1 instanceOf LanWebsite_User or $player2 instanceOf LanWebsite_User) {
            $teams = 0;
        } else {
            $teams = 1;
        }
        LanWebsite_Main::getDb()->query("INSERT INTO `tournament_matches` (tournament_id, player1, player2, teams_bool, round, game)
            VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $this->ID, $player1, $player2, $teams, $round, $game);
        $this->games[] = array('player1' => $player1, 'player2' => $player2, 'round' => $round, 'game' => $game);
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
<?php

class Tournament_Singleelimination extends Tournament_Structure {
    private $shuffle = true;
    public $minimumPlayers = 2;
    
    public function createMatches() {
        //Total number of signups. Don't use cache cos it needs to be exact
        $total = count($this->getSignups(false));
        
        //Minimum of 4 teams
        if($total < $this->minimumPlayers) return "There must be at least " . $this->minimumPlayers . " teams for a Single Elimination tournament";
        
        //We can use cache this time cos we just cleared it above
        $teams = $this->getSignups();
        //Shuffle the teams if wanted
        if($this->shuffle) shuffle($teams);
        //And reset the indexes
        $teamsMaster = array_values($teams);
        
        
        ////////////////
        //Create bracket
        ////////////////
        
        
        //The total number of rounds
        $rounds = ceil(log($total, 2));
        
        //If we aren't at a perfect number
        if(log($total, 2) !== $rounds) {
            $extraGames = $total - pow(2, $rounds-1);
        } else {
            $extraGames = false;
        }
        //The first 2^n number we reach, to base the round sizes on
        $masterBase = $base = pow(2, $rounds); 
        
        //Get a copy of the teams array, to shift players off of
        $teams = $teamsMaster;

        echo "base: " . $base;
        echo "\nrounds: " . $rounds;
        echo "\nExtra Games: " . (string)$extraGames;

        //Loop through the rounds, and create games
        for($round = 1; $round <= $rounds; $round++) {
            //Half the base per round. Games = users/2, /2 per every round
            $base /= 2;
            //This shouldn't happen, but just in case
            if($base < 1) break;
            
            //If we're in the starting round, and have extra games to create, create the required "extra" games
            //Users not in these games will get a bye. This is random ($this->shuffle)
            if($round == 1 && $extraGames) {
                for($game = 1; $game <= $extraGames; $game++) {
                    $this->createMatch($this->getNextTeam($teams), $this->getNextTeam($teams), $round, $game);
                }
                continue;
            }
            //If we're in the second round, and had extra games in our first round, this is the 'real' first round
            //It's not very straight forward to create as we have 3 circumstances to accomodate
            if($round == 2 && $extraGames) {
                $round2Game = 1;
                $extraGamesTemp = $extraGames;
                echo "\n" . $extraGamesTemp . '-' . $base;
                
                //First Circumstance:
                //There are more than half of our base in extra games
                //This means that there are games in the second round that have no initial seeds, and are waiting for 
                //Winners from the previous/qualifier round. So we create these matches with 0 seeds
                while($extraGamesTemp > $base) {
                    $this->createMatch('ghost', 'ghost', $round, $round2Game, true);
                    $round2Game++;
                    $extraGamesTemp -= 2;
                }
                //Second Circumstance:
                //There are the remaining games from above that are left to create
                //OR there were only a few qualifying games anyway, with one team qualifying, and one team having a bye
                //We create games with one seed, and one placeholder.
                for($game = $round2Game; $game <= $extraGamesTemp; $game++) {
                    $this->createMatch($this->getNextTeam($teams), 'ghost', $round, $game, true);
                    $round2Game++;
                }
                //Third Circumstance
                //Ordinary games with two seeds
                //Either there were no extra games or (more likely) they have already all been created
                //Create the rest of the games in the round
                //This also happens when the bracket is square
                for($game = $round2Game; $game <= $base; $game++) {
                    $this->createMatch($this->getNextTeam($teams), $this->getNextTeam($teams), $round, $game, true);
                }
            } else {
                //Create standard games
                for($game = 1; $game <= $base; $game++) {
                    $this->createMatch($this->getNextTeam($teams), $this->getNextTeam($teams), $round, $game, true);
                }
            }
        }
        
        return true;
    }
    
    private function getNextTeam(&$teams) {
        if(count($teams)) return Tournament_Main::getPlayerId(array_shift($teams));
        return 'ghost';
    }
    
    public function display() {
        $this->showTemplate('doubleelimination', array('matches'=>$this->getMatches()));
    }
    
    public function updateMatch($matchID) {
        $matches = $this->getMatches(false);
        $match = $matches[$info["match"]];
        
        $round = $match->getRound();
        $game = $match->getGame();
        
        $winnersBracketBool = ($round > 0) ? true:false;
        $nextRound = ($round > 0) ? $round+1 : $round-1;
        
        $res = $db->query("SELECT COUNT(*) FROM `tournament_matches` WHERE tournament_id = '%s' AND round = '%s'", $this->ID, $match->getRound());
        list($totalGames) = $res->fetch_row();
        
        
        $total = count($this->getSignups(false));
        //The total number of rounds
        $rounds = ceil(log($total, 2));
        
        //If we aren't at a perfect number
        if(log($total, 2) !== $rounds) {
            $extraGames = $total - pow(2, $rounds-1);
        } else {
            $extraGames = false;
        }
        $masterBase = $base = pow(2, $rounds); 
        
        
        //It's the first round so things might not be nice
        if($extraGames) {
            
        } else {
            $nextGame = ceil($game/2);
        }
        
        
        //Move the winner into the next round
        foreach($matches as $m) {
            if($m->getRound() == $nextRound && $m->getGame() == $nextGame) {
                $winner = Tournament_Main::getPlayerId($match->getWinner());
                if(Tournament_Main::isGhost($m->getPlayer1())) {
                    $player = '1';
                } elseif(Tournament_Main::isGhost($m->getPlayer2())) {
                    $player = '2';
                } else {
                    die("Tournament value error");
                }
                $db->query("UPDATE `tournament_matches` SET player%s = '%s' WHERE id = '%s'", $player, $winner, $m->ID);
                
                break;
            }
        }
    }
}

?>
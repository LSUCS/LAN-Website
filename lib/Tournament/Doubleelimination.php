<?php

class Tournament_Doubleelimination extends Tournament_Structure {
    private $shuffle = true;
    public $minimumPlayers = 4;
    
    public function createMatches() {
        //Total number of signups. Don't use cache cos it needs to be exact
        $total = count($this->getSignups(false));
        
        //Minimum of 4 teams
        if($total < $this->minimumPlayers) return "There must be at least " . $this->minimumPlayers . " teams for a Double Elimination tournament";
        
        //We can use cache this time cos we just cleared it above
        $teams = $this->getSignups();
        //Shuffle the teams if wanted
        if($this->shuffle) shuffle($teams);
        //And reset the indexes
        $teamsMaster = array_values($teams);
        
        
        /////////////////////////
        //Create winners bracket
        /////////////////////////
        
        
        //The total number of rounds
        $rounds = ceil(log($total, 2));
        
        //If we aren't at a perfect number
        if(log($total, 2) !== $rounds) {
            $extraGames = pow(2, $rounds) - $total;
        } else {
            $extraGames = false;
        }
        $masterBase = $base = pow(2, $rounds); 
        
        $teams = $teamsMaster;

        echo "base: " . $base;
        echo "\nrounds: " . $rounds;
        echo "\nExtra Games: " . $extraGames;

        for($round = 1; $round <= $rounds; $round++) {
            $base /= 2;
            if($base < 1) break;
            
            if($round == 1 && $extraGames) {
                for($game = 1; $game <= $extraGames; $game++) {
                    $this->createMatch($this->getNextTeam($teams), $this->getNextTeam($teams), $round, $game);
                }
                continue;
            }
            if($round == 2 && $extraGames) {
                $round2Game = 1;
                $extraGamesTemp = $extraGames;
                while($extraGamesTemp > $base/2) {
                    $this->createMatch('ghost', 'ghost', $round, $round2Game, true);
                    $round2Game++;
                    $extraGamesTemp -= 2;
                }
                
                
                for($game = $round2Game; $game <= $extraGames; $game++) {
                    $this->createMatch($this->getNextTeam($teams), 'ghost', $round, $game, true);
                }
                for($game = $round2Game + 1; $game <= $base; $game++) {
                    $this->createMatch($this->getNextTeam($teams), $this->getNextTeam($teams), $round, $game, true);
                }
            } else {
                for($game = 1; $game <= $base; $game++) {
                    $this->createMatch($this->getNextTeam($teams), $this->getNextTeam($teams), $round, $game, true);
                }
            }
        }
        
        /////////////////////////
        //Create losers bracket
        /////////////////////////
        
        //Total number of losers to be put into the initial losers bracket
        $losers = pow(2, $rounds)/2 + $extraGames;
        
        //The total number of rounds
        $loserRounds = ceil(log($losers, 2)) + 1;
        
        //If we aren't at a perfect number
        if(log($losers, 2) !== $loserRounds) {
            $extraLoserGames = pow(2, $loserRounds - $losers);
        } else {
            $extraLoserGames = false;
        }
        $loserBase = pow(2, $loserRounds);
        $winnerBase = $masterBase;
        
        echo "\n\nLoser Base: " . $loserBase;
        echo "\nLoser Rounds: " . $loserRounds;
        for($round = 1; $round <= $loserRounds; $round++) {
            if($round % 2 == 0) $loserBase += $winnerBase/2; 
            
            $loserBase /= 2;
            $winnerBase /= 2;
            if($loserBase < 1) break;
            
            if($round == 1 && $extraLoserGames) {
                for($game = 1; $game <= $extraLoserGames; $game++) {
                    $this->createMatch('ghost', 'ghost', -$round, $game, true);
                }
                continue;
            }
            if($round == 2 && $extraLoserGames) {
                $round2LGame = 1;
                $extraLoserGamesTemp = $extraLoserGames;
                while($extraLoserGamesTemp > $loserBase/2) {
                    $this->createMatch('ghost', 'ghost', -$round, $round2LGame, true);
                    $round2LGame++;
                    $extraLoserGamesTemp -= 2;
                }
                
                
                for($game = $round2LGame; $game <= $extraLoserGames; $game++) {
                    $this->createMatch('ghost', 'ghost', -$round, $game, true);
                }
                for($game = $round2LGame + 1; $game <= $loserBase; $game++) {
                    $this->createMatch('ghost', 'ghost', -$round, $game, true);
                }
            } else {
                for($game = 1; $game <= $loserBase; $game++) {
                    $this->createMatch('ghost', 'ghost', -$round, $game, true);
                }
            }
        }
        
        //Create the final
        $this->createMatch('ghost', 'ghost', $rounds+1, 1, true);
        $this->createMatch('ghost', 'ghost', $rounds+2, 1, true);
        
        return true;
    }
    
    private function getNextTeam(&$teams) {
        if(count($teams)) return array_shift($teams);
        return 'ghost';
    }
    
    public function display() {
        $this->showTemplate('doubleelimination', array('matches'=>$this->getMatches()));
    }
    
}

?>
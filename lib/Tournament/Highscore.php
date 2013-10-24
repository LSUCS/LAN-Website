<?php

class Tournament_Highscore extends Tournament_Structure {
    private $displayScores = 0;
    
    public function createMatches() {
        //No matches to create. Wooo
        return true;
    }
    
    public function updateMatch($info) {
        return;
    }
    
    public function display() {
        if(!$this->displayScores) {
            echo 'Scores are hidden for this Tournament';
            return;
        }
        
        //Entries are saved as matches
        $scores = $this->getMatches();
        $scores = $this->bubbleSort($scores);
        
        $this->showTemplate('highscore', $scores);
    }
    
    //No idea if this will work, I was feeling lazy so stole this off of the internet
    private function bubbleSort($array) {
        if(!$length = count($array)) {
            return $array;
        }      
        for ($outer = 0; $outer < $length; $outer++) {
            for ($inner = 0; $inner < $length; $inner++) {
                if ($array[$outer]->getScore1() > $array[$inner]->getScore1()) {
                    $tmp = $array[$outer];
                    $array[$outer] = $array[$inner];
                    $array[$inner] = $tmp;
                }
            }
        }
        return $array;
    }
}

?>
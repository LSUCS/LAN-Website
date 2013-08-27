<?php
/* In my comments I refer to players as teams. The same applies for single players also,
   but I found it less confusing to think of them as teams all of the time */
   
class Tournament_Roundrobin extends Tournament_Structure {
    private $shuffle = true;
    public function createMatches() {
        //Total number of signups. Don't use cache cos it needs to be exact
        $total = count($this->getSignups(false));
        
        //Minimum of 3 teams
        if($total < 3) return false;
        
        //If we have an odd number of signups we need to use a ghost team
        $ghost = false;
        if($total %2 !== 0) {
            $ghost = true;
            $total++;
        }
        //Number of rounds that we need
        $rounds = $total - 1;
        
        //We can use cache this time cos we just cleared it above
        $teams = $this->getSignups();
        //Shuffle the teams if wanted
        if($this->shuffle) shuffle($teams);
        //And reset the indexes
        $teams = array_values($teams);
        //If we need to use a ghost team add it to the end
        if($ghost) $teams[] = 'ghost';
        
        //Loop rounds
        for($i = 0; $i < $rounds; $i++) {
            //First round we don't need to alter anything
            if($i = 0) {
                $roundTeams = $teams;
            } else {
                //Change the order based on the order of last round
                foreach($roundTeams as $ind => $t) {
                    $rt2 = array();

                    //Initial team doesn't change, rotate all others
                    if($ind == 0) continue;
                    
                    //Move the top row right one
                    if($ind < $teams/2 - 1) $ind++;
                    //Move the top right corner to the bottom right corner
                    elseif($ind == $teams/2 - 1) $ind = $teams-1;
                    //Move the bottom row left one
                    elseif($ind > $teams/2) $ind--;
                    //Move the bottom left corner to the second position
                    elseif($ind == $teams/2) $ind = 1;
                    
                    $rt2[$ind] = $t;
                }
                $roundTeams = $rt2;
            }
            
            //Loop matches, there are half as many matches as there are teams
            for($j = 0; $j < $total/2; $j++) {
                //Create a match between the team, and the team opposite them in the chart
                //Using this model. Teams: 8, half is 4. So 0 and 4, 1 and 5, 2 and 6, etc.
                // 0 1 2 3
                // 4 5 6 7
                $this->createMatch($roundTeams[$j], $roundTeams[$j + $total/2], $i);
            }
        }
    }
    
    public function display() {
        $teams = array();
        $db = LanWebsite_Main::getDb();
        
        foreach($this->getSignups() as $team) {
            $played = 0;
            $won = 0;
            $lost = 0;
            
            if($team instanceOf LanWebsite_User) {
                $id = $team->getUserId();
            } else {
                $id = $team->ID;
            }
            
            foreach($this->getMatches() as $match) {
                if(!$match->getPlayed()) continue;
                if($match->getPlayer1() == $id) {
                    $played++;
                    if($match->getWinner() == 1) $won++;
                    else $lost++;
                } elseif($match->getPlayer2() == $id) {
                    $played++;
                    if($match->getWinner() == 2) $won++;
                    else $lost++;
                }
            }
            
            $teams[] = array('team' => $team, 'played' => $played, 'won' => $won, 'lost' => $lost);
        }
        
        $teams = $this->bubbleSort($teams);
        
        //Template-ception. Hopefully this will work. I actually have no idea though. I have a feeling I've done this before, but might be thinking of a different site
        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->addTemplate('tournament_roundrobin', $teams);
		$tmpl->output();
    }
    
    //No idea if this will work, I was feeling lazy so stole this off of the internet
    private function bubbleSort($array) {
        if(!$length = count($array)) {
            return $array;
        }      
        for ($outer = 0; $outer < $length; $outer++) {
            for ($inner = 0; $inner < $length; $inner++) {
                if ($array[$outer]["won"] < $array[$inner]["won"]) {
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
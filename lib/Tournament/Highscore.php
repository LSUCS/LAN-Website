<?php

class Tournament_Highscore extends Tournament_Structure {
    public function createMatches() {
        //No matches to create. Wooo
        return;
    }
    
    public function display() {
        $db = LanWebsite_Main::getDb();
        
        //Entries are saved as matches
        $scores = $this->getMatches();
        $scores = $this->bubbleSort($scores);
        
        //Template-ception. Hopefully this will work. I actually have no idea though. I have a feeling I've done this before, but might be thinking of a different site
        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->addTemplate('tournament_highscore', $scores);
		$tmpl->output();
    }
    
    //No idea if this will work, I was feeling lazy so stole this off of the internet
    private function bubbleSort($array) {
        if(!$length = count($array)) {
            return $array;
        }      
        for ($outer = 0; $outer < $length; $outer++) {
            for ($inner = 0; $inner < $length; $inner++) {
                if ($array[$outer]->getScore1() < $array[$inner]->getScore1()) {
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
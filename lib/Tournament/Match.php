<?php

class Tournament_Match {
    public $ID = null;
    private $tournamentID;
    private $round;
    private $game;
    //The IDs of the teams or users in the match#
    //Or placeholder strings
    private $player1;
    private $player2;
    //Whether or not the match has been played
    private $played_bool;
    //The score of each player
    private $score1;
    private $score2;
    //Who won, 1 or 2
    private $winner;
    //Whether or not player1 and player2 refer to teams, or users. True for teams, false for users
    private $teams_bool;
    
    function __construct($ID) {
        if(!LanWebsite_Cache::get('tournament', 'match_' . $ID, $r)) {
            $r = LanWebsite_Main::getDb()->query("SELECT * FROM `tournament_matches` WHERE id = '%s'", $ID)->fetch_assoc();
            if(!$r) return false;
            
            LanWebsite_Cache::set('tournament', 'match_' . $ID, $r);
        }
        
        $this->ID =             (int) $ID;
        $this->player1 =        $r['player1'];
        $this->player2 =        $r['player2'];
        $this->played_bool =    (bool) $r['played_bool'];
        $this->score1 =         (string) $r['score1'];
        $this->score2 =         (string) $r['score2'];
        $this->winner =         (int) $r['winner'];
        $this->teams_bool =     (bool) $r['teams_bool'];
        $this->tournamentID =   (int) $r['tournament_id'];
        $this->round =          (int) $r['round'];
        $this->game =           (int) $r['game'];
        
        if(ctype_digit($r['player1'])) {
            if($this->teams_bool) {
                $this->player1 = Tournament_Main::team($this->player1);
            } else {
                $this->player1 = ($this->player1) ? LanWebsite_Main::getUserManager()->getUserById($this->player1) : Tournament_Main::team(0);
            }
        } else {
            $this->player1 = new Tournament_Team(0, $r['player1']);
        }
        if(ctype_digit($r['player2'])) {
            if($this->teams_bool) {
                $this->player2 = Tournament_Main::team($this->player2);
            } else {
                $this->player2 = ($this->player2) ? LanWebsite_Main::getUserManager()->getUserById($this->player2) : Tournament_Main::team(0);
            }
        } else {
            $this->player2 = new Tournament_Team(0, $r['player2']);
        }        
    }
    
    function jsonSerialize() {
        return array(
            'id' =>                 $this->ID,
            'player1' =>            array(
                                        'id' => Tournament_Main::getPlayerId($this->player1), 
                                        'name' => Tournament_Main::getPlayerName($this->player1),
                                        'link' => Tournament_Main::getPlayerLink($this->player1)
                                    ),
            'player2' =>            array(
                                        'id' => Tournament_Main::getPlayerId($this->player2),
                                        'name' => Tournament_Main::getPlayerName($this->player2),
                                        'link' => Tournament_Main::getPlayerLink($this->player2)
                                    ),
            'played_bool' =>        $this->played_bool,
            'score1' =>             $this->getScore1(),
            'score2' =>             $this->getScore2(),
            'winner' =>             $this->getWinner(),
            'teams_bool' =>         $this->getTeamsBool(),
            'tournament_id' =>      $this->tournamentID,
            'round' =>              $this->getRound(),
            'game' =>               $this->getGame()
        );
    }
    
    //Getter Functions
    public function getPlayer1() {
        if(is_null($this->ID)) return false;
        return $this->player1;
    }
    
    public function getPlayer2() {
        if(is_null($this->ID)) return false;
        return $this->player2;
    }
    
    public function getPlayed() {
        if(is_null($this->ID)) return false;
        return $this->played_bool;
    }
    
    public function getScore1() {
        if(is_null($this->ID)) return false;
        return $this->score1;
    }
    
    public function getScore2() {
        if(is_null($this->ID)) return false;
        return $this->score2;
    }
    
    public function getWinner() {
        if(is_null($this->ID)) return false;
        return $this->winner;
    }
    
    public function getTeamsBool() {
        if(is_null($this->ID)) return false;
        return $this->teams_bool;
    }
    
    public function getRound() {
        if(is_null($this->ID)) return false;
        return $this->round;
    }
    
    public function getGame() {
        if(is_null($this->ID)) return false;
        return $this->game;
    }
}
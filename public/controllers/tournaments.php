<?php

    class Tournaments_Controller extends LanWebsite_Controller {
        public function getInputFilters($action) {
            switch ($action) {
                case "view": return array("id" => array('notnull', 'int'));
            }
        }
        
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Tournaments");
            $tmpl->addTemplate('tournaments');
			$tmpl->output();
        }
    
        public function get_View($inputs) {
            if($this->isInvalid('id')) $this->error("Invalid Tournament ID");
            
            $db = LanWebsite_Main::getDb();
            $tournament = $db->query("SELECT * FROM `tournament_tournaments` WHERE id = '%s'", $inputs['id'])->fetch_assoc();
            if(!$tournament || (!$tournament["visible"] && !LanWebsite_Main::getUserManager()->getActiveUser()->isAdmin())) $this->error("Invalid Tournament ID");
            
            //Team game. Two queries, one to get the teams, one to check if the user has signed up as any of them
            if($tournament["team_size"] > 1) {
                $signup_bool = (bool) $db->query("SELECT * FROM `tournament_signups` WHERE tournament_id = '%s' AND user_id = '%s'",
                                        $inputs['id'], LanWebsite_Main::getUserManager()->getActiveUser()->getUserId())->num_rows;
                
                //stupid fetch_all doesn't work
                $signups_list = array();
                $signups = $db->query("SELECT team_id, COUNT(user_id) AS players FROM `tournament_signups` WHERE tournament_id = '%s' GROUP BY team_id", $inputs['id']);
                while($Row = $signups->fetch_assoc()) {
				    $signups_list[$Row['user_id']] = $Row;
                }
            }
            //Single player game. One query to get all users. We then check if the user is in the list.
            else {
                $signups = $db->query("SELECT user_id FROM `tournament_signups` WHERE tournament_id = '%s'", $inputs['id']);
                
                $signups_list = array();
                while($Row = $signups->fetch_assoc()) {
				    $signups_list[$Row['user_id']] = $Row;
                }
                
                $signup_bool = array_key_exists(LanWebsite_Main::getUserManager()->getActiveUser()->getUserId(), $signups_list);
            }
            

            $tmpl = LanWebsite_Main::getTemplateManager();
            $tmpl->setSubTitle($tournament['name']);
            $tmpl->addTemplate('tournament', array('tournament'=>$tournament, 'signed-up'=>$signup_bool, 'signup_list'=>$signups_list, 'brackets' => 0, 'matches' => 0));
            $tmpl->output();
        }
    }

?>
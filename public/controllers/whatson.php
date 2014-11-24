<?php

    class Whatson_Controller extends LanWebsite_Controller {
        
        public function get_Index() {
            $data["rota"] = LanWebsite_Main::getSettings()->getSetting('committee_rota_url');
            $data["rotawidth"] = LanWebsite_Main::getSettings()->getSetting('committee_rota_width');
            $data["rotaheight"] = LanWebsite_Main::getSettings()->getSetting('committee_rota_height');
        
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("What's On");
            $tmpl->addTemplate('whatson', $data);
			$tmpl->output();
        
        }
        
        public function get_Gettimetable() {
        
            //Timetable
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `timetable` ORDER BY start_time ASC");
            
            $entries = array("timetable" => array(), "committee" => array(), "users" => array());
            $days = array();
            while ($row = $res->fetch_assoc()) {
                $entries["timetable"][] = $row;
                $start = str_replace(array(":30", ":00"), array("50", "00"), $row["start_time"]);
                $end = str_replace(array(":30", ":00"), array("50", "00"), $row["end_time"]);
                for ($i = $start; $i < $end; $i += 50) {
                    $days[$row["day"]][$i][] = $row["timetable_id"];
                }
            }
            
            foreach ($entries["timetable"] as $key => $entry) {
                $start = str_replace(array(":30", ":00"), array("50", "00"), $entry["start_time"]);
                $end = str_replace(array(":30", ":00"), array("50", "00"), $entry["end_time"]);
                $division = 1;
                for ($i = $start; $i < $end; $i += 50) {
                    if ($division < count($days[$entry["day"]][$i])) $division = count($days[$entry["day"]][$i]);
                }
                $entries["timetable"][$key]["division"] = $division;
                $position = array_search($entry["timetable_id"], $days[$entry["day"]][$start]);
                if ($position == 0) $entries["timetable"][$key]["previous"] = "";
                else $entries["timetable"][$key]["previous"] = $days[$entry["day"]][$start][$position -1];
            }
            
            //Committee
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `committee_timetable` ORDER BY day, start_time ASC");
            while ($row = $res->fetch_assoc()) {
                $entries["committee"][] = $row;
                
                if(!array_key_exists($row['user_id_1'], $entries["users"])) {
                    $user = LanWebsite_Main::getUserManager()->getUserById($row['user_id_1']);
                    
                    $res = LanWebsite_Main::getDb()->query("SELECT seat FROM `tickets` WHERE lan_number = '%s' AND assigned_forum_id = '%s' AND seat != ''",
                        LanWebsite_Main::getSettings()->getSetting('lan_number'), $user->getUserId());
                    if($res->num_rows) {
                        list($seat) = $res->fetch_row();
                    } else {
                        $seat = "";
                    }
                    
                    $entries["users"][$row['user_id_1']] = array("id" => $row['user_id_1'], "username" => $user->getUsername(), "seat" => $seat);
                }
                if(!array_key_exists($row['user_id_2'], $entries["users"])) {
                    $user = LanWebsite_Main::getUserManager()->getUserById($row['user_id_2']);
                    
                    $res = LanWebsite_Main::getDb()->query("SELECT seat FROM `tickets` WHERE lan_number = '%s' AND assigned_forum_id = '%s' AND seat != ''",
                        LanWebsite_Main::getSettings()->getSetting('lan_number'), $user->getUserId());
                    if($res->num_rows) {
                        list($seat) = $res->fetch_row();
                    } else {
                        $seat = "";
                    }
                    
                    $entries["users"][$row['user_id_2']] = array("id" => $row['user_id_2'], "username" => $user->getUsername(), "seat" => $seat);
                }
            }            
            
            
            echo json_encode($entries);
        
        }
    
    }

?>
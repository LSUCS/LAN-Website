<?php

    class Whatson_Controller extends LanWebsite_Controller {
        
        public function get_Index() {
        
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("What's On");
            $tmpl->addTemplate('whatson');
			$tmpl->output();
        
        }
        
        public function get_Gettimetable() {
        
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `timetable` ORDER BY start_time ASC");
            
            $entries = array();
            $days = array();
            while ($row = $res->fetch_assoc()) {
                $entries[] = $row;
                $start = str_replace(array(":30", ":00"), array("50", "00"), $row["start_time"]);
                $end = str_replace(array(":30", ":00"), array("50", "00"), $row["end_time"]);
                for ($i = $start; $i < $end; $i += 50) {
                    $days[$row["day"]][$i][] = $row["timetable_id"];
                }
            }
            
            foreach ($entries as $key => $entry) {
                $start = str_replace(array(":30", ":00"), array("50", "00"), $entry["start_time"]);
                $end = str_replace(array(":30", ":00"), array("50", "00"), $entry["end_time"]);
                $division = 1;
                for ($i = $start; $i < $end; $i += 50) {
                    if ($division < count($days[$entry["day"]][$i])) $division = count($days[$entry["day"]][$i]);
                }
                $entries[$key]["division"] = $division;
                $position = array_search($entry["timetable_id"], $days[$entry["day"]][$start]);
                if ($position == 0) $entries[$key]["previous"] = "";
                else $entries[$key]["previous"] = $days[$entry["day"]][$start][$position -1];
            }
            
            echo json_encode($entries);
        
        }
    
    }

?>
<?php

    class Whatson_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "addentry": return array("day" => "notnull", "start_time" => "notnull", "end_time" => "notnull", "title" => "notnull", "url" => "url", "colour" => "notnull"); break;
                case "deleteentry": return array("entry_id" => array("notnull", "int")); break;
            }
        }
        
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
            $tmpl->setSubtitle("What's On");
            $tmpl->addTemplate("whatson");
            $tmpl->output();
        }
        
        public function get_Getentries() {
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `timetable` ORDER BY day,start_time ASC");
            $arr = array();
            while ($row = $res->fetch_assoc()) $arr[] = $row;
            echo json_encode($arr);
        }
        
        public function post_Addentry($inputs) {
            
            $times = array();
            for ($i = 0; $i <= 2400; $i += 50) {
                $times[] = str_replace('00000', '00:00', str_pad(preg_replace('/^(.*?)(\d\d)$/', '$1:$2', str_replace(5, 3, $i)), 5, '0', STR_PAD_LEFT));
            }
            
            //Validation
            if (!in_array($inputs["day"], array("friday", "saturday", "sunday"))) $this->errorJSON("Invalid day");
            if (!in_array($inputs["start_time"], $times)) $this->errorJSON("Invalid start time");
            if (!in_array($inputs["end_time"], $times)) $this->errorJSON("Invalid end time");
            if (str_replace(":", "", $inputs["start_time"]) >= str_replace(":", "", $inputs["end_time"])) $this->errorJSON("Start time cannot be greater than or the same as end time");
            if ($inputs["title"] == "") $this->errorJSON("Invalid title");
            if (!in_array($inputs["colour"], array("orange", "blue", "green", "purple"))) $this->errorJSON("Invalid colour");
            
            //Insert
            LanWebsite_Main::getDb()->query("INSERT INTO `timetable` (day, start_time, end_time, title, url, colour) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $inputs["day"], $inputs["start_time"], $inputs["end_time"], $inputs["title"], $inputs["url"], $inputs["colour"]);
        
        }
        
        public function post_Deleteentry($inputs) {
        
            if (!LanWebsite_Main::getDb()->query("SELECT * FROM `timetable` WHERE timetable_id = '%s'", $inputs["entry_id"])->fetch_assoc()) $this->errorJSON("Invalid entry ID");
            LanWebsite_Main::getDb()->query("DELETE FROM `timetable` WHERE timetable_id = '%s'", $inputs["entry_id"]);
        
        }
        
    }
    
?>
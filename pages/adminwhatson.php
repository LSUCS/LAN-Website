<?php

    class Adminwhatson_Page extends Page {
    
        public function getInputs() {
            return array(
                        "actionAddentry" => array("day" => "post", "start_time" => "post", "end_time" => "post", "title" => "post", "url" => "post", "colour" => "post"),
                        "actionDeleteentry" => array("entry_id" => "post")
                        );
        }
        
        public function actionIndex() {
            $this->parent->template->setSubtitle("What's On");
            $this->parent->template->outputTemplate("adminwhatson");
        }
        
        public function actionGetentries() {
            $res = $this->parent->db->query("SELECT * FROM `timetable` ORDER BY day,start_time ASC");
            $arr = array();
            while ($row = $res->fetch_assoc()) $arr[] = $row;
            echo json_encode($arr);
        }
        
        public function actionAddentry() {
            
            $times = array();
            for ($i = 0; $i <= 2400; $i += 50) {
                $times[] = str_replace('00000', '00:00', str_pad(preg_replace('/^(.*?)(\d\d)$/', '$1:$2', str_replace(5, 3, $i)), 5, '0', STR_PAD_LEFT));
            }
            
            //Validation
            if (!in_array($this->inputs["day"], array("friday", "saturday", "sunday"))) $this->errorJSON("Invalid day");
            if (!in_array($this->inputs["start_time"], $times)) $this->errorJSON("Invalid start time");
            if (!in_array($this->inputs["end_time"], $times)) $this->errorJSON("Invalid end time");
            if (str_replace(":", "", $this->inputs["start_time"]) >= str_replace(":", "", $this->inputs["end_time"])) $this->errorJSON("Start time cannot be greater than or the same as end time");
            if ($this->inputs["title"] == "") $this->errorJSON("Invalid title");
            if (!in_array($this->inputs["colour"], array("orange", "blue", "green", "purple"))) $this->errorJSON("Invalid colour");
            
            //Insert
            $this->parent->db->query("INSERT INTO `timetable` (day, start_time, end_time, title, url, colour) VALUES ('%s', '%s', '%s', '%s', '%s', '%s')", $this->inputs["day"], $this->inputs["start_time"], $this->inputs["end_time"], $this->inputs["title"], $this->inputs["url"], $this->inputs["colour"]);
        
        }
        
        public function actionDeleteentry() {
        
            if (!$this->parent->db->query("SELECT * FROM `timetable` WHERE timetable_id = '%s'", $this->inputs["entry_id"])->fetch_assoc()) $this->errorJSON("Invalid entry ID");
            $this->parent->db->query("DELETE FROM `timetable` WHERE timetable_id = '%s'", $this->inputs["entry_id"]);
        
        }
        
    }
    
?>
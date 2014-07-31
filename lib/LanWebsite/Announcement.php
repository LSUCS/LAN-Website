<?php

class LanWebsite_Announcement {

    public static $colourList = array('Red', 'Blue', 'Green');
    public static function getColourList($chosen = null) {
        $out = "<select class='colourlist'><option></option>";
        foreach(self::$colourList as $c) {
            $out .= "<option";
            if(!is_null($chosen) && $c == $chosen) $out .= " selected='selected'";
            $out .= ">" . $c . "</option>";
        }
        $out .= "</select>";
        return $out;
    }

    public $id = null;
    public $name = null;
    public $message = null;        
    public $displayTime = null;
    public $duration = null;
    public $colour = null;
    
    //Feeling lazy
    public $timer1 = false;
    public $timer2 = false;
    public $timer3 = false;
    
    public function __construct($info) {
        if(is_array($info)) {
            $this->_loadFromRecord($info);
        } elseif(!is_null($info)) {
            $this->_loadFromId($info);
        }
    }
    
    private function _loadFromRecord($record) {
        $this->id = $record['ID'];
        $this->name = $record['Name'];
        $this->message = $record['Message'];
        $this->displayTime = $record['DisplayTime'];
        $this->duration = $record['Duration'];
        $this->timer1 = $record['Timer1'];
        $this->timer2 = $record['Timer2'];
        $this->timer3 = $record['Timer3'];
        $this->colour = $record['Colour'];
    }
    
    private function _loadFromId($id) {
        $res = LanWebsite_Main::getDb()->query("SELECT * FROM Announcements WHERE ID = '%s'", $id);
        if($res->num_rows == 0) {
            return;
        }
        $this->_loadFromRecord($res->fetch_assoc());
    }
    
    public function save() {
        if(!is_null($this->id)) $this->edit();
        else $this->create();
    }
    
    private function edit() {
        LanWebsite_Main::getDb()->query("UPDATE Announcements SET Name = '%s', Message = '%s', DisplayTime = '%s', Duration = '%s', Timer1 = '%s', Timer2 = '%s', Timer3 = '%s', Colour = '%s' WHERE ID = '%s'",
            $this->name, $this->message, $this->displayTime, $this->duration, $this->timer1, $this->timer2, $this->timer3, $this->colour, $this->id);
    }
    
    private function create() {
        $res = LanWebsite_Main::getDb()->query("INSERT INTO Announcements (Name, Message, DisplayTime, Duration, Timer1, Timer2, Timer3, Colour) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            $this->name, $this->message, $this->displayTime, $this->duration, $this->timer1, $this->timer2, $this->timer3, $this->colour);
        $this->id = LanWebsite_Main::getDb()->getLink()->insert_id;
    }
    
    public function delete() {
        LanWebsite_Main::getDb()->query("DELETE FROM Announcements WHERE ID = '%s' LIMIT 1", $this->id);
        $this->id = null;
    }
    
    public function getMessage() {
        $message = $this->message;
        if($this->timer1) {
            $message = str_replace('%T1%', ceil($this->timer1 - (time() - strtotime($this->displayTime))/60), $message);
        }
        if($this->timer2) {
            $message = str_replace('%T2%', ceil($this->timer2 - (time() - strtotime($this->displayTime))/60), $message);
        }
        if($this->timer3) {
            $message = str_replace('%T3%', ceil($this->timer3 - (time() - strtotime($this->displayTime))/60), $message);
        }
        return $message;
    }
}    
?>
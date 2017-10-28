<?php
    
class Announcements_Controller extends LanWebsite_Controller {
    private $announcements = null;
    
    public function getInputFilters($action) {
        switch ($action) {
            case "create": return array("name" => "notnull", "colour" => "notnull", "message" => "notnull", "timer1" => "int", "timer2" => "int", "timer3" => "int", "start" => "notnull", "duration" => array("notnull", "int"));
            case "edit": return array("id" => array("notnull", "int"), "name" => "notnull", "colour" => "notnull", "message" => "notnull", "timer1" => "int", "timer2" => "int", "timer3" => "int", "start" => "notnull", "duration" => array("notnull", "int"));
            case "delete": return array("id" => array("notnull", "int"));
        }
    }
    
    public function get_index() {
        //$this->loadAnnouncements();
        $this->announcements = array();
        $colours = LanWebsite_Announcement::getColourList();        
        
        $tmpl = LanWebsite_Main::getTemplateManager();
        $tmpl->setSubTitle("Announcements");
        $tmpl->enablePlugin('timepicker');
        $tmpl->addTemplate('announcements', array('Announcements' => $this->announcements, 'ColourList' => $colours));
        $tmpl->output();
    }
    
    private function validateFields() {
        if($this->isInvalid("name")) $this->errorJSON("You must supply a name!");
        if($this->isInvalid("message")) $this->errorJSON("You must supply message!");
        if($this->isInvalid("colour")) $this->errorJSON("You must specify a colour!");
        if($this->isInvalid("timer1")) $this->errorJSON("Invalid Timer 1");
        if($this->isInvalid("timer2")) $this->errorJSON("Invalid Timer 2");
        if($this->isInvalid("timer3")) $this->errorJSON("Invalid Timer 3");
        if($this->isInvalid("start")) $this->errorJSON("You must supply a start time!");
        if($this->isInvalid("duration")) $this->errorJSON("You must supply a duration!");
    }
    
    private function loadFields($inputs) {
        return array(
            'ID' => (array_key_exists('id', $inputs)) ? $inputs['id'] : null,
            'Name' => $inputs['name'],
            'Message' => $inputs['message'],
            'Colour' => $inputs['colour'],
            'Timer1' => $inputs['timer1'],
            'Timer2' => $inputs['timer2'],
            'Timer3' => $inputs['timer3'],
            'DisplayTime' => $inputs['start'],
            'Duration' => $inputs['duration']
        );
    }
    
    public function post_create($inputs) {
        $this->validateFields();
        
        //Now Functionality
        if($inputs['start'] == "now") $inputs['start'] = date('Y-m-d H:i:s');
        
        $announcement = $this->loadFields($inputs);
        $ann = new LanWebsite_Announcement($announcement);
        $ann->save();
        
        echo json_encode(array('time' => $ann->displayTime));
        
        $this->sendUpdate();
        return true;
    }
    
    public function post_edit($inputs) {
        if($this->isInvalid("id")) $this->errorJSON("Invalid ID");
        $this->post_create($inputs);
    }
    
    public function post_delete($inputs) {
        if($this->isInvalid("id")) $this->errorJSON("Invalid ID");
        $ann = new LanWebsite_Announcement($inputs['id']);
        $ann->delete();
        $this->sendUpdate();
    }
    
    private function loadAnnouncements() {
        $res = LanWebsite_Main::getDb()->query("SELECT * FROM Announcements");

        $this->announcements = array();
        while($a = $res->fetch_assoc()) {
            $this->announcements[] = new LanWebsite_Announcement($a);
        }
    }
    
    private function sendUpdate() {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        $msg = "Ping !";
        $len = strlen($msg);

        socket_connect($sock, '127.0.0.1', 1525);
        socket_send($sock, $msg, $len, 0);
        socket_close($sock);
    }
}

?>
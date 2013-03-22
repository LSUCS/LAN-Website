<?php
    
    class Blog_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "add": return array("title" => "notnull", "content" => "notnull"); break;
                case "load": return array("id" => array("int", "notnull")); break;
                case "edit": return array("id" => array("int", "notnull"), "title" => "notnull", "content" => "notnull"); break;
                case "delete": return array("id" => array("int", "notnull")); break;
            }
        }
        
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Blog");
            $tmpl->enablePlugin('cleditor');
            $tmpl->addTemplate('blog');
			$tmpl->output();
        }
        
        public function post_Add($inputs) {
            //Validate
            if ($this->isInvalid("title")) $this->errorJSON("You must supply a title!");
            if ($this->isInvalid("content")) $this->errorJSON("You must supply content!");
            
            $userdata = $this->parent->auth->getactiveUserData();
            
            //Let's insert
            LanWebsite_Main::getDb()->query("INSERT INTO `blog` (user_id, title, body) VALUES ('%s', '%s', '%s')", $userdata["xenforo"]["user_id"], $inputs["title"], $inputs["content"]);
        }
        
        public function get_Getentries() {
            $res = LanWebsite_Main::getDb()->query("SELECT * FROM `blog` ORDER BY date DESC");
            $blog = array();
            while ($row = $res->fetch_assoc()) {
                $user = LanWebsite_Main::getUserManager()->getUserById($row["user_id"]);
                $row["username"] = $user->getUsername();
                $row["date"] = date("D jS M g:iA", strtotime($row["date"]));
                $blog[] = $row;
            }
            echo json_encode($blog);
        }
        
        public function post_Load($inputs) {
            $entry = LanWebsite_Main::getDb()->query("SELECT * FROM `blog` WHERE blog_id = '%s'", $inputs["id"])->fetch_assoc();
            if (!$entry) $this->errorJSON("Invalid ID");
            echo json_encode($entry);
        }
        
        public function post_Edit($inputs) {
            //Validate
            if ($this->isInvalid("title")) $this->errorJSON("You must supply a title!");
            if ($this->isInvalid("content")) $this->errorJSON("You must supply content!");
            
            //Check id
            $entry = LanWebsite_Main::getDb()->query("SELECT * FROM `blog` WHERE blog_id = '%s'", $inputs["id"])->fetch_assoc();
            if (!$entry) $this->errorJSON("Invalid ID");
            
            //Update
            LanWebsite_Main::getDb()->query("UPDATE `blog` SET title='%s', body='%s' WHERE blog_id = '%s'", $inputs["title"], $inputs["content"], $inputs["id"]);
            
        }
        
        public function actionDelete() {
            //Check id
            $entry = LanWebsite_Main::getDb()->query("SELECT * FROM `blog` WHERE blog_id = '%s'", $inputs["id"])->fetch_assoc();
            if (!$entry) $this->errorJSON("Invalid ID");
            
            //Delete
            LanWebsite_Main::getDb()->query("DELETE FROM `blog` WHERE blog_id = '%s'", $inputs["id"]);
        }
        
    }
    
?>
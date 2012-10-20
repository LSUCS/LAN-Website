<?php
    
    class Adminblog_Page extends Page {
    
        public function getInputs() {
            return array(
                        "actionAdd" => array("title" => "post", "content" => "post"),
                        "actionLoad" => array("id" => "post"),
                        "actionEdit" => array("id" => "post", "title" => "post", "content" => "post"),
                        "actionDelete" => array("id" => "post")
                        );
        }
        
        public function actionIndex() {
            $this->parent->template->setSubtitle("Blog");
            $this->parent->template->outputTemplate("adminblog");
        }
        
        public function actionAdd() {
            //Validate
            if ($this->inputs["title"] == "") $this->errorJSON("You must supply a title!");
            if ($this->inputs["content"] == "") $this->errorJSON("You must supply content!");
            
            $userdata = $this->parent->auth->getactiveUserData();
            
            //Let's insert
            $this->parent->db->query("INSERT INTO `blog` (user_id, title, body) VALUES ('%s', '%s', '%s')", $userdata["xenforo"]["user_id"], $this->inputs["title"], $this->inputs["content"]);
        }
        
        public function actionGetentries() {
            $res = $this->parent->db->query("SELECT * FROM `blog` ORDER BY date DESC");
            $blog = array();
            while ($row = $res->fetch_assoc()) {
                $userdata = $this->parent->auth->getUserById($row["user_id"]);
                $row["username"] = $userdata["xenforo"]["username"];
                $row["date"] = date("D jS M g:iA", strtotime($row["date"]));
                $blog[] = $row;
            }
            echo json_encode($blog);
        }
        
        public function actionLoad() {
            $entry = $this->parent->db->query("SELECT * FROM `blog` WHERE blog_id = '%s'", $this->inputs["id"])->fetch_assoc();
            if (!$entry) $this->errorJSON("Invalid ID");
            echo json_encode($entry);
        }
        
        public function actionEdit() {
            //Validate
            if ($this->inputs["title"] == "") $this->errorJSON("You must supply a title!");
            if ($this->inputs["content"] == "") $this->errorJSON("You must supply content!");
            
            //Check id
            $entry = $this->parent->db->query("SELECT * FROM `blog` WHERE blog_id = '%s'", $this->inputs["id"])->fetch_assoc();
            if (!$entry) $this->errorJSON("Invalid ID");
            
            //Update
            $this->parent->db->query("UPDATE `blog` SET title='%s', body='%s' WHERE blog_id = '%s'", $this->inputs["title"], $this->inputs["content"], $this->inputs["id"]);
            
        }
        
        public function actionDelete() {
            //Check id
            $entry = $this->parent->db->query("SELECT * FROM `blog` WHERE blog_id = '%s'", $this->inputs["id"])->fetch_assoc();
            if (!$entry) $this->errorJSON("Invalid ID");
            
            //Delete
            $this->parent->db->query("DELETE FROM `blog` WHERE blog_id = '%s'", $this->inputs["id"]);
        }
        
    }
    
?>
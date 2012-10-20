<?php

    class Gallery_Page extends Page {
    
        public function getInputs() {
            return array("actionLoadfolder" => array("folder" => "post"));
        }
    
        public function actionIndex() {
            //Get gallery folders
            $files = array_reverse(glob("gallery/*"));
            $folders = array();
            foreach($files as $file) {
                if(is_dir($file)) {
                    $folder = array();
                    $folder["name"] = str_replace("gallery/", "", $file);
                    $folder["link"] = $file;
                    $images = glob($file . '/*');
                    $folder["cover"] = $images[0];
                    $folders[] = $folder;
                }
            }
            $data["folders"] = $folders;
            
            $this->parent->template->setSubtitle("gallery");
            $this->parent->template->outputTemplate(array("template" => 'gallery', "data" => $data));
        }
        
        public function actionLoadfolder() {
            
            //Validate
            if ($this->inputs["folder"] == "" || !file_exists("gallery/" . $this->inputs["folder"]) || !is_dir("gallery/" . $this->inputs["folder"])) $this->errorJSON("Invalid folder");
            
            $images = glob("gallery/" . $this->inputs["folder"] . "/*");
            $output = array();
            foreach ($images as $image) {
                if (!is_dir($image)) {
                    $img = array();
                    $file = substr($image, strrpos($image, "/") +1);
                    $folder = substr($image, 0, strrpos($image, "/"));
                    $img["image"] = $image;
                    $img["thumb"] = $folder . "/thumbnails/" . $file;
                    $output[] = $img;
                }
            }
            
            echo json_encode($output);
            
        }
    
    }

?>
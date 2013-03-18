<?php

    class Gallery_Controller extends LanWebsite_Controller {
		
		public function getInputFilters($action) {
			switch ($action) {
				case "loadfolder": return array("folder" => "notnull"); break;
			}
		}
    
        public function get_Index() {
            //Get gallery folders
            $files = array_reverse(glob("data/gallery/*"));
            $folders = array();
            foreach($files as $file) {
                if(is_dir($file)) {
                    $folder = array();
                    $folder["name"] = str_replace("data/gallery/", "", $file);
                    $folder["link"] = $file;
                    $images = glob($file . '/*');
                    $folder["cover"] = (substr($images[0], 0, 1) == "/"?"":"/") . $images[0];
                    $folders[] = $folder;
                }
            }
            $data["folders"] = $folders;
            
            $tmpl = new LanWebsite_Template();
			$tmpl->setSubTitle("Gallery");
            $tmpl->addScript('/js/pages/gallery.js');
            $tmpl->addStyle('/css/pages/gallery.css');
            $tmpl->enablePlugin('galleria');
            $tmpl->addTemplate('public/gallery', $data);
            $tmpl->output();
        }
        
        public function post_Loadfolder($inputs) {
            
            //Validate
            if ($inputs["folder"] == "" || !file_exists("data/gallery/" . $inputs["folder"]) || !is_dir("data/gallery/" . $inputs["folder"])) $this->errorJSON("Invalid folder");
            
            $images = glob("data/gallery/" . $inputs["folder"] . "/*");
            $output = array();
            foreach ($images as $image) {
                if (!is_dir($image)) {
                    $img = array();
                    $file = substr($image, strrpos($image, "/") +1);
                    $folder = substr($image, 0, strrpos($image, "/"));
                    $img["image"] = (substr($image, 0, 1) == "/"?"":"/") . $image;
                    $img["thumb"] = (substr($folder, 0, 1) == "/"?"":"/") . $folder . "/thumbnails/" . $file;
                    $output[] = $img;
                }
            }
            
            echo json_encode($output);
            
        }
    
    }

?>
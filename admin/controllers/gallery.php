<?php

    class Gallery_Controller extends LanWebsite_Controller {
        
        public function getInputFilters($action) {
            switch ($action) {
                case "process": return array("force" => "bool"); break;
            }
        }
        
        public function get_Index() {
			$tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Gallery Management");
            $tmpl->addTemplate('gallery');
			$tmpl->output();
        }
        
        public function post_Process($inputs) {
            
            //Loop through folders
            $folders = glob("gallery/*");
            $total = 0;
            $processed = 0;
            foreach ($folders as $folder) {
                if (!is_dir($folder)) continue;
                
                //Check if thumb folder exists
                if (!file_exists($folder . "/thumbnails/")) {
                    mkdir($folder . "/thumbnails/");
                }
                
                //Loop images
                $images = glob($folder . "/*");
                foreach ($images as $image) {
                
                    if (is_dir($image)) continue;
                    $file = substr($image, strrpos($image, "/") +1);
                    
                    //Check if thumbnail exists or if we are doing a force
                    if (in_array($inputs["force"], array(1, "true")) || !file_exists($folder . "/thumbnails/" . $file)) {
                    
                        //Thumbnail
                        $converter = new SimpleImage();
                        $converter->load($image);
                        $converter->resizeToHeight(80);
                        $converter->save($folder . "/thumbnails/" . $file);
                        
                        $processed++;
                        
                    }
                    $total++;
                    
                }
                
            }
            
            echo json_encode(array("total" => $total, "processed" => $processed));
            
        }
        
    }
    
?>
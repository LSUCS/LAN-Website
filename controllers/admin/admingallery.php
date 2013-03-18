<?php

    class Admingallery_Controller extends LanWebsite_Controller {
    
        public function getInputs() {
            return array("actionProcess" => array("force" => "post"));
        }
        
        public function actionIndex() {
            $this->parent->template->setSubTitle("Gallery");
            $this->parent->template->outputTemplate('admingallery');
        }
        
        public function actionProcess() {
            
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
                    if (in_array($this->inputs["force"], array(1, "true")) || !file_exists($folder . "/thumbnails/" . $file)) {
                    
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
<?php

    class Map_Page extends Page {
    
        public function actionIndex() {
            $this->parent->template->setSubtitle("lan map");
            $this->parent->template->outputTemplate('map');
        }
    
    }

?>
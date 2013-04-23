<?
    class Onlan_Controller extends LanWebsite_Controller {
    
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
            $tmpl->setSubtitle("Home");
            $tmpl->addTemplate("onlan");
            $tmpl->output();
        }
    
    }
?>
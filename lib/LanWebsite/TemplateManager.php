<?php

    class LanWebsite_TemplateManager {
    
        private $base_dir = '/public/';
    
        private $subtitle = '';
        private $refresh = false;
        private $include_core = true;
        private $include_main = true;
        
        private $template_stack = array();
        
        private $styles = array();
        private $scripts = array();
        
        private $plugins = array(
                                "jquery" => array( "scripts" => array( "/js/jquery.min.js" ), "styles" => array(), "include" => true ),
                                "jquery-ui" => array( "scripts" => array( "/js/jquery-ui.custom.min.js" ), "styles" => array( "/css/jquery-ui/jquery-ui.custom.css" ), "include" => true ),
                                "cleditor" => array( "scripts" => array( "/js/jquery.cleditor.min.js" ), "styles" => array( "/css/jquery.cleditor.css" ), "include" => false ),
                                "datatables" => array( "scripts" => array( "/js/jquery.dataTables.min.js", "/js/TableTools.min.js", "/js/ZeroClipboard.js" ), "styles" => array( "/css/jquery.dataTables_themeroller.css", "/css/TableTools_JUI.css" ), "include" => false ),
                                "scrollbar" => array( "scripts" => array( "/js/jquery.mCustomScrollbar.min.js" ), "styles" => array( "css/jquery.mCustomScrollbar.css" ), "include" => false ),
                                "timers" => array( "scripts" => array( "/js/jquery.timers.js" ), "styles" => array(), "include" => false ),
                                "tools" => array( "scripts" => array( "/js/jquery.tools.min.js", "/js/jquery.mousewheel.min.js", "/js/jquery.easing.js" ), "styles" => array(), "include" => false ),
                                "timepicker" => array( "scripts" => array( "/js/jquery-ui.timepicker.js" ), "styles" => array(), "include" => false ),
                                "spinner" => array( "scripts" => array( "/js/ui.spinner.min.js" ), "styles" => array( "/css/ui.spinner.css" ), "include" => false ),
                                "galleria" => array( "scripts" => array( "/js/galleria/galleria-1.2.8.min.js" ), "styles" => array( ), "include" => false),
                                "date" => array( "scripts" => array( "/js/date.js" ), "styles" => array(), "include" => true ),
                                "main" => array( "scripts" => array( "/js/main.js" ), "styles" => array( "/css/main.css" ), "include" => true ),
                                "chat" => array( "scripts" => array( "/js/chat.js" ), "styles" => array( "/css/chat.css" ), "include" => true ),
                                "twitter" => array( "scripts" => array( "//platform.twitter.com/widget.js" ), "styles" => array(), "include" => false)
                                );
                                
        public function setBaseDir($dir) {
            $this->base_dir = $dir;
        }
        
        /**
         *  Add to template stack and add template scripts/styles
         */
        public function addTemplate($template, $data = array()) {
            $this->template_stack[] = array("template" => trim($this->base_dir, "/") . "/templates/" . $template . ".tmpl", "data" => $data);
            if (file_exists(trim($this->base_dir, "/") . "/js/" . $template . ".js")) $this->addScript("/" . trim($this->base_dir, "/") . "/js/" . $template . ".js");
            if (file_exists(trim($this->base_dir, "/") . "/css/" . $template . ".css")) $this->addStyle("/" . trim($this->base_dir, "/") . "/css/" . $template . ".css");
        }
        
        /**
         *  Extra styles/scripts
         */
        public function addScript($script) {
            $this->scripts[] = $script;
        }
        
        public function addStyle($style) {
            $this->styles[] = $style;
        }
        
        
        /**
         *  Plugins
         */
        public function enablePlugin($plugin) {
            if (isset($this->plugins[$plugin])) {
                $this->plugins[$plugin]["include"] = true;
            }
        }
        public function disablePlugin($plugin) {
            if (isset($this->plugins[$plugin])) {
                $this->plugins[$plugin]["include"] = false;
            }
        }
        
        /**
         *  Core
         */
        public function enableCore() {
            $this->include_core = true;
        }
        public function disableCore() {
            $this->include_core = false;
            $this->disableMainTemplate();
        }
        
        /**
         *  Main Template
         */
        public function enableMainTemplate() {
            $this->include_main = true;
            $this->enablePlugin('main');
            $this->enablePlugin('chat');
        }
        public function disableMainTemplate() {
            $this->include_main = false;
            $this->disablePlugin('main');
            $this->disablePlugin('chat');
        }
        
        /**
         *  Refresh
         */
        public function enableRefresh() {
            $this->refresh = true;
        }
        public function disableRefresh() {
            $this->refresh = false;
        }
        
        /**
         *  Subtitle
         */
        public function setSubTitle($title) {
            $this->subtitle = $title;
        }
        
        
        public function output() {
        
            $templates = array();
        
            //Header
            if ($this->include_core) {
                
                //Calculate scripts and styles
                $scripts = array();
                $styles = array();
                foreach ($this->plugins as $plugin) {
                    if (!$plugin['include']) continue;
                    foreach ($plugin['scripts'] as $script) {
                        if (!in_array($script, $scripts)) $scripts[] = $script;
                    }
                    foreach ($plugin['styles'] as $style) {
                        if (!in_array($style, $styles)) $styles[] = $style;
                    }
                }
                $data["styles"] = array_merge($styles, $this->styles);
                $data["scripts"] = array_merge($scripts, $this->scripts);
                
                $templates[] = array("template" => 'templates/core_header.tmpl', "data" => $data);
            }
            if ($this->include_main) {
                $data['title'] = ucwords($this->subtitle) . " | LSUCS LAN" . LanWebsite_Main::getSettings()->getSetting("lan_number");
                $data['refresh'] = $this->refresh;
                $templates[] = array("template" => 'templates/header.tmpl', "data" => $data);
            }
            
            //Body
            if ($this->include_core) {
                $templates[] = array("template" => 'templates/core_body.tmpl', "data" => array());
            }
            if ($this->include_main) {
                $user = LanWebsite_Main::getUserManager()->getActiveUser();
                $data["countdown"] = LanWebsite_Main::getSettings()->getSetting("enable_timer");
                $data["subtitle"] = $this->subtitle;
                $data["loggedin"] = LanWebsite_Main::getAuth()->isLoggedIn();
                $data["admin"] = $user->isAdmin();
                $data["user"] = $user;
                $data["lan"] = LanWebsite_Main::getSettings()->getSetting("lan_number");
                $data["datestring"] = date('d', strtotime(LanWebsite_Main::getSettings()->getSetting("lan_start_date"))) . date("-dS M Y", strtotime(LanWebsite_Main::getSettings()->getSetting("lan_end_date")));
                $data["styles"] = array();
                $data["scripts"] = array();
                $templates[] = array("template" => 'templates/body.tmpl', "data" => $data);
            }
            
            //Content templates
            $templates = array_merge($templates, $this->template_stack);
            
            //Footer
            if ($this->include_core) {
                $templates[] = array("template" => 'templates/core_footer.tmpl', "data" => array());
            }
            if ($this->include_main) {
                $templates[] = array("template" => 'templates/footer.tmpl', "data" => array());
            }
            
            //Output templates
            foreach ($templates as $template) {
                $DataBag = $template['data'];
                include trim($template['template'], "/");
            }
            
        
        }
    
    }

?>
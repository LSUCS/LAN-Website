<?php

    class GameHub_Controller extends LanWebsite_Controller {
    
        public function getInputFilters($action) {
            switch ($action) {
                case "searchsteamgames": return array("game" => "notnull"); break;
            }
        }
    
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
            $tmpl->setSubTitle("Game Hub");
            $tmpl->addTemplate("gamehub");
            $tmpl->enablePlugin("scrollbar");
            $tmpl->enablePlugin('spinner');
            $tmpl->output();
        }
        
        public function get_Getlobbydetails() {
			//Check if lobby is enabled in settings
			if (!LanWebsite_Main::getAuth()->isLoggedIn() || LanWebsite_Main::getSettings()->getSetting("lobby_enabled") == 0) {
				echo json_encode(array("disabled" => true));
				return;
			}
			
			echo json_encode(array("url" => LanWebsite_Main::getSettings()->getSetting("lobby_url")));
        }
        
        public function get_Searchsteamgames($inputs) {
            //Check for valid search term
            if ($this->isInvalid("game")) die(json_encode(array("error" => "No search term provided")));
            
            //Get contents and check for results
            $page = file_get_contents('http://store.steampowered.com/search/results?term=' . $inputs['game'] . '&category1=998&advanced=0&sort_order=ASC&page=1');
            if (strpos($page, 'No results were returned for that query') !== false) die(json_encode(array("error" => "No results found")));
            
            //Parse page for results
            preg_match_all('/<a href="http:\/\/store\.steampowered\.com\/app\/(\d+?)\/.*?" class="search_result_row.*?">.*?<div class="col search_capsule">.*?<img src="(.*?)".*?<div class="col search_name ellipsis">.*?<h4>(.*?)<\/h4>/ism', $page, $matches);
            
            //Output
            $output = array();
            for ($i = 0; $i < count($matches[0]); $i++) {
                $output[] = array("appid" => $matches[1][$i], "icon" => $matches[2][$i], "name" => $matches[3][$i]);
            }
            echo json_encode($output);
        }
    
    }

?>
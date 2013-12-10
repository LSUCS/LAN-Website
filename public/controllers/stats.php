<?
    class Stats_Controller extends LanWebsite_Controller {
    
        public function get_Index() {
            $tmpl = LanWebsite_Main::getTemplateManager();
            $tmpl->setSubtitle("Lan Stats");
            $tmpl->addTemplate("stats");
            $tmpl->enablePlugin("jqplot");
            $tmpl->output();
        }
        
        public function get_Loadstats() {
            $return = null;
            if (!LanWebsite_Cache::get("stats", "gametime", $return)) {
                $res = LanWebsite_Main::getDb()->query("SELECT logger_entries.time,logger_entries.data,logger_entries.type FROM logger_entries,logger_sessions WHERE logger_entries.logger_session_id = logger_sessions.logger_session_id AND logger_sessions.lan_number = '%s' AND (logger_entries.type = 'startgame' OR logger_entries.type = 'stopgame')", LanWebsite_Main::getSettings()->getSetting("lan_number"));
                $total = array();
                $users = array();
                $user = array();
                while ($row = $res->fetch_assoc()) {
                    $data = json_decode($row["data"], true);
                    if ($row["type"] == "stopgame") {
                        if (isset($user[$data["userid"]])) {
                            if (isset($total[$data["game"]])) $total[$data["game"]] += ceil($row["time"] - $user[$data["userid"]]);
                            else $total[$data["game"]] = ceil($row["time"] - $user[$data["userid"]]);
                            unset($user[$data["userid"]]);
                            if (!isset($users[$data["game"]]) || !in_array($data["userid"], $users[$data["game"]])) $users[$data["game"]][] = $data["userid"];
                        }
                    } else {
                        $user[$data["userid"]] = $row["time"];
                    }
                }
                foreach ($users as $game => $l) $users[$game] = count($l);
                asort($total);
                $return = json_encode(array("data" => $total, "total" => array_sum($total), "count" => $users));
                LanWebsite_Cache::set("stats", "gametime", $return, 30);
            }
            echo $return;
        }
    
    }
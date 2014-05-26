<?php

    class Groupseats_Controller extends LanWebsite_Controller {
        
        public function get_Index() {
        
            $db = LanWebsite_Main::getDb();
            
            $dbGroups = $db->query("SELECT ID, seatPreference, groupOwner FROM seatbooking_groups");
            
            $groups = array();
            while($group = $dbGroups->fetch_assoc()) {
                $dbMembers = $db->query("SELECT assigned_forum_id FROM tickets WHERE lan_number = '%s' AND seatbooking_group = '%s'", LanWebsite_Main::getSettings()->getSetting('lan_number'), $group["ID"]);
                $members = array();
                while($member = $dbMembers->fetch_assoc()) {
                    if($member == 0) continue;
                    $members[] = LanWebsite_Main::getUserManager()->getUserById($member["assigned_forum_id"]);
                }
                $group["members"] = $members;
                $groups[] = $group;
            }
            
            
            $tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle("Group Seat Viewer");
            $tmpl->addTemplate('groupseats', $groups);
            $tmpl->output();
        }
        
        public function post_cleanUp() {
            $db = LanWebsite_Main::getDb();
            
            $empty = $db->query("
                DELETE FROM seatbooking_groups
                WHERE ID NOT IN (
                    SELECT DISTINCT seatbooking_group AS ID
                    FROM tickets
                    WHERE lan_number = '%s' AND seatbooking_group != ''
                )",
            LanWebsite_Main::getSettings()->getSetting('lan_number'));
            
            echo $db->getLink()->affected_rows;
        }
    }
    
?>
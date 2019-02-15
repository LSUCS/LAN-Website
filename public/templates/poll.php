<?php
	class Poll_Controller extends LanWebsite_Controller
	{
		public function get_Index()
		{
			$tmpl = LanWebsite_Main::getTemplateManager();
			$tmpl->setSubTitle('Totally Secret Poll');
			$tmpl->addTemplate('poll');
			$tmpl->output();
		}

		public function get_Load()
		{
			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `poll_question_data`");
			$questions = array();
			while($row = $res->fetch_assoc()) $questions[] = $row;

			$res = LanWebsite_Main::getDb()->query("SELECT * FROM `poll_question_choice` ORDER BY question_id");
			$choices = array();
			while($row = $res->fetch_assoc()) $choices[] = $row;

			$return = array($questions, $choices);

			echo json_encode($return);
		}

		public function post_Send($choices)
		{
			LanWebsite_Main::getDb()->query("INSERT INTO `poll_entries` (user_id, choices) VALUES ('%s', '%s')", $user->getUserId(), $choices);
			echo "";
		}
	}
?>
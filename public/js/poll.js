$(document).ready(function()
{
	loadPoll();
	$('#submit_button').live('click', function()
	{
		sendVotes();
	});
});

function loadPoll()
{
	$.get(UrlBuilder.buildUrl(false, "poll", "load"), function(data)
	{
		if(!data) return;
		var questions = data[0];
		var choices = data[1];
		var k = 0;
		for(i in questions)
		{
			$('#award_poll').append("<div class='question' id='question" + i + "'><p>" + questions[i].text + "</p>");
			while(k < choices.length && choices[k].question_id == i)
			{
				$('#award_poll').append("<label><input type='radio' name='q" + i + "' value='" + choices[k].choice_id + "' />" + choices[k].text + "</label><br />");
				++k;
			}
			$('#award_poll').append("</div>");
		}

		$('#award_poll').append("<input type='button' id='submit_button' value='Submit'/>");
	}, 'json');
}

function sendVotes()
{
	var answers = new Array();
	$.each($('.question'), function(counter, el)
	{
		var choice = Number($('input[name=q' + counter + ']:checked').val());
		answers.push({ question: counter, choice: choice });
	});
	
	console.log(answers);

	$.post(UrlBuilder.buildUrl(false, "poll", "send"), { answers: JSON.stringify(answers) },
	function(data)
	{
		//if(data.error)
		//{
		//	alert("Error");
		//}
	}, 'json');
}
var connection = null;
var conversations = Array();

$(document).ready(function() {

	$("#chat").hide();
	return;

	//If websockets not supported, don't load chat
	if (!('WebSocket' in window)) return;
	
	chatConnect();
	
	
	//Contact list toggle
	$("#contacts").click(function() { $("#contact-list").toggle(); });
	
	//Contact click
	$(".contact").live('click', function() {
		initiateConversation($(this).attr('value'));
	});
	
});

function chatConnect() {

	//TODO: Retrieve connection details
	
	connection = new WebSocket('ws://dev.lsucs.org.uk:8087');
	
	connection.onclose = function() {
		console.log("Chat Connection Closed");
		connection = null;
		$("#contacts").html('Chat Service Offline');
		$("#contact-list").html('');
	};
	
	connection.onopen = function() {
		console.log("Chat Connection Opened");

		sendChatCommand("init", "");
		
	}
	
	connection.onerror = function(error) {
		console.log("Chat Error: " + error);
	}
	
	connection.onmessage = function(e) {
		handleChatMessage(e.data);
	}
}

function handleChatMessage(message) {

	//Extract command and payload
	var command = message.substr(0, message.indexOf(":")).toLowerCase();
	var payload = JSON.parse(message.substr(message.indexOf(":") + 1));
	
	console.log(command);
	console.log(payload);
	
	switch (command) {
	
		case 'init':
			loadContactList(payload.contacts);
			break;
		case 'openconversation':
			openConversation(payload);
			break;
	
		case 'error':
			console.log("Chat Error: " + payload.error);
			break;
		
	}

}

function initiateConversation(userid) {
	sendChatCommand("openconversation", { userid: userid });
}

function openConversation(conversation) {
	if ($.inArray(conversation.conversationid, conversations) > -1) return;
	$("#chat").append('<div class="chatbar-element conversation-element" value="' + conversation.conversationid + '"><span class="status ' + conversation.contacts[0].status + '"><li></li></span>' + conversation.contacts[0].name + '<div class="conversation">' + conversation.contacts[0].name + '<div class="messages"></div></div></div>');
	conversations[conversations.length] = conversation.conversationid;
}

function loadContactList(list) {
	$("#chat #contact-list").html('');
	$("#chat #contacts").html('<img src="images/user.png" />' + list.length + ' contacts available');
	for (var i = 0; i < list.length; i++) {
		var contact = list[i];
		$("#chat #contact-list").append('<div class="contact" value="' + contact.userid + '"><span class="chat-avatar"><img src="' + contact.avatar + '" /></span>' + contact.name + '<span class="status ' + contact.status + '"><li></li></span></div>');
	}
}

function sendChatCommand(command, message) {
	if (message.length > 0) message = JSON.stringify(message);
	console.log("Sending command: " + command + ":" + message);
	connection.send(command + ":" + message);
}
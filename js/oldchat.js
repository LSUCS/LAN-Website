var connection = null;
var conversations = Array();
var unread = Array();
var userid = "";
var snd = new Audio("/data/beep.mp3");
var blink = true;

$(document).ready(function() {

	//If websockets not supported, don't load chat
	if (!('WebSocket' in window)) return;
	
	chatConnect();
	
	//Contact list toggle
	$("#contacts").click(function() { $("#contact-list").toggle(); });
	
	//Contact click
	$(document).on({ click: function() { initiateConversation($(this).attr('value')); } }, ".contact");
    
    //Conversation visibility toggle
    $(document).on({ click: function() { toggleConversationVisibility($(this).parent().attr('value')); } }, ".status-bar");
    
    //Conversation height change
    $(document).on({ input: function() { adjustConversationHeight($(this).parent().attr('value')); } }, ".conversation-element .input");
    
    //Conversation message send
    $(document).on(
        { keypress:  function(data) {
                //Catch if enter and cancel default action
                if (data.which == 13) {
                    if ($.trim($(this).html()).length > 0) {
                        sendMessage($(this).parent().parent().attr('value'), $(this).html());
                    }
                    data.preventDefault();
                    $(this).html('');
                    adjustConversationHeight($(this).parent().parent().attr('value'));
                } 
            }
        }, ".conversation-element .input-box");
        
    //Conversation close button
    $(document).on({ click: function() { closeConversation($(this).parent().parent().attr('value')); }}, ".conversation-element .close");
    
    //Conversation focus
    $(document).on({ click: function() { $(this).parent().find(".input-box").focus(); }}, ".conversation-element .messages");
    $(document).on({ focus: function() { checkReadStatus($(this).parent().attr('value')); }}, ".conversation-element .input");
    
    //Conversation blinking
    setInterval(function() {
        if (blink) {
            $(".conversation-blink").removeClass("conversation-blink");
            blink = false;
        }
        else {
            blink = true;
            for (var i = 0; i < unread.length; i++) {
                $(".conversation-element[value='" + unread[i] + "']").addClass("conversation-blink");
            }
        }
    }, 900);
    
    //Adjust conversation height on window change
    $(window).resize(function() {
        $(".conversation-element").each(function() { adjustConversationHeight($(this).attr('value')); });
    });
	
});

function chatConnect() {

	//Get connection details
	$.get(
		UrlBuilder.buildUrl(false, 'chat', 'getdetails'),
		function (data) {
		
			//Chat disabled?
			if (data && data.disabled == 1) {
				$("#chat").hide();
				$("#contacts").html('Chat Service Offline');
				$("#contact-list").html('');
				return;
			}
			
			$("#chat").show();
			connection = new WebSocket(data.url);
			
			connection.onclose = function() {
				console.log("Chat Connection Closed");
				connection = null;
				$("#contacts").html('Chat Service Offline');
				$("#contact-list").html('');
				setTimeout(function() { chatConnect(); }, 3000);
			};
			
			//On open, send init command
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
			
		},
		'json');
	
	

}

function handleChatMessage(message) {

	//Extract command and payload
	var command = message.substr(0, message.indexOf(":")).toLowerCase();
	var payload = JSON.parse(message.substr(message.indexOf(":") + 1));
	
	console.log(command);
	console.log(payload);
	
	switch (command) {
	
		case 'updatecontactlist':
			loadContactList(payload);
			break;
		case 'init':
            userid = payload.userid;
			loadContactList(payload.contacts);
            for (var i = 0; i < payload.conversations.length; i++) {
                if (payload.conversations[i].minimised == 1) var open = false;
                else open = true;
                if (payload.conversations[i].read == 0) unread.push(payload.conversations[i].conversationid);
                openConversation(payload.conversations[i], open, true, false);
            }
			break;
		case 'openconversation':
			openConversation(payload, true, false);
			break;
		case 'error':
			console.log("Chat Error: " + payload.error);
			break;
        case 'sendmessage':
            //If not in focus, play alert and set to unread
            if (!$(".conversation-element[value='" + payload.conversationid + "'] .input-box").is(":focus")) {
                if (!$.inArray(parseInt(payload.conversationid), unread) > -1) unread.push(payload.conversationid);
                playAlert();
            }
            //Otherwise mark as read
            else {
                sendChatCommand("readconversation", { convID: payload.conversationid });
            }
            displayMessage(payload);
            break;
		
	}

}

function playAlert() {
    snd.play();
}

function checkReadStatus(convId) {
    if ($.inArray(convId, unread) > -1) {
        sendChatCommand("readconversation", { convID: convId });
        var i = unread.indexOf(convId);
        unread.splice(i, 1);
    }
}

function closeConversation(convId) {
    sendChatCommand("closeconversation", { convID: convId });
    $(".conversation-element[value='" + convId + "']").remove();
    var i = conversations.indexOf(convId);
    conversations.splice(i, 1);
}

function displayMessage(message) {
    var convElem = $(".conversation-element[value='" + message.conversationid + "']")
    convElem.find('.messages').append('<div>' + message.contact.name + ': ' + message.message + '</div>').scrollTop(convElem.find('.messages')[0].scrollHeight);
}

function adjustConversationHeight(conversationId) {
    var conv = $(".conversation-element[value='" + conversationId + "']");
    if (conv.hasClass('conversation-closed')) conv.css('margin-top', '0px');
    else conv.css('margin-top', ($(window).height() - conv.find('.messages').outerHeight() - conv.find('.input').outerHeight() - conv.find('.status-bar').outerHeight()) + 'px');
}

function toggleConversationVisibility(conversationId) {
    var conv = $(".conversation-element[value='" + conversationId + "']");
    //Minimise
    if (conv.hasClass("conversation-open")) {
        conv.removeClass("conversation-open").addClass("conversation-closed");
        sendChatCommand('minimiseconversation', { convID: conversationId });
    }
    //Maximise
    else {
        conv.removeClass("conversation-closed").addClass("conversation-open");
        sendChatCommand('maximiseconversation', { convID: conversationId });
        conv.find(".input-box").focus();
    }
    adjustConversationHeight(conversationId);
}

function sendMessage(convId, message) {
    sendChatCommand("sendmessage", { convID: convId, message: message });
}

function initiateConversation(userid) {
	sendChatCommand("openconversation", { userid: userid });
}

function openConversation(conversation, open, refresh, focus) {
	if ($.inArray(parseInt(conversation.conversationid), conversations) > -1 && !refresh) return $(".conversation-element[value='" + conversation.conversationid + "']").find(".input-box").focus();
    if (refresh) $(".conversation-element[value='" + conversation.conversationid + "']").remove();
    
	conversations[conversations.length] = parseInt(conversation.conversationid);
    var title = new Array();
    for (var i = 0; i < conversation.contacts.length; i++) {
        if (conversation.contacts[i].userid != userid) title.push(conversation.contacts[i].name);
    }
    if (open) var visibility = "open";
    else var visibility = "closed";
	$("#chat #conversations").append('<div class="conversation-element conversation-' + visibility + '" value="' + conversation.conversationid + '"><div class="status-bar"><span class="status ' + conversation.contacts[0].status + '"><li></li></span>' + title.join(', ') + '<span class="close"></span></div><div class="messages"></div><div class="input"><div class="input-box" contentEditable></div></div></div>');
	adjustConversationHeight(conversation.conversationid);
    for (var i = 0; i < conversation.history.length; i++) {
        displayMessage(conversation.history[i]);
    }
    if (focus != null && focus != false) $(".conversation-element[value='" + conversation.conversationid + "']").find(".input-box").focus();
}

function loadContactList(list) {
	$("#chat #contact-list").html('');
	$("#chat #contacts").html('<img src="/images/user.png" />' + list.length + ' contacts available');
	for (var i = 0; i < list.length; i++) {
		var contact = list[i];
		$("#chat #contact-list").append('<div class="contact" value="' + contact.userid + '"><span class="chat-avatar"><img src="' + contact.avatar + '" /></span>' + contact.name + '<span class="status ' + contact.status + '"><li></li></span></div>');
	}
}

function sendChatCommand(command, message) {
	if (typeof(message) === 'object') {
		message = JSON.stringify(message);
	}
	console.log("Sending command: " + command + ":" + message);
	connection.send(command + ":" + message);
}
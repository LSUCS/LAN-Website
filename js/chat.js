$(document).ready(function() {

	//If websockets not supported, don't load chat
	if (!('WebSocket' in window)) return;
	
	ChatClient.connect();
	
	//Contact list toggle
	$("#contacts").click(function() { if (ChatClient.connection != null) $("#contact-list").toggle(); });
	
	//Contact click
	$(document).on({ click: function() { ChatClient.initiateConversation($(this).attr('value')); } }, ".contact");
    
    //Conversation visibility toggle
    $(document).on({ click: function() { ChatClient.toggleConversationVisibility($(this).parent().attr('value')); } }, ".status-bar");
    
    //Conversation height change
    $(document).on({ input: function() { ChatClient.adjustConversationHeight($(this).parent().attr('value')); } }, ".conversation-element .input");
    
    //Conversation message send
    $(document).on(
        { keypress:  function(data) {
                //Catch if enter and cancel default action
                if (data.which == 13) {
                    if ($.trim($(this).html()).length > 0) {
                        ChatClient.sendMessage($(this).parent().parent().attr('value'), $(this).html());
                    }
                    data.preventDefault();
                    $(this).html('');
                    ChatClient.adjustConversationHeight($(this).parent().parent().attr('value'));
                } 
            }
        }, ".conversation-element .input-box");
        
    //Conversation close button
    $(document).on({ click: function(e) { ChatClient.closeConversation($(this).parent().parent().attr('value')); e.stopPropagation(); }}, ".conversation-element .close");
    
    //Conversation focus
    $(document).on({ click: function() { $(this).parent().find(".input-box").focus(); }}, ".conversation-element .messages");
    $(document).on({ focus: function() { ChatClient.checkReadStatus($(this).parent().attr('value')); }}, ".conversation-element .input");
    
    //Conversation blinking
    setInterval(function() {
        ChatClient.toggleBlink();
    }, 700);
    
    //Adjust conversation height on window change
    $(window).resize(function() {
        $(".conversation-element").each(function() { ChatClient.adjustConversationHeight($(this).attr('value')); });
    });
	
});

var ChatClient = {

    connection: null,
    userid: null,
    conversations: {},
    snd: new Audio("/data/beep.mp3"),
    blink: true,
    
    connect: function () {
    
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
                ChatClient.connection = new WebSocket(data.url);
                
                ChatClient.connection.onclose = function() {
                    console.log("Chat Connection Closed");
                    ChatClient.connection = null;
                    this.conversations = {};
                    $("#contact-list").hide();
                    $("#chat .contact, #chat .conversation-element").remove();
                    $("#chat #chat-offline").fadeIn(200);
                    $("#chat #chat-online").fadeOut(200)
                    setTimeout(function() { ChatClient.connect(); }, 3000);
                };
                
                ChatClient.connection.onopen = function() {
                    console.log("Chat Connection Opened");
                    ChatClient.sendChatCommand("init", "");
                }
                
                ChatClient.connection.onerror = function(error) {
                    console.log("Chat Error: " + error);
                }
                
                ChatClient.connection.onmessage = function(e) {
                    ChatClient.handleChatMessage(e.data);
                }
                
            },
            'json');
    
    },
    
    handleChatMessage: function (message) {
    
    	//Extract command and payload
        var command = message.substr(0, message.indexOf(":")).toLowerCase();
        var payload = JSON.parse(message.substr(message.indexOf(":") + 1));
        
        console.log("Received chat command: " + command);
        console.log(payload);
        
        switch (command) {
                
            case 'init':
                this.userid = payload.userid;
                $("#chat .contact").remove();
                $("#chat #chat-offline").fadeOut(200);
                $("#chat #chat-online").fadeIn(200);
                $("#contact-list").show();
                
                //Load contacts
                for (var contact in payload.contacts) {
                    this.updateContact(payload.contacts[contact]);
                }
                
                //Load conversations
                this.conversations = {};
                for (var c in payload.conversations) {
                    if (payload.conversations[c].minimised == 1) var open = false;
                    else open = true;
                    this.openConversation(payload.conversations[c], true, false);
                }
                break;
                
            case 'updatecontact':
                this.updateContact(payload);
                break;
                
            case 'openconversation':
                this.openConversation(payload, false);
                break;
                
            case 'error':
                console.log("Chat Error: " + payload.error);
                break;
                
            case 'sendmessage':
                //If not in focus, play alert
                if (!$(".conversation-element[value='" + payload.conversationid + "'] .input-box").is(":focus")) {
                    this.playAlert();
                    this.conversations[payload.conversationid].read = 0;
                }
                //Otherwise mark as read
                else {
                    this.sendChatCommand("readconversation", { convID: payload.conversationid });
                }
                this.displayMessage(payload);
                break;
            
        }
        
    },
    
    toggleBlink: function() {
        if (this.blink) {
            $(".conversation-blink").removeClass("conversation-blink");
            this.blink = false;
        }
        else {
            this.blink = true;
            for (var c in this.conversations) {
                if (this.conversations[c].read == 0) {
                    $(".conversation-element[value='" + this.conversations[c].conversationid + "']").addClass("conversation-blink");
                    for (var i in this.conversations[c].contacts) {
                        if (this.conversations[c].contacts[i].userid != this.userid) $("#chat .contact[value='" + this.conversations[c].contacts[i].userid + "']").addClass("conversation-blink");
                    }
                }
            }
        }
    },
    
    playAlert: function () {
        this.snd.play();
    },
    
    checkReadStatus: function (convId) {
        if (this.conversations[convId].read == 0) {
            this.sendChatCommand("readconversation", { convID: convId });
            this.conversations[convId].read = 1;
        }
    },
    
    closeConversation: function (convId) {
        this.sendChatCommand("closeconversation", { convID: convId });
        $(".conversation-element[value='" + convId + "']").remove();
        delete this.conversations[convId];
    },
    
    displayMessage: function (message) {
        var convElem = $(".conversation-element[value='" + message.conversationid + "']");
        this.conversations[message.conversationid].history.push(message);
        convElem.find('.messages').append('<div>' + message.contact.name + ': ' + message.message + '</div>').scrollTop(convElem.find('.messages')[0].scrollHeight);
    },
    
    adjustConversationHeight: function (conversationId) {
        var conv = $(".conversation-element[value='" + conversationId + "']");
        if (conv.hasClass('conversation-closed')) conv.css('margin-top', ($(window).height() - 30) + 'px');
        else conv.css('margin-top', ($(window).height() - conv.find('.messages').outerHeight() - conv.find('.input').outerHeight() - conv.find('.status-bar').outerHeight()) + 'px');
    },
    
    toggleConversationVisibility: function (conversationId) {
        var conv = $(".conversation-element[value='" + conversationId + "']");
        //Minimise
        if (conv.hasClass("conversation-open")) {
            conv.removeClass("conversation-open").addClass("conversation-closed");
            this.sendChatCommand('minimiseconversation', { convID: conversationId });
        }
        //Maximise
        else {
            conv.removeClass("conversation-closed").addClass("conversation-open");
            this.sendChatCommand('maximiseconversation', { convID: conversationId });
            conv.find(".input-box").focus();
        }
        this.adjustConversationHeight(conversationId);
    },
    
    sendMessage: function (convId, message) {
        this.sendChatCommand("sendmessage", { convID: convId, message: message });
    },
    
    initiateConversation: function (userid) {
        this.sendChatCommand("openconversation", { userID: userid });
    },
    
    openConversation: function (conversation, refresh, focus) {
        if (this.conversations[conversation.conversationid] != null && !refresh) return $(".conversation-element[value='" + conversation.conversationid + "']").find(".input-box").focus();
        if (refresh) {
            $(".conversation-element[value='" + conversation.conversationid + "']").remove();
            delete this.conversations[conversation.conversationid];
        }
        
        this.conversations[conversation.conversationid] = conversation;
        var contact = null;
        var self = null;
        for (var c in conversation.contacts) {
            if (conversation.contacts[c].userid != this.userid) contact = conversation.contacts[c];
            else self = conversation.contacts[c];
        }
        if (self.minimised == 0) var visibility = "open";
        else var visibility = "closed";
        $("#chat #conversations").append('<div class="conversation-element conversation-' + visibility + '" value="' + conversation.conversationid + '"><div class="status-bar"><span value="' + contact.userid + '" class="status ' + contact.details.status + '"><li></li></span>' + contact.details.name + '<span class="close"></span></div><div class="messages"></div><div class="input"><div class="input-box" contentEditable></div></div></div>');
        this.adjustConversationHeight(conversation.conversationid);
        for (var h in conversation.history) {
            this.displayMessage(conversation.history[h]);
        }
        if (focus != null && focus != false) $(".conversation-element[value='" + conversation.conversationid + "']").find(".input-box").focus();
    },
    
    sendChatCommand: function (command, message) {
        if (typeof(message) === 'object') {
            message = JSON.stringify(message);
        }
        console.log("Sending command: " + command + ":" + message);
        this.connection.send(command + ":" + message);
    },
    
    updateContact: function (contact) {
    
        //Work out 'domain' (group for contact)
        if (contact.status == "online") var domain = $("#chat #contact-list #online");
        else var domain = $("#chat #contact-list #offline");
        
        //Get contact element
        var elem = $("#chat .contact[value='" + contact.userid + "']");
        
        //Add new?
        if (elem.length == 0) {
            domain.append('<div style="opacity: 0; height: 0;" class="contact" value="' + contact.userid + '"><span class="chat-avatar"><img src="' + contact.avatar + '" /></span><span class="name">' + contact.name + '</span><span value="' + contact.userid + '" class="status ' + contact.status + '"><li></li></span></div>');
        }
        //Or skip?
        else if (elem.parent().attr('id') == contact.status) {
            return;
        }
        //Else fade out
        else {
            elem.animate({ opacity: 0 }, 100).animate({ height: 0 }, 100);
        }
        
        elem.appendTo(domain.get());
        $("#chat").find(".status[value='" + contact.userid + "']").removeClass("offline online").addClass(contact.status);
        
        //Sort
        domain.find(".contact").sortElements(function(a, b) {
            return $(a).find('.name').html() > $(b).find('.name').html() ? 1: -1;
        });
        
        //Reveal element
        $("#chat .contact[value='" + contact.userid + "']").animate({ height: "30px" }, 100).animate({ opacity: 1 }, 100);
        
        this.updateContactCount();
    },
    
    updateContactCount: function () {
        $("#chat #contact-count").html($("#chat #contact-list .contact").length);
    },


};
var searchtimer = null;
$(document).ready(function() {

    //Editor binds
    $(document).on({ click: function() {
        $(".other-game").fadeOut(300);
        $(".steam-input-container").fadeIn(300);
        $("#overlay .icon-section").slideUp(200);
    }}, ".steam-game-label");
    $(document).on({ click: function() {
        $(".steam-input-container").fadeOut(300);
        $("#overlay .steam-search").slideUp(200);
        $("#overlay .steam-search").html('');
        $("#overlay .steam-game").val('');
        $("#overlay .icon-section").slideDown(200);
        $(".other-game").fadeIn(300);
    }}, ".other-game-label");
    $(document).on({ click: function() {
        $(".max-players").parent().fadeOut(300);
    }}, ".unlimited-players-label");
    $(document).on({ click: function() {
        $(".max-players").parent().fadeIn(300);
    }}, ".max-players-label");
    $(document).on({ click: function() {
        $(".password").fadeOut(300);
    }}, ".nopassword-label");
    $(document).on({ click: function() {
        $(".password").fadeIn(300);
    }}, ".password-label");
    //Steam game search
    $(document).on({ input: function() {
        clearTimeout(searchtimer);
        $("#overlay .steam-search").html('');
        $("#overlay .steam-search").slideUp(200);
        if ($(this).val().length > 0) {
            searchtimer = setTimeout(function() {
                $("#overlay .steam-search").slideUp(200);
                $("#overlay .steam-input-container .spinner").fadeIn(200);
                $.get(
                    UrlBuilder.buildUrl(false, "gamehub", "searchsteamgames", { game: $("#overlay .steam-game").val() }),
                    function(data) {
                        $("#overlay .steam-search").html('');
                        if ($("#overlay #select-steam-game").is(":checked")) {
                            if (data.length > 0) {
                                for (i = 0; i < data.length; i++) {
                                    $("#overlay .steam-search").append('<div class="search-result" value="' + data[i].appid + '"><img src="' + data[i].icon + '" /><div class="name">' + data[i].name + '</div></div>');
                                }
                                setTimeout(function() { $("#overlay .steam-search").mCustomScrollbar({
                                    scrollButtons: { enable: true },
                                    theme: "dark"
                                });}, 210);
                            } else {
                                $("#overlay .steam-search").append('<div class="search-noresults">No results found for that search</div>');
                            }
                            $("#overlay .steam-search").slideDown(200);
                        }
                        $("#overlay .steam-input-container .spinner").fadeOut(200);

                    },
                    'json');
                }, 500);
        }
        
    }}, ".steam-game");
    $(document).on({ click: function() {
        $(".result-selected").removeClass("result-selected");
        $(this).addClass("result-selected");
    }}, ".search-result");
    
    //Scroll bars
    $("#lobbies, #info-container").mCustomScrollbar({
        scrollButtons: { enable: true },
        theme: "dark"
    });
    
    //Open lobby editor bind
    $(".create-lobby").click(function() {
        LobbyClient.openLobbyEditor();
    });
    
    //Submit lobby editor bind
    $(document).on({ click: function() {
        LobbyClient.submitLobbyEditor();
    }}, ".submit-lobbyeditor");
    
    LobbyClient.connect();
    
});

var LobbyClient = {

    connection: null,
    lobby: null,

    connect: function () {
    
    	//Get connection details
        $.get(
            UrlBuilder.buildUrl(false, 'gamehub', 'getlobbydetails'),
            function (data) {
            
                //Disabled?
                if (data && data.disabled == 1) {
                    return;
                }
                
                LobbyClient.connection = new WebSocket(data.url);
                
                LobbyClient.connection.onclose = function() {
                    console.log("Lobby server connection closed");
                    LobbyClient.connection = null;
                    $("#connecting").slideDown(200);
                    $("#lobby-client").slideUp(200);
                    setTimeout(function() { LobbyClient.connect(); }, 3000);
                };
                
                LobbyClient.connection.onopen = function() {
                    console.log("Lobby server connection opened");
                    LobbyClient.sendLobbyCommand("init", "");
                    $("#connecting").slideUp(200);
                    $("#lobby-client").slideDown(200);
                }
                
                LobbyClient.connection.onerror = function(error) {
                    console.log("Lobby Error: " + error);
                }
                
                LobbyClient.connection.onmessage = function(e) {
                    LobbyClient.handleLobbyCommand(e.data);
                }
                
            },
            'json');
        
    },
    
    handleLobbyCommand: function (data) {
    
        //Extract command and payload
        var command = data.substr(0, data.indexOf(":")).toLowerCase();
        var payload = JSON.parse(data.substr(data.indexOf(":") + 1));
        
        console.log("Received lobby command: " + command);
        console.log(payload);
        
        switch (command) {
        
            //Init command - sends active lobby, lobby list and global chat history
            case 'init':
                
                break;
                
            //Create lobby - reciprocated method for reporting creation errors
            case "createlobby":
            
                if (payload.error != null) {
                    $("#overlay .error").html(payload.error).slideDown(200);
                    $("#overlay .loading").slideUp(200);
                    $("#overlay .submit-lobbyeditor").slideDown(200);
                } else {
                    Overlay.closeOverlay();
                }
            
                break;
                
            //Join lobby command
            case 'joinlobby':
            
                $("#lobbies-container").fadeOut(200);
                $("#in-lobby").fadeIn(200);
            
                break;
            
            //New information for a lobby
            case 'updatelobby':
            
                break;
            
            //Message received for lobby chat
            case 'sendlobbychat':
            
                break;
            
            //Message received for global chat
            case 'sendglobalchat':
            
                break;
                
            default:
                console.log("Invalid command received from lobby server");
                break;
        }
    
    },
    
    sendLobbyCommand: function (command, message) {
        if (this.connection == null) {
            console.log("Error: attempting to send lobby command without connection");
            return;
        }
        if (typeof(message) === 'object') {
            message = JSON.stringify(message);
        }
        console.log("Sending lobby command: " + command + ":" + message);
        this.connection.send(command + ":" + message);
    },

    openLobbyEditor: function (edit, title, steamgame, othergame, players) {
        Overlay.openOverlay(true, $("#lobbyeditor-container").html());
        $("#overlay .max-players").spinner({ min: 2, max: 50 });
        if (edit != null && edit == true) {
            $("#overlay .submit-lobbyeditor").html('Save Lobby');
        } else {
            $("#overlay .submit-lobbyeditor").html('Create Lobby');
        }
        $("#overlay .submit-lobbyeditor").button();
    },
    
    submitLobbyEditor: function () {
    
        var obj = {
            password: "",
            maxplayers: -1,
            icon: "",
            game: "",
            title: "",
            description: $("#overlay .description").val()
        };
        
        var error = null;
        
        //Password
        if ($("#overlay #select-password").is(":checked") && $.trim($("#overlay .password").val()).length < 3) {
            error = "Invalid password - must be at least 3 characters in length";
        } else if ($("#overlay #select-password").is(":checked")) {
            obj.password = $("#overlay .password").val();
        }
        
        //Players
        if ($("#overlay #select-max-players").is(":checked")) {
            obj.maxplayers = $("#overlay .max-players").val();
        }
        
        //Game + icon
        if ($("#overlay #select-other-game").is(":checked") && $.trim($("#overlay .other-game").val()).length == 0) {
            error = "Invalid game provided - must be at least 1 character in length";
        } else if ($("#overlay #select-other-game").is(":checked")) {
            obj.game = $("#overlay .other-game").val();
            obj.icon = $("#overlay .custom-icon").val();
        } else if ($("#overlay #select-steam-game").is(":checked") && $("#overlay .result-selected").length == 0) {
            error = "Please search and select a Steam game or select 'Other'";
        } else {
            obj.game = $("#overlay .result-selected").find(".name").html();
            obj.icon = "http://cdn3.steampowered.com/v/gfx/apps/" + $("#overlay .result-selected").attr('value') + "/header_292x136.jpg";
        }
        
        //Title
        if ($.trim($("#overlay .title").val()).length == 0) {
            error = "Invalid title provided - must be at least 1 character in length";
        } else {
            obj.title = $("#overlay .title").val();
        }
        
        //Error check
        if (error != null) {
            $("#overlay .error").html(error).slideDown(200);
            return;
        } else {
            $("#overlay .error").slideUp(200);
        }
        
        $("#overlay .submit-lobbyeditor").slideUp(200);
        $("#overlay .loading").slideDown(200);
    
        //New lobby
        if ($("#overlay .submit-lobbyeditor").find('span').html() == 'Create Lobby') {
            this.sendLobbyCommand("createlobby", obj);
        }
        //Edit existing lobby
        else {
            obj.lobbyid = this.lobby.lobbyid;
            this.sendLobbyCommand("editlobby", obj);
        }
    
    },
    
    leaveLobby: function () {
    
    },
    
    joinLobby: function (lobbyId) {
    
    },
    
    sendLobbyChat: function (message) {
    
    },
    
    sendGlobalChat: function(message) {
    
    }

}
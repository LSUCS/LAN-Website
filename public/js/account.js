var assignID = "";
var odd = true;

$(document).ready(function() {

    //Check details
    if (PageVars["page"] && PageVars["page"].toLowerCase() != "account") {
        checkAccountDetails();
    }

    loadDetails();
    
    //Check details
    $.get(
        UrlBuilder.buildUrl(false, "account", "checkdetails"),
        function (data) {
            if (data != null && data.incomplete) {
                Overlay.openOverlay(true, data.message);
            }
        },
        'json');
    
    //Save details button
    $("#save-account-details").click(function() {
        saveAccountDetails();
    });
    
    //Claim ticket button
    $("#claim-ticket-button").click(function() {
        if ($("#claim-code").val() == "") {
            Overlay.openOverlay(true, "Please supply a claim code");
            return;
        }
        $("#overlay-content").html('<label for="claim-email">Claim Email: </label><input type="text" id="claim-email" /><button id="confirm-claim">Claim</button>');
        $("#confirm-claim").button();
        Overlay.openOverlay(true, "");
    });
    
    $(document).on({'click': function() {
        claimTicket();
    }}, "#confirm-claim");
    
    $('#group-info').hide();
    //Change booking group buttons
    $("#input-creategroup").click(function() {
        groupButton('create');
    });
    $("#input-joingroup").click(function() {
        groupButton('join');        
    });
    $('#input-leavegroup').click(function() {
        groupButton('leave');
    });
    $('#update-info').click(function() {
        
        $.post(UrlBuilder.buildUrl(false, "account", "updatepreference"),
            {preference: $('#seating-info').val(), groupid: $('#current-group').html()},
            function() {
                Overlay.openOverlay(false, "Preference Updated", 1000);
            }
        ); 
    });
    
    //Code link
    if (PageVars["code"] && PageVars["code"] != "") {
        $("#claim-code").val(PageVars["code"]);
        $("#claim-ticket-button").trigger('click');
    }
    
    //Assign link
    $(document).on({'click': function() {
        assignID = $(this).parent().siblings().first().html();
        $("#overlay-content").html('<label for="assign-name">Forum Name: </label><input id="assign-name" /><button id="assign-ticket">Assign</button>');
        $("#assign-ticket").button();
        $("#assign-name").autocomplete({
            source: UrlBuilder.buildUrl(false, "account", "autocomplete"),
            minLength: 2
        });
        Overlay.openOverlay(true, "");
    }}, ".assign-link");
    $(document).on({'click': function() {
        $.post(UrlBuilder.buildUrl(false, "account", "assignticket"),
        { name: $("#assign-name").val(), ticket_id: assignID },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Ticket assigned", 1000);
            loadTickets();
        },
        'json');
    }}, "#assign-ticket");
    
    //Game autocomplete
    $("#add-game").autocomplete({
        source: UrlBuilder.buildUrl(false, "account", "suggestgame"),
        minLength: 2
    });
    
    //Add Game Button
    $("#add-game-button").click(function() {
        if ($("#add-game").val().length > 0) {
            var exists = false;
            $(".game").each(function() { if ($(this).attr('value').toLowerCase() == $("#add-game").val().toLowerCase()) exists = true; });
            if (exists) return;
            if ($("#favourite-games").html().indexOf("No Games Added") != -1) $("#favourite-games").html("");
            $("#favourite-games").append('<div class="game ' + (odd?"odd":"even") + '" value="' + $("#add-game").val() + '">' + $("#add-game").val() + '<span class="delete-game"></span></div>');
            odd = !odd;
            $("#add-game").val("");
        }
    });
    
    //Delete game
    $(document).on({'click': function() {
        $(this).parent().remove();
        odd = true;
        $(".game").each(function() {
            $(this).removeClass('odd');
            $(this).removeClass('even');
            $(this).addClass((odd?"odd":"even"));
            odd = !odd;
        });
        if ($(".game").length == 0) $("#favourite-games").html("No Games Added");
    }}, ".delete-game");
    
    //Save game details
    $("#save-game-details").click(function() {
        saveGameDetails();
    });
    
    //Van buttons
    $("#cancel-van").click(function() {
        deleteVan();
    });
    $("#request-van, #edit-van").click(function() {
        saveVanDetails();
    });
    
});

function saveVanDetails() {
    var string = "By clicking continue you are accepting that LSU Computer Society is not responsible for the welfare of any equipment collected in the LAN Van and whilst the utmost care will be taken we can offer no gaurantee that " +
                "that items will not get damaged and are not liable to cover any costs if damage were to occur. You are also accepting that if you are a non-member you will be charged the sum of £2.50 for each collection and drop-off " +
                "(total of £5 per LAN) to be paid to us when we collect your equipment. We reserve the right to reject any equipment that we feel is unfit for travel (including incomplete/missing computer cases and unprotected fragile equipment). " +
                "Only two pieces of equipment may be transported per person in the LAN Van.<br /><button id='accept-van'>Continue</button>";
    Overlay.openOverlay(true, string);
    $("#accept-van").button();
    $("#accept-van").click(function() {
        $.post(
            UrlBuilder.buildUrl(false, "account", "editvandetails"),
            { phone: $("#phone_number").val(), address: $("#address").val(), postcode: $("#postcode").val(), collection: $("#collection").prop("checked"), dropoff: $("#dropoff").prop("checked"), availability: $("#availability").val() },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Van request sent", 1000);
                loadDetails();
            },
            'json');
    });
}

function deleteVan() {
    Overlay.loadingOverlay();
    $.get(
        UrlBuilder.buildUrl(false, "account", "deletevan"),
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Van request deleted", 1000);
            loadDetails();
        },
        'json');
}

function saveGameDetails() {
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(false, "account", "editgamedetails"),
        { steam: $("#steam-name").val(), currently_playing: $("#currently-playing").val(), favourite_games: $.map($(".game"), function (a) { return $(a).attr('value'); }) },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Game details updated", 1000);
            loadDetails();
        },
        'json');
}

function saveAccountDetails() {
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(false, "account", "editaccountdetails"),
        { name: $("#real-name").val(), emergency_contact_name: $("#emergency-contact-name").val(), emergency_contact_number: $("#emergency-contact-number").val() },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Account details updated", 1000);
            loadDetails();
        },
        'json');
}

function claimTicket() {
    $.post(
        UrlBuilder.buildUrl(false, "account", "claimticket"),
        { email: $("#claim-email").val(), code: $("#claim-code").val()  },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Ticket claimed to your account", 1000);
            loadTickets();
        },
        'json');
    Overlay.openOverlay(false, '<img src="images/loading.gif" />');
}

function loadDetails() {
    $.post(
        UrlBuilder.buildUrl(false, "account", "getdetails"),
        function (data) {
            //Account details
            $("#real-name").val(data.real_name);
            $("#emergency-contact-name").val(data.emergency_contact_name);
            $("#emergency-contact-number").val(data.emergency_contact_number);
            //Games
            $("#steam-name").val(data.steam_name);
            $("#currently-playing").val(data.currently_playing);
            if (data.games && data.games.length > 0) {
                $("#favourite-games").html("");
                for (var i = 0; i < data.games.length; i++) {
                    $("#favourite-games").append('<div class="game ' + (odd?"odd":"even") + '" value="' + data.games[i] + '">' + data.games[i] + '<span class="delete-game"></span></div>');
                    odd = !odd;
                }
            }
            //Van
            $("#van-buttons button, #disabled-van").hide();
            if (data.van) {
                $("#phone_number").val(data.van.phone_number);
                $("#address").val(data.van.address);
                $("#postcode").val(data.van.postcode);
                $("#availability").val(data.van.available);
                if (data.van.collection == 1) $("#collection").prop('checked', true);
                else $("#collection").prop('checked', false);
                if (data.van.dropoff == 1) $("#dropoff").prop('checked', true);
                else $("#dropoff").prop('checked', false);
                if (data.van_enabled) {
                    $("#edit-van").show();
                    $("#cancel-van").show();
                } else {
                    $("#disabled-van").show();
                    $("#lan-van input, #lan-van textarea").attr('disabled', 'disabled');
                }
            }
            else {
                $("#phone_number").val("");
                $("#address").val("");
                $("#postcode").val("");
                $("#availability").val("");
                $("#collection").prop('checked', true);
                $("#dropoff").prop('checked', true);
                if (data.van_enabled) {
                    $("#request-van").show();
                } else {
                    $("#disabled-van").show();
                    $("#lan-van input, #lan-van textarea").attr('disabled', 'disabled');
                }
            }
            
            //Tickets
            $("#table-body").html("");
            if (data.tickets.length) {
                for (var i = 0; i < data.tickets.length; i++) {
                    var ticket = data.tickets[i];
                    var row = '<div class="ticket-row' + (i % 2?' odd':' even') + (i == data.tickets.length-1?' end-ticket':'') + '"><span class="ticket-id">' + ticket["ticket_id"] + '</span>';
                    row += '<span class="lan">' + ticket["lan_number"] + '</span>';
                    row += '<span class="ticket-type">' + ticket["member_ticket"].replace("1", "Member").replace("0", "Non-Member") + '</span>';
                    row += '<span class="purchaser">' + ticket["purchased_forum_name"] + '</span>';
                    if (ticket["assigned_forum_name"] != "") {
                        row += '<span class="assigned">' + ticket["assigned_forum_name"] + '</span>';
                    } else {
                        row += '<span class="assigned"><a class="assign-link">Assign Ticket</a></span>';
                    }
                    row += '<span class="activated">' + ticket["activated"].replace("1", "Yes").replace("0", "No") + '</span>';
                    row += '<span class="seat">' + ticket["seat"] + '</span></div>';
                    $("#table-body").append(row);
                }
            } else {
                $("#table-body").append('<div class="no-tickets odd end-ticket">No tickets found for your account</div>');
            }
            
            //Group Seat Booking
            $("#booking-table-body").html("");
            if(data.groupBooking.error) {
                $("#seatbookingError").html(data.groupBooking.error);
                $("#input-groupid").prop('disabled', true);
                $("#input-groupid").css('background-color', '#DDD');
                $("#input-groupid").css('background-image', 'none');
                $("#claim-ticket-button").prop("disabled", true);
                $('#group-info').hide();                
            }
            if(data.groupBooking.group) {
                $("#current-group").html(data.groupBooking.group.ID);
                $('#seating-info').html(data.groupBooking.group.seatPreference);
                if(data.groupBooking.groupMembers.length) {
                    for(var i = 0; i < data.groupBooking.groupMembers.length; i++) {
                        var groupMember = data.groupBooking.groupMembers[i];
                        var row = '<div class="booking-row' + (i % 2?' odd':' even') + '">';
                        row += '<span class="username">' + groupMember + '</span>';
                        row += '</div>';
                        $("#booking-table-body").append(row);
                    }
                }
                $('#group-info').show();
                if(data.groupBooking.group.showUpdate) {
                    $('#update-info').show();
                } else {
                    $('#update-info').hide();
                    $('#seating-info').attr("readonly", "readonly");
                }
            } else {
                $('#group-info').hide();
            }
        },
        'json');
}

function groupButton(action) {
    if(action == "leave") {
        var groupID = $('#current-group').html();
    } else {
        if($("#input-groupid").val() == "") {
            $('#input-groupid').css('border-color', 'red');
            $('#input-groupid').addClass('shaking');
            window.setTimeout("$('#input-groupid').removeClass('shaking');", 1000);
            return;
        } else {
            var groupID = $("#input-groupid").val();
        }
    }            
    $.post(UrlBuilder.buildUrl(false, "account", action + "group"),
        {groupid: groupID},
        function(data) {
            console.log(data.error);
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            loadDetails();
        }
    , 'json');
}
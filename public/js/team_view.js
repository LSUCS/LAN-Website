$(document).ready(function() {    
    $('#invite-button').button().live("click", function() {
        inviteButton();
    });
    
    if($('#invite-accept').length) {
        $('#invite-accept').button().live("click", function() {
            inviteAccept();
        });
    }
    if($('#invite-decline').length) {
        $('#invite-decline').button().live("click", function() {
            inviteDecline();
        });
    }
});

function inviteButton() {
    Overlay.openOverlay(true, '<h2>Invite Member</h2><div id="invite-form"><div id="name-input">Member <input id="name" /></div><button id="invite-member">Invite</button></div>');
    $('#invite-member').button().unbind("click").live("click", function() {
        invite();
    });
    $("#name").autocomplete({
        source: UrlBuilder.buildUrl(false, "account", "autocomplete"),
        minLength: 2
    });
}

function invite() {
    var username = $("#name").val();
    
    Overlay.loadingOverlay();
    
    $.post(UrlBuilder.buildUrl(false, "teams", "invite"),
        { username: username, teamid: teamID },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, username + " was invited to join your team.", 1500);
        },
        'json');
}

function inviteAccept() {
    $.post(UrlBuilder.buildUrl(false, "teams", "inviteRespond"),
        { teamid: teamID, accept: 1 },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "You have accepted the invite to join this team", 1500);
            window.setTimeout("location.reload();", 1000); 
        },
        'json');
}

function inviteDecline() {
    $.post(UrlBuilder.buildUrl(false, "teams", "inviteRespond"),
    { teamid: teamID, accept: 0 },
    function (data) {
        if (data != null && data.error) {
            Overlay.openOverlay(true, data.error);
            return;
        }
        Overlay.openOverlay(false, "You have declined the invite to join this team", 1500);
        window.setTimeout("location.reload();", 1000); 
    },
    'json');
}
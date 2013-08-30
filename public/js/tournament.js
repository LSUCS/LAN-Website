$(document).ready(function() {
    $("#tournament-tabs").tabs();
    $("#teams").mCustomScrollbar({
        scrollButtons: { enable: true },
        theme: "dark"
    });
    
    $('#create-team').live('click', function() {
        window.location = UrlBuilder.buildUrl(false, 'tournaments', 'create');
    });
    
    $('#join-solo').live('click', function() {
        tournaments.joinSolo();
    });
    
    $('#join-team').live('click', function() {
        tournaments.joinTeam();
    });
    
    $('#join-team-form').dialog({
        autoOpen: false,
        height: 200,
        width: 350,
        modal: true,
        buttons: {
            "Join Tournament": function() {
                tournaments.joinClick();
            },
            Cancel: function() {
                $( this ).dialog( "close" );
            }
        },
        close: function() {
            tournaments.clearJoinForm();
        }
    });
});

var tournaments = {
    joinTeam: function() {
        if(typeof(window.userTeams == null)) return;
        var options = "";
        for(var x in userTeams) {
            options += "<option value='" + userTeams[x].id + "'>" + userTeams[x].name + "</option>";
        }
        $('#join-teams').html(options);
        $('#join-team-form').dialog('open');
    },
    
    joinClick: function() {
        var selectedTeam = $('#join-teams').val();
        $.post(
            UrlBuilder.buildUrl(false, "tournaments", "joinasteam"),
            { team: selectedTeam },
            function(data) {
                if(data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, 'Joined successfully', 1000);
                window.setTimeout("location.reload()", 500);
                
            },
            'json');
    },
    
    clearJoinForm: function () {
        $('#join-teams').html('');
    },
    
    joinSolo: function() {
        $.post(
        UrlBuilder.buildUrl(false, "tournaments", "joinsolo"),
        {},
        function(data) {
            if(data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, 'Joined successfully', 1000);
            window.setTimeout("location.reload()", 500);
        },
        'json');
    }
}
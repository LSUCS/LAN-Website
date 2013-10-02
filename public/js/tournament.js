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
    
    $('#leave-tournament').live('click', function() {
        tournaments.leave();
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
    userTeams: [],
    joinTeam: function() {
        if(!this.userTeams.length) return;
        var options = "";
        for(var x in this.userTeams) {
            options += "<option value='" + this.userTeams[x].id + "'>" + this.userTeams[x].name + "</option>";
        }
        $('#join-teams').html(options);
        $('#join-team-form').dialog('open');
    },
    
    joinClick: function() {
        var selectedTeam = $('#join-teams').val();
        $.post(
            UrlBuilder.buildUrl(false, "tournaments", "joinasteam"),
            { team_id: selectedTeam, tournament_id: $('#tournament-id').html() },
            function(data) {
                if(data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, 'Joined successfully', 2000);
                window.setTimeout("location.reload()", 2000);
                
            },
            'json');
    },
    
    clearJoinForm: function () {
        $('#join-teams').html('');
    },
    
    joinSolo: function() {
        $.post(
            UrlBuilder.buildUrl(false, "tournaments", "joinsolo"),
            {tournament_id: $('#tournament-id').html() },
            function(data) {
                if(data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, 'Joined successfully', 2000);
                window.setTimeout("location.reload()", 2000);
            },
            'json');
    },
    
    leave: function() {
        $.post(
            UrlBuilder.buildUrl(false, "tournaments", "leave"),
            {tournament_id: $('#tournament-id').html() },
            function(data) {
                if(data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, 'You have left this tournament', 2000);
                window.setTimeout("location.reload()", 2000);
            },
            'json');
    }
}
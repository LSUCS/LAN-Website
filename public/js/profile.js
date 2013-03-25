$(document).ready(function() {
    
    loadProfile();

});

function loadProfile() {
    $("#profile").hide();
    $("#loading").show();
    $("#invalid-profile").hide();
    $.post(
        UrlBuilder.buildUrl(false, "profile", "loadprofile"),
        { name: PageVars.member },
        function (data) {
            //Error checking
            $("#loading").hide();
            if (data && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            if (data == null) {
                $("#invalid-profile").show();
                return;
            }
            
            $(".info-row").show();
            
            //Input data
            $("#avatar").css('background-image', 'url("' + data.avatar + '")');
            
            if (data.name) $("#name").html(data.name);
            else $("#name").parent().hide();
            
            $("#username").html('<a href="http://lsucs.org.uk/members/' + data.username + '.' + data.user_id + '/">' + data.username + '</a>');
            
            if (data.steam) $("#steam").html('<a href="http://steamcommunity.com/id/' + data.steam + '/"> ' + data.steam + '</a>');
            else $("#steam").parent().hide();
            
            if (data.ingame) {
                $("#profile").addClass("ingame");
                $("#status").html('In Game');
                if (data.game_link) $("#game").html((data.game_icon?'<img src="' + data.game_icon + '" />':'') + '<a href="' + data.game_link + '">' + data.game + '</a>');
                else $("#game").html((data.game_icon?'<img src="' + data.game_icon + '" />':'') + data.game);
            } else {
                $("#profile").removeClass("ingame");
                $("#status").parent().hide();
                $("#game").parent().hide();
            }
            
            if (data.seat) $("#seat").html('<a href="index.php?page=map#' + data.seat + '">' + data.seat + '</a>');
            else $("#seat").parent().hide();
            
            if (data.favourite) {
                $("#favourite-games").html(data.favourite);
                $("#favourite-games-container").show();
            } else {
                $("#favourite-games-container").hide();
            }
            
            if (data.mostplayed) {
                $("#recently-played-games").html(data.mostplayed);
                $("#recently-played-games-container").show();
            } else {
                $("#recently-played-games-container").hide();
            }
            
            if (data.raffle) {
                $("#raffle-tickets").html(data.raffle);
                $("#raffle-tickets-container").show();
            } else {
                $("#raffle-tickets-container").hide();
            }
            
            //Show
            $("#profile").slideDown(500);
        },
        'json');
}
var loaded = false;
var lock = false;
$(document).ready(function() {

    //Load
    loadMap();
    
    //Seat hover
    $("#main-hall .occupied-seat, #main-hall .ingame-seat, #d-block-room .occupied-seat, #d-block-room .ingame-seat").live('mouseover', function() {
        $(this).find('.seat-hover').show();
    });
    $("#main-hall .occupied-seat, #main-hall .ingame-seat, #d-block-room .occupied-seat, #d-block-room .ingame-seat").live('mouseleave', function() {
        $(this).find('.seat-hover').hide();
    });

});

function loadMap() {

    //Lock
    if (lock) return;
    lock = true;
    
    //Get
    $.get(
        UrlBuilder.buildUrl(false, "map", "load"),
        function (data) {
            if (!data) return;
            
            //Clear existing classes
            $("#main-hall .ingame-seat, #main-hall .occupied-seat").removeClass("occupied-seat ingame-seat lanwebsite-contact");
            
            //Remove seat hovers
            $(".seat-hover").remove();
            
            //Remove seat urls
            $(".seat").attr('value', '');
            
            //Remove game images
            $(".game-image").remove();
            $(".preview-icon").remove();
            
            //Loop
            for (var i = 0; i < data["data"].length; i++) {
            
                //Set classes
                var info = data["data"][i];
                var seat = $("#" + info.seat);
                seat.addClass("occupied-seat lanwebsite-contact");
                if (info.ingame == 1) {
                    seat.addClass("ingame-seat");
                    if (info.game_icon) seat.append('<img class="preview-icon" src="' + info.game_icon + '" />');
                }
                
                seat.attr('value', info.user_id);
                
                //HOVER BOX
                var hover = '<div class="hover-container"><div class="seat-hover"><div class="head-box">';
                //Avatar
                hover += '<div class="avatar"><img src="' + info.avatar + '" /></div>';
                //Main info
                hover += '<div class="main-info">' + (info.name == ""?info.username:info.name) + '<br />';
                if (info.ingame == 1) hover += 'In Game<br />' + info.game;
                else hover += 'At LAN';
                hover += '</div></div>';
                //Seat
                hover += '<div class="seat-number">Seat: ' + info.seat + '</div>';
                //Steam name
                if (info.steam != "") hover += '<div class="steam-name">Steam Name: ' + info.steam + '</div>';
                //Favourite games
                if (info.favourite != "") hover += '<div class="favourite"><h3>Favourite Games</h3>' + info.favourite + '</div>';
                //Most played games
                if (info.mostplayed != "") hover += '<div class="mostplayed"><h3>Recently Played Games</h3>' + info.mostplayed + '</div>';
                hover += '</div></div>';
                
                seat.append(hover);
                
            }
            
            //Seat to show?
            if (location.hash != "#" && !loaded) {
                $(location.hash).find(".seat-hover").show();
                loaded = true;
            }
            
            //Unset lock
            lock = false;
        },
        'json');
        
}
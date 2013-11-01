var dropdown_entered = false;
var infobar_entered = false;
var PageVars = {};

$(document).ready(function() {

	//User dropdown
	$("#userbox").mouseenter(function() {
		$("#userdropdown").stop().slideDown(200);
	});
    $("#userbox").mouseleave(function(e) {
        setTimeout(function() { if (!dropdown_entered) {
            $("#userdropdown").stop().slideUp(200);
        }}, 50);
        
    });
    $("#userdropdown").mouseenter(function() {
        dropdown_entered = true;
    });
    $("#userdropdown").mouseleave(function() {
        $("#userdropdown").stop().slideUp(200);
        dropdown_entered = false;
    });

    if($('#countdown').length) {    
        //Date countdown
        $.get(UrlBuilder.buildUrl(false, 'account', 'date'),
            function (data) {
                Countdown.start(data);
            });
    
        Countdown.start(countdown_start);
    }
          
    //Buttons
    $("button").each(function() { $(this).button(); });
    
    //Error boxes
    $(".error-box").each(function() { makeError($(this)); });
    
    //Overlay
    Overlay.initialiseOverlay();
    
    //Contact popups
    LanContact.initialize();
    
    //Parse page vars
    var q = window.location.search.substring(1);
    var vars = q.split("&");
    for (var i = 0; i < vars.length; i++ ) {
        var element = vars[i].split("=");
        PageVars[element[0].toLowerCase()] = element[1];
    }
    
	//Info dropdown
	$("#navbar .info").mouseenter(function() {
        $("#info-bar").slideDown(200);
	});
    $("#navbar .info").mouseleave(function(e) {
		setTimeout(function() { if (!infobar_entered) {
			$("#info-bar").slideUp(200);
		}}, 200);
    });
    $("#info-bar").mouseenter(function() {
        infobar_entered = true;
    });
    $("#info-bar").mouseleave(function() {
        $("#info-bar").slideUp(200);
        infobar_entered = false;
    });
    
    $('.alert .alert-close').live("click", function(e) {
        e.stopPropagation();
        
        var alertID = $($(this).parent()).attr('id');
        alertID = alertID.substr(alertID.indexOf('-')+1);
        $.post(
            UrlBuilder.buildUrl(false, "tournaments", "clearalert"),
            { alert_id: alertID },
            function (data) {
                
            }
        );
        $($(this).parent()).slideUp(300, function() {$(this).remove()});
        return false;
    });
    
});

//Check account details
function checkAccountDetails() {
    $.get(
        UrlBuilder.buildUrl(true, 'account', 'checkdetails'),
        function (data) {
            if (data != null && data.incomplete) {
                window.location = "index.php?page=account";
            }
        },
        'json');
}

//Error function
function makeError(obj) {
    var text = obj.html();
    obj.addClass('ui-widget');
    obj.html('<div class="ui-state-error ui-corner-all"><p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span><strong>Alert:</strong> ' + text + '</p></div>');
}

//LAN contact popups
var LanContact = {
    
    userid: null,

    initialize: function () {
        $(document).on({ click: function() { LanContact.openPopup($(this).attr('value')); }}, ".lanwebsite-contact");
        $(window).resize(function() { LanContact.adjustPopup(); });
        $(document).scroll(function() { LanContact.adjustPopup(); });
        $(document).on({ click: function() { LanContact.closePopup(); }}, "#lancontact-popup .close");
        $(document).on({ click: function() { ChatClient.initiateConversation($(this).attr('value')); }}, "#lancontact-popup .openchat");
    },
    
    openPopup: function (userId) {
        if ($("#lancontact-popup").length > 0) this.closePopup();
        this.userid = userId;
        $("body").append('<div id="lancontact-popup" style="opacity: 0;"><div class="close"></div><div class="loading"></div></div>');
        this.adjustPopup();
        $("#lancontact-popup").animate({ opacity: 1 }, 500);
        $.post(
            UrlBuilder.buildUrl(false, "profile", "loadprofile"),
            { userid: userId },
            function (data) {
                if ($("#lancontact-popup").length == 0 || LanContact.userid != data.user_id) return;
                if (data == null) return LanContact.closePopup();
                $("#lancontact-popup .loading").remove();
                
                if (data.ingame == 1) var c = "ingame";
                else if (data.online == 1) var c = "online";
                else var c = 'offline';
                
                var hover = '<div class="head-box ' + c + '">';
                hover += '<div class="avatar"><img src="' + data.avatar + '" /></div>';
                hover += '<div class="main-info"><a href="http://lsucs.org.uk/members/' + data.user_id + '">' + (data.name == ""?data.username:data.name) + '</a><br />';
                if (data.ingame == 1) hover += 'In Game<br />' + data.game;
                else if (data.online == 1) hover += 'At LAN';
                else hover += 'Offline';
                hover += '</div></div>';
                if (data.seat != null) hover += '<div class="seat-number">Seat: <a href="' + UrlBuilder.buildUrl(false, "map") + "#" + data.seat + '">' + data.seat + '</a></div>';
                if (data.steam != "") hover += '<div class="steam-name">Steam Name: <a href="http://steamcommunity.com/id/' + data.steam + '/">' + data.steam + '</a></div>';
                if (data.favourite != "") hover += '<div class="favourite"><h3>Favourite Games</h3>' + data.favourite + '</div>';
                if (data.mostplayed != "") hover += '<div class="mostplayed"><h3>Recently Played Games</h3>' + data.mostplayed + '</div>';
                if (ChatClient.isValidContact(data.user_id)) hover += '<button class="openchat" value="' + data.user_id + '">Send Message</button>';
                $("#lancontact-popup").append(hover);
                $("#lancontact-popup .openchat").button();
                
                LanContact.adjustPopup();
            },
            'json');
    },
    
    closePopup: function () {
        this.userid = null;
        $("#lancontact-popup").fadeOut(100).remove();
    },
    
    adjustPopup: function () {
        var elem = $("#lancontact-popup");
        if (elem.length > 0) {
            elem.css('margin-top', - (elem.height()/2 + 15));
            elem.css('margin-left', - (elem.width()/2 + 10));
        }
    }

};

//Countdown object
var Countdown = {
    
    timer: null,

    calcAge: function(secs, num1, num2) {
      s = ((Math.floor(secs/num1))%num2).toString();
      return "<b>" + s + "</b>";
    },

    countBack: function(secs) {
        if (secs < 0) return;
        
        $("#countdown-days").html(this.calcAge(secs,86400,100000));
        $("#countdown-hours").html(this.calcAge(secs,3600,24));
        $("#countdown-minutes").html(this.calcAge(secs,60,60));
        $("#countdown-seconds").html(this.calcAge(secs,1,60));

        setTimeout("Countdown.countBack(" + (secs-1) + ")", 995);
    },
    
    start: function(dstring) {
        var dthen = Date.parseExact(dstring, "yyyy-MM-dd HH:mm:ss");
        var dnow = new Date();
        ddiff = new Date(dnow-dthen);
        gsecs = Math.abs(Math.floor(ddiff.valueOf()/1000));
        this.countBack(gsecs);
    }
    
}

//Overlay object
var Overlay = {
    
    initialiseOverlay: function() {
        $(window).resize(function() { Overlay.resizeScreen(); });
        $(document).scroll(function() { Overlay.resizeScreen(); });
        $("#close-overlay").on({ click: function() { Overlay.closeOverlay(); } });
    },
    openOverlay: function(showButton, text, timeout) {

        if (showButton) this.showCloseButton();
        else this.hideCloseButton();
        
        if (text != null && text.length > 0) {
            $("#overlay").removeClass().addClass("notice-overlay");
            $("#overlay-content").html(text);
        }
        else $("#overlay").removeClass().addClass("content-overlay");
        
        this.resizeScreen();
        this.adjustOverlay();
        $("#screen").fadeIn("300");
        $("#overlay").fadeIn("300");
        
        if (timeout > 0) {
            setTimeout(function () { Overlay.closeOverlay(); }, timeout);
        }
        
    },
    loadingOverlay: function() {
        this.openOverlay(false, '<img src="/images/loading.gif" />');
    },
    closeOverlay: function() {
        $("#screen").fadeOut("300");
        $("#overlay").fadeOut("300");
    },
    adjustOverlay: function() {
        $("#overlay").css('margin-top', - $("#overlay").height()/2 -50);
        $("#overlay").css('margin-left', - ($("#overlay").width()/2 + 75));
    },
    resizeScreen: function() {
        $("#screen").css('width', $(document).width());
        $("#screen").css('height', $(document).height());
        this.adjustOverlay();
    },
    hideCloseButton: function() {
        $("#close-overlay").css('display', 'none');
    },
    showCloseButton: function() {
        $("#close-overlay").css('display', 'block');
    }

}


var UrlBuilder = {
    
    seo: true,
    
    buildUrl: function(admin, controller, action, args) {
        
        //SEO urls
        if (this.seo) {
            
            //admin or not
            if (admin) var url = "/admin/";
            else var url = "/";
            
            //controller
            if (controller != null) url += controller + "/";
            
            //action
            if (action != null) url += action + "/";
            
            //arguments
            var a = [];
            for (var k in args) {
                a.push(k + "=" + args[k]);
            }
            if (a.length > 0) url += "?" + a.join("&");
            
            return url;
        
        }
        
        //Standard urls
        else {
        
            //admin or not
            if (admin) var url = "/admin.php";
            else var url = "/index.php";
            
            //action
            if (action != null) args.action = action;
            
            //controller
            if (controller != null) args.page = controller;
            
            //arguments
            var a = [];
            for (var k in args) {
                a.push(k + "=" + args[k]);
            }
            a.reverse();
            if (a.length > 0) url += "?" + a.join("&");
            
            return url;
            
        }
        
    }

}
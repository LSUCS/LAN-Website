var dropdown_entered = false;
var infobar_entered = false;
var PageVars = {};

$(document).ready(function() {

	//User dropdown
	$("#userbox").mouseenter(function() {
		$("#userdropdown").slideDown(200);
	});
    $("#userbox").mouseleave(function(e) {
        setTimeout(function() { if (!dropdown_entered) {
            $("#userdropdown").slideUp(200);
        }}, 50);
        
    });
    $("#userdropdown").mouseenter(function() {
        dropdown_entered = true;
    });
    $("#userdropdown").mouseleave(function() {
        $("#userdropdown").slideUp(200);
        dropdown_entered = false;
    });
    
    //Date countdown
    $.get(UrlBuilder.buildUrl(true, 'account', 'date'),
          function (data) {
            Countdown.start(data);
          });
          
    //Buttons
    $("button").button();
    
    //Error boxes
    $(".error-box").each(function() { makeError($(this)); });
    
    //Overlay
    Overlay.initialiseOverlay();
    
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

//Countdown object
var Countdown = {

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

        setTimeout("Countdown.countBack(" + (secs-1) + ")", 990);
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
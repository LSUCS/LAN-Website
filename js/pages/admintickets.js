var claimedTable = null;
var unclaimedTable = null;
var assignID = null;
var claimID = null;

$(document).ready(function() {

    //Init tables
    claimedTable = $("#claimed-tickets").dataTable( {
        "bJQueryUI": true,
        "sPaginationType": "full_numbers",
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "bAutoWidth": false,
        "iDisplayLength": 10,
        "sDom": 'Rf<"H"lrT>t<"F"ip>',
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "sTitle": "ID", "sWidth": "50px", "sClass": "idcell" },
            { "sTitle": "Ticket Type", "sWidth": "120px" },
            { "sTitle": "Purchased" },
            { "sTitle": "Assigned", "sClass": "assigned" },
            { "sTitle": "Activated", "sClass": "activated" },
            { "sTitle": "Seat" }
        ] } );
        
    unclaimedTable = $("#unclaimed-tickets").dataTable( {
        "bJQueryUI": true,
        "sPaginationType": "full_numbers",
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "bAutoWidth": false,
        "iDisplayLength": 10,
        "sDom": 'Rf<"H"lrT>t<"F"ip>',
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "sTitle": "ID", "sWidth": "50px", "sClass": "idcell" },
            { "sTitle": "Ticket Type", "sWidth": "120px" },
            { "sTitle": "Name" },
            { "sTitle": "Email" }
        ] } );
        
    loadTables();
    
    //Row highlighting
    $("tbody tr").live('mouseover', function() {
        $(this).find('td').addClass("row-hover");
    });
    $("tbody tr").live('mouseleave', function() {
        $(this).find('td').removeClass("row-hover");
    });
    
    //CLAIM TABLE //
    //Row clicking
    $("#claimed-tickets tbody tr").live('click', function() {
        $('#claimed-tickets .row-selected').removeClass('row-selected');
        $(this).find('td').removeClass('row-hover').addClass('row-selected');
        
        //Buttons
        $("#claimed-buttons button").hide();
        $("#seat").show();
        if ($(this).find('.activated').html() == "Yes") $("#deactivate").show();
        else $("#activate").show();
        if ($(this).find('.assigned').html() == "") $("#assign").show();
        else $("#reassign").show();
        
    });
    //Filter bind
    $("#claimed-tickets").bind('filter', function() {
        $("#claimed-buttons button").hide();
        $('#claimed-tickets .row-selected').removeClass('row-selected');
    });
    //Button binds
    $("#assign, #reassign").live('click', function() {
        assign();
    });
    $("#activate").live('click', function() {
        activate();
    });
    $("#deactivate").live('click', function() {
        deactivate();
    });
    $("#seat").live('click', function() {
        seat();
    });
    
    //UNCLAIMED TABLE//
    //Row clicking
    $("#unclaimed-tickets tbody tr").live('click', function() {
        $('#unclaimed-tickets .row-selected').removeClass('row-selected');
        $(this).find('td').removeClass('row-hover').addClass('row-selected');
        $("#claim").show();
    });
    //Filter bind
    $("#unclaimed-tickets").bind('filter', function() {
        $("#unclaimed-buttons button").hide();
        $('#unclaimed-tickets .row-selected').removeClass('row-selected');
    });
    //Button binds
    $("#claim").live('click', function() {
        claim();
    });
    
});

function seat() {

    assignID = $("#claimed-tickets .row-selected").parent().find('.idcell').html();
    $("#overlay-content").html('<label for="seat-input">Seat: </label><input id="seat-input" /><button id="seat-button">Set Seat</button>');
    $("#seat-button").button();
    Overlay.openOverlay(true, "");
    
    $("#seat-button").click(function() {
        $.post("index.php?route=admin&page=admintickets&action=seat",
            { seat: $("#seat-input").val(), ticket_id: assignID },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Seat set", 1500);
                loadTables();
            },
            'json');
    });

}

function activate() {
    Overlay.loadingOverlay();
    $.post(
        "index.php?route=admin&page=admintickets&action=activate",
        { id: $("#claimed-tickets .row-selected").first().parent().find('.idcell').html() },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Ticket Activated", 1500);
            loadTables();
        },
        'json');
    $("#claimed-buttons button").hide();
}

function deactivate() {
    Overlay.loadingOverlay();
    $.post(
        "index.php?route=admin&page=admintickets&action=deactivate",
        { id: $("#claimed-tickets .row-selected").first().parent().find('.idcell').html() },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Ticket Deactivated", 1500);
            loadTables();
        },
        'json');
    $("#claimed-buttons button").hide();
}

function claim() {

    claimID = $("#unclaimed-tickets .row-selected").parent().find('.idcell').html();
    $("#overlay-content").html('<label for="claim-name">Forum Name: </label><input id="claim-name" /><button id="claim-ticket">Claim</button>');
    $("#claim-ticket").button();
    $("#claim-name").autocomplete({
        source: "index.php?page=account&action=autocomplete",
        minLength: 2
    });
    Overlay.openOverlay(true, "");
    
    $("#claim-ticket").click(function() {
        $.post("index.php?route=admin&page=admintickets&action=claim",
            { name: $("#claim-name").val(), ticket_id: claimID },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Ticket claimed", 1500);
                loadTables();
            },
            'json');
    });

}

function assign() {

    assignID = $("#claimed-tickets .row-selected").parent().find('.idcell').html();
    $("#overlay-content").html('<label for="assign-name">Forum Name: </label><input id="assign-name" /><button id="assign-ticket">Assign</button>');
    $("#assign-ticket").button();
    $("#assign-name").autocomplete({
        source: "index.php?page=account&action=autocomplete",
        minLength: 2
    });
    Overlay.openOverlay(true, "");
    
    $("#assign-ticket").click(function() {
        $.post("index.php?route=admin&page=admintickets&action=assign",
            { name: $("#assign-name").val(), ticket_id: assignID },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Ticket assigned", 1500);
                loadTables();
            },
            'json');
    });
    
}

function loadTables() {
    $.get(
        "index.php?route=admin&page=admintickets&action=loadtables",
        function (data) {
        
            claimedTable.fnClearTable();
            unclaimedTable.fnClearTable();
            claimedTable.fnAddData(data.claimed);
            unclaimedTable.fnAddData(data.unclaimed);

        },
        'json');
}
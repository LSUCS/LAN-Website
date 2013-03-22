var claimedTable = null;
var unclaimedTable = null;
var raffleTable = null;
var assignID = null;
var claimID = null;
var raffleID = null;

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
            { "sTitle": "ID", "sWidth": "40px", "sClass": "idcell" },
            { "sTitle": "Ticket Type", "sWidth": "100px" },
            { "sTitle": "Purchased", "bSearchable": true },
            { "sTitle": "Purchased Name", "bSearchable": true },
            { "sTitle": "Assigned", "sClass": "assigned", "bSearchable": true },
            { "sTitle": "Activated", "sClass": "activated", "sWidth": "80px" },
            { "sTitle": "Seat", "sWidth": "40px" }
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
        
    raffleTable = $("#raffle-tickets").dataTable( {
        "bJQueryUI": true,
        "sPaginationType": "full_numbers",
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "bAutoWidth": false,
        "iDisplayLength": 10,
        "sDom": 'Rf<"H"lrT>t<"F"ip>',
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "sTitle": "Ticket Number", "sClass": "idcell", "sWidth": "120px" },
            { "sTitle": "Name" },
            { "sTitle": "Username" },
            { "sTitle": "Reason" }
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
        else if ($(this).find('.assigned').html() != "") $("#activate").show();
        if ($(this).find('.assigned').html() == "") $("#assign").show();
        else {
            $("#reassign").show();
            $("#add-raffle").show();
        }
        
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
    $("#add-raffle").live('click', function() {
        addRaffle();
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
    
    //RAFFLE TABLE//
    //Row clicking
    $("#raffle-tickets tbody tr").live('click', function() {
        $('#raffle-tickets .row-selected').removeClass('row-selected');
        $(this).find('td').removeClass('row-hover').addClass('row-selected');
        $("#delete-raffle").show();
    });
    //Filter bind
    $("#raffle-tickets").bind('filter', function() {
        $("#raffle-buttons button").hide();
        $('#raffle-tickets .row-selected').removeClass('row-selected');
    });
    //Button binds
    $("#delete-raffle").live('click', function() {
        deleteRaffle();
    });
    
});

function deleteRaffle() {

    raffleID = $("#raffle-tickets .row-selected").parent().find('.idcell').html();
    $("#overlay-content").html('<button id="confirm-delete">Delete Raffle Ticket?</button>');
    $("#confirm-delete").button();
    Overlay.openOverlay(true, "");
    
    $("#confirm-delete").click(function() {
        $.post("index.php?route=admin&page=admintickets&action=deleteraffle",
            { ticket_number: raffleID },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Raffle ticket deleted", 1500);
                loadTables();
            },
            'json');
    });
    
}

function addRaffle() {

    assignID = $("#claimed-tickets .row-selected").parent().find('.idcell').html();
    $("#overlay-content").html('<label for="raffle-input">Reason: </label><input id="raffle-input" /><button id="raffle-button">Add Raffle Ticket</button>');
    $("#raffle-button").button();
    Overlay.openOverlay(true, "");
    
    $("#raffle-button").click(function() {
        $.post("index.php?route=admin&page=admintickets&action=addraffle",
            { ticket_id: assignID, reason: $("#raffle-input").val() },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Raffle ticket issued", 1500);
                loadTables();
            },
            'json');
    });
    
}

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
            raffleTable.fnClearTable();
            claimedTable.fnAddData(data.claimed);
            unclaimedTable.fnAddData(data.unclaimed);
            raffleTable.fnAddData(data.raffle);

        },
        'json');
}
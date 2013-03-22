var paidTable = null;
var unpaidTable = null;

$(document).ready(function() {

    //Init tables
    paidTable = $("#paid-orders").dataTable( {
        "bJQueryUI": true,
        "sPaginationType": "full_numbers",
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "bAutoWidth": true,
        "iDisplayLength": 10,
        "sDom": 'Rf<"H"lrT>t<"F"ip>',
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "sTitle": "ID", "sWidth": "40px", "sClass": "idcell" },
            { "sTitle": "Name", "bSearchable": true },
            { "sTitle": "Seat", "bSearchable": true },
            { "sTitle": "Shop", "bSearchable": true },
            { "sTitle": "Option", "bSearchable": true },
            { "sTitle": "Price" }
        ] } );
		
    unpaidTable = $("#unpaid-orders").dataTable( {
        "bJQueryUI": true,
        "sPaginationType": "full_numbers",
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "bAutoWidth": true,
        "iDisplayLength": 10,
        "sDom": 'Rf<"H"lrT>t<"F"ip>',
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "sTitle": "ID", "sWidth": "40px", "sClass": "idcell" },
            { "sTitle": "Name", "bSearchable": true },
            { "sTitle": "Seat", "bSearchable": true },
            { "sTitle": "Shop", "bSearchable": true },
            { "sTitle": "Option", "bSearchable": true },
            { "sTitle": "Price" }
        ] } );
		
		
	loadTables();
	
    //Row highlighting
    $("tbody tr").live('mouseover', function() {
        $(this).find('td').addClass("row-hover");
    });
    $("tbody tr").live('mouseleave', function() {
        $(this).find('td').removeClass("row-hover");
    });
	
	
	//UNPAID TABLE //
    //Row clicking
    $("#unpaid-orders tbody tr").live('click', function() {
        $('#unpaid-orders .row-selected').removeClass('row-selected');
        $(this).find('td').removeClass('row-hover').addClass('row-selected');
        
        //Buttons
        $("#paidbutton").show();
        
    });
    //Filter bind
    $("#unpaid-orders").bind('filter', function() {
        $("#paidbutton").hide();
        $('#unpaid-orders .row-selected').removeClass('row-selected');
    });
    //Button binds
    $("#paidbutton").live('click', function() {
        paid();
    });
		
});

function loadTables() {
    $.get(
        "index.php?route=admin&page=adminfood&action=loadtables",
        function (data) {
        
            paidTable.fnClearTable();
            unpaidTable.fnClearTable();
            paidTable.fnAddData(data.paid);
            unpaidTable.fnAddData(data.unpaid);

        },
        'json');
}

function paid() {
    Overlay.loadingOverlay();
	$.post("index.php?route=admin&page=adminfood&action=paid",
		{ order_id: $("#unpaid-orders .row-selected").parent().find('.idcell').html() },
		function (data) {
			if (data != null && data.error) {
				Overlay.openOverlay(true, data.error);
				return;
			}
			Overlay.openOverlay(false, "Order marked as paid", 1000);
			loadTables();
		},
		'json');
    
}
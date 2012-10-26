var tf2Table = null;

$(document).ready(function() {

    tf2Table = $("#tf2").dataTable( {
        "bJQueryUI": true,
        "sPaginationType": "full_numbers",
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "bAutoWidth": true,
        "iDisplayLength": 10,
        "sDom": 'Rf<"H"lrT>t<"F"ip>',
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "sTitle": "Name" },
            { "sTitle": "Username" },
            { "sTitle": "Steam Community Name" },
            { "sTitle": "Seat" }
        ] } );

    load();

});

function load() {
    
    $.get(
        "index.php?route=admin&page=admintf2&action=load",
        function (data) {
            tf2Table.fnClearTable();
            tf2Table.fnAddData(data);
        },
        'json');

}
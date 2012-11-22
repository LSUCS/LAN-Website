var hungerTable = null;

$(document).ready(function() {

    hungerTable = $("#hungergames").dataTable( {
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
            { "sTitle": "Minecraft Name" },
            { "sTitle": "Seat" }
        ] } );

    load();

});

function load() {
    
    $.get(
        "index.php?route=admin&page=adminhungergames&action=load",
        function (data) {
            hungerTable.fnClearTable();
            hungerTable.fnAddData(data);
        },
        'json');

}
$(document).ready(function() {
    $.get(
        UrlBuilder.buildUrl(true, "lanvan", "load"),
        function (data) {
        
            $("#lan-van").dataTable( {
                "bJQueryUI": true,
                "sPaginationType": "full_numbers",
                "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "aaData": data,
                "bAutoWidth": false,
                "iDisplayLength": 10,
                "sDom": 'Rf<"H"lrT>t<"F"ip>',
                "oTableTools": {
                    "sSwfPath": "/swf/copy_csv_xls_pdf.swf"
                },
                "aaSorting": [[ 0, "desc" ]],
                "aoColumns": [
                    { "sTitle": "Name" },
                    { "sTitle": "Phone Number" },
                    { "sTitle": "Address" },
                    { "sTitle": "Postcode" },
                    { "sTitle": "Collection", "sWidth": "75px" },
                    { "sTitle": "Drop-off", "sWidth": "75px" },
                    { "sTitle": "Availability" }
                ] } );

        },
        'json');
});
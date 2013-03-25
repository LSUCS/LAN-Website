var entry_id = false;

$(document).ready(function() {

    loadEntries();
    $("#edit-entry, #delete-entry").button('disable');
    
    $(".entry-row").live('click', function() {
        $(".entry-row").removeClass("selected-row");
        $(this).addClass("selected-row");
        entry_id = $(this).find("input").val();
        $("#delete-entry").button('enable');
    });
    
    $("#add-button").click(function() {
        $.post(
            UrlBuilder.buildUrl(true, "whatson", "addentry"),
            { day: $("#entry-day option:selected").val(),
              start_time: $("#entry-start-time option:selected").val(),
              end_time: $("#entry-end-time option:selected").val(),
              title: $("#entry-title").val(),
              url: $("#entry-url").val(),
              colour: $("#entry-colour option:selected").val() },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                loadEntries();
                entry_id = false;
                $(".entry-row").removeClass("selected-row");
                $("#delete-entry").button('disable');
                Overlay.openOverlay(false, "Entry added", 1000);
            },
            'json');
    });
    
    $("#delete-entry").click(function() {
        Overlay.openOverlay(true, '<button id="confirm-delete">Confirm</button>');
        $("#confirm-delete").button();
        $("#confirm-delete").click(function() {
            $.post(
                UrlBuilder.buildUrl(true, "whatson", "deleteentry"),
                { entry_id: entry_id },
                function (data) {
                    if (data != null && data.error) {
                        Overlay.openOverlay(true, data.error);
                        return;
                    }
                    loadEntries();
                    entry_id = false;
                    $(".entry-row").removeClass("selected-row");
                    $("#delete-entry").button('disable');
                    Overlay.openOverlay(false, "Entry deleted", 1000);
                },
                'json');
        });
    });
    
});

function loadEntries() {
    $.get(
        UrlBuilder.buildUrl(true, "whatson", "getentries"),
        function (data) {
            $("#table-body").html("");
            if (data.length > 0) {
                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    var string = '<div class="entry-row ' + (i % 2?'odd':'even') + ' ' + (i == data.length -1?'end-entry':'') + '">';
                    string += '<input type="hidden" class="entry-id" value="' + row.timetable_id + '" />';
                    string += '<span class="day">' + row.day + '</span>';
                    string += '<span class="start-time">' + row.start_time + '</span>';
                    string += '<span class="end-time">' + row.end_time + '</span>';
                    string += '<span class="title">' + row.title + '</span>';
                    string += '<span class="url">' + (row.url == ""?"None":'<a href="' + row.url + '">Click</a>') + '</span>';
                    string += '<span class="colour">' + row.colour + '</span></div>';
                    $("#table-body").append(string);
                }
            } else {
                $("#table-body").append('<div class="no-tickets odd end-ticket">No tickets found for your account</div>');
            }
        },
        'json');
}
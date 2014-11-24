var entry_id = false;
var committee_entry_id = false;

$(document).ready(function() {

    loadEntries();
    $("#edit-entry, #delete-entry, #committee-edit-entry, #committee-delete-entry").button('disable');
    
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
    
    
    $(".committee-entry-row").live('click', function() {
        $(".committee-entry-row").removeClass("selected-row");
        $(this).addClass("selected-row");
        committee_entry_id = $(this).find("input").val();
        $("#committee-delete-entry").button('enable');
    });
    
    $("#committee-add-button").click(function() {
        $.post(
            UrlBuilder.buildUrl(true, "whatson", "addcommitteeentry"), {
                day: $("#committee-entry-day option:selected").val(),
                start_time: $("#committee-entry-start-time option:selected").val(),
                //end_time: $("#committee-entry-end-time option:selected").val(),
                username_1: $("#committee-entry-username-1").val(),
                username_2: $("#committee-entry-username-2").val()
            },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                loadEntries();
                committee_entry_id = false;
                $(".committee-entry-row").removeClass("selected-row");
                $("#committee-delete-entry").button('disable');
                Overlay.openOverlay(false, "Entry added", 1000);
            },
            'json');
    });
    
    $("#committee-delete-entry").click(function() {
        Overlay.openOverlay(true, '<button id="committee-confirm-delete">Confirm</button>');
        $("#committee-confirm-delete").button();
        $("#committee-confirm-delete").click(function() {
            $.post(
                UrlBuilder.buildUrl(true, "whatson", "deletecommitteeentry"),
                { entry_id: committee_entry_id },
                function (data) {
                    if (data != null && data.error) {
                        Overlay.openOverlay(true, data.error);
                        return;
                    }
                    loadEntries();
                    committee_entry_id = false;
                    $(".committee-entry-row").removeClass("selected-row");
                    $("#committee-delete-entry").button('disable');
                    Overlay.openOverlay(false, "Entry deleted", 1000);
                },
                'json');
        });
    });
    
    $("#committee-entry-username-1").autocomplete({
        source: UrlBuilder.buildUrl(false, "account", "autocomplete"),
        minLength: 2
    });
    
    $("#committee-entry-username-2").autocomplete({
        source: UrlBuilder.buildUrl(false, "account", "autocomplete"),
        minLength: 2
    });
    
});

function loadEntries() {
    $.get(
        UrlBuilder.buildUrl(true, "whatson", "getentries"),
        function (data) {
            //Timetable
            $("#table-body").html("");
            if (data.timetable.length > 0) {
                for (var i = 0; i < data.timetable.length; i++) {
                    var row = data.timetable[i];
                    var string = '<div class="entry-row ' + (i % 2 ? 'odd':'even') + ' ' + (i == data.timetable.length -1 ? 'end-entry':'') + '">';
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
                $("#table-body").append('<div class="no-tickets odd end-ticket">No timetable events found</div>');
            }
            
            //Committee
            $("#committee-table-body").html("");
            if (data.committee.length > 0) {
                for (var i = 0; i < data.committee.length; i++) {
                    var row = data.committee[i];
                    var string = '<div class="committee-entry-row ' + (i % 2 ? 'odd':'even') + ' ' + (i == data.committee.length -1 ? 'end-entry':'') + '">';
                    string += '<input type="hidden" class="entry-id" value="' + row.timetable_id + '" />';
                    string += '<span class="day">' + row.day + '</span>';
                    string += '<span class="start-time">' + row.start_time + '</span>';
                    //string += '<span class="end-time">' + row.end_time + '</span>';
                    string += '<span class="username">' + data.users[row.user_id_1].username + '</span>';
                    string += '<span class="username">' + data.users[row.user_id_2].username + '</span></div>';
                    $("#committee-table-body").append(string);
                }
            } else {
                $("#committee-table-body").append('<div class="no-tickets odd end-ticket">No committee timetable found</div>');
            }
        },
        'json');
}
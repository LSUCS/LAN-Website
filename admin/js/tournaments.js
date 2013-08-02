var selectedid = null;
$(document).ready(function() {

    //Bind click
    $("#new-submit").click(function() {
        submitNew();
    });
    
    //Load entries
    loadEntries();
    
    //Entry click
    $(".entry-row").live('click', function() {
        showOptions($(this).find(".id").html());
    });

});

function loadEntries() {
    $.get(
        UrlBuilder.buildUrl(true, 'tournaments', 'getentries'),
        function (data) {
            $("#table-body").html("");
            if (data.length > 0) {
                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    var string = '<div class="entry-row ' + (i % 2?'odd':'even') + ' ' + (i == data.length -1 ? 'end-entry':'') + '">';
                    string += '<span class="id">' + row.id + '</span>';
                    string += '<span class="name">' + row.name + '</span>';
                    string += '<span class="type">' + row.type + '</span>';
                    string += '<span class="team-size">' + row.team_size + '</span>';
                    string += '<span class="signups-enabled"><input type="checkbox" class="signup-checkbox" ' + (row.signups_enabled) ? 'checked="checked"' : '' + ' /></span>';
                    string += '<span class="visible"><input type="checkbox" class="visible-checkbox" ' + (row.visible) ? 'checked="checked"' : '' + ' /></span>';
                    string += '</div>';
                    $("#table-body").append(string);
                }
            } else {
                $("#table-body").append('<div class="no-entries odd end-entry">No entries found</div>');
            }
        },
        'json');
}

function submitNew() {
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(true, 'blog', 'add'), {
            name: $("#new-name").val(),
            team-size: $("#new-size").val(),
            type: $("#new-type").val(),
            signups: $("#new-signups").val(),
            visible: $("#new-visible").val(),
        },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Entry added", 1000);
            loadEntries();
            clearForm();
        },
        'json');
}

function clearForm() {
    $("#add-tournament input").val('');
}
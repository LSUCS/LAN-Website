var editor = null;
var selectedid = null;
$(document).ready(function() {

    //Start editor
    editor = $("#entry-content").cleditor({
        width: 540,
        height: 400
    })[0];
    
    //Bind click
    $("#submit-entry").click(function() {
        submitEntry();
    });
    
    //Load entries
    loadEntries();
    
    
    //Entry click
    $(".entry-row").live('click', function() {
        loadEntry($(this).find(".id").html());
    });
    
    //Edit and delete
    $("#edit-submit-entry").live('click', function() {
        editEntry();
    });
    $("#delete-entry").live('click', function() {
        deleteEntry();
    });
    
});

function editEntry() {
    $.post(
        "index.php?route=admin&page=adminblog&action=edit",
        { id: selectedid, title: $("#edit-entry-title").val(), content: $("#edit-entry-content").val() },
        function (data) {
            if (data != null && data.error) {
                $("#edit-error").html(data.error);
                return;
            }
            Overlay.openOverlay(false, "Entry edited", 1000);
            loadEntries();
        },
        'json');
}

function deleteEntry() {
    Overlay.loadingOverlay();
    $.post(
        "index.php?route=admin&page=adminblog&action=delete",
        { id: selectedid },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Entry deleted", 1000);
            loadEntries();
        },
        'json');
}

function loadEntry(id) {
    Overlay.loadingOverlay();
    $.post(
        "index.php?route=admin&page=adminblog&action=load",
        { id: id },
        function (data) {
            var string = '<div id="edit-error"></div><div id="edit-entry"><div id="edit-entry-title-container"><label for="edit-entry-title">Title: </label><input type="text" id="edit-entry-title" value="' + data.title + '" /></div>' +
                         '<textarea id="edit-entry-content">' + data.body + '</textarea><button id="edit-submit-entry">Edit</button><button id="delete-entry">Delete</button></div>';
            $("#overlay-content").html(string);
            $("#edit-entry-content").cleditor({
                width: 540,
                height: 400
            });
            selectedid = data.blog_id;
            $("#edit-submit-entry, #delete-entry").button();
            Overlay.openOverlay(true, '');
        },
        'json');
}

function submitEntry() {
    Overlay.loadingOverlay();
    $.post(
        "index.php?route=admin&page=adminblog&action=add",
        { title: $("#entry-title").val(), content: $("#entry-content").val() },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Entry added", 1000);
            loadEntries();
            editor.clear();
            $("#entry-title").val("");
        },
        'json');
}

function loadEntries() {
    $.get(
        "index.php?route=admin&page=adminblog&action=getentries",
        function (data) {
            $("#table-body").html("");
            if (data.length > 0) {
                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    var string = '<div class="entry-row ' + (i % 2?'odd':'even') + ' ' + (i == data.length -1?'end-entry':'') + '">';
                    string += '<span class="id">' + row.blog_id + '</span>';
                    string += '<span class="date">' + row.date + '</span>';
                    string += '<span class="author">' + row.username + '</span>';
                    string += '<span class="title">' + row.title + '</span></div>';
                    $("#table-body").append(string);
                }
            } else {
                $("#table-body").append('<div class="no-entries odd end-entry">No entries found</div>');
            }
        },
        'json');
}
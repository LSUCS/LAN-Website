$(document).ready(function() {

    $('#create').submit(function() { announcements.create(); return false; });
    $('.datetime').datetimepicker();
    
    $('.edit').click(function() { announcements.edit(this); });
    $('.delete').click(function() { announcements.delete(this); });
});

var announcements = {
    create: function() {
        var startTime = ($('#displayNow').prop('checked')) ? "now" : $('#date').val();    
        $.post(UrlBuilder.buildUrl(true, 'announcements', 'create'),
            {
                name: $('#name').val(),
                message: $('#message').val(),
                colour: $('#create .colourlist').val(),
                timer1: $('#timer1').val() | 0,
                timer2: $('#timer2').val() | 0,
                timer3: $('#timer3').val() | 0,
                start: startTime,
                duration: $('#displayTime').val()
            },
            function (data) {
                if (data != null && data.error) {
                    alert(data.error);
                    return;
                }
                Overlay.openOverlay(false, "Announcement Created", 1000);
                window.setTimeout(1000, location.reload());
            },
            'json');
    },
    
    edit: function(button) {
        $('button').prop('id', 'testing');
        var info = $(button).parent().siblings();
        var startTime = ($(info[3]).find('input[type=checkbox]').prop('checked')) ? "now" : $(info[3]).find('input[type=text]').val();   
        $.post(UrlBuilder.buildUrl(true, 'announcements', 'edit'),
            {
                id: announcements.getId(button),
                name: $(info[0]).text(),
                message: $(info[1]).text(),
                colour: $(info[2]).find('select').val(),
                timer1: $(info[5]).text() | 0,
                timer2: $($(info[6])).text() | 0,
                timer3: $(info[7]).text() | 0,
                start: startTime,
                duration: $(info[4]).text()
            },
            function (data) {
                if (data != null && data.error) {
                    alert(data.error);
                    return;
                }
                $(info[3]).find('input[type=text]').val(data.time);
                $(info[3]).find('input[type=checkbox]').prop('checked', false);
                Overlay.openOverlay(false, "Successfully Edited", 1000);
            },
            'json');
    },
    
    delete: function(button) {
        if(confirm("Are you sure you wish to delete this announcement?\nThis action cannot be undone")) {
            $.post(UrlBuilder.buildUrl(true, 'announcements', 'delete'),
            {
                id: announcements.getId(button)
            },
            function (data) {
                if (data != null && data.error) {
                    alert(data.error);
                    return;
                }
                Overlay.openOverlay(false, "Successfully Deleted", 1000);
                window.setTimeout(1000, location.reload());
            },
            'json');
        }
    },
    
    getId: function(button) {
        var id = $(button).parent().parent().prop('id');
        return id.substr(id.indexOf('-')+1);
    }
};
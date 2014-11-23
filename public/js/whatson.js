$(document).ready(function() {

    getTimetable();

});

function getTimetable() {
    $.get(
        UrlBuilder.buildUrl(false, "whatson", "gettimetable"),
        function (data) {
            //Timetable
            for (var i = 0; i < data.timetable.length; i++) {
            
                var row = data.timetable[i];
                
                //Form entry string
                var string = '';
                if (row.url) string += '<a class="entry-link" href="' + row.url + '">';
                string += '<div class="timetable-entry" id="entry' + row.timetable_id + '">' + row.title + '</div>';
                if (row.url) string += '</a>';
                $('#' + row.day).append(string);
                
                //Set entry properties
                var entry = $("#entry" + row.timetable_id);
                entry.addClass(row.colour);
                var position1 = $("#" + row.day + ' .time-row[value="' + row.start_time + '"]').position();
                var position2 = $("#" + row.day + ' .time-row[value="' + row.end_time + '"]').position();
                entry.css('top', position1.top);
                entry.css('width', 270/row.division -20);
                
                if (row.previous) {
                    var position3 = $("#entry" + row.previous).position();
                    entry.css('left', $("#entry" + row.previous).width() + position3.left + 20);
                } else {
                    entry.css('left', 0);
                }
                entry.css('height', position2.top - position1.top -20);
                
            }
            
            //Committee
            $("#committee-timetable-body").html("");
            var day;
            var start_time;
            var string = "";
            var parity = 1;
            for (var i = 0; i < data.committee.length; i++) {            
                var row = data.committee[i];
                string = "";
                
                //New day
                if(row.day != day) {
                    day = row.day;
                    parity++;
                    string = '<div class="entry-row"><span class="committee-day">' + day + '</span>';
                }
                string += '<div class="entry-row ' + ((parity % 2 == 0) ? 'odd' : '') + ' ' + (i == data.committee.length -1 ? 'end-entry':'') + '"><span class="committee-time">' + row.start_time + "</span>";
                parity++;
                string += '<span class="committee-username">' + data.users[row.user_id_1].username + '</span>';
                string += '<span class="committee-username">' + data.users[row.user_id_2].username + '</span>';
                $("#committee-timetable-body").append(string +  "</div>");
            }
        },
        'json');
}

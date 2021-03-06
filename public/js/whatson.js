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
				switch(row.colour) {
					case "organisational":
						entry.addClass("orange");
						break;
						
					case "food":
						entry.addClass("green");
						break;
						
					case "other":
						entry.addClass("blue");
						break;
						
					case "tournament":
						entry.addClass("purple");
						break;
						
					case "langames":
						entry.addClass("mustard");
						break;
				}
                //entry.addClass(row.colour);
                var position1 = $("#" + row.day + ' .time-row[value="' + row.start_time + '"]').position();
                var position2 = $("#" + row.day + ' .time-row[value="' + row.end_time + '"]').position();
                entry.css('top', position1.top);
                entry.css('width', 270/row.division -20);
                
                if (row.previous) {
                    var position3 = $("#entry" + row.previous).position();
                    if(position3.left == position1.left) {
                        entry.css('left', $("#entry" + row.previous).width() + position1.left + 20);
                    }
                    else if(position3.left == $("#entry" + row.previous).width() + position1.left + 20) {
                    	if(position3.top == position1.top)
                        	entry.css('left', $("#entry" + row.previous).width() * 2 + position1.left + 40);
                        else
                        	entry.css('left', 0);
                    }
                    //entry.css('left', $("#entry" + row.previous).position().right() + position1.left + 20);
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
                parity++;
                string = '<div class="entry-row ' + ((parity % 2 == 0) ? 'odd' : '') + ' ' + (i == data.committee.length -1 ? 'end-entry':'') + '">';
                
                //New day
                if(row.day != day) {
                    day = row.day;
                    string += '<span class="committee-day">' + day + '</span>';
                } else {
                    string += '<span class="committee-day"></span>';
                }
                string += '<span class="committee-time">' + row.start_time + "</span>";
                string += '<span class="committee-username"><a href="/profile?member=' + data.users[row.user_id_1].username + '">' + data.users[row.user_id_1].username;
                if(data.users[row.user_id_1].seat != "") string += " (" + data.users[row.user_id_1].seat + ")";
                string += '</a></span>';
                string += '<span class="committee-username"><a href="/profile?member=' + data.users[row.user_id_2].username + '">' + data.users[row.user_id_2].username;
                if(data.users[row.user_id_2].seat != "") string += " (" + data.users[row.user_id_2].seat + ")";
                string += '</a></span>';
                $("#committee-timetable-body").append(string +  "</div>");
            }
        },
        'json');
}

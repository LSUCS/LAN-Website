$(document).ready(function() {

    Carousel.initialise();
    
    getTimetable();
    

});

function getTimetable() {
    $.get(
        "index.php?page=whatson&action=gettimetable",
        function (data) {
            for (var i = 0; i < data.length; i++) {
            
                var row = data[i];
                
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
                entry.css('width', 700/row.division -20);
                
                if (row.previous) {
                    var position3 = $("#entry" + row.previous).position();
                    entry.css('left', $("#entry" + row.previous).width() + position3.left + 20);
                } else {
                    entry.css('left', 64);
                }
                entry.css('height', position2.top - position1.top -20);
                
            }
        },
        'json');
}

var Carousel = {

    activeSlide: 1,
    slideWidth: 830,
    slideCount: 3,
    
    initialise: function(slide) {
        if (slide > 0) this.moveSlide(slide);
        else this.moveSlide(this.activeSlide); 
        $("#left").click(function() {
            Carousel.left();
        });
        $("#right").click(function() {
            Carousel.right();
        });
    },
    moveSlide: function (slide) {
        $("#timetable-days").stop().animate({ left: -this.slideWidth*(slide-1) }, 500, "swing");
        $("#timetable-container").stop().animate({ height: $("#timetable-days .timetable-day:nth-child(" + slide + ")").height() }, 500, "swing");
        $("#day").html($("#timetable-days .timetable-day:nth-child(" + slide + ")").attr('id'));
        this.activeSlide = slide;
    },
    left: function() {
        if (this.activeSlide == 1) this.moveSlide(this.slideCount);
        else this.moveSlide(this.activeSlide -1);
    },
    right: function() {
        if (this.activeSlide == this.slideCount) this.moveSlide(1);
        else this.moveSlide(this.activeSlide +1);
    },

};
$(document).ready(function() {
    
    loadStats();

});

function loadStats() {
    $.get(
        UrlBuilder.buildUrl(false, "stats", "loadstats"),
        function(ret) {
            var data = ret.data;
            var ticks = [];
            var s1 = [];
            var labels = [];
            var max = 0;
            for (var game in data) {
                var time = data[game];
                s1.push(time);
                ticks.push(game);
                labels.push(ret.count[game] + " player" + (ret.count[game] > 1?"s":""));
                if (time > max) max = time;
            }
            
            var unit = "seconds";
            if (max > 60) unit = "minutes";
            if (max > 60*60) unit = "hours";
            if (max > 60*60*24) unit = "days";
            if (max > 60*60*24*7) unit = "weeks";
            
            for (var i = 0; i < s1.length; i++) {
                switch (unit) {
                    case "minutes": s1[i] = s1[i] / 60; break;
                    case "hours": s1[i] = s1[i] / (60*60); break;
                    case "days": s1[i] = s1[i] / (60*60*24); break;
                    case "weeks": s1[i] = s1[i] / (60*60*24*7); break;
                }
            }
            
            $("#gametime").height(54*s1.length + 80);
            
            var gametime = $.jqplot("gametime", [ s1 ], {
                title: "Total time per game (Steam games only)",
                animate: true,
                animateReplot: true,
                seriesDefaults: {
                    renderer:$.jqplot.BarRenderer,
                    pointLabels: { show: true, labels: labels },
                    shadowAngle: 135,
                    rendererOptions: {
                        barDirection: 'horizontal',
                        barWidth: 40,
                        varyBarColor: true,
                        animation: {
                            speed: 1000
                        }
                    },
                    xaxis: "x2axis"
                },
                axes: {
                    yaxis: {
                        renderer: $.jqplot.CategoryAxisRenderer,
                        ticks: ticks,
                    },
                    x2axis: {
                        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                        label: "Time (" + unit + ")"
                    }
                }
            });
            
            var tot = ret.total;
            switch (unit) {
                case "minutes": tot = tot / 60; break;
                case "hours": tot = tot / (60*60); break;
                case "days": tot = tot / (60*60*24); break;
                case "weeks": tot = tot / (60*60*24*7); break;
            }
            $("#totaltime").html("<b>Total:</b> " + Math.round(tot * 10) / 10 + ' ' + unit);
            
        },
        "json");
}
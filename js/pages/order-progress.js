var attempt = 1;
$(document).ready(function() {
    checkComplete();
});

function checkComplete() {
    $.post(
        "index.php?page=tickets&action=checkcomplete",
        { pending_id: $("#pending_id").val(), attempt: attempt},
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            if (data.status == "complete") {
                $("#loading").hide();
                $("#complete").show();
            }
            else if (data.status == "failed") {
                $("#loading").hide();
                $("#failed").show();
            }
            else if (data.status == "retry") {
                attempt++;
                setTimeout(function(){checkComplete();}, 1000);
            }
        },
        'json');
}
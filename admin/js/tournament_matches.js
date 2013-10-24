function edit(id) {
    if(!id) return;
    
    var winner1 = $("#winner1-" + id).prop('checked');
    var winner2 = $("#winner2-" + id).prop('checked');
    
    if(winner1 && winner2) {
        Overlay.openOverlay(false, "There can only be one winner!", 2000);
        return;
    }
    var winner = 0;
    if(winner1) winner = 1;
    else if(winner2) winner = 2;
    
    var score1 = $("#score1-" + id).val();
    var score2 = $("#score2-" + id).val();
    
    if(winner == 0 && score1 !== "" && score2 !== "") {
        Overlay.openOverlay(false, "You cannot have scores if there is no winner!", 2000);
        return;
    }
    
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(true, 'tournaments', 'editmatch'), {
            'id': id,
            'score1': score1,
            'score2': score2,
            'winner': winner,
        },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Match Edited", 1000);
            window.setTimeout("location.reload()", 1000);
        },
        'json');
}
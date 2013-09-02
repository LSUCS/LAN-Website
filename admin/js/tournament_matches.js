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
    
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(true, 'tournaments', 'editmatch'), {
            'id': id,
            'score1': $("#score1-" + id).val(),
            'score2': $("#new-type-" + id).val(),
            'winner': winner,
        },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "Match Edited", 1000);
            window.setTimeout("window.reload();", 1000);
        },
        'json');
}
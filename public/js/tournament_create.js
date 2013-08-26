$(document).ready(function() {
    //Save details button
    $("#create-team").click(function() {
        createTeam();
    });

function saveGameDetails() {
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(false, "tournament", "createteam"),
        { name: $("#team-name").val(), icon: $("#team-icon").val(), description: $("#team-description") },
        function (data) {
            if (data != null) {
                if(data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Team Created, Redirecting", 1000);
                window.setTimeout("document.location = '" + UrlBuilder.buildUrl(false, "tournament", "viewteam", {id: data.id}) + "';", 500);
        },
        'json');
}
$(document).ready(function() {
    //Save details button
    $("#create-team-button").click(function() {
        createTeam();
    });
});

function createTeam() {
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(false, "teams", "createteam"),
        { name: $("#team-name").val(), icon: $("#team-icon").val(), description: $("#team-description").val() },
        function (data) {
            if (data != null) {
                if(data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, "Team Created, Redirecting", 1000);
                window.setTimeout("document.location = '" + UrlBuilder.buildUrl(false, "teams", "view", {id: data.id}) + "';", 500);
            }
        },
        'json');
}
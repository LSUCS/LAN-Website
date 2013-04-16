$(document).ready(function() {
    
    //Signup/tournament click
    $(".signup, .tournament").click(function() { window.location = UrlBuilder.buildUrl(false, 'tournaments', 'tournament', { id: $(this).attr('value') }); });
    
});
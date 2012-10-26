$(document).ready(function() {

    loadSignup();
    $("#sign-up").click(function() { signUp(); });

});

function loadSignup() {
    $.get(
        "index.php?page=tournaments&action=checktf2",
        function (data) {
            $("#sign-up, #message").hide();
            if (data.not_signed_up) {
                $("#sign-up").show();
            } else if (data.error) {
                $("#message").html(data.error).show();
            }
        },
        'json');
}

function signUp() {
    Overlay.loadingOverlay();
    
    $.get(
        "index.php?page=tournaments&action=signuptf2",
        function (data) {
            if (data && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "You have signed up for TF2", 1500);
            setTimeout(function() { loadSignup(); }, 1500);
        },
        'json');
}
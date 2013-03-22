$(document).ready(function() {

    loadSignup();
    $("#sign-up").click(function() { 
		Overlay.openOverlay(true, 'Please enter your Minecraft username</br><input id="minecraftname" type="text" /><button id="minecraftsignup">Sign Up</button>');
		$("#minecraftsignup").button();
		$("#minecraftsignup").click(function() { signUp($("#minecraftname").val()); });
	});

});

function loadSignup() {

    $.get(
        "index.php?page=tournaments&action=checkhungergames",
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

function signUp(minecraft) {
	Overlay.loadingOverlay();
    
    $.post(
        "index.php?page=tournaments&action=signuphungergames",
		{ minecraft: minecraft },
        function (data) {
            if (data && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            Overlay.openOverlay(false, "You have signed up for Hungergames", 1500);
            setTimeout(function() { loadSignup(); }, 1500);
        },
        'json');
}
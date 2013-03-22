var images = new Array();

$(document).ready(function() {
    loadGallery();
});

function runGallery() {
    
    if (images[0]) {
        $("#gallery").fadeOut(500);
        setTimeout(
            function() {
                $("#gallery").html('<img src="' + images[0] + '" />').fadeIn(500);
                images.splice(0,1);
                setTimeout("runGallery()", 5000);
            }, 500);
    }
    else {
        loadGallery();
    }
}


function loadGallery() {
    $.get(
        "index.php?page=home&action=getimage",
        function (data) {
            images = data;
            runGallery();
        },
        'json');
}
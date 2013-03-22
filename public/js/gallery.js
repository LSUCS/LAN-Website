var select = null;
var album = null;
$(document).ready(function() {
    
    $(".folder").click(function() {
        album = $(this).find('.folder-label').html();
        loadGallery($(this).find('.folder-label').html());
    });
    Galleria.loadTheme('/js/galleria/themes/classic/galleria.classic.min.js');
    Galleria.run("#galleria", { autoplay: 5000, lightbox: true });
    
    //Load hash
    var hash = window.location.hash.replace("#", "").split("&");
    if (hash.length == 2 && hash[0] != "" && !isNaN(hash[1])) {
        album = hash[0];
        setTimeout(function() { loadGallery(hash[0]); }, 200);
        select = hash[1];
    }
    
    Galleria.ready(function (options) {
        this.bind('image', function(e) {
            window.location.hash = "#" + album + "&" + e.index;
        });
    });
});


function loadGallery(folder) {
    Overlay.loadingOverlay();
    $.post(
        "index.php?page=gallery&action=loadfolder",
        { folder: folder },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            if (select != null) {
                $("#galleria").data("galleria").setOptions({show: select});
                select = null;
            } else {
                $("#galleria").data("galleria").setOptions({show: 0});
            }
            $("#galleria").data("galleria").load(data);
            if ($("#gallery-container").css('display') == 'none') $("#gallery-container").slideDown();
            Overlay.closeOverlay();
        },
        'json');
}
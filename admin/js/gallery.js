$(document).ready(function() {
    $("#process-button").click(function() {
        Overlay.loadingOverlay();
        $.post(
            UrlBuilder.buildUrl(true, 'gallery', 'process'),
            { force: $("#force").is(":checked") },
            function (data) {
                if (data != null && data.error) {
                    Overlay.openOverlay(true, data.error);
                    return;
                }
                Overlay.openOverlay(false, data.total + ' images scanned, ' + data.processed + ' resized', 2000);
            },
            'json');
    });
});
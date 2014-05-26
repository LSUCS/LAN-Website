$( document ).ready(function() {
    $('#cleanup').click(function() {
        $.post(
            UrlBuilder.buildUrl(true, 'groupseats', 'cleanup'),
            function (data) {
                alert("Deleted: " + data);
                location.reload();
            }
        );
        return false;
    });
});
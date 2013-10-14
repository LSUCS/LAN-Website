$(document).ready(function() {
    $('#login-button').click(function() {
        if($('#username').val() == "") {
            shakeUsername();
            return false;
        }
        if($('#password').val() == "") {
            shakePassword();
            return false;
        }
    });
});

function shakeUsername() {
    $('#username').css('border-color', 'red');
    
    $('#username').addClass('shaking');
    window.setTimeout("$('#username').removeClass('shaking');", 1000);
}

function shakePassword() {
    $('#password').css('border-color', 'red');
    
    $('#password').addClass('shaking');
    window.setTimeout("$('#password').removeClass('shaking');", 1000);
}
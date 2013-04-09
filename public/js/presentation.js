var src = "";
$(document).ready(function() {
    src = $("#presentation").prop('src');
	setInterval(function() {
		$("#presentation").prop('src', src);
		}, $("#refresh").attr('value'));
});
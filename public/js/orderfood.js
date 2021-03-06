$(document).ready(function() {

	if ($(".shop-option").length == 0) $("#place-order").hide();

	$("#place-order").click(function() {
		Overlay.openOverlay(true, 'After ordering you must give your money to a committee member before the cut off or you will not get your food<br /><button id="ok-button">Ok</button>');
		$("#ok-button").button();
	});
	
	$("#ok-button").live('click', function() {
		order();
	});
	
    //HTML5 requires a little more space
    if($(".option-amount")[0].type == "number") {
        $(".option-amount").css({
            "width": "30px",
            "border-radius": "5px 2px 2px 5px",
            "-moz-border-radius": "5px 2px 2px 5px",
            "-webkit-border-radius": "5px 2px 2px 5px"
        });
    }
});

// Function to tabularise food shops
$(function() {
    $( "#shops" ).tabs();
});

function order() {

	//Get options
	var options = new Array();
	$(".shop-option").each(function() {
		options[options.length] = { option_id: $(this).attr('value'), amount: $(this).find('.option-amount').val() };
	});
	
	Overlay.loadingOverlay();
	$.post(
        UrlBuilder.buildUrl(false, "orderfood", "order"),
		{ options: JSON.stringify(options) },
		function (data) {
			if (data && data.error) {
				Overlay.openOverlay(true, data.error);
				return;
			}
			Overlay.openOverlay(false, 'Your order has been placed but NOT confirmed.<br />You must pay a committee member before <b>' + data.order_by + '</b> if you want to receive your food.<br /> Your order total is: &pound;<b>' + data.cost + '</b><br /><button id="paynow">I have read the above</button>');
			$("#paynow").button();
			$("#paynow").click(function() { Overlay.closeOverlay(); });
			$(".option-amount").val(0);
		},
		'json');

}

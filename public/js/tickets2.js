$(document).ready(function() {
    mm = $('#member-price').val();
    nmm = $('#nonmember-price').val();
    //Spinners
    $("#nonmember-amount, #member-amount").spinner({ min: 0, max: 5 });
    $(".ui-spinner").live('click', function() {
        updatePaypalBox();
    });
    $("#nonmember-amount, #member-amount").live('change', function() {
        updatePaypalBox();
    });
    updatePaypalBox();
    
    //Checkout button
    $(".checkout-button").click(function() {
        
        //Agree to terms and conditions
        var string = '<p>By purchasing this ticket you are agreeing to abide by our LAN rules found <a href="http://lans.lsucs.org.uk/index.php?page=info&action=rules">here</a>. ';
        string += 'If you do not agree to follow these rules then do not purchase this ticket. Breaking these rules gives us the right to remove you from the event.<p><br />';
        string += '<p>If you are under 18 and attending a LAN you are required by Union policy to have <b>written consent from a parent or guardian</b>. If you do not have this you will not be allowed entry to the LAN and will not be offered a refund.</p>';
        string += '<p><button id="continue-checkout">Continue</button><button id="cancel-checkout">Cancel</button></p>';
        $("#overlay-content").html(string);
        $("#continue-checkout, #cancel-checkout").button();
        Overlay.openOverlay(false, "");
        
    });
    $("#cancel-checkout").live('click', function() {
        Overlay.closeOverlay();
    });
    $("#continue-checkout").live('click', function() {
        checkout();
    });
    
    //Charity
    if($("#member-charity").length > 0) {
        $("#member-price").spinner({ min: mm, numberFormat: "n", step: "0.50" });
        $("#member-price").live('change', function() {
            updatePaypalBox();
        });
    }
    if($('#nonmember-charity').length > 0) {
        $('#nonmember-price').spinner({ min: nmm, numberFormat: "n", step: "0.50" });
        $("#nonmember-price").live('change', function() {
            updatePaypalBox();
        });
    }
    
    //Free tickets
    if($("#claim-member-free").length > 0) {
        $('#claim-member-free').click(function() {
            $.post(
                UrlBuilder.buildUrl(false, "tickets", "free"),
                function (data) {
                    if (data != null && data.error) {
                        Overlay.openOverlay(true, data.error);
                        return;
                    }
                    if (data.successful) {
                        Overlay.openOverlay(true, "Ticket Claimed Successfully");
                        //window.setTimeout(function() { location.reload(); }, 1000);
                    }
                },
            'json');
        });
    }
    
});

function checkout() {
    if(!updatePaypalBox()) return;
    Overlay.loadingOverlay();
    $.post(
        UrlBuilder.buildUrl(false, "tickets", "checkout"),
        { member_amount: $("#member-amount").val(), nonmember_amount: $("#nonmember-amount").val(), member_price: $("#member-price").attr('value'), nonmember_price: $("#nonmember-price").attr('value') },
        function (data) {
            if (data != null && data.error) {
                Overlay.openOverlay(true, data.error);
                return;
            }
            if (data.pending_id) {
                $("#custom-field").val(data.pending_id);
                document.forms["paypal-form"].submit();
            }
        },
        'json');
}

function checkPrices() {
    if(parseInt($("#member-price").attr('value')) < mm) {
        Overlay.openOverlay(true, "Member price cannot be this low, min: £" + mm + '.00');
        $("#member-price").val(mm);
        return false;
    }
    if(parseInt($("#nonmember-price").attr('value')) < nmm) {
        Overlay.openOverlay(true, "Non-Member price cannot be this low, min: £" + nmm + '.00');
        $("#nonmember-price").val(nmm);
        return false;
    }
    return true;
}

function updatePaypalBox() {
    if(!checkPrices()) return false;

    $(".paypalitem").remove();
    var i = 1;
    if ($("#member-amount").val() > 0) {
        var line = '<input class="paypalitem" type="hidden" name="item_name_' + i + '" value="LAN' + $("#lan span").html() + ' Member Ticket">';
        line += '<input class="paypalitem" type="hidden" name="amount_' + i + '" value="' + $("#member-price").attr('value') + '">';
        line += '<input class="paypalitem" type="hidden" name="quantity_' + i + '" value="' + $("#member-amount").val() + '">';
        line += '<input class="paypalitem" type="hidden" name="item_number_' + i + '" value="member">';
        $("#paypal-form").prepend(line);
        i++;
    }
    if ($("#nonmember-amount").val() > 0) {
        var line = '<input class="paypalitem" type="hidden" name="item_name_' + i + '" value="LAN' + $("#lan span").html() + ' Non-Member Ticket">';
        line += '<input class="paypalitem" type="hidden" name="amount_' + i + '" value="' + $("#nonmember-price").attr('value') + '">';
        line += '<input class="paypalitem" type="hidden" name="quantity_' + i + '" value="' + $("#nonmember-amount").val() + '">';
        line += '<input class="paypalitem" type="hidden" name="item_number_' + i + '" value="nonmember">';
        $("#paypal-form").prepend(line);
        i++;
    }
    return true;
}
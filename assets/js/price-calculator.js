jQuery(document).ready(function($) {
    $('#kiso24-calculate-price').on('click', function(e) {
        e.preventDefault();
        var width = $('#kiso24-width').val();
        var height = $('#kiso24-height').val();
        var priceGroup = $('#selected_blind_price_group').val(); // Use the hidden input for the selected blind's price group
        var productId = $('input[name="product_id"]').val();

        $.ajax({
            url: kiso24_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_blind_price',
                width: width,
                height: height,
                price_group: priceGroup,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    $('#kiso24-price-display').text('Price: $' + response.data.price.toFixed(2));
                } else {
                    $('#kiso24-price-display').text('Error: ' + response.data);
                }
            },
            error: function() {
                $('#kiso24-price-display').text('Error calculating price');
            }
        });
    });
});
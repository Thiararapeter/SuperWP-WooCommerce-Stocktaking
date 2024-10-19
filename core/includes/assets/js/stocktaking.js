jQuery(document).ready(function($) {
    function updateTotals() {
        var totalProducts = 0;
        var totalCurrentStock = 0;
        var totalCurrentStockValue = 0;
        var totalCounted = 0;
        var totalCountedValue = 0;

        $('.current-stock').each(function() {
            totalProducts++;
            totalCurrentStock += parseInt($(this).text()) || 0;
        });

        $('.current-stock-value').each(function() {
            totalCurrentStockValue += parseFloat($(this).text().replace(/[^0-9.-]+/g,"")) || 0;
        });

        $('.counted').each(function() {
            totalCounted += parseInt($(this).text()) || 0;
        });

        $('.counted-value').each(function() {
            totalCountedValue += parseFloat($(this).text().replace(/[^0-9.-]+/g,"")) || 0;
        });

        $('#total-products').text(totalProducts);
        $('#total-current-stock').text(totalCurrentStock);
        $('#total-current-stock-value').html('KSh ' + totalCurrentStockValue.toFixed(2));
        $('#total-counted').text(totalCounted);
        $('#total-counted-value').html('KSh ' + totalCountedValue.toFixed(2));
    }

    // Add this at the beginning of the file
    function showLoading() {
        $('#loading-indicator').show();
    }

    function hideLoading() {
        $('#loading-indicator').hide();
    }

    // Product search
    $('#product-search-form').on('submit', function(e) {
        e.preventDefault();
        var searchTerm = $('#product-search').val();
        var stocktakeId = $('#stocktake-id').val();
        
        showLoading();
        $.ajax({
            url: wc_stocktaking_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_stocktaking_search_products',
                security: wc_stocktaking_ajax.nonce,
                search_term: searchTerm,
                stocktake_id: stocktakeId
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#product-list').html(response.data.html);
                    updateTotals();
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                hideLoading();
                showError('An error occurred while searching for products');
            }
        });
    });

    // Update count
    $(document).on('change', '.new-count', function() {
        var $input = $(this);
        var productId = $input.data('product-id');
        var newCount = parseInt($input.val());
        var $reason = $('#reason_' + productId);

        if (isNaN(newCount)) {
            showError('Please enter a valid number');
            $input.val(0);
            return;
        }

        showLoading();
        $.ajax({
            url: wc_stocktaking_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_stocktaking_update_count',
                security: wc_stocktaking_ajax.nonce,
                product_id: productId,
                new_count: newCount,
                reason: $reason.val()
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#counted_' + productId).text(response.data.new_count);
                    $('#discrepancy_' + productId).text(response.data.discrepancy);
                    showSuccess('Count updated successfully');
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                hideLoading();
                showError('An error occurred while updating the count');
            }
        });
    });

    $('#save-count').on('click', function(e) {
        e.preventDefault();
        var newCounts = {};
        $('.new-count').each(function() {
            var productId = $(this).data('product-id');
            var count = $(this).val();
            if (count !== '0') {
                newCounts[productId] = count;
            }
        });

        showLoading();
        $.ajax({
            url: wc_stocktaking_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_stocktaking_save_count',
                security: wc_stocktaking_ajax.nonce,
                new_counts: newCounts
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#stocktaking-messages').html('<div class="updated"><p>' + response.data.message + '</p></div>');
                    // Update the displayed counts
                    for (var productId in newCounts) {
                        var currentCount = parseInt($('#counted_' + productId).text()) || 0;
                        var newTotal = currentCount + parseInt(newCounts[productId]);
                        $('#counted_' + productId).text(newTotal);
                    }
                } else {
                    $('#stocktaking-messages').html('<div class="error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                hideLoading();
                $('#stocktaking-messages').html('<div class="error"><p>An error occurred while saving the count.</p></div>');
            }
        });
    });

    $('#update-stock').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: wc_stocktaking_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_stocktaking_update_stock',
                security: wc_stocktaking_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#stocktaking-messages').html('<div class="updated"><p>' + response.data.message + '</p></div>');
                    location.reload(); // Reload the page to reflect updated stock
                } else {
                    $('#stocktaking-messages').html('<div class="error"><p>Error: ' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $('#stocktaking-messages').html('<div class="error"><p>An error occurred while updating the stock.</p></div>');
            }
        });
    });

    updateTotals();

    // Function to show error messages
    function showError(message) {
        $('#stocktaking-messages').html('<div class="notice notice-error"><p>' + message + '</p></div>');
        setTimeout(function() {
            $('#stocktaking-messages').empty();
        }, 5000);
    }

    // Function to show success messages
    function showSuccess(message) {
        $('#stocktaking-messages').html('<div class="notice notice-success"><p>' + message + '</p></div>');
        setTimeout(function() {
            $('#stocktaking-messages').empty();
        }, 5000);
    }
});

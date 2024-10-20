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
            var value = parseFloat($(this).text().replace(/[^0-9.-]+/g,"")) || 0;
            totalCurrentStockValue += value;
            console.log("Current Stock Value: ", value); // Debugging line
        });

        $('.counted').each(function() {
            totalCounted += parseInt($(this).text()) || 0;
        });

        $('.counted-value').each(function() {
            var value = parseFloat($(this).text().replace(/[^0-9.-]+/g,"")) || 0;
            totalCountedValue += value;
            console.log("Counted Value: ", value); // Debugging line
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

        showLoading();
        $.ajax({
            url: wc_stocktaking_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_stocktaking_update_count',
                security: wc_stocktaking_ajax.nonce,
                product_id: productId,
                new_count: newCount
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#counted_' + productId).text(response.data.new_count);
                    $('#counted_value_' + productId).html(response.data.total_count_value);
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
        
        var stocktakeId = $('#stocktake-id').val();
        var newCounts = {}; // Collect new counts here

        // Add logic to populate newCounts

        $.ajax({
            url: wc_stocktaking_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wc_stocktaking_save_count',
                security: wc_stocktaking_ajax.nonce,
                stocktake_id: stocktakeId,
                new_counts: newCounts
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
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
        alert(message); // Replace with your error display logic
    }

    // Function to show success messages
    function showSuccess(message) {
        $('#stocktaking-messages').html('<div class="notice notice-success"><p>' + message + '</p></div>');
        setTimeout(function() {
            $('#stocktaking-messages').empty();
        }, 5000);
    }

    $('#select-all-categories').change(function() {
        $('.category-checkbox').prop('checked', this.checked);
    });

    $('.category-checkbox').change(function() {
        if (!this.checked) {
            $('#select-all-categories').prop('checked', false);
        } else if ($('.category-checkbox:checked').length === $('.category-checkbox').length) {
            $('#select-all-categories').prop('checked', true);
        }
    });

    // Handle new count input
    $('.new-count').on('focus', function() {
        $(this).select(); // Highlight the text for easy replacement
    });

    $('.new-count').on('change', function() {
        var $input = $(this);
        var productId = $input.data('product-id');
        var newCount = parseInt($input.val());

        if (isNaN(newCount)) {
            showError('Please enter a valid number');
            $input.val(0);
            return;
        }

        var currentCounted = parseInt($('#counted_' + productId).text());
        var updatedCounted = currentCounted + newCount;

        if (updatedCounted < 0) {
            showError('Count cannot be negative');
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
                new_count: updatedCounted
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#counted_' + productId).text(updatedCounted);
                    $('#counted_value_' + productId).html(response.data.total_count_value);
                    $('#last_update_' + productId).text(response.data.last_update_time);
                    showSuccess('Count updated successfully. Total counted: ' + updatedCounted);
                } else {
                    showError(response.data.message);
                }
                // Reset the new count input to 0 without affecting the counted value
                $input.val('').attr('placeholder', '0');
            },
            error: function() {
                hideLoading();
                showError('An error occurred while updating the count');
            }
        });
    });

    // Function to format price
    function wc_price(amount) {
        return 'KSh ' + amount.toFixed(2); // Adjust currency symbol and format as needed
    }
});

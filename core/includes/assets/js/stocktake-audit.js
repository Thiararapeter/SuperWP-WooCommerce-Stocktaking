jQuery(document).ready(function($) {
    function setFormState(isClosed) {
        $('#audit-form select, #audit-form input[type="text"]').prop('disabled', isClosed);
        $('#audit-form input[type="text"]').prop('readonly', isClosed);
        $('input[name="update_audit"]').prop('disabled', isClosed).toggle(!isClosed);
    }

    setFormState($('#toggle-audit-status').data('status') === 'Closed');

    $('#toggle-audit-status').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var currentStatus = $button.data('status');
        var newStatus = currentStatus === 'Closed' ? 'Open' : 'Closed';
        var action = currentStatus === 'Closed' ? 'reopen_audit' : 'close_audit';

        $.ajax({
            url: stocktakeAuditData.ajaxurl,
            type: 'POST',
            data: {
                action: action,
                stocktake_id: stocktakeAuditData.stocktakeId,
                nonce: stocktakeAuditData.nonce
            },
            success: function(response) {
                if (response.success) {
                    setFormState(newStatus === 'Closed');
                    $('#audit-status').text(newStatus);
                    $button.text(newStatus === 'Closed' ? 'Reopen Audit' : 'Close Audit').data('status', newStatus);
                    $('#audit-form').toggleClass('audit-closed', newStatus === 'Closed');
                } else {
                    console.error('Error toggling audit status:', response.data);
                    alert('Failed to toggle audit status: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('Failed to toggle audit status. Please check the console for more information.');
            }
        });
    });

    // Updated code for category filter
    $('#category-filter').on('change', function() {
        var selectedCategory = $(this).val();
        console.log('Selected category:', selectedCategory); // Debugging

        if (selectedCategory) {
            $('.product-row').each(function() {
                var $row = $(this);
                var rowClasses = $row.attr('class').split(/\s+/);
                console.log('Row classes:', rowClasses); // Debugging
                
                if (rowClasses.includes(selectedCategory)) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        } else {
            $('.product-row').show();
        }
        
        console.log('Visible rows:', $('.product-row:visible').length); // Debugging
    });

    // New code for product search
    $('#product-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.product-row').each(function() {
            var productName = $(this).data('product-name');
            if (productName.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Existing JavaScript for custom actions
    $('.follow-up-action').on('change', function() {
        var customActionInput = $(this).siblings('.custom-action');
        if ($(this).val() === 'Custom') {
            customActionInput.show();
        } else {
            customActionInput.hide();
        }
    });

    $('.follow-up-action').each(function() {
        if ($(this).val() === 'Custom') {
            $(this).siblings('.custom-action').show();
        }
    });
});

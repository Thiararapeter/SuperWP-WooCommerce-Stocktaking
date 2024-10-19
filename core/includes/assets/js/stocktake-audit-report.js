jQuery(document).ready(function($) {
    // Audit functionality
    $('.follow-up-action').on('change', function() {
        var customActionInput = $(this).siblings('.custom-action');
        if ($(this).val() === 'Custom') {
            customActionInput.show();
        } else {
            customActionInput.hide();
        }
    });

    // Initialize custom action inputs
    $('.follow-up-action').each(function() {
        if ($(this).val() === 'Custom') {
            $(this).siblings('.custom-action').show();
        }
    });

    // Report functionality
    $('.stocktake-report-table').DataTable({
        responsive: true,
        order: [[4, 'desc']], // Sort by discrepancy column by default
        columnDefs: [
            { targets: [3, 4], className: 'dt-body-right' } // Right-align numeric columns
        ]
    });

    // Toggle between all products and products with discrepancies
    $('#toggle-discrepancies').on('click', function() {
        var table = $('.stocktake-report-table').DataTable();
        if ($(this).hasClass('showing-all')) {
            table.column(4).search('(?!^0$)', true, false).draw();
            $(this).text('Show All Products').removeClass('showing-all');
        } else {
            table.column(4).search('').draw();
            $(this).text('Show Only Discrepancies').addClass('showing-all');
        }
    });

    // Print report
    $('#print-report').on('click', function(e) {
        e.preventDefault();
        window.print();
    });

    // Export to CSV
    $('#export-csv').on('click', function(e) {
        e.preventDefault();
        var table = $('.stocktake-report-discrepancies table');
        var csv = [];
        
        // Add headers
        var headers = [];
        table.find('thead th').each(function() {
            headers.push('"' + $(this).text().trim() + '"');
        });
        csv.push(headers.join(','));
        
        // Add rows
        table.find('tbody tr').each(function() {
            var row = [];
            $(this).find('td').each(function() {
                row.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });
            csv.push(row.join(','));
        });
        
        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement("a");
        if (link.download !== undefined) {
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "stocktake_report.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });

    // Highlight rows with discrepancies
    $('.stocktake-report-table tbody tr').each(function() {
        var discrepancy = parseInt($(this).find('td:eq(4)').text());
        if (discrepancy !== 0) {
            $(this).addClass('has-discrepancy');
        }
    });
});

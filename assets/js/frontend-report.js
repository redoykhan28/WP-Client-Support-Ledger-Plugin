jQuery(document).ready(function($) {
    var xhr; // To manage ongoing AJAX requests
    var searchTimeout; // For debouncing search input

    // Function to load client summary
    function loadClientSummary(searchTerm, paged, month, year) {
        var $container = $('#wcsl_client_summary_ajax_container');
        var $searchInput = $('#wcsl_frontend_client_search_input');
        var $spinner = $searchInput.siblings('.spinner');

        if (xhr && xhr.readyState !== 4) {
            xhr.abort(); // Abort previous request if still running
        }

        $spinner.css('display', 'inline-block'); // Show spinner
        $container.html('<p class="wcsl-loading-message">' + wcsl_frontend_ajax.loading_message + '</p>');


        xhr = $.ajax({
            url: wcsl_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcsl_load_frontend_client_summary', // Our WP AJAX action hook
                search_term: searchTerm,
                paged: paged,
                month: month,
                year: year,
                _ajax_nonce: $('#wcsl_frontend_report_nonce').val() // Get nonce from hidden field
            },
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    $container.html(response.data.html);
                } else {
                    $container.html('<p class="wcsl-error-message">' + response.data.message + '</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $spinner.hide();
                if (textStatus !== 'abort') { // Don't show error if we aborted it
                    $container.html('<p class="wcsl-error-message">AJAX Error: ' + textStatus + ' - ' + errorThrown + '</p>');
                }
            }
        });
    }

    // Initial load when the container is present
    if ($('#wcsl_client_summary_ajax_container').length) {
        var initialSearchTerm = $('#wcsl_frontend_client_search_input').val() || '';
        var initialMonth = $('#wcsl_frontend_client_search_input').data('month');
        var initialYear = $('#wcsl_frontend_client_search_input').data('year');
        loadClientSummary(initialSearchTerm, 1, initialMonth, initialYear);
    }

    // Search input handling (with debounce)
    $('#wcsl_frontend_client_search_input').on('keyup', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val();
        var month = $(this).data('month');
        var year = $(this).data('year');

        searchTimeout = setTimeout(function() {
            loadClientSummary(searchTerm, 1, month, year); // Reset to page 1 on new search
        }, 500); // 500ms delay after user stops typing
    });

    // Pagination link handling (delegated event)
    $('#wcsl_client_summary_ajax_container').on('click', '.wcsl-pagination a.page-numbers', function(e) {
        e.preventDefault();
        var $link = $(this);
        var href = $link.attr('href');
        var paged = 1;

        if (href && href.includes('wcsl_paged_clients=')) {
            paged = href.split('wcsl_paged_clients=')[1].split('&')[0];
        } else if (href && href.includes('paged=')) { // Fallback if using 'paged' from WP default
             paged = href.split('paged=')[1].split('&')[0];
        }


        var searchTerm = $('#wcsl_frontend_client_search_input').val();
        var month = $('#wcsl_frontend_client_search_input').data('month');
        var year = $('#wcsl_frontend_client_search_input').data('year');
        loadClientSummary(searchTerm, parseInt(paged), month, year);
    });
});


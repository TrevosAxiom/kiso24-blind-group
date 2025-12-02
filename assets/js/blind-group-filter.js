jQuery(document).ready(function($) {
    // --- AJAX Filtering and Pagination ---

    const grid = $('.blind-group-grid');
    const loadMoreButton = $('#load-more-blinds');
    const noMoreBlindsMessage = $('#no-more-blinds');
    const searchInput = $('#blind-search');
    const filterDropdowns = $('.filter-dropdown-container select');

    let isLoading = false;
    let searchTimeout;

    /**
     * Main function to fetch blinds via AJAX.
     * @param {boolean} isNewFilter - True if this is a new filter/search, false if it's for pagination.
     */
    function fetchBlinds(isNewFilter = false) {
        if (isLoading) {
            return;
        }
        isLoading = true;
        grid.addClass('loading'); // Optional: Add a class for loading indicator
        noMoreBlindsMessage.hide();

        let currentPage = isNewFilter ? 1 : parseInt(grid.attr('data-page'), 10) + 1;

        // Collect filter values
        const filters = {};
        filterDropdowns.each(function() {
            const taxonomy = $(this).attr('id').replace('-filter', '');
            const value = $(this).val();
            if (value) {
                filters[taxonomy] = value;
            }
        });

        const data = {
            action: 'filter_blinds',
            nonce: kiso24_ajax_params.nonce,
            group_id: grid.data('group-id'),
            page: currentPage,
            per_page: grid.data('per-page'),
            search: searchInput.val(),
            filters: filters
        };

        console.log('Filtering: Sending data to server...', data);

        $.ajax({
            url: kiso24_ajax_params.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('Filtering: Received response from server...', response);
                if (response.success) {
                    if (isNewFilter) {
                        grid.html(response.data.html); // Replace content for new filters
                    } else {
                        grid.append(response.data.html); // Append for "Load More"
                    }

                    grid.attr('data-page', currentPage);

                    // Handle visibility of "Load More" button
                    if (response.data.has_more) {
                        loadMoreButton.show();
                    } else {
                        loadMoreButton.hide();
                        // Show "no more results" only if there are already items in the grid
                        if (grid.children().length > 0) {
                            noMoreBlindsMessage.show();
                        }
                    }

                    // If it's a new filter and no results are found, the AJAX handler returns a message.
                    // We don't need extra handling here for that case.

                    // --- SELECTION PERSISTENCE ---
                    // After the grid is re-rendered, re-apply the 'blind-selected' class if a blind was selected.
                    // The 'selectedBlindDetails' variable is made global in blind-group-modal.js.
                    if (typeof selectedBlindDetails !== 'undefined' && selectedBlindDetails.blind_name) {
                        grid.find('.selectable-blind[data-blind-name="' + selectedBlindDetails.blind_name + '"]').addClass('blind-selected');
                    }
                    // --- END SELECTION PERSISTENCE ---

                } else {
                    console.error('AJAX Error:', response.data.message);
                    grid.html('<p>An error occurred. Please try again.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Request Failed:', textStatus, errorThrown);
                grid.html('<p>A network error occurred. Please try again.</p>');
            },
            complete: function() {
                isLoading = false;
                grid.removeClass('loading');
            }
        });
    }

    // --- Event Listeners ---

    // 1. Filter dropdowns
    filterDropdowns.on('change', function() {
        fetchBlinds(true); // isNewFilter = true
    });

    // 2. Search input with debounce to avoid sending requests on every keystroke
    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            fetchBlinds(true); // isNewFilter = true
        }, 500); // 500ms delay before triggering the search
    });


    // 3. "Load More" button
    loadMoreButton.on('click', function() {
        fetchBlinds(false); // isNewFilter = false
    });

    // Note: The initial set of blinds is loaded via PHP. No initial AJAX call is needed.

    // --- Help Icon Popup ---
    const helpModal = $('#filter-help-modal');
    const helpModalBody = helpModal.find('#kiso24-help-modal-body');
    const helpModalClose = helpModal.find('.kiso24-help-modal-close');

    $('.blind-group-filter').on('click', '.filter-help-icon', function(e) {
        e.preventDefault(); // Prevent the default label action (like opening the select).
        e.stopPropagation(); // Stop the event from bubbling up to the label.

        const targetSelector = $(this).data('target');
        const explanation = $(targetSelector).html();
        helpModalBody.html(explanation);
        helpModal.show();
    });

    // Close the modal when the close icon (×) is clicked
    helpModalClose.on('click', function() {
        helpModal.hide();
        helpModalBody.html(''); // Clear content
    });

    // Close the modal when clicking outside of the modal content
    $(window).on('click', function(event) {
        if ($(event.target).is(helpModal)) {
            helpModal.hide();
            helpModalBody.html(''); // Clear content
        }
    });
});
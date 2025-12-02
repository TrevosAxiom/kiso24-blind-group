// Make selectedBlindDetails globally accessible to persist selection across AJAX reloads.
var selectedBlindDetails = {};

jQuery(document).ready(function($) {
    const grid = $('.blind-group-grid');
 
    // When a blind item is clicked (for selection)
    // Use event delegation to handle clicks on dynamically loaded items
    grid.on('click', '.selectable-blind', function(e) {
        // Prevent the selection logic from running if the click is on the magnifier or its children
        if ($(e.target).closest('.blind-magnifier').length === 0) {
            var $this = $(this);
            
            // If the clicked blind is already selected, do nothing.
            // This prevents accidental deselection. A blind can only be unselected
            // by choosing a different one.
            if ($this.hasClass('blind-selected')) {
                return;
            } else {
                // If it's not selected, select it and unselect others
                $('.selectable-blind').removeClass('blind-selected');
                $this.addClass('blind-selected');

                // Get the details of the selected blind
                var blindName = $this.data('blind-name');
                var priceGroup = $this.data('price_group');
                var transparency = $this.data('transparency');
                var color = $this.data('color');
                var characteristics = $this.data('characteristics');

                // Store the selected blind's details
                selectedBlindDetails = {
                    blind_name: blindName,
                    price_group: priceGroup,
                    transparency: transparency,
                    color: color,
                    characteristics: characteristics
                };

                // Update the hidden fields (used when adding to cart)
                $('#blind_details_name').val(blindName);
                $('#blind_details_price_group').val(priceGroup);
                $('#selected_blind_price_group').val(priceGroup); // Set the dedicated price group field
                $('#blind_details_transparency').val(transparency);
                $('#blind_details_color').val(color);
                $('#blind_details_characteristics').val(characteristics);
            }
        }
    });

    // Modal functionality
    var modal = $('#blind-modal');
    var modalContent = modal.find('.blind-modal-body');

    // Use event delegation for the magnifier as well
    grid.on('click', '.blind-magnifier', function(e) {
        e.stopPropagation(); // Prevent the click from propagating to the blind item
        var parentItem = $(this).closest('.blind-item');

        // Get the details of the blind from the clicked item
        var blindName = parentItem.data('blind-name');
        var blindImage = parentItem.data('blind-image'); // This should be the image URL
        var priceGroup = parentItem.data('price_group');
        var transparency = parentItem.data('transparency');
        var color = parentItem.data('color');
        var characteristicsDisplay = parentItem.data('characteristics-names') || 'N/A';
        
        // Check if the image URL exists and update the modal image
        if (blindImage) {
            $('.modal-blind-img').attr('src', blindImage); // Update the image in the modal
        } else {
            $('.modal-blind-img').attr('src', 'https://via.placeholder.com/150'); // Placeholder
        }

        // Populate the modal with the blind details
        $('.modal-blind-name').text(blindName);
        $('.modal-blind-price-group').text(priceGroup);
        $('.modal-blind-transparency').text(transparency);
        $('.modal-blind-color').text(color);
        $('.modal-blind-characteristics').text(characteristicsDisplay);

        modal.show(); // Show the modal
    });

    // Close the modal when the close icon (×) is clicked
    $('.close').on('click', function() {
        modal.hide(); // Hide the modal
    });

    // Close the modal when clicking outside of the modal content
    $(window).on('click', function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    $('form.cart').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission
        var width = $('#plm_width').val();
        var height = $('#plm_height').val();

        if (Object.keys(selectedBlindDetails).length === 0) {
            alert('Bitte ein Muster auswählen, um mit der Konfiguration fortzufahren');
            return false;
        }

        if (!width || !height) {
            alert('Please enter both width and height.');
            return false;
        }

        // Remove any existing hidden inputs to avoid duplication
        $('input[name^="selected_blind"], input[name^="blind_details"]').remove();
        // Add hidden inputs to the form
        $('<input>').attr({
            type: 'hidden',
            name: 'selected_blind',
            value: selectedBlindDetails.blind_name
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'blind_details[price_group]',
            value: selectedBlindDetails.price_group
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'blind_details[transparency]',
            value: selectedBlindDetails.transparency
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'blind_details[color]',
            value: selectedBlindDetails.color
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'blind_details[characteristics]',
            value: selectedBlindDetails.characteristics
        }).appendTo(this);

        // Add width and height to hidden inputs
        $('<input>').attr({
            type: 'hidden',
            name: 'plm_width',
            value: width
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'plm_height',
            value: height
        }).appendTo(this);

        // Submit the form
        // this.submit();
    });


    // Toggle search bar
    $('#toggle-search').on('click', function() {
        $('#search-bar').slideToggle();
        $(this).find('.dashicons-arrow-down-alt2').toggleClass('dashicons-arrow-up-alt2');
    });

    // Search functionality
    $('#blind-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.blind-item').each(function() {
            var blindName = $(this).data('blind-name').toLowerCase();
            var characteristics = $(this).data('characteristics').toLowerCase();
            if (blindName.includes(searchTerm) || characteristics.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
});

jQuery(document).ready(function($) {
    console.log('Blind group table script loaded');
    var mediaUploader;

    function openMediaUploader(button) {
        var index = button.data('index');
        console.log('Opening media uploader for index:', index);

        // Create a new media uploader instance for each row
        var rowMediaUploader = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false // Disable multiple selections
        });

        // When an image is selected
        rowMediaUploader.on('select', function() {
            var attachment = rowMediaUploader.state().get('selection').first().toJSON();
            var imageUrl = attachment.url;
            var imageId = attachment.id;

            console.log('Selected image:', imageUrl, 'ID:', imageId, 'for index:', index);

            // Update the correct image preview and input field for the row
            $('.blind-image-preview-' + index).attr('src', imageUrl).show();
            $('.image-input-' + index).val(imageId);

            console.log('Updated preview:', $('.blind-image-preview-' + index));
            console.log('Updated input:', $('.image-input-' + index));
        });

        rowMediaUploader.open();
    }

    // Add new row functionality with a unique identifier
    $('#add-row').click(function() {
        var rowTemplate = $('#blind-group-row-template').html();
        var uniqueId = Date.now(); // Generate a unique identifier using timestamp
        console.log('Adding new row with ID:', uniqueId);
        var newRow = $(rowTemplate);

        // Update the classes and attributes for the image-related fields
        newRow.find('.blind-image-preview').addClass('blind-image-preview-' + uniqueId);
        newRow.find('.image-input').addClass('image-input-' + uniqueId);
        newRow.find('.select-image').attr('data-index', uniqueId);
        newRow.find('.remove-image').attr('data-index', uniqueId);

        $('#blind-group-rows').append(newRow);
        console.log('New row added:', newRow);
    });

    // Media uploader event handler
    $(document).on('click', '.select-image', function(e) {
        e.preventDefault();
        var button = $(this);
        console.log('Select image clicked for index:', button.data('index'));
        openMediaUploader(button); // Call the function to handle media selection
    });

    // Remove image functionality
    $(document).on('click', '.remove-image', function(e) {
        e.preventDefault();
        var button = $(this);
        var index = button.data('index'); // Use the unique identifier
        console.log('Removing image for index:', index);

        // Clear the image preview and remove the image ID from the hidden input
        $('.blind-image-preview-' + index).attr('src', '').hide();
        $('.image-input-' + index).val('');

        console.log('Image removed for index:', index);
    });

    // Remove row functionality
    $(document).on('click', '.remove-row', function(e) {
        e.preventDefault();
        console.log('Removing row');
        $(this).closest('tr').remove();
    });

    // Initialize existing rows with unique identifiers
    $('.blind-group-row').each(function(index) {
        var uniqueId = Date.now() + index;
        $(this).find('.blind-image-preview').addClass('blind-image-preview-' + uniqueId);
        $(this).find('.image-input').addClass('image-input-' + uniqueId);
        $(this).find('.select-image').attr('data-index', uniqueId);
        $(this).find('.remove-image').attr('data-index', uniqueId);
        console.log('Initialized existing row with ID:', uniqueId);
    });

    $('#csv-import-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    alert('CSV imported successfully!');
                    processImportedData(response.data);
                } else {
                    alert('Error importing CSV: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred during import.');
            }
        });
    });

    function processImportedData(data) {
        // Clear existing rows
        $('#blind-group-rows').empty();

        // Add new rows from imported data
        data.forEach(function(blind) {
            var uniqueId = Date.now();
            var newRow = $($('#blind-group-row-template').html());

            // Populate the new row with data
            newRow.find('.blind-name').val(blind.blind_name);
            newRow.find('.price-group').val(blind.price_group);
            newRow.find('.transparency').val(blind.transparency);
            newRow.find('.color').val(blind.color);
            newRow.find('.characteristics').val(blind.characteristics.join(', '));

            // Handle image
            if (blind.image) {
                newRow.find('.blind-image-preview').attr('src', blind.image).show();
                newRow.find('.image-input').val(blind.image_id);
            }

            // Update classes and attributes for image-related fields
            newRow.find('.blind-image-preview').addClass('blind-image-preview-' + uniqueId);
            newRow.find('.image-input').addClass('image-input-' + uniqueId);
            newRow.find('.select-image').attr('data-index', uniqueId);
            newRow.find('.remove-image').attr('data-index', uniqueId);

            $('#blind-group-rows').append(newRow);
        });

        console.log('Table updated with imported data');
    }
});


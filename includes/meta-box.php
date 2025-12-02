<?php
require_once plugin_dir_path(__FILE__) . 'import-csv.php';

// Add the Meta Boxes for Allowed Taxonomies and Blind Group
function kiso24_pleated_add_meta_boxes() {
    // Allowed Taxonomies Meta Box
    add_meta_box(
        'kiso24_allowed_taxonomies',
        __( 'Allowed Taxonomies', 'kiso24-pleated' ),
        'kiso24_render_allowed_taxonomies_meta_box',
        'kiso24_pleated',
        'normal',
        'high'
    );

    // Blind Group Meta Box
    add_meta_box(
        'kiso24_pleated_blind_group',
        __( 'Blind Group', 'kiso24-pleated' ),
        'kiso24_pleated_render_blind_group_meta_box',
        'kiso24_pleated',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'kiso24_pleated_add_meta_boxes' );

// Add this function to handle the CSV import
function kiso24_pleated_handle_csv_import($post_id) {
    if (isset($_FILES['csv_import']) && $_FILES['csv_import']['error'] == 0) {
        $result = kiso24_pleated_import_csv_to_blind_group($_FILES['csv_import'], $post_id);
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('CSV imported successfully!', 'kiso24-pleated') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error importing CSV.', 'kiso24-pleated') . '</p></div>';
            });
        }
    }
}

// Render the Blind Group Table in Meta Box
function kiso24_pleated_render_blind_group_meta_box( $post ) {
    wp_nonce_field('kiso24_pleated_blind_group', 'kiso24_pleated_blind_group_nonce');
    $blind_group_data = get_post_meta( $post->ID, '_blind_group_data', true );
    $price_groups = get_terms( array( 'taxonomy' => 'price_group', 'hide_empty' => false ) );
    $transparencies = get_terms( array( 'taxonomy' => 'transparency', 'hide_empty' => false ) );
    $colors = get_terms( array( 'taxonomy' => 'color', 'hide_empty' => false ) );
    $characteristics = get_terms( array( 'taxonomy' => 'characteristics', 'hide_empty' => false ) );

    ?>
    <!-- 
        <div class="csv-import-form" style="margin-bottom: 20px;">
            <h4><?php _e( 'Import Blinds from CSV', 'kiso24-pleated' ); ?></h4>
            <form id="csv-import-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="import_blind_group_csv">
                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
                <?php wp_nonce_field( 'import_blind_group_csv', 'import_blind_group_csv_nonce' ); ?>
                <input type="file" name="csv_file" accept=".csv" required>
                <input type="submit" class="button" value="<?php _e( 'Import CSV', 'kiso24-pleated' ); ?>">
            </form>
        </div>
        // Existing blind group table code
    -->
    <div class="csv-import-section" style="margin-top: 20px;">
        <h3><?php _e('Import from CSV', 'kiso24-pleated'); ?></h3>
        <input type="file" id="csv_import" name="csv_import" accept=".csv" />
        <button type="button" id="import_csv_button" class="button"><?php _e('Import CSV', 'kiso24-pleated'); ?></button>
        <p class="description"><?php _e('Upload a CSV file to import blind group data.', 'kiso24-pleated'); ?></p>
    </div>

    
    <div id="blind-group-table">
        <table>
            <thead>
                <tr>
                    <th><?php _e( 'Blind Name', 'kiso24-pleated' ); ?></th>
                    <th><?php _e( 'Price Group', 'kiso24-pleated' ); ?></th>
                    <th><?php _e( 'Transparency', 'kiso24-pleated' ); ?></th>
                    <th><?php _e( 'Color', 'kiso24-pleated' ); ?></th>
                    <th><?php _e( 'Characteristics', 'kiso24-pleated' ); ?></th>
                    <th><?php _e( 'Image', 'kiso24-pleated' ); ?></th>
                </tr>
            </thead>
            <tbody id="blind-group-rows">
                <?php if ( ! empty( $blind_group_data ) && is_array( $blind_group_data ) ) : ?>
                    <?php foreach ( $blind_group_data as $index => $group ) :
                        $uniqueId = 'existing-' . $index; // Unique identifier for each existing row
                    ?>
                        <tr>
                            <td><input type="text" name="blind_name[]" value="<?php echo esc_attr( $group['blind_name'] ); ?>" /></td>
                            <td>
                                <select name="price_group[]">
                                    <?php foreach ( $price_groups as $group_term ) : ?>
                                        <option value="<?php echo esc_attr( $group_term->slug ); ?>" <?php selected( $group['price_group'], $group_term->slug ); ?>>
                                            <?php echo esc_html( $group_term->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="transparency[]">
                                    <?php foreach ( $transparencies as $transparency_term ) : ?>
                                        <option value="<?php echo esc_attr( $transparency_term->slug ); ?>" <?php selected( $group['transparency'], $transparency_term->slug ); ?>>
                                            <?php echo esc_html( $transparency_term->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="color[]">
                                    <?php foreach ( $colors as $color_term ) : ?>
                                        <option value="<?php echo esc_attr( $color_term->slug ); ?>" <?php selected( $group['color'], $color_term->slug ); ?>>
                                            <?php echo esc_html( $color_term->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="characteristics[]" class="characteristics-input" value="<?php 
                                    echo isset($group['characteristics']) && is_array($group['characteristics']) 
                                        ? esc_attr(implode(', ', $group['characteristics'])) 
                                        : ''; 
                                ?>" placeholder="<?php _e('Selected characteristics', 'kiso24-pleated'); ?>" readonly />
                                <div class="characteristics-accordion">
                                    <button type="button" class="accordion-toggle"><?php _e('Select Characteristics', 'kiso24-pleated'); ?></button>
                                    <div class="characteristics-buttons" style="display: none;">
                                        <?php foreach ( $characteristics as $characteristic ) : // Use slug for data attribute ?>
                                            <button type="button" class="characteristic-button" data-characteristic="<?php echo esc_attr( $characteristic->slug ); ?>">
                                                <?php echo esc_html( $characteristic->name ); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <img src="<?php echo esc_url( $group['image'] ); ?>" 
                                    alt="Blind Image" 
                                    class="blind-image-preview-<?php echo $uniqueId; ?>" 
                                    style="max-width: 100px; height: auto; <?php echo empty( $group['image'] ) ? 'display: none;' : ''; ?>" />
                                <input type="hidden" name="image[]" class="image-input-<?php echo $uniqueId; ?>" value="<?php echo esc_attr( $group['image'] ); ?>" />
                                <button type="button" class="button add-image-url" data-index="<?php echo $uniqueId; ?>"><?php _e( 'Add Image URL', 'kiso24-pleated' ); ?></button>
                                <button type="button" class="button remove-row" data-index="<?php echo $uniqueId; ?>"><?php _e( 'Remove Row', 'kiso24-pleated' ); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Buttons to add, remove, and clear rows -->
        <button type="button" id="add-row"><?php _e( 'Add New', 'kiso24-pleated' ); ?></button>
        <button type="button" id="remove-selected"><?php _e( 'Remove Selected', 'kiso24-pleated' ); ?></button>
        <button type="button" id="clear-all"><?php _e( 'Clear All', 'kiso24-pleated' ); ?></button>
        <button type="button" id="fix-characteristics-slugs" class="button" title="<?php _e('Corrects values like "Super Clean" to "super-clean" in all rows.', 'kiso24-pleated'); ?>"><?php _e('Fix Characteristics Slugs', 'kiso24-pleated'); ?></button>
        <button type="button" id="save-blinds" class="button button-primary"><?php _e('Save Blinds', 'kiso24-pleated'); ?></button>
        <span id="save-blinds-message" style="display: none; margin-left: 10px;"></span>

    </div>
    <script type="text/template" id="blind-group-row-template">
        <tr>
            <td><input type="text" name="blind_name[]" /></td>
            <td>
                <select name="price_group[]">
                    <?php foreach ( $price_groups as $group ) : ?>
                        <option value="<?php echo esc_attr( $group->slug ); ?>"><?php echo esc_html( $group->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="transparency[]">
                    <?php foreach ( $transparencies as $transparency ) : ?>
                        <option value="<?php echo esc_attr( $transparency->slug ); ?>"><?php echo esc_html( $transparency->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="color[]">
                    <?php foreach ( $colors as $color ) : ?>
                        <option value="<?php echo esc_attr( $color->slug ); ?>"><?php echo esc_html( $color->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" name="characteristics[]" class="characteristics-input" placeholder="<?php _e('Selected characteristics', 'kiso24-pleated'); ?>" readonly />
                <div class="characteristics-accordion">
                    <button type="button" class="accordion-toggle"><?php _e('Select Characteristics', 'kiso24-pleated'); ?></button>
                    <div class="characteristics-buttons" style="display: none;">
                        <?php foreach ( $characteristics as $characteristic ) : // Use slug for data attribute ?>
                            <button type="button" class="characteristic-button" data-characteristic="<?php echo esc_attr( $characteristic->slug ); ?>">
                                <?php echo esc_html( $characteristic->name ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </td>
            <td>
                <img src="" alt="Blind Image" class="blind-image-preview" style="max-width: 100px; height: auto; display: none;" />
                <input type="hidden" name="image[]" class="image-input" value="" />
                <button type="button" class="button add-image-url"><?php _e( 'Add Image URL', 'kiso24-pleated' ); ?></button>
                <button type="button" class="button remove-row"><?php _e( 'Remove Row', 'kiso24-pleated' ); ?></button>
            </td>
        </tr>
    </script>
    <?php wp_nonce_field( 'kiso24_pleated_save_blind_group', 'kiso24_pleated_nonce' ); ?>
    <style>
        #import-log pre {
        background-color: #f4f4f4;
        border: 1px solid #ddd;
        border-left: 3px solid #f36d33;
        color: #666;
        page-break-inside: avoid;
        font-family: monospace;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 1.6em;
        max-width: 100%;
        overflow: auto;
        padding: 1em 1.5em;
        display: block;
        word-wrap: break-word;
    }
    .characteristics-accordion {
        margin-top: 5px;
    }
    
    .accordion-toggle {
        background-color: #f0f0f0;
        color: #444;
        cursor: pointer;
        padding: 10px;
        width: 100%;
        text-align: left;
        border: none;
        outline: none;
        transition: 0.4s;
    }
    
    .accordion-toggle:hover {
        background-color: #ddd;
    }
    
    .accordion-toggle:after {
        content: '\02795'; /* Unicode character for "plus" sign (+) */
        font-size: 13px;
        color: #777;
        float: right;
        margin-left: 5px;
    }
    
    .accordion-toggle.active:after {
        content: "\2796"; /* Unicode character for "minus" sign (-) */
    }
    
    .characteristics-buttons {
        padding: 10px;
        background-color: white;
        border: 1px solid #ddd;
        display: none;
    }
    
    .characteristic-button {
        background-color: #f0f0f0;
        border: 1px solid #ccc;
        border-radius: 15px;
        padding: 5px 10px;
        margin: 2px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .characteristic-button.selected {
        background-color: #007cba;
        color: white;
    }
    
    .characteristics-input {
        width: 100%;
        margin-bottom: 5px;
    }
    </style>

    <div id="import-log"></div>
    <script>
        // Initialize buttons state on page load and when adding new rows
        function initCharacteristicButtons() {
            jQuery('.characteristics-input').each(function() {
                var $input = jQuery(this);
                // Get the saved characteristics, split them, and trim any whitespace.
                var savedCharacteristics = $input.val().split(',').map(item => item.trim());

                $input.closest('td').find('.characteristic-button').each(function() {
                    var $button = jQuery(this);
                    var buttonSlug = $button.data('characteristic'); // e.g., "super-clean"

                    // Check if any of the saved characteristics match the button's slug.
                    // This check is case-insensitive and handles both "Super Clean" and "super-clean".
                    var isSelected = savedCharacteristics.some(savedChar => 
                        savedChar.toLowerCase().replace(/ /g, '-') === buttonSlug.toLowerCase()
                    );

                    if (isSelected) {
                        $button.addClass('selected');
                    } else {
                        $button.removeClass('selected');
                    }
                });
            });
        }

        jQuery(document).ready(function($) {
            initCharacteristicButtons(); // Call on page load

            $('#import_csv_button').on('click', function() {
                var fileInput = document.getElementById('csv_import');
                var file = fileInput.files[0];
            
/*
        function initCharacteristicButtons() {
            jQuery('.characteristics-input').each(function() {
                var $input = jQuery(this);
                var selectedCharacteristics = $input.val().split(', ');
                $input.closest('td').find('.characteristic-button').each(function() {
                    var $button = jQuery(this);
                    if (selectedCharacteristics.includes($button.data('characteristic'))) { // Check against slug
                        $button.addClass('selected');
                    } else {
                        $button.removeClass('selected');
                    }
                });
            });
        }
*/

                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var csv = e.target.result;
                        var lines = csv.split("\n");
                        var importLog = [];
            
                        // Remove all existing rows except the first one
                        $('#blind-group-rows tr:not(:first)').remove();
            
                        // Skip the header row
                        for (var i = 1; i < lines.length; i++) {
                            if (lines[i].trim() !== "") {
                                var columns = lines[i].split(",");
            
                                // Add a new row
                                $('#add-row').click();
            
                                // Get the last added row
                                var $lastRow = $('#blind-group-rows tr:last');
            
                                // Populate the fields
                                $lastRow.find('input[name="blind_name[]"]').val(columns[0].trim());
            
                                // Helper function for case-insensitive option selection
                                function selectOptionCaseInsensitive($select, value) {
                                    value = value.toLowerCase().trim();
                                    $select.find('option').each(function() {
                                        if ($(this).text().toLowerCase().trim() === value) {
                                            $select.val($(this).val());
                                            return false;
                                        }
                                    });
                                }
            
                                // Case-insensitive selection for price group, transparency, and color
                                selectOptionCaseInsensitive($lastRow.find('select[name="price_group[]"]'), columns[1]);
                                selectOptionCaseInsensitive($lastRow.find('select[name="transparency[]"]'), columns[2]);
                                selectOptionCaseInsensitive($lastRow.find('select[name="color[]"]'), columns[3]);
            
                                // Handle characteristics (replace semicolons with commas)
                                var characteristics = columns[4].trim().replace(/;/g, ',');
                                $lastRow.find('input[name="characteristics[]"]').val(characteristics);
            
                                // Handle image URL (if provided)
                                if (columns[5] && columns[5].trim() !== "") {
                                    var $imageInput = $lastRow.find('input[name="image[]"]');
                                    var $imagePreview = $lastRow.find('img.blind-image-preview');
                                    $imageInput.val(columns[5].trim());
                                    $imagePreview.attr('src', columns[5].trim()).show();
                                }
            
                                // Log the imported data
                                importLog.push({
                                    'Blind Name': columns[0].trim(),
                                    'Price Group': columns[1].trim(),
                                    'Transparency': columns[2].trim(),
                                    'Color': columns[3].trim(),
                                    'Characteristics': characteristics,
                                    'Image URL': columns[5] ? columns[5].trim() : ''
                                });
                            }
                        }
            
                        // Display import log
                        console.log('Import Log:', importLog);
                        var logOutput = '<h3>Import Log</h3><pre>' + JSON.stringify(importLog, null, 2) + '</pre>';
                        $('#import-log').html(logOutput);
            
                        alert('CSV import completed! Check the Import Log below for details.');
                    };
                    reader.readAsText(file);
                } else {
                    alert('Please select a CSV file to import.');
                }
            });

            // Add handler for the new "Fix Characteristics Slugs" button
            $('#fix-characteristics-slugs').on('click', function() {
                if (!confirm('<?php _e('This will correct the characteristic values in all rows to the proper slug format (e.g., "Super Clean" becomes "super-clean"). The changes will be visible in the editor but not saved until you click "Update". Do you want to continue?', 'kiso24-pleated'); ?>')) {
                    return;
                }

                let rowsFixed = 0;
                $('#blind-group-rows tr').each(function() {
                    var $row = $(this);
                    var $input = $row.find('.characteristics-input');
                    var currentVal = $input.val();

                    if (currentVal) {
                        var characteristics = currentVal.split(',').map(function(item) {
                            return item.trim().toLowerCase().replace(/\s+/g, '-');
                        });
                        
                        var newVal = characteristics.join(', ');
                        if (newVal !== currentVal) {
                            $input.val(newVal);
                            rowsFixed++;
                        }
                    }
                });

                // Re-initialize the button states to reflect the corrected values
                initCharacteristicButtons();

                alert(rowsFixed + ' <?php _e('row(s) had characteristics corrected. Please click the "Update" button to save these changes.', 'kiso24-pleated'); ?>');
            });

            // Handle form submission
            $('form#post').on('submit', function(e) {
                // Prevent the form from submitting normally
                e.preventDefault();

                // Serialize all characteristics for each row
                $('#blind-group-rows tr:not(:first)').each(function(index) {
                    var $characteristicsSelect = $(this).find('.characteristics-select');
                    var selectedCharacteristics = $characteristicsSelect.val() || [];

                    // Create hidden inputs for each selected characteristic
                    selectedCharacteristics.forEach(function(char, charIndex) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'characteristics[' + index + '][]',
                            value: char
                        }).appendTo('form#post');
                    });

                    // Remove the original select to avoid duplication
                    $characteristicsSelect.prop('disabled', true);
                });

                // Submit the form
                this.submit();
            });

            // Clear All button functionality
            $('#clear-all').on('click', function() {
                if (confirm('Are you sure you want to remove all rows? This action cannot be undone.')) {
                    $('#blind-group-rows tr:not(:first)').remove();
                    //updateRowNumbers();
                }
            });

            // Remove Row functionality
            $(document).on('click', '.remove-row', function() {
                if (confirm('Are you sure you want to remove this row?')) {
                    $(this).closest('tr').remove();
                    // Optionally, update row numbers if you're using them
                    // updateRowNumbers();
                }
            });

            // Add Image URL functionality
            $(document).on('click', '.add-image-url', function(e) {
                e.preventDefault();
                var button = $(this);
                var row = button.closest('tr');
                var index = row.index() - 1; // Subtract 1 to account for the header row
                var imageUrl = prompt("<?php _e('Enter the URL of the image:', 'kiso24-pleated'); ?>");
                
                if (imageUrl) {
                    // Update the image preview
                    row.find('.blind-image-preview').attr('src', imageUrl).show();
                    
                    // Update the hidden input field
                    row.find('input[name="image[]"]').val(imageUrl);
                    
                    console.log('Image URL added:', imageUrl, 'for index:', index);
                }
            });
        
            // Update existing rows to use the new Add Image URL functionality
            $('.select-image').each(function() {
                $(this).text("<?php _e('Add Image URL', 'kiso24-pleated'); ?>").removeClass('select-image').addClass('add-image-url');
            });

            // Handle accordion toggle
            $(document).on('click', '.accordion-toggle', function() {
                $(this).toggleClass('active');
                var content = $(this).next('.characteristics-buttons');
                if (content.is(':visible')) {
                    content.slideUp();
                } else {
                    content.slideDown();
                }
            });
            
            // Handle characteristic button clicks
            $(document).on('click', '.characteristic-button', function() {
                var $button = $(this);
                var $input = $button.closest('td').find('.characteristics-input');
                var characteristicSlug = $button.data('characteristic'); // Get slug from data attribute
                var currentCharacteristics = $input.val() ? $input.val().split(', ') : [];
                
                var index = currentCharacteristics.findIndex(item => item.toLowerCase() === characteristicSlug.toLowerCase());

                if (index > -1) {
                    // Remove characteristic if it already exists
                    currentCharacteristics.splice(index, 1);
                    $button.removeClass('selected');
                } else {
                    // Add characteristic if it doesn't exist
                    currentCharacteristics.push(characteristicSlug);
                    $button.addClass('selected');
                }
                $input.val(currentCharacteristics.join(', '));
            });

            // Call initCharacteristicButtons after adding a new row
            $('#add-row').on('click', function() {
                // After adding the new row, update row indices
                updateRowIndices();
                // Re-initialize buttons for the new row
                setTimeout(initCharacteristicButtons, 50); // Use a small timeout to ensure the row is in the DOM
            });

            // Function to update row indices
            function updateRowIndices() {
                $('#blind-group-rows tr:not(:first)').each(function(index) {
                    $(this).attr('data-index', index);
                });
            }

            // Function to update row numbers
            function updateRowNumbers() {
                $('#blind-group-rows tr').each(function(index) {
                    if (index > 0) {  // Skip the header row
                        $(this).find('td:first').text(index);
                    }
                });
            }

            $('#save-blinds').on('click', function() {
                var blindData = [];
                $('#blind-group-rows tr').each(function() {
                    var row = $(this);
                    blindData.push({
                        blind_name: row.find('input[name="blind_name[]"]').val(),
                        price_group: row.find('select[name="price_group[]"]').val(),
                        transparency: row.find('select[name="transparency[]"]').val(),
                        color: row.find('select[name="color[]"]').val(),
                        characteristics: row.find('input[name="characteristics[]"]').val().split(','),
                        image: row.find('input[name="image[]"]').val()
                    });
                });
            
                console.log('Sending data:', {
                    action: 'save_blind_group_data',
                    post_id: $('#post_ID').val(),
                    blind_data: JSON.stringify(blindData),
                    nonce: $('#kiso24_pleated_blind_group_nonce').val()
                });
            
                $.ajax({
                    url: kiso24_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'save_blind_group_data',
                        post_id: $('#post_ID').val(),
                        blind_data: JSON.stringify(blindData),
                        nonce: $('#kiso24_pleated_blind_group_nonce').val()
                    },
                    success: function(response) {
                        console.log('Full response:', response);
                        if(response.success) {
                            $('#save-blinds-message').text('Blinds saved successfully!').css('color', 'green').show().fadeOut(3000);
                        } else {
                            $('#save-blinds-message').text('Error saving blinds: ' + response.data).css('color', 'red').show();
                            console.error('Server response:', response);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('#save-blinds-message').text('An error occurred while saving.').css('color', 'red').show();
                        console.error('AJAX error:', textStatus, errorThrown);
                        console.log('Response Text:', jqXHR.responseText);
                    }
                });
            });
        });
    </script>
<?php
}

function kiso24_save_blind_group_data() {
    // Check nonce for security
    if (!check_ajax_referer('kiso24_pleated_blind_group', 'nonce', false)) {
        wp_send_json_error('Nonce verification failed.');
        return;
    }

    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('You do not have permission to edit this post.');
        return;
    }

    if (!isset($_POST['post_id']) || !isset($_POST['blind_data'])) {
        wp_send_json_error('Missing required data.');
        return;
    }

    $post_id = intval($_POST['post_id']);
    $blind_data = json_decode(stripslashes($_POST['blind_data']), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON data: ' . json_last_error_msg());
        return;
    }

    // Sanitize and validate the data
    $sanitized_data = array();
    foreach ($blind_data as $blind) {
        $sanitized_data[] = array(
            'blind_name' => sanitize_text_field($blind['blind_name']),
            'price_group' => sanitize_text_field($blind['price_group']),
            'transparency' => sanitize_text_field($blind['transparency']),
            'color' => sanitize_text_field($blind['color']),
            'characteristics' => array_map('sanitize_text_field', $blind['characteristics']),
            'image' => esc_url_raw($blind['image']) // Use esc_url_raw for image URL
        );
    }

    // Update the post meta
    $result = update_post_meta($post_id, '_blind_group_data', $sanitized_data);

    if ($result !== false) {
        wp_send_json_success('Blind group data saved successfully.');
    } else {
        $error_message = 'Failed to save blind group data. ';
        if ($result === false) {
            $error_message .= 'update_post_meta() returned false. ';
        }
        $error_message .= 'Post ID: ' . $post_id . '. ';
        $error_message .= 'Data: ' . print_r($sanitized_data, true);
        wp_send_json_error($error_message);
    }
}
add_action('wp_ajax_save_blind_group_data', 'kiso24_save_blind_group_data');

// Save the meta box data
function kiso24_pleated_save_blind_group($post_id) {
    // Check if our nonce is set.
    if (!isset($_POST['kiso24_pleated_nonce'])) {
        return $post_id;
    }

    $nonce = $_POST['kiso24_pleated_nonce'];

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($nonce, 'kiso24_pleated_save_blind_group')) {
        return $post_id;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // Check the user's permissions.
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
    }
    
    $blind_group_data = array();

    if (isset($_POST['blind_name']) && is_array($_POST['blind_name'])) {
        foreach ($_POST['blind_name'] as $index => $name) {
            $blind_group_data[] = array(
                'blind_name' => sanitize_text_field($name),
                'price_group' => isset($_POST['price_group'][$index]) ? sanitize_text_field($_POST['price_group'][$index]) : '',
                'transparency' => isset($_POST['transparency'][$index]) ? sanitize_text_field($_POST['transparency'][$index]) : '',
                'color' => isset($_POST['color'][$index]) ? sanitize_text_field($_POST['color'][$index]) : '',
                'characteristics' => isset($_POST['characteristics'][$index]) && !empty($_POST['characteristics'][$index])
                                        ? array_map('sanitize_key', explode(', ', sanitize_text_field($_POST['characteristics'][$index])))
                                        : array(),
                'image' => isset($_POST['image'][$index]) ? esc_url_raw($_POST['image'][$index]) : '',
            );
        }
    }

    update_post_meta($post_id, '_blind_group_data', $blind_group_data);
}

add_action('save_post', 'kiso24_pleated_save_blind_group');
add_action('publish_post', 'kiso24_pleated_save_blind_group');

// Render the Allowed Taxonomies Meta Box
function kiso24_render_allowed_taxonomies_meta_box( $post ) {
    $selected_taxonomies = get_post_meta( $post->ID, '_allowed_taxonomies', true );
    $selected_taxonomies = is_array($selected_taxonomies) ? $selected_taxonomies : [];

    $taxonomy_explanations = get_post_meta($post->ID, '_taxonomy_explanations', true);
    $taxonomy_explanations = is_array($taxonomy_explanations) ? $taxonomy_explanations : [];
    $default_explanation = get_post_meta($post->ID, '_default_taxonomy_explanation', true);

    // Get all taxonomies for the post type, except "blind_category"
    $taxonomies = get_object_taxonomies( 'kiso24_pleated', 'objects' );
    unset( $taxonomies['blind_category'] ); // Exclude "Blind Categories"

    ?>
    <div id="allowed-taxonomy-settings">
        <h4><?php _e('Enable Filters', 'kiso24-pleated'); ?></h4>
        <?php foreach ( $taxonomies as $taxonomy ) : ?>
            <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">
                <label style="font-weight: bold;">
                    <input type="checkbox" name="allowed_taxonomies[]" value="<?php echo esc_attr( $taxonomy->name ); ?>" 
                        <?php checked( in_array( $taxonomy->name, $selected_taxonomies ) ); ?>>
                    <?php echo esc_html( $taxonomy->label ); ?>
                </label>
                <p><label for="taxonomy_explanation_<?php echo esc_attr($taxonomy->name); ?>"><?php _e('Explanation for this filter (optional):', 'kiso24-pleated'); ?></label></p>
                <?php
                // Settings for a minimal WP editor
                $editor_settings = array(
                    'textarea_name' => 'taxonomy_explanations[' . esc_attr($taxonomy->name) . ']',
                    'textarea_rows' => 5,
                    'teeny'         => true,
                    'media_buttons' => false,
                    'quicktags'     => false,
                );
                $editor_content = $taxonomy_explanations[$taxonomy->name] ?? '';
                wp_editor(wp_kses_post($editor_content), 'taxonomy_explanation_' . esc_attr($taxonomy->name), $editor_settings);
                ?>
            </div>
        <?php endforeach; ?>
        <hr>
        <h4><?php _e('Default Filter Explanation', 'kiso24-pleated'); ?></h4>
        <p><?php _e('This text will be shown for any filter that does not have a specific explanation above.', 'kiso24-pleated'); ?></p>
        <?php
        $default_editor_settings = array('textarea_name' => 'default_taxonomy_explanation', 'textarea_rows' => 5, 'teeny' => true, 'media_buttons' => false, 'quicktags' => false);
        wp_editor(wp_kses_post($default_explanation ?? ''), 'default_taxonomy_explanation_editor', $default_editor_settings);
        ?>
    </div>
    <?php
}

// Save the Allowed Taxonomies data
function kiso24_save_allowed_taxonomies( $post_id ) {
    // Ensure this is the correct post type before saving
    if (get_post_type($post_id) !== 'kiso24_pleated') {
        return;
    }

    if (isset($_POST['allowed_taxonomies']) && is_array($_POST['allowed_taxonomies'])) {
        update_post_meta($post_id, '_allowed_taxonomies', array_map('sanitize_text_field', $_POST['allowed_taxonomies']));
    } else {
        delete_post_meta($post_id, '_allowed_taxonomies');
    }

    if (isset($_POST['taxonomy_explanations']) && is_array($_POST['taxonomy_explanations'])) {
        $sanitized_explanations = array_map('wp_kses_post', $_POST['taxonomy_explanations']);
        update_post_meta($post_id, '_taxonomy_explanations', $sanitized_explanations);
    } else {
        delete_post_meta($post_id, '_taxonomy_explanations');
    }

    if (isset($_POST['default_taxonomy_explanation'])) {
        update_post_meta($post_id, '_default_taxonomy_explanation', wp_kses_post($_POST['default_taxonomy_explanation']));
    }
}
add_action( 'save_post', 'kiso24_save_allowed_taxonomies' );
add_action('publish_post', 'kiso24_save_allowed_taxonomies');

// // Handle image upload and return attachment ID
// function kiso24_pleated_handle_image_upload( $image, $index, $post_id ) {
//     require_once( ABSPATH . 'wp-admin/includes/file.php' );
//     require_once( ABSPATH . 'wp-admin/includes/image.php' );
//     require_once( ABSPATH . 'wp-admin/includes/media.php' );

//     $uploaded_file = array(
//         'name'     => $image['name'][ $index ],
//         'type'     => $image['type'][ $index ],
//         'tmp_name' => $image['tmp_name'][ $index ],
//         'error'    => $image['error'][ $index ],
//         'size'     => $image['size'][ $index ]
//     );

//     $upload_overrides = array( 'test_form' => false );
//     $movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

//     if ( $movefile && ! isset( $movefile['error'] ) ) {
//         $filename = $movefile['file'];
//         $filetype = wp_check_filetype( $filename );
//         $attachment = array(
//             'guid'           => $movefile['url'],
//             'post_mime_type' => $filetype['type'],
//             'post_title'     => sanitize_file_name( $filename ),
//             'post_content'   => '',
//             'post_status'    => 'inherit'
//         );

//         $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
//         $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
//         wp_update_attachment_metadata( $attach_id, $attach_data );

//         return $attach_id;
//     } else {
//         return null;
//     }
// }

// Enqueue the script for handling image uploads
function kiso24_enqueue_admin_scripts() {
    $plugin_dir_path = dirname(plugin_dir_path(__FILE__));
    $plugin_dir_url = dirname(plugin_dir_url(__FILE__));

    wp_enqueue_media();
    wp_enqueue_script( 'kiso24-pleated-admin-js', $plugin_dir_url . '/assets/js/admin.js', array( 'jquery' ), filemtime($plugin_dir_path . '/assets/js/admin.js'), true );
}
add_action( 'admin_enqueue_scripts', 'kiso24_enqueue_admin_scripts' );

function kiso24_enqueue_admin_script($hook) {
    global $post;

    if ( in_array($hook, ['post-new.php', 'post.php']) && $post ) {
        if ('kiso24_pleated' === $post->post_type) {
            $script_path = dirname(plugin_dir_path(__FILE__)) . '/assets/js/admin-script.js';
            $script_url = dirname(plugin_dir_url(__FILE__)) . '/assets/js/admin-script.js';
            wp_enqueue_script('kiso24-admin-script', $script_url, array('jquery'), filemtime($script_path), true);
            wp_localize_script('kiso24-admin-script', 'kiso24_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php')
            ));
        }
    }
}
add_action('admin_enqueue_scripts', 'kiso24_enqueue_admin_script');
?>

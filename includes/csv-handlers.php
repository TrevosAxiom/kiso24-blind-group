<?php
// Handle CSV export
function kiso24_pleated_export_blind_group_csv() {
    if ( isset( $_GET['post_id'] ) ) {
        $post_id = intval( $_GET['post_id'] );

        // Fetch the Blind Group data
        $blind_group_data = get_post_meta( $post_id, '_blind_group_data', true );

        if ( ! empty( $blind_group_data ) ) {
            // Set CSV headers
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=blind_group_' . $post_id . '.csv' );

            // Open output stream
            $output = fopen( 'php://output', 'w' );

            // Write column headers
            fputcsv( $output, array( 'Blind Name', 'Price Group', 'Transparency', 'Color', 'Characteristics', 'Image URL' ) );

            // Write each row of blind group data
            foreach ( $blind_group_data as $blind ) {
                fputcsv( $output, array(
                    $blind['blind_name'],
                    $blind['price_group'],
                    $blind['transparency'],
                    $blind['color'],
                    implode( ',', $blind['characteristics'] ),  // Characteristics as comma-separated values
                    $blind['image'] ? wp_get_attachment_url( $blind['image'] ) : '',  // Get the image URL or leave it empty
                ));
            }

            fclose( $output );
            exit;
        }
    }
}
add_action( 'admin_post_export_blind_group_csv', 'kiso24_pleated_export_blind_group_csv' );

function kiso24_pleated_handle_csv_import() {
    if (!isset($_POST['import_blind_group_csv_nonce']) || !wp_verify_nonce($_POST['import_blind_group_csv_nonce'], 'import_blind_group_csv')) {
        wp_die('Invalid nonce');
    }

    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to perform this action');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_die('Invalid post ID');
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        wp_die('CSV file upload failed');
    }

    $csv_file = $_FILES['csv_file']['tmp_name'];

    // Start the batch import process
    $batch_size = 10; // Adjust this value based on your needs
    $total_rows = kiso24_pleated_count_csv_rows($csv_file) - 1; // Subtract 1 for header row
    $current_row = 0;

    // Store the file path and initial import data in a transient
    set_transient('kiso24_csv_import_' . $post_id, [
        'file_path' => $csv_file,
        'total_rows' => $total_rows,
        'current_row' => $current_row,
        'batch_size' => $batch_size
    ], HOUR_IN_SECONDS);

    // Redirect to the batch processing page
    wp_redirect(admin_url("admin.php?page=kiso24-csv-import-progress&post_id={$post_id}"));
    exit;
}

add_action( 'admin_post_import_blind_group_csv', 'kiso24_pleated_handle_csv_import' );

function kiso24_pleated_parse_csv( $file ) {
    $blind_group_data = array();
    if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {
        // Skip the header row
        fgetcsv( $handle, 1000, "," );

        while ( ( $data = fgetcsv( $handle, 1000, "," ) ) !== FALSE ) {
            $blind_group_data[] = array(
                'blind_name' => sanitize_text_field( $data[0] ),
                'price_group' => sanitize_text_field( $data[1] ),
                'transparency' => sanitize_text_field( $data[2] ),
                'color' => sanitize_text_field( $data[3] ),
                'characteristics' => array_map( 'trim', explode( ',', sanitize_text_field( $data[4] ) ) ),
                'image' => '' // You may need to handle image imports separately
            );
        }
        fclose( $handle );
    }
    return $blind_group_data;
}

// Handle CSV import
function kiso24_pleated_import_blind_group_csv() {
    if ( isset( $_POST['post_id'] ) && ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
        $post_id = intval( $_POST['post_id'] );

        // Check if the file was uploaded without errors
        if ( $_FILES['csv_file']['error'] === UPLOAD_ERR_OK ) {
            $csv_file = fopen( $_FILES['csv_file']['tmp_name'], 'r' );

            if ( $csv_file !== false ) {
                $blind_group_data = array();

                // Skip the header row
                fgetcsv( $csv_file );

                // Read each row of the CSV
                while ( ( $row = fgetcsv( $csv_file ) ) !== false ) {
                    // Ensure the row contains at least 6 columns
                    if ( count( $row ) < 6 ) {
                        continue;
                    }

                    // Process each row and add to blind_group_data array
                    $blind_group_data[] = array(
                        'blind_name'     => sanitize_text_field( $row[0] ),
                        'price_group'    => sanitize_text_field( $row[1] ),
                        'transparency'   => sanitize_text_field( $row[2] ),
                        'color'          => sanitize_text_field( $row[3] ),
                        'characteristics'=> explode( ',', sanitize_text_field( $row[4] ) ),  // Split characteristics by commas
                        'image'          => null  // Handle images separately later
                    );
                }

                // Save the imported data to post meta
                update_post_meta( $post_id, '_blind_group_data', $blind_group_data );

                fclose( $csv_file );

                // Redirect to the edit screen after import
                wp_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit&import_success=1' ) );
                exit;
            } else {
                // Log error if CSV file couldn't be opened
                error_log( 'Could not open CSV file for reading.' );
            }
        } else {
            // Log error if file upload failed
            error_log( 'Error uploading file: ' . $_FILES['csv_file']['error'] );
        }
    } else {
        // Log error if no file was uploaded
        error_log( 'No CSV file uploaded.' );
    }

    // If we reach here, redirect back to the edit screen with error flag
    wp_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit&import_error=1' ) );
    exit;
}
add_action( 'admin_post_import_blind_group_csv', 'kiso24_pleated_import_blind_group_csv' );

// Handle importing images from a URL and returning the attachment ID
function kiso24_pleated_handle_image_import_from_url( $image_url, $post_id ) {
    // Check if the URL is a valid image
    if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
        return null;
    }

    // Get the file extension
    $file_info = pathinfo( $image_url );
    $extension = isset( $file_info['extension'] ) ? $file_info['extension'] : '';
    if ( ! in_array( strtolower( $extension ), array( 'jpg', 'jpeg', 'png', 'gif' ) ) ) {
        return null;
    }

    // Download the image
    $temp_file = download_url( $image_url );
    if ( is_wp_error( $temp_file ) ) {
        return null;
    }

    // Move the file to the WordPress uploads directory
    $file = array(
        'name'     => basename( $image_url ),
        'type'     => mime_content_type( $temp_file ),
        'tmp_name' => $temp_file,
        'error'    => 0,
        'size'     => filesize( $temp_file ),
    );

    // Let WordPress handle the upload
    $upload = wp_handle_sideload( $file, array( 'test_form' => false ) );
    if ( isset( $upload['error'] ) ) {
        return null;
    }

    // Get the file info and insert it into the media library
    $attachment = array(
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name( $file_info['basename'] ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    // Insert attachment and generate attachment metadata
    $attachment_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
    wp_update_attachment_metadata( $attachment_id, $attach_data );

    return $attachment_id;  // Return the attachment ID
}
function kiso24_pleated_process_csv_batch() {
    if (!isset($_GET['post_id'])) {
        wp_die('Invalid request');
    }

    $post_id = intval($_GET['post_id']);
    $import_data = get_transient('kiso24_csv_import_' . $post_id);

    if (!$import_data) {
        wp_die('Import session expired or not found');
    }

    $file_path = $import_data['file_path'];
    $total_rows = $import_data['total_rows'];
    $current_row = $import_data['current_row'];
    $batch_size = $import_data['batch_size'];

    $blind_group_data = kiso24_pleated_parse_csv_batch($file_path, $current_row, $batch_size);

    if (!empty($blind_group_data)) {
        $existing_data = get_post_meta($post_id, '_blind_group_data', true);
        $updated_data = is_array($existing_data) ? array_merge($existing_data, $blind_group_data) : $blind_group_data;
        update_post_meta($post_id, '_blind_group_data', $updated_data);

        $current_row += count($blind_group_data);
        
        if ($current_row >= $total_rows) {
            // Import complete
            delete_transient('kiso24_csv_import_' . $post_id);
            wp_redirect(admin_url("post.php?post={$post_id}&action=edit&message=1"));
            exit;
        } else {
            // Update the transient for the next batch
            $import_data['current_row'] = $current_row;
            set_transient('kiso24_csv_import_' . $post_id, $import_data, HOUR_IN_SECONDS);

            // Redirect to process the next batch
            wp_redirect(admin_url("admin.php?page=kiso24-csv-import-progress&post_id={$post_id}"));
            exit;
        }
    } else {
        wp_die('Error parsing CSV file');
    }
}
function kiso24_pleated_parse_csv_batch($file, $start_row, $batch_size) {
    $blind_group_data = array();
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Skip to the start row
        for ($i = 0; $i < $start_row; $i++) {
            fgetcsv($handle, 1000, ",");
        }

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $count < $batch_size) {
            $blind_group_data[] = array(
                'blind_name' => sanitize_text_field($data[0]),
                'price_group' => sanitize_text_field($data[1]),
                'transparency' => sanitize_text_field($data[2]),
                'color' => sanitize_text_field($data[3]),
                'characteristics' => array_map('trim', explode(',', sanitize_text_field($data[4]))),
                'image' => '' // You may need to handle image imports separately
            );
            $count++;
        }
        fclose($handle);
    }
    return $blind_group_data;
}
function kiso24_pleated_count_csv_rows($file) {
    $row_count = 0;
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (fgetcsv($handle, 1000, ",") !== FALSE) {
            $row_count++;
        }
        fclose($handle);
    }
    return $row_count;
}
function kiso24_pleated_csv_import_progress_page() {
    ?>
    <div class="wrap">
        <h1>CSV Import Progress</h1>
        <p>The import is processing. Please do not close this page.</p>
        <script>
            (function($) {
                $(document).ready(function() {
                    $.get(ajaxurl, {
                        action: 'kiso24_process_csv_batch',
                        post_id: <?php echo intval($_GET['post_id']); ?>
                    });
                });
            })(jQuery);
        </script>
    </div>
    <?php
}

// Register the progress page
add_action('admin_menu', function() {
    add_submenu_page(null, 'CSV Import Progress', 'CSV Import Progress', 'manage_options', 'kiso24-csv-import-progress', 'kiso24_pleated_csv_import_progress_page');
});

// Register the AJAX action for batch processing
add_action('wp_ajax_kiso24_process_csv_batch', 'kiso24_pleated_process_csv_batch');

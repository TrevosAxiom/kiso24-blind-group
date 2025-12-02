<?php
function kiso24_pleated_import_csv_to_blind_group($file, $post_id) {
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $uploadedfile = array(
        'name'     => $file['name'],
        'type'     => $file['type'],
        'tmp_name' => $file['tmp_name'],
        'error'    => $file['error'],
        'size'     => $file['size']
    );

    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $csv_file = $movefile['file'];
        $blind_group_data = array();

        if (($handle = fopen($csv_file, "r")) !== FALSE) {
            // Skip the header row
            fgetcsv($handle, 1000, ",");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $blind_group_data[] = array(
                    'blind_name'     => sanitize_text_field($data[0]),
                    'price_group'    => sanitize_text_field($data[1]),
                    'transparency'   => sanitize_text_field($data[2]),
                    'color'          => sanitize_text_field($data[3]),
                    'characteristics'=> array_map('trim', explode(',', sanitize_text_field($data[4]))),
                    'image'          => esc_url_raw($data[5])
                );
            }
            fclose($handle);
        }

        update_post_meta($post_id, '_blind_group_data', $blind_group_data);
        return true;
    } else {
        return false;
    }
}
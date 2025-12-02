<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function kiso24_import_blinds() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    check_admin_referer('kiso24_import_blinds_nonce', 'kiso24_import_blinds_nonce');

    if (!isset($_FILES['import_file'])) {
        wp_die('No file uploaded.');
    }

    $file = $_FILES['import_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_die('File upload error: ' . $file['error']);
    }

    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        wp_die('Error opening file.');
    }

    $header = fgetcsv($handle);
    $log = [];
    $debug_data = [];

    while (($data = fgetcsv($handle)) !== false) {
        $blind = array_combine($header, $data);
        $blind_name = $blind['blind_name'] ?? '';

        if (empty($blind_name)) {
            $log[] = "Skipping row: Missing blind name.";
            continue;
        }

        $debug_blind = [
            'name' => $blind_name,
            'taxonomies' => []
        ];

        // Process taxonomies
        $taxonomies = ['price_group', 'transparency', 'color', 'characteristics'];
        foreach ($taxonomies as $taxonomy) {
            if (!empty($blind[$taxonomy])) {
                $terms = explode(',', $blind[$taxonomy]);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $term_exists = term_exists($term, $taxonomy);
                    $debug_blind['taxonomies'][$taxonomy][$term] = (bool)$term_exists;
                }
            }
        }

        $debug_data[] = $debug_blind;
        $log[] = "Processed blind: $blind_name";
    }

    fclose($handle);

    // Store debug data in a transient
    set_transient('kiso24_import_debug_data', $debug_data, 60 * 60); // Store for 1 hour

    // Store log messages
    set_transient('kiso24_import_log', $log, 60 * 60); // Store for 1 hour

    wp_safe_redirect(admin_url('admin.php?page=kiso24-pleated-import-export'));
    exit;
}
add_action('admin_post_kiso24_import_blinds', 'kiso24_import_blinds');

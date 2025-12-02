<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function kiso24_pleated_import_export_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php
        // Display import log messages if available
        $import_log = get_transient('kiso24_import_log');
        if ($import_log) {
            echo '<div class="updated"><p><strong>Import Log:</strong></p>';
            echo '<pre>';
            foreach ($import_log as $message) {
                echo esc_html($message) . "\n";
            }
            echo '</pre></div>';
            delete_transient('kiso24_import_log');
        }

        // Display export log messages if available
        $export_log = get_transient('kiso24_export_log');
        if ($export_log) {
            echo '<div class="updated"><p><strong>Export Log:</strong></p>';
            echo '<pre>';
            foreach ($export_log as $message) {
                echo esc_html($message) . "\n";
            }
            echo '</pre></div>';
            delete_transient('kiso24_export_log');
        }

        // Display debug table if available
        $debug_data = get_transient('kiso24_import_debug_data');
        if ($debug_data) {
            echo '<h2>Import Debug Information</h2>';
            echo '<table class="widefat">';
            echo '<thead><tr><th>Blind Name</th><th>Taxonomy</th><th>Term</th><th>Exists in DB</th></tr></thead>';
            echo '<tbody>';
            foreach ($debug_data as $blind) {
                foreach ($blind['taxonomies'] as $taxonomy => $terms) {
                    foreach ($terms as $term => $exists) {
                        echo '<tr>';
                        echo '<td>' . esc_html($blind['name']) . '</td>';
                        echo '<td>' . esc_html($taxonomy) . '</td>';
                        echo '<td>' . esc_html($term) . '</td>';
                        echo '<td>' . ($exists ? '✅' : '❌') . '</td>';
                        echo '</tr>';
                    }
                }
            }
            echo '</tbody></table>';
            delete_transient('kiso24_import_debug_data');
        }
        ?>

        <h2>Import Blinds</h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="kiso24_import_blinds">
            <?php wp_nonce_field('kiso24_import_blinds_nonce', 'kiso24_import_blinds_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="import_file">CSV File</label></th>
                    <td><input type="file" name="import_file" id="import_file" accept=".csv" required></td>
                </tr>
            </table>
            <?php submit_button('Import Blinds'); ?>
        </form>

        <h2>Export Blinds</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="kiso24_export_blinds">
            <?php wp_nonce_field('kiso24_export_blinds_nonce', 'kiso24_export_blinds_nonce'); ?>
            <?php submit_button('Export Blinds'); ?>
        </form>
    </div>
    <?php
}


function kiso24_pleated_add_import_export_page() {
    add_submenu_page(
        'edit.php?post_type=kiso24_pleated',
        'Import/Export Blinds',
        'Import/Export',
        'manage_options',
        'kiso24-pleated-import-export',
        'kiso24_pleated_import_export_page'
    );
}
add_action('admin_menu', 'kiso24_pleated_add_import_export_page');
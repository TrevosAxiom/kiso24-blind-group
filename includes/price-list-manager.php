<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Kiso24_Price_List_Manager {

    public function __construct() {
        // ... (existing code)
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        if ('product_page_price-list-manager' !== $hook) {
            return;
        }
        wp_enqueue_script('kiso24-admin-price-lists', plugins_url('assets/js/admin-price-lists.js', dirname(__FILE__)), array('jquery'), '1.0', true);
    }



    // ... (keep other methods)

    public function price_list_manager_page() {
        ?>
        <div class="wrap">
            <h1>Price List Manager</h1>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('price_list_upload', 'price_list_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="price_list_name">Price List Name</label></th>
                        <td><input type="text" name="price_list_name" id="price_list_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="price_list_csv">CSV File</label></th>
                        <td><input type="file" name="price_list_csv" id="price_list_csv" accept=".csv" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" value="Upload Price List" class="button button-primary">
                </p>
            </form>

            <h2>Existing Price Lists</h2>
            <?php $this->display_price_lists(); ?>
        </div>
        <?php
    }

    public function handle_price_list_upload() {
        if (isset($_POST['price_list_nonce']) && wp_verify_nonce($_POST['price_list_nonce'], 'price_list_upload')) {
            if (!empty($_FILES['price_list_csv']['tmp_name']) && !empty($_POST['price_list_name'])) {
                $upload_dir = wp_upload_dir();
                $price_list_dir = $upload_dir['basedir'] . '/price-lists';

                if (!file_exists($price_list_dir)) {
                    wp_mkdir_p($price_list_dir);
                }

                $price_list_name = sanitize_text_field($_POST['price_list_name']);
                $file_name = sanitize_file_name($_FILES['price_list_csv']['name']);
                $file_path = $price_list_dir . '/' . $file_name;

                if (move_uploaded_file($_FILES['price_list_csv']['tmp_name'], $file_path)) {
                    $price_lists = get_option('kiso24_price_lists', array());
                    $price_lists[$price_list_name] = $file_name;
                    update_option('kiso24_price_lists', $price_lists);
                    add_action('admin_notices', array($this, 'price_list_upload_success'));
                } else {
                    add_action('admin_notices', array($this, 'price_list_upload_error'));
                }
            }
        }
    }

    public function display_price_lists() {
        $price_lists = get_option('kiso24_price_lists', array());
        if (empty($price_lists)) {
            echo '<p>No price lists uploaded yet.</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>File</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($price_lists as $name => $file) : ?>
                <tr>
                    <td><?php echo esc_html($name); ?></td>
                    <td><?php echo esc_html($file); ?></td>
                    <td>
                        <a href="#" class="delete-price-list" data-name="<?php echo esc_attr($name); ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    // ... (keep other methods)

    public function add_price_list_dropdown() {
        global $post;

        $price_lists = get_option('kiso24_price_lists', array());
        $selected_price_list = get_post_meta($post->ID, '_kiso24_selected_price_list', true);

        woocommerce_wp_select(array(
            'id' => '_kiso24_selected_price_list',
            'label' => __('Select Price List', 'kiso24-pleated'),
            'options' => array_combine(array_keys($price_lists), array_keys($price_lists)),
            'value' => $selected_price_list,
        ));
    }

    // ... (keep other methods)
}

new Kiso24_Price_List_Manager();
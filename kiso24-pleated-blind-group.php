<?php
/**
 * Plugin Name: Kiso24 Blind Group
 * Description: A custom post type for managing Kiso24 blinds with blind group details, CSV import/export, and image uploads.
 * Version: 2.6.0
 * Author: Adebayo Taofeek (The Tao of WP)
 * Email: thetaoofwp@gmail.com
 * URL: https://twitter.com/ooshanla/
 * Author URI: https://twitter.com/ooshanla/
 * License: GPL2
 * Text Domain: plm
 * Domain Path: /languages/
 * Requires at least: 5.2
 * Requires PHP: 8.0
 * Plugin URI: https://twitter.com/ooshanla/
 * Tags: WooCommerce, Blind List, CSV Import, vLookup
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include necessary files
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt-register.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/taxonomy-register.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/meta-box.php';
//require_once plugin_dir_path( __FILE__ ) . 'includes/csv-handlers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/product-type.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/blind-group-display.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/blind-group-import-export.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/import-csv.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
//include_once plugin_dir_path( __FILE__ ) . 'includes/taxonomy-image.php';

// Enqueue JavaScript for handling rows in the Blind Group meta box and media uploader
function kiso24_pleated_enqueue_scripts() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_media(); // Enqueue WordPress media uploader    
    $script_path = plugin_dir_path( __FILE__ ) . 'assets/js/blind-group-table.js';
    wp_enqueue_script( 'blind-group-table-js', plugin_dir_url( __FILE__ ) . 'assets/js/blind-group-table.js', array( 'jquery' ), file_exists( $script_path ) ? filemtime( $script_path ) : false, true );
}
add_action( 'admin_enqueue_scripts', 'kiso24_pleated_enqueue_scripts' );

// Enqueue JavaScript and CSS for the blind group filtering
function kiso24_pleated_enqueue_filter_scripts() {
    $plugin_dir_path = plugin_dir_path(__FILE__);
    $plugin_dir_url = plugin_dir_url(__FILE__);

    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');

    wp_enqueue_script('blind-group-filter', $plugin_dir_url . 'assets/js/blind-group-filter.js', array('jquery'), filemtime($plugin_dir_path . 'assets/js/blind-group-filter.js'), true);
    wp_enqueue_script('blind-group-modal', $plugin_dir_url . 'assets/js/blind-group-modal.js', array('jquery'), filemtime($plugin_dir_path . 'assets/js/blind-group-modal.js'), true);
    // Add some basic CSS for styling the grid
    wp_enqueue_style('kiso24-pleated-style', $plugin_dir_url . 'assets/css/kiso24-pleated-style.css', array(), filemtime($plugin_dir_path . 'assets/css/kiso24-pleated-style.css'));

    // Pass ajax_url and nonce to our script
    wp_localize_script('blind-group-filter', 'kiso24_ajax_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('kiso24_filter_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'kiso24_pleated_enqueue_filter_scripts');

// Add custom blind data to WooCommerce cart
function kiso24_pleated_add_blind_to_cart($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['selected_blind'])) {
        $selected_blind = sanitize_text_field($_POST['selected_blind']);
        $blind_details = isset($_POST['blind_details']) ? $_POST['blind_details'] : array();

        $width = isset($_POST['plm_width']) ? sanitize_text_field($_POST['plm_width']) : '';
        $height = isset($_POST['plm_height']) ? sanitize_text_field($_POST['plm_height']) : '';

        // Validate width and height
        if (empty($width) || empty($height)) {
            wc_add_notice(__('Please enter both width and height.', 'kiso24-pleated'), 'error');
            return $cart_item_data;
        }

        $cart_item_data['selected_blind'] = array(
            'name' => $selected_blind,
            'price_group' => isset($blind_details['price_group']) ? sanitize_text_field($blind_details['price_group']) : '',
            'transparency' => isset($blind_details['transparency']) ? sanitize_text_field($blind_details['transparency']) : '',
            'color' => isset($blind_details['color']) ? sanitize_text_field($blind_details['color']) : '',
            'characteristics' => isset($blind_details['characteristics']) ? sanitize_text_field($blind_details['characteristics']) : '',
            'width' => $width,
            'height' => $height,
        );
    }
    return $cart_item_data;
}

//add_filter('woocommerce_add_cart_item_data', 'kiso24_pleated_add_blind_to_cart', 10, 3);

// Display custom blind data in WooCommerce cart
function kiso24_pleated_display_blind_data_in_cart($item_data, $cart_item) {
    if (isset($cart_item['selected_blind'])) {
        $selected_blind = $cart_item['selected_blind'];

        $item_data[] = array(
            'key' => __('Blind', 'kiso24-pleated'),
            'value' => $selected_blind['name'],
        );
        if (!empty($selected_blind['price_group'])) {
            $item_data[] = array(
                'key' => __('Preisgruppe', 'kiso24-pleated'),
                'value' => $selected_blind['price_group'],
            );
        }
        if (!empty($selected_blind['transparency'])) {
            $item_data[] = array(
                'key' => __('Transparency', 'kiso24-pleated'),
                'value' => $selected_blind['transparency'],
            );
        }
        if (!empty($selected_blind['color'])) {
            $item_data[] = array(
                'key' => __('Color', 'kiso24-pleated'),
                'value' => $selected_blind['color'],
            );
        }
        if (!empty($selected_blind['characteristics'])) {
            $item_data[] = array(
                'key' => __('Characteristics', 'kiso24-pleated'),
                'value' => $selected_blind['characteristics'],
            );
        }
        if (!empty($selected_blind['width'])) {
            $item_data[] = array(
                'key' => __('Width', 'kiso24-pleated'),
                'value' => $selected_blind['width'],
            );
        }
        if (!empty($selected_blind['height'])) {
            $item_data[] = array(
                'key' => __('Height', 'kiso24-pleated'),
                'value' => $selected_blind['height'],
            );
        }
    }
    return $item_data;
}

//add_filter('woocommerce_get_item_data', 'kiso24_pleated_display_blind_data_in_cart', 10, 2);

function enqueue_dashicons() {
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'enqueue_dashicons');
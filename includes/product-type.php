<?php
// Register the Kiso24 Pleated Blind product type class
function register_kiso24_pleated_product_class() {
    class WC_Product_Kiso24_Pleated extends WC_Product {
        public function __construct($product) {
            $this->product_type = 'kiso24_pleated';
            parent::__construct($product);
        }

        public function get_type() {
            return 'kiso24_pleated';
        }

        public function is_purchasable() {
            return true;
        }

        public function is_sold_individually() {
            return false;
        }

        public function is_virtual() {
            return false;
        }

        public function is_visible() {
            return true;
        }

        public function add_to_cart_url() {
            return $this->get_permalink();
        }

        public function add_to_cart_text() {
            return apply_filters('woocommerce_product_add_to_cart_text', __('Select options', 'woocommerce'), $this);
        }

        public function supports($feature) {
            return $this->supports[] = 'ajax_add_to_cart';
        }
    }

}
add_action( 'init', 'register_kiso24_pleated_product_class' );

// Add Kiso24 Pleated Blind to WooCommerce product types
function add_kiso24_pleated_product_type( $types ) {
    $types['kiso24_pleated'] = __( 'Kiso24 Blind Type', 'kiso24-pleated' );
    return $types;
}
add_filter( 'product_type_selector', 'add_kiso24_pleated_product_type' );

// Save the product type correctly for custom product types
function kiso24_pleated_save_custom_product_type($post_id) {
    if (isset($_POST['product-type']) && 'kiso24_pleated' === $_POST['product-type']) {
        wp_set_object_terms($post_id, 'kiso24_pleated', 'product_type');
    }
}
add_action('woocommerce_process_product_meta', 'kiso24_pleated_save_custom_product_type');

// Add the Blind Options tab for the custom product type
function kiso24_pleated_blind_options_tab( $tabs ) {
    $tabs['kiso24_pleated_blind_options'] = array(
        'label'    => __( 'Blind Options', 'kiso24-pleated' ),
        'target'   => 'kiso24_pleated_blind_options_data',
        'class'    => array( 'show_if_kiso24_pleated' ),
        'priority' => 21,
    );
    return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'kiso24_pleated_blind_options_tab' );

// Add content to the Blind Options tab
function kiso24_pleated_blind_options_tab_content() {
    global $post;
    $selected_blind_group = get_post_meta( $post->ID, '_kiso24_selected_blind_group', true );
    $blind_groups = get_posts( array(
        'post_type'   => 'kiso24_pleated',
        'post_status' => 'publish',
        'numberposts' => -1,
    ) );
    ?>
    <div id="kiso24_pleated_blind_options_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <p class="form-field">
                <label for="kiso24_pleated_blind_group"><?php _e( 'Select Blind Group', 'kiso24-pleated' ); ?></label>
                <select id="kiso24_pleated_blind_group" name="kiso24_pleated_blind_group">
                    <option value=""><?php _e( 'Select a Blind Group', 'kiso24-pleated' ); ?></option>
                    <?php foreach ( $blind_groups as $group ) : ?>
                        <option value="<?php echo esc_attr( $group->ID ); ?>" <?php selected( $selected_blind_group, $group->ID ); ?>>
                            <?php echo esc_html( $group->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>
    </div>
    <?php
}
add_action( 'woocommerce_product_data_panels', 'kiso24_pleated_blind_options_tab_content' );


// Add custom JavaScript to handle showing/hiding options based on the product type
function kiso24_pleated_product_type_admin_script() {
    if ( 'product' !== get_post_type() ) {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery( document ).ready( function( $ ) {
            $( 'select#product-type' ).on( 'change', function() {
                var productType = $( this ).val();
                if ( 'kiso24_pleated' === productType ) {
                    $( '.show_if_simple' ).hide();
                    $( '.show_if_kiso24_pleated' ).show();
                } else {
                    $( '.show_if_kiso24_pleated' ).hide();
                }
            }).trigger( 'change' );
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'kiso24_pleated_product_type_admin_script' );

// Ensure the correct product type is assigned in WooCommerce product lists
function kiso24_pleated_set_product_type( $product_type, $product_id ) {
    $product_type_meta = get_post_meta( $product_id, '_product_type', true );
    if ( 'kiso24_pleated' === $product_type_meta ) {
        return 'kiso24_pleated';
    }
    return $product_type;
}
add_filter( 'woocommerce_product_type', 'kiso24_pleated_set_product_type', 10, 2 );

// Save the selected Blind Group when the product is saved
function kiso24_pleated_save_blind_group_options( $post_id ) {
    // Check if the product type is 'kiso24_pleated' and a blind group is selected
    if ( isset( $_POST['product-type'] ) && 'kiso24_pleated' === $_POST['product-type'] ) {
        if ( isset( $_POST['kiso24_pleated_blind_group'] ) ) {
            // Save the selected blind group to the product meta
            update_post_meta( $post_id, '_kiso24_selected_blind_group', sanitize_text_field( $_POST['kiso24_pleated_blind_group'] ) );
        }
    }
}
add_action( 'woocommerce_process_product_meta', 'kiso24_pleated_save_blind_group_options' );


// Display the selected Blind Group on the product page
function kiso24_pleated_display_blind_group() {
    global $product;

    // Ensure this is the custom product type
    if ( 'kiso24_pleated' === $product->get_type() ) {
        // Get the selected blind group ID for this product
        $selected_blind_group_id = get_post_meta( $product->get_id(), '_kiso24_selected_blind_group', true );

        // Check if a blind group is selected
        if ( $selected_blind_group_id ) {
            // Pass the selected blind group ID to the display function
            kiso24_pleated_blind_grid_display( $selected_blind_group_id );
        } else {
            echo __( 'No blinds available for this product.', 'kiso24-pleated' );
        }
    }
}
add_action( 'woocommerce_single_product_summary', 'kiso24_pleated_display_blind_group', 25 );
function kiso24_pleated_add_to_cart() {
    global $product;
    
    if ('kiso24_pleated' === $product->get_type()) {
        do_action('woocommerce_simple_add_to_cart');
    }
}
add_action('woocommerce_kiso24_pleated_add_to_cart', 'kiso24_pleated_add_to_cart');

function kiso24_pleated_is_purchasable($purchasable, $product) {
    if ($product->get_type() === 'kiso24_pleated') {
        $purchasable = true;
    }
    return $purchasable;
}
add_filter('woocommerce_is_purchasable', 'kiso24_pleated_is_purchasable', 10, 2);

function kiso24_pleated_add_to_cart_handler($cart_item_data, $product_id) {
    if (isset($_POST['selected_blind'])) {
        $selected_blind = sanitize_text_field($_POST['selected_blind']);
        $blind_details = isset($_POST['blind_details']) ? $_POST['blind_details'] : array();

        $cart_item_data['selected_blind'] = array(
            'name' => $selected_blind,
            'price_group' => isset($blind_details['price_group']) ? sanitize_text_field($blind_details['price_group']) : '',
            'transparency' => isset($blind_details['transparency']) ? sanitize_text_field($blind_details['transparency']) : '',
            'color' => isset($blind_details['color']) ? sanitize_text_field($blind_details['color']) : '',
            'characteristics' => isset($blind_details['characteristics']) ? sanitize_text_field($blind_details['characteristics']) : '',
        );
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'kiso24_pleated_add_to_cart_handler', 10, 2);

function kiso24_pleated_display_cart_item_custom_meta_data($item_data, $cart_item) {
    if (isset($cart_item['selected_blind'])) {
        $blind_info = $cart_item['selected_blind'];
        
        $item_data[] = array(
            'key' => __('Muster', 'kiso24-pleated'),
            'value' => $blind_info['name']
        );
        
//         if (!empty($blind_info['price-group'])) {
//             $item_data[] = array(
//                 'key' => __('Preisgruppe', 'kiso24-pleated'),
//                 'value' => $blind_info['price-group']
//             );
//         }
        
//         if (!empty($blind_info['transparency'])) {
//             $item_data[] = array(
//                 'key' => __('Transparency', 'kiso24-pleated'),
//                 'value' => $blind_info['transparency']
//             );
//         }
        
//         if (!empty($blind_info['color'])) {
//             $item_data[] = array(
//                 'key' => __('Color', 'kiso24-pleated'),
//                 'value' => $blind_info['color']
//             );
//         }
        
        // if (!empty($blind_info['characteristics'])) {
        //     $item_data[] = array(
        //         'key' => __('Characteristics', 'kiso24-pleated'),
        //         'value' => $blind_info['characteristics']
        //     );
        // }
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'kiso24_pleated_display_cart_item_custom_meta_data', 10, 2);

function kiso24_pleated_add_custom_data_to_order_items($item, $cart_item_key, $values, $order) {
    if (isset($values['selected_blind'])) {
        $blind_info = $values['selected_blind'];
        
        $item->add_meta_data(__('Muster', 'kiso24-pleated'), $blind_info['name']);
        
        if (!empty($blind_info['price_group'])) {
            $item->add_meta_data(__('Preisgruppe', 'kiso24-pleated'), $blind_info['price_group']);
        }
        
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'kiso24_pleated_add_custom_data_to_order_items', 10, 4);
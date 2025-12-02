<?php
require_once plugin_dir_path(__FILE__) . 'taxonomy-icons.php'; // Ensure taxonomy icon functions are available

function get_lowest_price_for_group($term_id) {
    $price = get_term_meta($term_id, 'lowest_price', true);
    return $price ? floatval($price) : PHP_FLOAT_MAX;
}

// Display the Blind Grid based on individual blinds from the selected blind group
function kiso24_pleated_blind_grid_display() {
    global $post;

    // Get the selected blind group for this product
    $selected_blind_group_id = get_post_meta($post->ID, '_kiso24_selected_blind_group', true);

    // If no blind group is selected for the product, return early with a message.
    if (empty($selected_blind_group_id)) {
        echo __('No blinds available for this product.', 'kiso24-pleated');
        return;
    }

    // Initialize $allowed_taxonomies
    $allowed_taxonomies = [];

    // Fetch the allowed taxonomies from the selected blind group
    $allowed_taxonomies = get_post_meta($selected_blind_group_id, '_allowed_taxonomies', true);
    $allowed_taxonomies = is_array($allowed_taxonomies) ? $allowed_taxonomies : [];

    // Fetch explanations
    $taxonomy_explanations = get_post_meta($selected_blind_group_id, '_taxonomy_explanations', true);
    $taxonomy_explanations = is_array($taxonomy_explanations) ? $taxonomy_explanations : [];
    $default_explanation = get_post_meta($selected_blind_group_id, '_default_taxonomy_explanation', true);

    // If the selected blind group has no allowed taxonomies, we can't show filters.
    // The grid can still be shown, but filters will be absent.

    // Fetch the selected blind group to ensure it exists and is valid
    $blind_group = get_post( $selected_blind_group_id );
    if ( ! $blind_group || $blind_group->post_type !== 'kiso24_pleated' ) {
        echo __( 'Invalid blind group selected.', 'kiso24-pleated' );
        return;
    }

    // Fetch the blinds from the selected blind group
    $blinds = get_post_meta( $selected_blind_group_id, '_blind_group_data', true );

    // If no blinds exist in the selected group, display a message
    if ( empty( $blinds ) || ! is_array( $blinds ) ) {
        echo __( 'No blinds found in the selected group.', 'kiso24-pleated' );
        return;
    }

    // --- PERFORMANCE IMPROVEMENT ---
    // Define pagination parameters
    $blinds_per_page = 48; // Number of blinds to show per page
    $initial_blinds = array_slice($blinds, 0, $blinds_per_page);
    ?>
    <div class="blind-group-filter">
    <?php
    // Dynamically render dropdowns for allowed taxonomies
    foreach ( $allowed_taxonomies as $taxonomy_slug ) {
        // Ensure the taxonomy exists
        $taxonomy_object = get_taxonomy( $taxonomy_slug );
        if ( ! $taxonomy_object ) {
            continue;
        }

        // Get terms for the taxonomy that are actually used in the blinds
        $terms = array();
        foreach ( $blinds as $blind ) {
            if (isset($blind[$taxonomy_slug])) {
                // The value in $blind[$taxonomy_slug] is an array of term slugs for 'characteristics'
                // and a single term slug for others.
                $term_slugs = is_array($blind[$taxonomy_slug]) ? $blind[$taxonomy_slug] : [$blind[$taxonomy_slug]];
                foreach ($term_slugs as $term_slug) {
                    $term = get_term_by('slug', trim($term_slug), $taxonomy_slug);
                    if ($term && !is_wp_error($term) && !isset($terms[$term->term_id])) {
                        $terms[$term->term_id] = $term;
                    }
                }
            }
        }

         // If we have terms, render the dropdown
         if ( !empty($terms) ) {
            ?>
            <div class="filter-dropdown-container">
                <label for="<?php echo esc_attr($taxonomy_slug); ?>-filter" class="filter-label">
                    <?php echo esc_html($taxonomy_object->label); ?>
                    <?php
                        $explanation = !empty($taxonomy_explanations[$taxonomy_slug]) ? $taxonomy_explanations[$taxonomy_slug] : $default_explanation;
                        if (!empty($explanation)) :
                    ?>
                        <span class="filter-help-icon" data-target="#explanation-<?php echo esc_attr($taxonomy_slug); ?>">?</span>
                        <div id="explanation-<?php echo esc_attr($taxonomy_slug); ?>" style="display: none;">
                             <?php echo wpautop(wp_kses_post($explanation)); ?>
                        </div>
                    <?php endif; ?>
                </label>
                <select id="<?php echo esc_attr( $taxonomy_slug ); ?>-filter">
                    <option value=""><?php _e( 'Alle anzeigen', 'kiso24-pleated' ); ?></option>
                    <?php 
                    if ($taxonomy_slug === 'price-group') {
                        // Sort terms by lowest price for price_group taxonomy
                        usort($terms, function($a, $b) {
                            return get_lowest_price_for_group($a->term_id) - get_lowest_price_for_group($b->term_id);
                        });
                    }
                    foreach ( $terms as $term ) : 
                    ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
        }
    }
    ?>
    </div>

    <div class="blind-search-container">
        <button id="toggle-search" class="toggle-search-btn">
            <span class="dashicons dashicons-search"></span>
            <span style="text-transform: uppercase;"> Direktsuche nach einem Farbcode </span>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </button>
        <div id="search-bar" class="blind-group-search" style="display: none;">
            <input type="text" id="blind-search" placeholder="...bitte Farbcode eingeben">
        </div>
    </div>

    <!-- Blind Group Grid Display -->
    <div class="blind-group-grid" data-group-id="<?php echo esc_attr($selected_blind_group_id); ?>" data-page="1" data-per-page="<?php echo esc_attr($blinds_per_page); ?>">
        <?php
        // --- PERFORMANCE IMPROVEMENT --- Display only the initial set of blinds
        foreach ( $initial_blinds as $blind ) :
            $blind_name = isset( $blind['blind_name'] ) ? $blind['blind_name'] : '';
            $blind_image_url = isset( $blind['image'] ) ? $blind['image'] : '';  // Use the URL directly
            //$blind_image_url = $blind_image_id ? wp_get_attachment_url( $blind_image_id ) : '';

            $price_group = isset( $blind['price_group'] ) ? $blind['price_group'] : 'N/A';
            $transparency = isset( $blind['transparency'] ) ? $blind['transparency'] : 'N/A';
            $color = isset( $blind['color'] ) ? $blind['color'] : 'N/A';
            $characteristics_display = 'N/A';
            $characteristics_names_for_data = ''; // For the data attribute
            if (isset($blind['characteristics']) && is_array($blind['characteristics']) && !empty($blind['characteristics'])) {
                $char_names = array_map(function($slug) {
                    $term = get_term_by('slug', $slug, 'characteristics');
                    return $term ? $term->name : ucwords(str_replace('-', ' ', $slug)); // Fallback to formatted slug
                }, $blind['characteristics']);
                $characteristics_display = implode(', ', $char_names);
                $characteristics_names_for_data = esc_attr($characteristics_display);
            }

            // Add this line to get the taxonomy icons
            $taxonomy_icons = kiso24_pleated_get_taxonomy_icons($blind);

            // Render each blind as a grid item
            ?>
            <div class="blind-item selectable-blind"
                data-blind-name="<?php echo esc_attr( $blind_name ); ?>"
                data-blind-image="<?php echo esc_url( $blind_image_url ); ?>"
                data-characteristics-names="<?php echo $characteristics_names_for_data; ?>"
                <?php
                foreach ($allowed_taxonomies as $taxonomy_slug) {
                    if (isset($blind[$taxonomy_slug])) {
                        $term_slugs = is_array($blind[$taxonomy_slug]) ? $blind[$taxonomy_slug] : array($blind[$taxonomy_slug]);
                        echo 'data-' . esc_attr($taxonomy_slug) . '="' . esc_attr(implode(',', $term_slugs)) . '" ';
                    } 
                }
            ?>

                <!-- Blind Image -->
                <?php if ( $blind_image_url ) : ?>
                    <img src="<?php echo esc_url( $blind_image_url ); ?>" alt="<?php echo esc_attr( $blind_name ); ?>" class="blind-image" />
                <?php else : ?>
                    <img src="https://via.placeholder.com/150" alt="<?php echo esc_attr( $blind_name ); ?>" class="blind-image" />
                <?php endif; ?>

                <!-- Blind Name -->
                <p class="blind-label"><?php echo esc_html( $blind_name ); ?></p>

                <!-- Display Taxonomy Icons -->
                <div class="taxonomy-icons">
                    <?php echo $taxonomy_icons; ?>
                </div>

                <!-- Magnifier Icon for Popup -->
                <div class="blind-magnifier" data-blind-id="<?php echo esc_attr( $blind_name ); ?>">
                    <i class="fa fa-search-plus"></i>
                </div>
            </div>
            <?php
        endforeach;
        ?>
    </div>
    <div class="blind-group-footer">
        <button id="load-more-blinds" style="<?php echo count($blinds) <= $blinds_per_page ? 'display: none;' : ''; ?>"><?php _e('MEHR MUSTER LADEN', 'kiso24-pleated'); ?></button>
    </div>

    <!-- Hidden input to store the price group of the selected blind for the price calculator -->
    <input type="hidden" id="selected_blind_price_group" value="">

    <!-- Modal for Blind Details -->
    <div id="blind-modal" class="blind-modal">
        <div class="blind-modal-content">
            <span class="close">&times;</span> 
            <div class="blind-modal-body">
                <div class="modal-blind-image">
                    <!-- This image will be updated with the selected blind's image URL -->
                    <img src="" alt="Blind Image" class="modal-blind-img" />
                </div>
                <div class="modal-blind-details">
                    <h2 class="modal-blind-name"></h2>
                    <p><strong><?php _e( 'Preisgruppe:', 'kiso24-pleated' ); ?></strong> <span class="modal-blind-price-group"></span></p>
                    <p><strong><?php _e( 'Transparenz:', 'kiso24-pleated' ); ?></strong> <span class="modal-blind-transparency"></span></p>
                    <p><strong><?php _e( 'Farbe:', 'kiso24-pleated' ); ?></strong> <span class="modal-blind-color"></span></p>
                    <p><strong><?php _e( 'Eigenschaften:', 'kiso24-pleated' ); ?></strong> <span class="modal-blind-characteristics"><?php echo esc_html($characteristics_display); ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Filter Help Explanations -->
    <div id="filter-help-modal" class="kiso24-help-modal">
        <div class="kiso24-help-modal-content">
            <span class="kiso24-help-modal-close">&times;</span>
            <div class="kiso24-help-modal-header">
                <i class="fas fa-info-circle kiso24-help-modal-icon"></i>
            </div>
            <div id="kiso24-help-modal-body" class="kiso24-help-modal-body">
                <!-- Content will be injected here by JavaScript -->
            </div>
        </div>
    </div>

    <style>
        .blind-item {
            display: block; /* Ensure items are visible by default */
        }
        .blind-item.hidden {
            display: none !important;
        }
        .filter-label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .filter-help-icon {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #0073aa;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        /* Styles for the new help modal */
        .kiso24-help-modal {
            display: none; 
            position: fixed; 
            z-index: 1001; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.6);
        }
        .kiso24-help-modal-content {
            background-color: #fefefe;
            margin: 15% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 600px;
            position: relative;
            border-radius: 5px;
        }
        .kiso24-help-modal-close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
        }
        .kiso24-help-modal-close:hover,
        .kiso24-help-modal-close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .kiso24-help-modal-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .kiso24-help-modal-icon {
            font-size: 28px;
            color: #0073aa;
        }
        .kiso24-help-modal-body {
            /* padding-top: 20px; */ /* No longer needed with header */
        }
        .kiso24-help-modal-body p:first-child {
            margin-top: 0;
        }
    </style>

    <?php
}

// Debug function to check the selected blind group for the product
// function kiso24_debug_selected_blind_group() {
//     global $post;

//     // Get the selected blind group ID
//     $selected_blind_group_id = get_post_meta( $post->ID, '_kiso24_selected_blind_group', true );

//     if ( ! empty( $selected_blind_group_id ) ) {
//         echo '<p><strong>Selected Blind Group ID:</strong> ' . esc_html( $selected_blind_group_id ) . '</p>';
//     } else {
//         echo '<p><strong>No Blind Group Selected for this product.</strong></p>';
//     }
// }
// add_action( 'woocommerce_single_product_summary', 'kiso24_debug_selected_blind_group', 5 );
?>

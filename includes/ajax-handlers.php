<?php
/**
 * AJAX handlers for filtering and loading blinds.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_filter_blinds', 'kiso24_filter_blinds_callback');
add_action('wp_ajax_nopriv_filter_blinds', 'kiso24_filter_blinds_callback');

function kiso24_filter_blinds_callback() {
    // Nonce check for security
    check_ajax_referer('kiso24_filter_nonce', 'nonce');

    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 48;
    $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $filters = isset($_POST['filters']) ? (array) $_POST['filters'] : [];
    $debug_log = [];

    if (!$group_id) {
        wp_send_json_error(['message' => 'Invalid Group ID.']);
    }

    $all_blinds = get_post_meta($group_id, '_blind_group_data', true);
    if (empty($all_blinds) || !is_array($all_blinds)) {
        wp_send_json_success(['html' => '<p>No blinds found.</p>', 'has_more' => false, 'debug' => ['No blinds in group.']]);
    }

    $filtered_blinds = array_filter($all_blinds, function ($blind) use ($search_term, $filters) {
        // Search filter
        if (!empty($search_term)) {
            $name_matches = stripos($blind['blind_name'], $search_term) !== false;
            if (!$name_matches) {
                return false;
            }
        }

        // Taxonomy filters
        foreach ($filters as $taxonomy => $value) {
            if (!empty($value)) {
                if (!isset($blind[$taxonomy])) {
                    return false; // Blind doesn't have this taxonomy set
                }

                // Handle characteristics, which are saved as an array of slugs.
                if ($taxonomy === 'characteristics') {
                    // Data can be an array or a comma-separated string. Normalize it to an array.
                    $blind_characteristics = [];
                    if (is_array($blind[$taxonomy])) {
                        $blind_characteristics = $blind[$taxonomy];
                    } elseif (is_string($blind[$taxonomy])) {
                        $blind_characteristics = explode(',', $blind[$taxonomy]);
                    }
                    $blind_characteristics = array_map('trim', $blind_characteristics);

                    if (!in_array($value, $blind_characteristics, true)) {
                        return false; // The selected filter is not in this blind's characteristics.
                    }
                } else {
                    // Handle other single-value taxonomies.
                    if (is_string($blind[$taxonomy]) && $blind[$taxonomy] !== $value) {
                        return false;
                    }
                }
            }
        }

        return true;
    });

    // Add debug info for the first blind being processed (if any)
    if (!empty($all_blinds)) {
        $first_blind = reset($all_blinds);
        $debug_log[] = "Filter value for 'characteristics': " . ($filters['characteristics'] ?? 'Not set');
        $debug_log[] = "First blind's characteristics data: " . json_encode($first_blind['characteristics'] ?? 'Not set');
    }

    $total_filtered = count($filtered_blinds);
    $offset = ($page - 1) * $per_page;
    $paginated_blinds = array_slice($filtered_blinds, $offset, $per_page);

    $has_more = ($total_filtered > ($page * $per_page));

    ob_start();
    if (!empty($paginated_blinds)) {
        foreach ($paginated_blinds as $blind) {
            // Re-use the rendering logic by creating a template part function if desired,
            // or duplicate the HTML structure here. For simplicity, we'll build it here.
            $blind_name = esc_attr($blind['blind_name'] ?? '');
            $blind_image_url = esc_url($blind['image'] ?? '');
            $allowed_taxonomies = get_post_meta($group_id, '_allowed_taxonomies', true);
            $allowed_taxonomies = is_array($allowed_taxonomies) ? $allowed_taxonomies : [];

            // Get characteristic names for display in the modal
            $characteristics_names_for_data = '';
            if (isset($blind['characteristics']) && !empty($blind['characteristics'])) {
                $char_slugs = is_array($blind['characteristics']) ? $blind['characteristics'] : explode(',', $blind['characteristics']);
                $char_names = array_map(function($slug) {
                    $term = get_term_by('slug', trim($slug), 'characteristics');
                    return $term ? $term->name : ucwords(str_replace('-', ' ', trim($slug)));
                }, $char_slugs);
                $characteristics_names_for_data = esc_attr(implode(', ', $char_names));
            }


            $data_attributes = '';
            foreach ($allowed_taxonomies as $taxonomy_slug) {
                if (isset($blind[$taxonomy_slug])) {
                    $term_slugs = is_array($blind[$taxonomy_slug]) ? $blind[$taxonomy_slug] : [$blind[$taxonomy_slug]];
                    $data_attributes .= 'data-' . esc_attr($taxonomy_slug) . '="' . esc_attr(implode(',', $term_slugs)) . '" ';
                }
            }

            ?>
            <div class="blind-item selectable-blind"
                 data-blind-name="<?php echo $blind_name; ?>"
                 data-blind-image="<?php echo $blind_image_url; ?>"
                 data-characteristics-names="<?php echo $characteristics_names_for_data; ?>"
                <?php echo $data_attributes; ?>>

                <?php if ($blind_image_url): ?>
                    <img src="<?php echo $blind_image_url; ?>" alt="<?php echo $blind_name; ?>" class="blind-image"/>
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" alt="<?php echo $blind_name; ?>" class="blind-image"/>
                <?php endif; ?>

                <p class="blind-label"><?php echo esc_html($blind['blind_name'] ?? ''); ?></p>

                <div class="taxonomy-icons">
                    <?php echo kiso24_pleated_get_taxonomy_icons($blind); ?>
                </div>

                <div class="blind-magnifier" data-blind-id="<?php echo $blind_name; ?>">
                    <i class="fa fa-search-plus"></i>
                </div>
            </div>
            <?php
        }
    } else {
        // This part is only shown if there are zero results for the filters.
        // The 'no-more-blinds' message handles the case where there are results but no more pages.
        if ($page === 1) {
            echo '<p>' . __('No results match your criteria.', 'kiso24-pleated') . '</p>';
        }
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html, 'has_more' => $has_more, 'debug' => $debug_log]);
}

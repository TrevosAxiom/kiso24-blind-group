<?php
function kiso24_pleated_get_taxonomy_icons($blind) {
    $icons = '';
    $default_icon_url = plugins_url('assets/images/default-icon.png', dirname(__FILE__)); // Adjust this path to your default icon

    $taxonomies = array(
        'price_group' => 'Preisgruppe',
        'transparency' => 'Transparenz',
        'color' => 'Farbe',
        'characteristics' => 'Eigenschaften'
    );

    foreach ($taxonomies as $taxonomy => $label) {
        if (isset($blind[$taxonomy])) {
            $terms = is_array($blind[$taxonomy]) ? $blind[$taxonomy] : array($blind[$taxonomy]);

            foreach ($terms as $term_slug) {
                $term = get_term_by('slug', $term_slug, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $icon_url = get_term_meta($term->term_id, 'taxonomy_icon', true);

                    if (!$icon_url) {
                        $icon_url = $default_icon_url;
                    }

                    $icons .= '<img src="' . esc_url($icon_url) . '" 
                                   class="taxonomy-icon ' . esc_attr($taxonomy) . '-icon" 
                                   alt="' . esc_attr($term->name) . '" 
                                   title="' . esc_attr($label . ': ' . $term->name) . '"
                                   onerror="this.onerror=null;this.src=\'' . esc_url($default_icon_url) . '\';">';
                }
            }
        }
    }

    return $icons;
}



function kiso24_pleated_enqueue_tooltip_scripts() {
    wp_enqueue_script('tippy', 'https://unpkg.com/@popperjs/core@2', array(), null, true);
    wp_enqueue_script('tippy-js', 'https://unpkg.com/tippy.js@6', array('tippy'), null, true);
    wp_add_inline_script('tippy-js', '
        document.addEventListener("DOMContentLoaded", function() {
            tippy(".taxonomy-icon", {
                content: (reference) => reference.getAttribute("title"),
                allowHTML: true,
            });
        });
    ');
}
function kiso24_pleated_add_taxonomy_icon_field($term) {
    ?>
    <div class="form-field term-group">
        <label for="taxonomy-icon"><?php _e('Taxonomy Icon', 'kiso24-pleated'); ?></label>
        <input type="hidden" name="taxonomy_icon" id="taxonomy-icon" value="">
        <div id="taxonomy-icon-preview"></div>
        <input type="button" class="button button-secondary" value="<?php _e('Choose Icon', 'kiso24-pleated'); ?>" id="upload_icon_button">
        <input type="button" class="button button-secondary" value="<?php _e('Remove Icon', 'kiso24-pleated'); ?>" id="remove_icon_button" style="display:none;">
        <p><?php _e('Select an icon for this term.', 'kiso24-pleated'); ?></p>
    </div>
    <?php
    kiso24_pleated_enqueue_media_scripts();
}

function kiso24_pleated_edit_taxonomy_icon_field($term) {
    $icon = get_term_meta($term->term_id, 'taxonomy_icon', true);
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="taxonomy-icon"><?php _e('Taxonomy Icon', 'kiso24-pleated'); ?></label></th>
        <td>
            <input type="hidden" name="taxonomy_icon" id="taxonomy-icon" value="<?php echo esc_attr($icon); ?>">
            <div id="taxonomy-icon-preview">
                <?php if ($icon) : ?>
                    <img src="<?php echo esc_url($icon); ?>" style="max-width:100px;height:auto;">
                <?php endif; ?>
            </div>
            <input type="button" class="button button-secondary" value="<?php _e('Choose Icon', 'kiso24-pleated'); ?>" id="upload_icon_button">
            <input type="button" class="button button-secondary" value="<?php _e('Remove Icon', 'kiso24-pleated'); ?>" id="remove_icon_button" <?php echo $icon ? '' : 'style="display:none;"'; ?>>
            <p class="description"><?php _e('Select an icon for this term.', 'kiso24-pleated'); ?></p>
        </td>
    </tr>
    <?php
    kiso24_pleated_enqueue_media_scripts();
}

function kiso24_pleated_enqueue_media_scripts() {
    wp_enqueue_media();
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var frame;
        $('#upload_icon_button').click(function(e) {
            e.preventDefault();
            if (frame) {
                frame.open();
                return;
            }
            frame = wp.media({
                title: '<?php _e('Select or Upload Icon', 'kiso24-pleated'); ?>',
                button: {
                    text: '<?php _e('Use this icon', 'kiso24-pleated'); ?>'
                },
                multiple: false
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#taxonomy-icon').val(attachment.url);
                $('#taxonomy-icon-preview').html('<img src="'+attachment.url+'" style="max-width:100px;height:auto;">');
                $('#remove_icon_button').show();
            });
            frame.open();
        });
        $('#remove_icon_button').click(function(e) {
            e.preventDefault();
            $('#taxonomy-icon').val('');
            $('#taxonomy-icon-preview').empty();
            $(this).hide();
        });
    });
    </script>
    <?php
}
function kiso24_pleated_save_taxonomy_icon_field($term_id) {
    if (isset($_POST['taxonomy_icon'])) {
        update_term_meta($term_id, 'taxonomy_icon', esc_url_raw($_POST['taxonomy_icon']));
    }
}

$taxonomies = array('price_group', 'transparency', 'color', 'characteristics');

foreach ($taxonomies as $taxonomy) {
    add_action("{$taxonomy}_add_form_fields", 'kiso24_pleated_add_taxonomy_icon_field', 10, 2);
    add_action("created_{$taxonomy}", 'kiso24_pleated_save_taxonomy_icon_field', 10, 2);
    add_action("{$taxonomy}_edit_form_fields", 'kiso24_pleated_edit_taxonomy_icon_field', 10, 2);
    add_action("edited_{$taxonomy}", 'kiso24_pleated_save_taxonomy_icon_field', 10, 2);
}

// Add icon column to taxonomy list table
function kiso24_pleated_add_taxonomy_icon_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'name') {
            $new_columns[$key] = $value;
            $new_columns['icon'] = __('Icon', 'kiso24-pleated');
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}

// Add content to the custom icon column
function kiso24_pleated_taxonomy_icon_column_content($content, $column_name, $term_id) {
    if ($column_name === 'icon') {
        $icon_url = get_term_meta($term_id, 'taxonomy_icon', true);
        if ($icon_url) {
            $content = '<img src="' . esc_url($icon_url) . '" style="max-width:50px;height:auto;">';
        } else {
            $content = '—';
        }
    }
    return $content;
}

// Apply the new column and content to each taxonomy
foreach ($taxonomies as $taxonomy) {
    add_filter("manage_edit-{$taxonomy}_columns", 'kiso24_pleated_add_taxonomy_icon_column');
    add_filter("manage_{$taxonomy}_custom_column", 'kiso24_pleated_taxonomy_icon_column_content', 10, 3);
}
add_action('wp_enqueue_scripts', 'kiso24_pleated_enqueue_tooltip_scripts');
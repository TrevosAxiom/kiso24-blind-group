<?php
// Add image field to taxonomy add and edit screens
function kiso24_add_image_field_to_taxonomy( $taxonomy ) {
    ?>
    <div class="form-field term-image-wrap">
        <label for="term-image"><?php _e( 'Image', 'kiso24-pleated' ); ?></label>
        <input type="button" class="button button-secondary kiso24-upload-image-button" value="<?php _e( 'Upload/Add Image', 'kiso24-pleated' ); ?>" />
        <input type="hidden" id="term-image" name="term_image" value="">
        <div id="term-image-preview" style="margin-top: 10px;"></div>
    </div>
    <?php
}
add_action( 'price_group_add_form_fields', 'kiso24_add_image_field_to_taxonomy' );
add_action( 'price_group_edit_form_fields', 'kiso24_add_image_field_to_taxonomy' );
add_action( 'transparency_add_form_fields', 'kiso24_add_image_field_to_taxonomy' );
add_action( 'transparency_edit_form_fields', 'kiso24_add_image_field_to_taxonomy' );
add_action( 'color_add_form_fields', 'kiso24_add_image_field_to_taxonomy' );
add_action( 'color_edit_form_fields', 'kiso24_add_image_field_to_taxonomy' );
add_action( 'characteristics_add_form_fields', 'kiso24_add_image_field_to_taxonomy' );
add_action( 'characteristics_edit_form_fields', 'kiso24_add_image_field_to_taxonomy' );

// JavaScript to handle the media uploader
function kiso24_taxonomy_image_scripts() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Media uploader for taxonomy image
            $('.kiso24-upload-image-button').click(function(e) {
                e.preventDefault();
                var button = $(this);
                var imageField = button.siblings('input[name="term_image"]');

                var mediaUploader = wp.media({
                    title: '<?php _e( "Select or Upload Image", "kiso24-pleated" ); ?>',
                    button: {
                        text: '<?php _e( "Use this image", "kiso24-pleated" ); ?>'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    imageField.val(attachment.id);
                    $('#term-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;" />');
                });

                mediaUploader.open();
            });
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'kiso24_taxonomy_image_scripts' );

// Save the image ID to term meta when a term is created or edited
function kiso24_save_taxonomy_image( $term_id ) {
    if ( isset( $_POST['term_image'] ) && '' !== $_POST['term_image'] ) {
        update_term_meta( $term_id, 'term_image', absint( $_POST['term_image'] ) );
    }
}
add_action( 'created_price_group', 'kiso24_save_taxonomy_image' );
add_action( 'edited_price_group', 'kiso24_save_taxonomy_image' );
add_action( 'created_transparency', 'kiso24_save_taxonomy_image' );
add_action( 'edited_transparency', 'kiso24_save_taxonomy_image' );
add_action( 'created_color', 'kiso24_save_taxonomy_image' );
add_action( 'edited_color', 'kiso24_save_taxonomy_image' );
add_action( 'created_characteristics', 'kiso24_save_taxonomy_image' );
add_action( 'edited_characteristics', 'kiso24_save_taxonomy_image' );

// Add image column to taxonomy list table
function kiso24_add_image_column_to_taxonomy( $columns ) {
    $columns['term_image'] = __( 'Image', 'kiso24-pleated' );
    return $columns;
}
add_filter( 'manage_edit-price_group_columns', 'kiso24_add_image_column_to_taxonomy' );
add_filter( 'manage_edit-transparency_columns', 'kiso24_add_image_column_to_taxonomy' );
add_filter( 'manage_edit-color_columns', 'kiso24_add_image_column_to_taxonomy' );
add_filter( 'manage_edit-characteristics_columns', 'kiso24_add_image_column_to_taxonomy' );

// Display the image in the taxonomy list table
function kiso24_display_image_column_content( $content, $column_name, $term_id ) {
    if ( 'term_image' === $column_name ) {
        $image_id = get_term_meta( $term_id, 'term_image', true );
        $image_url = wp_get_attachment_url( $image_id );
        if ( $image_url ) {
            $content = '<img src="' . esc_url( $image_url ) . '" style="width: 50px; height: auto;" />';
        }
    }
    return $content;
}
add_filter( 'manage_price_group_custom_column', 'kiso24_display_image_column_content', 10, 3 );
add_filter( 'manage_transparency_custom_column', 'kiso24_display_image_column_content', 10, 3 );
add_filter( 'manage_color_custom_column', 'kiso24_display_image_column_content', 10, 3 );
add_filter( 'manage_characteristics_custom_column', 'kiso24_display_image_column_content', 10, 3 );
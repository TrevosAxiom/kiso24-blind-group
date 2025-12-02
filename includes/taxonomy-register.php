<?php
// Register Taxonomies: Price Group, Transparency, Color, and Characteristics
function register_kiso24_taxonomies() {

    // Price Group Taxonomy
    register_taxonomy( 'price_group', array( 'kiso24_pleated' ), array(
        'labels' => array(
            'name'          => _x( 'Preisgruppe', 'taxonomy general name' ),
            'singular_name' => _x( 'Price Group', 'taxonomy singular name' ),
        ),
        'hierarchical' => true,
        'show_ui'      => true,
        'show_admin_column' => true,
        'query_var'    => true,
        'rewrite'      => array( 'slug' => 'price-group' ),
    ));

    // Transparency Taxonomy
    register_taxonomy( 'transparency', array( 'kiso24_pleated' ), array(
        'labels' => array(
            'name'          => _x( 'Transparenz', 'taxonomy general name' ),
            'singular_name' => _x( 'Transparency', 'taxonomy singular name' ),
        ),
        'hierarchical' => true,
        'show_ui'      => true,
        'show_admin_column' => true,
        'query_var'    => true,
        'rewrite'      => array( 'slug' => 'transparency' ),
    ));

    // Color Taxonomy (Hierarchical, like categories)
    register_taxonomy( 'color', array( 'kiso24_pleated' ), array(
        'labels' => array(
            'name'          => _x( 'Farbe', 'taxonomy general name' ),
            'singular_name' => _x( 'Color', 'taxonomy singular name' ),
        ),
        'hierarchical' => true,
        'show_ui'      => true,
        'show_admin_column' => true,
        'query_var'    => true,
        'rewrite'      => array( 'slug' => 'color' ),
    ));

    // Characteristics Taxonomy (Non-hierarchical, multi-select)
    register_taxonomy( 'characteristics', array( 'kiso24_pleated' ), array(
        'labels' => array(
            'name'          => _x( 'Eigenschaften', 'taxonomy general name' ),
            'singular_name' => _x( 'Characteristic', 'taxonomy singular name' ),
        ),
        'hierarchical' => false,
        'show_ui'      => true,
        'show_admin_column' => true,
        'query_var'    => true,
        'rewrite'      => array( 'slug' => 'characteristics' ),
    ));
    
    // Blind Category Taxonomy
    register_taxonomy( 'blind_category', array( 'kiso24_pleated' ), array(
        'labels' => array(
            'name'          => _x( 'Blind Categories', 'taxonomy general name' ),
            'singular_name' => _x( 'Blind Category', 'taxonomy singular name' ),
        ),
        'hierarchical' => true,
        'show_ui'      => true,
        'show_admin_column' => true,
        'query_var'    => true,
        'rewrite'      => array( 'slug' => 'blind-category' ),
    ));
}
add_action( 'init', 'register_kiso24_taxonomies' );
?>

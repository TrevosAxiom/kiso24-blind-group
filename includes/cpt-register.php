<?php
// Register Custom Post Type "Kiso24 Pleated"
function register_kiso24_pleated_cpt() {

    $labels = array(
        'name'                  => _x( 'Kiso24 Blinds', 'Post Type General Name', 'kiso24-pleated' ),
        'singular_name'         => _x( 'Kiso24 Blind', 'Post Type Singular Name', 'kiso24-pleated' ),
        'menu_name'             => __( 'Kiso24 Blinds', 'kiso24-pleated' ),
        'name_admin_bar'        => __( 'Kiso24 Blinds', 'kiso24-pleated' ),
    );

    $args = array(
        'label'                 => __( 'Kiso24 Blinds', 'kiso24-pleated' ),
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-welcome-view-site',
        'capability_type'       => 'post',
        'has_archive'           => true,
        'rewrite'               => array( 'slug' => 'kiso24-pleated' ),
    );

    register_post_type( 'kiso24_pleated', $args );
}
add_action( 'init', 'register_kiso24_pleated_cpt' );

<?php 
function bc_location_register_location_taxonomy() {
    $labels = array(
        'name' => __( 'Categories', BCLOCATIONDOMAIN ),
        'singular_name' => __( 'Category', BCLOCATIONDOMAIN ),
        'add_new_item' => __( 'Add New Category', BCLOCATIONDOMAIN ),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_admin_column' => true,
        'show_in_quick_edit' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => array( 'hierarchical' => true, 'has_front' => true )
    );

    $post_types = array( 'bc_locations');

    register_taxonomy( 'bc_location_category', $post_types, $args );
}


<?php
function bc_location_register_location_type() {
    $labels = array( 
        'name' => __( 'Locations', BCLOCATIONDOMAIN ),
        'singular_name' => __( 'Location', BCLOCATIONDOMAIN ),
        'archives' => __( 'Locations Calendar', BCLOCATIONDOMAIN ),
        'add_new' => __( 'Add New Location', BCLOCATIONDOMAIN ),
        'add_new_item' => __( 'Add New Location', BCLOCATIONDOMAIN ),
    );

    $args = array( 
        'labels' => $labels,
        'public' => true,
        'has_archive' => 'locations',
        'rewrite' => array( 'has_front' => true ),
        'menu_icon' => 'dashicons-location',
        'supports' => false,
        'show_in_rest' => true,
    );

    register_post_type( 'bc_locations', $args );
}


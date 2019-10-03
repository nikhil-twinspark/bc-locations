<?php
function bc_location_register_location_type() {
    $labels = array( 
        'name' => __( 'Locations', BCDOMAIN ),
        'singular_name' => __( 'Location', BCDOMAIN ),
        'archives' => __( 'Locations Calendar', BCDOMAIN ),
        'add_new' => __( 'Add New Location', BCDOMAIN ),
        'add_new_item' => __( 'Add New Location', BCDOMAIN ),
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


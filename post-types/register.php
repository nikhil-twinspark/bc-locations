<?php
function bc_location_register_location_type() {
    $labels = array( 
        'name' => __( 'Geotargeting', BCLOCATIONDOMAIN ),
        'singular_name' => __( 'Location', BCLOCATIONDOMAIN ),
        'archives' => __( 'Locations', BCLOCATIONDOMAIN ),
        'add_new' => __( 'Add New', BCLOCATIONDOMAIN ),
        'add_new_item' => __( 'Geotargeting - Add New', BCLOCATIONDOMAIN ),
    );

    $args = array( 
        'labels' => $labels,
        'public' => true,
        'has_archive' => 'locations',
        'rewrite' => array( 'has_front' => false ,'slug' => 'geolocation'),
        'menu_icon' => 'dashicons-location',
        'supports'  => array('title','editor','thumbnail'),
        'show_in_rest' => true,
    );

    register_post_type( 'bc_locations', $args );
}


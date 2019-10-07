<?php
/**
 * Plugin Name:       BC Locations
 * Plugin URI:        https://github.com/nikhil-twinspark/locations
 * Description:       A simple plugin for creating custom post types and taxonomies related to a locations.
 * Version:           1.0.0
 * Author:            Blue Corona
 * Author URI:        #
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bc-locations
 * Domain Path:       /languages
 */

 if ( ! defined( 'WPINC' ) ) {
     die;
 }

define( 'BC_LOCATION_VERSION', '1.0.0' );
define( 'BCLOCATIONDOMAIN', 'bc-locations' );
define( 'BCLOCATIONPATH', plugin_dir_path( __FILE__ ) );

require_once( BCLOCATIONPATH . '/post-types/register.php' );
add_action( 'init', 'bc_location_register_location_type' );

require_once( BCLOCATIONPATH . '/taxonomies/register.php' );
add_action( 'init', 'bc_location_register_location_taxonomy' );

require_once( BCLOCATIONPATH . '/custom-fields/register.php' );

function bc_location_rewrite_flush() {
    bc_location_register_location_type();
    bc_location_register_location_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bc_location_rewrite_flush' );

// plugin uninstallation
register_uninstall_hook( BCLOCATIONPATH, 'bc_location_uninstall' );
function bc_location_uninstall() {
    // Removes the directory not the data
}

// Convert location taxonomy multiselect to radio button 
add_action('admin_footer', 'bc_location_check_to_radio');
function bc_location_check_to_radio(){
    $current_screen = get_current_screen();
    if ( $current_screen->post_type == 'bc_locations') {
    echo '<script type="text/javascript">jQuery("#bc_location_categorychecklist-pop input, #bc_location_categorychecklist input, .cat-checklist input").each(function(){this.type="radio"});</script>';
    }
}


// Add Conditionally css & js for specific pages
add_action('admin_enqueue_scripts', 'bc_location_include_css_js');
function bc_location_include_css_js($hook){
    $current_screen = get_current_screen();
    if ( $current_screen->post_type == 'bc_locations') {
        wp_register_style('bc-location-plugin-css', plugins_url('assests/css/bootstrap.min.css', __FILE__), array(), '1.0.0', 'all');
        wp_enqueue_style('bc-location-plugin-css');
    } 
}


/***
Creates menu if not exisits from custom taxonomy location category
Reference Link  https://developer.wordpress.org/reference/functions/get_the_category_by_id/
https://clicknathan.com/web-design/automatically-create-wordpress-navigation-menu-items/
https://raghunathgurjar.wordpress.com/2015/08/26/how-perform-action-during-addeditdelete-category-using-hooks-wordpress/
**/
add_action('create_term','bc_location_save_custom_taxonomy');
function bc_location_save_custom_taxonomy($term_id) {
    $get_taxonomy = $_POST['taxonomy'];
    if($get_taxonomy == 'bc_location_category'){
        $name = $_POST['tag-name'];
        $menu_exists = wp_get_nav_menu_object($name);
        // if menu not exists create new menu 
        if( !$menu_exists){
            $menu_id = wp_create_nav_menu($name);
            $menu = get_term_by( 'name', $name, 'nav_menu' );
        }
    }
}

/*add_action( 'edit_term', 'pdf_save_magazine', 10, 3 );
function pdf_save_magazine($term_id, $tt_id, $taxonomy) {

    echo "<pre>";
    // print_r($term_id);
    // die();
    // print_r($taxonomy); die();
   $term = get_term($term_id, $taxonomy);
   // print_r($term->slug);//die('ss');
   // echo "<br>";
   // print_r($term->name);
   // echo "<br>";
   // print_r($tt_id);
   // echo "<br>";
   // // print_r($taxonomy);
   // echo "<br>";
   // $term_slug = $term->slug;
   // die();
    $slug = $term->slug;
    $name = $term->name;
    $menus = get_terms(['hide_empty' => false,'taxonomy'=>'nav_menu']);
    // print_r($menus); die();
    foreach ( $menus as $key => $value) {
        print_r($value); die('ss');
        $data = (array) $value;
        if (in_array($slug, [$data['slug']])) {
            echo 'in array';
            echo "<br>";
            echo $slug;
            echo "<br>";
            echo $name;

            $menu_data = [
                'menu-item-title' => $name,
                // 'menu-item-object-id' => $post->ID,
                // 'menu-item-object' => 'post',
                // 'menu-item-status' => 'publish',
                // 'menu-item-type' => 'post_type',
            ];
            // print_r($menu_data);die('ss');
            // $res = wp_update_nav_menu_item($value->term_id,$value->term_id, $menu_data);
            // print_r($res);die('ss');
        }
    }
    // $menu_exists = wp_get_nav_menu_object($name);

    // die();
}*/


/**
* Creates a select field with US states as values for locations
*/

// add & remove menu items when the status is updated
add_action('transition_post_status', 'bc_location_new_post_create_menu_as_per_location_category', 10, 3);
// Listen for publishing of a new post
function bc_location_new_post_create_menu_as_per_location_category($new_status, $old_status, $post){
    if($post->post_type !== 'bc_locations' || empty($post) ||in_array($post, [null, false, ''])){
        return;
    }
    $post_title = $post->post_title;
    $get_category = get_the_terms($post->ID,'bc_location_category');
    if(!$get_category){
        return;
    }
    $name = $get_category[0]->name;
    $menu_exists = wp_get_nav_menu_object($name);
    if(!$menu_exists){
        return;
    }
    $menu = get_term_by( 'name', $name, 'nav_menu' );
    $menu_data = [
        'menu-item-title' => $post_title,
        'menu-item-object-id' => $post->ID,
        'menu-item-object' => 'post',
        'menu-item-status' => 'publish',
        'menu-item-type' => 'post_type',
    ];
    $menu_items = wp_get_nav_menu_items($menu);
    $found_item = false;
    $db_id = null;
    foreach ($menu_items as $value) {
        if($value->object_id == $post->ID){
            $found_item = true;
            $db_id = $value->db_id;
            break;
        }
    }
    if(($found_item && $new_status == 'publish') || (!$found_item && $new_status != 'publish')){
        return;
    }
    if($new_status !== 'publish') {
        wp_delete_post($db_id);
        return;
    }
    wp_update_nav_menu_item($menu->term_id, $db_id, $menu_data);
}

/** ADMIN COLUMN - HEADERS*/
add_filter('manage_edit-bc_locations_columns', 'add_new_locations_columns');
function add_new_locations_columns($concerts_columns) {
    $new_columns['cb'] = '<input type="checkbox" />';
    $new_columns['title'] = _x('Title', 'column name');
    $new_columns['city'] = __('City');
    $new_columns['state'] = __('State');
    $new_columns['category'] = __('Category');
    $new_columns['date_custom'] = __('Date');
    $new_columns['status'] = __('Status');
    return $new_columns;
}

/** ADMIN COLUMN - CONTENT*/
add_action('manage_bc_locations_posts_custom_column', 'manage_locations_columns', 10, 2);
function manage_locations_columns($column_name, $id) {
    global $post;
    switch ($column_name) {
        case 'title':
            echo $get_title = get_post_meta( $post->ID , 'custom_title' , true );
            break;
        case 'city':
            echo get_post_meta( $post->ID , 'custom_city' , true );
            break;
        case 'state':
            echo get_post_meta( $post->ID , 'custom_state' , true );
            break;
        case 'category':
            $list_tax =  get_the_terms( $post->ID , 'bc_location_category' , true );
            if(isset($list_tax) && !empty($list_tax)){
                foreach ($list_tax as $key => $value) {
                    echo $value->name;
                }
            }
            break;
        case 'date_custom':
            echo get_the_date('m-d-Y'); 
            break;
        case 'status':
            $status = $post->post_status;
            if($status == 'publish'){
                echo 'Active';
            }else{
                echo ucfirst($status);
            }
            break;
        default:
            break;
    } // end switch
}

/*
 * ADMIN COLUMN - SORTING - MAKE HEADERS SORTABLE
 * https://gist.github.com/906872
 */
add_filter("manage_edit-bc_locations_sortable_columns", 'locations_sort');
function locations_sort($columns) {
    $custom = array(
        'state' => 'state',
        'city'  => 'city',
        'category'  => 'category',
        'date_custom'   => 'date_custom',
        'status'    => 'status',
    );
    return wp_parse_args($custom, $columns);
}

// remove menu items if category is changed while editing the post
add_action('set_object_terms','bc_location_delete_menu_item_if_category_changed',10,6);
function bc_location_delete_menu_item_if_category_changed($post_id, $new_terms, $tt_ids, $taxonomy, $append=true, $old){
    if($taxonomy == 'bc_location_category'){
        if($old[0] != $new_terms[1]){
            $get_category = get_term($old[0]);
            if(!$get_category){
                return;
            }
            $name = $get_category->name;
            $menu_exists = wp_get_nav_menu_object($name);
            if(!$menu_exists){
                return;
            }
            $menu = get_term_by( 'name', $name, 'nav_menu' );
            $menu_items = wp_get_nav_menu_items($menu);
            $found_item = false;
            $db_id = null;
            foreach ($menu_items as $value) {
                if($value->post_id == $post->ID){
                    $found_item = true;
                    $db_id = $value->db_id;
                    break;
                }
            }
            if($found_item){
                wp_delete_post($db_id);
                return;
            }
        }
    }
}

// Admin notice for permalink before adding
function bc_location_general_admin_notice(){
    global $pagenow;
    global $post;
    if ( $pagenow == 'post-new.php' && $post->post_type == "bc_locations" ) {
         echo '<div class="notice notice-warning is-dismissible">
             <p>Save draft to modify permalink</p>
         </div>';
    }
}
add_action('admin_notices', 'bc_location_general_admin_notice');

function bc_location_us_state_list(){
$states = array(
    'AL' => 'Alabama',
    'AK' => 'Alaska',
    'AZ' => 'Arizona',
    'AR' => 'Arkansas',
    'CA' => 'California',
    'CO' => 'Colorado',
    'CT' => 'Connecticut',
    'DE' => 'Delaware',
    'DC' => 'District Of Columbia',
    'FL' => 'Florida',
    'GA' => 'Georgia',
    'HI' => 'Hawaii',
    'ID' => 'Idaho',
    'IL' => 'Illinois',
    'IN' => 'Indiana',
    'IA' => 'Iowa',
    'KS' => 'Kansas',
    'KY' => 'Kentucky',
    'LA' => 'Louisiana',
    'ME' => 'Maine',
    'MD' => 'Maryland',
    'MA' => 'Massachusetts',
    'MI' => 'Michigan',
    'MN' => 'Minnesota',
    'MS' => 'Mississippi',
    'MO' => 'Missouri',
    'MT' => 'Montana',
    'NE' => 'Nebraska',
    'NV' => 'Nevada',
    'NH' => 'New Hampshire',
    'NJ' => 'New Jersey',
    'NM' => 'New Mexico',
    'NY' => 'New York',
    'NC' => 'North Carolina',
    'ND' => 'North Dakota',
    'OH' => 'Ohio',
    'OK' => 'Oklahoma',
    'OR' => 'Oregon',
    'PA' => 'Pennsylvania',
    'RI' => 'Rhode Island',
    'SC' => 'South Carolina',
    'SD' => 'South Dakota',
    'TN' => 'Tennessee',
    'TX' => 'Texas',
    'UT' => 'Utah',
    'VT' => 'Vermont',
    'VA' => 'Virginia',
    'WA' => 'Washington',
    'WV' => 'West Virginia',
    'WI' => 'Wisconsin',
    'WY' => 'Wyoming'
    );
return $states;
}

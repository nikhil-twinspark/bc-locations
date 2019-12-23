<?php
/**
 * Plugin Name:       BC Geotargeting
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

// Remove parent dropdown from taxonomy
add_action( 'admin_head-edit-tags.php', 'bc_location_remove_tax_parent_dropdown' );
add_action( 'admin_head-term.php', 'bc_location_remove_tax_parent_dropdown' );
add_action( 'admin_head-post.php', 'bc_location_remove_tax_parent_dropdown' );
add_action( 'admin_head-post-new.php', 'bc_location_remove_tax_parent_dropdown' ); 
function bc_location_remove_tax_parent_dropdown() {
    $screen = get_current_screen();
    if ($screen->taxonomy == 'bc_location_category') {
        if ($screen->base == 'edit-tags') {
            $parent = "$('label[for=parent]').parent()";
        } elseif ($screen->base == 'term') {
            $parent = "$('label[for=parent]').parent().parent()";
        }
    } elseif ($screen->post_type == 'post') {
        $parent = "$('#newcategory_parent')";
    } else {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {<?php echo $parent; ?>.remove();});
    jQuery('table tr').find('td:eq(1),th:eq(1)').remove();
    </script>
<?php 
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

// update the menu while editing the category
add_action( 'edited_term', 'bc_location_category_after_save', 10, 3 );
function bc_location_category_after_save($term_id, $tt_id, $taxonomy) {
    if($taxonomy != 'bc_location_category'){
        return;
    }
    $term = get_term($term_id, $taxonomy);
    $slug = $term->slug;
    $name = $term->name;
    $menu = wp_get_nav_menu_object($slug);
    if(!$menu){
        return;
    }
    $menu = (array) $menu;
    $menu = array_merge($menu, ['name' => $name]);
    wp_update_term($menu['term_id'], 'nav_menu', $menu);
}

/*
delete the menu when taxonomy is deleted 
@$term_id integer id of the term is gonna be deleted
@$term_taxonomy_id integer
@$deleted_term object term object (object of class stdClass)
*/    
add_action( "delete_bc_location_category",'bc_location_delete_taxonomy_meta', 10,3 );
function bc_location_delete_taxonomy_meta($term_id, $term_taxonomy_id, $deleted_term ){
    if($deleted_term->taxonomy != 'bc_location_category'){
        return;
    }
    $slug = $deleted_term->slug;
    $name = $deleted_term->name;
    $menu = wp_get_nav_menu_object($slug);
    if(!$menu){
        return;
    }
    $menu = (array) $menu;
    $menu = array_merge($menu, ['name' => $name]);
    wp_delete_term($menu['term_id'], 'nav_menu', $menu);
}

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
function add_new_locations_columns($columns) {
    return array(
                'cb' => $columns['cb'],
                'title' => $columns['title'],
                'city' => __('City'),
                'state' => __('State'),
                'taxonomy-bc_location_category' => 'Categories',
                'date' => 'Date',
            ); 
}

/** ADMIN COLUMN - CONTENT*/
add_action('manage_bc_locations_posts_custom_column', 'manage_locations_columns', 10, 2);
function manage_locations_columns($column_name, $id) {
    global $post;
    switch ($column_name) {
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
                    echo $value->name.",";
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


// Remove description text field from category
function remove_description_form() {
    echo "<style> .term-description-wrap{display:none;}</style>";
}
add_action( "bc_location_category_edit_form", 'remove_description_form');
add_action( "bc_location_category_add_form", 'remove_description_form');


add_shortcode( 'bc-geotargeting', 'bc_location_shortcode' );
function bc_location_shortcode ( $atts ) {
    $categoryIds = null;
    $args  = array( 'post_type' => 'bc_locations', 'posts_per_page' => -1, 'order'=> 'ASC','post_status'  => 'publish');
    if(isset($atts['category_id'])) {
        $categoryIds = explode(',', $atts['category_id']);
        $taxArgs = array(
                        array(
                            'taxonomy' => 'bc_location_category', //double check your taxonomy name in you dd 
                            'field'    => 'id',
                            'terms'    => $categoryIds,
                        ),
                        );
        $args['tax_query'] = $taxArgs;
    }
    
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) :
        if(isset($atts['withrowwrapper']) == 1) { echo "<div class='row bc_geolocation_list'>"; }
        while($query->have_posts()) : $query->the_post();
        ?>
            <div class="col-md-3 bc_geolocation_list_item">
                <h5 class="nav-item mr-5">
                    <a class="nav-link" href="<?php the_permalink(); ?>"><?php the_title(); ?> </a>
                </h5>
            </div>
        <?php
        endwhile; 
        if(isset($atts['withrowwrapper']) == 1) 
            { echo "</div>"; }
        wp_reset_query();
    endif;
}

// Admin notice for displaying shortcode on index page
add_action('admin_notices', 'bc_locations_general_admin_notice');
function bc_locations_general_admin_notice(){
    global $pagenow;
    global $post;
    if ($pagenow == 'edit.php' &&  (isset($post->post_type) ? $post->post_type : null) == 'bc_locations') { 
     echo '<div class="notice notice-success is-dismissible">
            <p>Shortcode [bc-geotargeting]</p>
         </div>';
    }
}


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
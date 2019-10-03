<?php
/**
 * Create the metabox
 * @link https://developer.wordpress.org/reference/functions/add_meta_box/
 */
function bc_location_create_metabox() {

    // Can only be used on a single post type (ie. page or post or a custom post type).
    // Must be repeated for each post type you want the metabox to appear on.
    add_meta_box(
        'bc_location_metabox', // Metabox ID
        ' ', // Title to display
        'bc_location_metabox', // Function to call that contains the metabox content
        'bc_locations', // Post type to display metabox on
        'test', // Where to put it (normal = main colum, side = sidebar, etc.)
        'high' // Priority relative to other metaboxes
    );
}
add_action( 'add_meta_boxes', 'bc_location_create_metabox' );



function bc_location_run_after_title_meta_boxes() {
    global $post, $wp_meta_boxes;
    # Output the `below_title` meta boxes:
    do_meta_boxes( get_current_screen(), 'test', $post );
    // unset($wp_meta_boxes['bc_locations']['test']);
    unset($wp_meta_boxes['bc_locations']['test']);
}
add_action( 'edit_form_after_title', 'bc_location_run_after_title_meta_boxes' );


/**
 * Render the metabox markup
 * This is the function called in `bc_location_create_metabox()`
 */
function bc_location_metabox() {
    global $post; // Get the current post data
    $title = get_post_meta( $post->ID, 'custom_title', true ); // Get the saved values
    $city = get_post_meta( $post->ID, 'custom_city', true ); // Get the saved values
    $state = get_post_meta( $post->ID, 'custom_state', true ); // Get the saved values
    // print_r($state); die('ss');
    $isChecked = get_post_meta( $post->ID, 'custom_coupon', true ); // Get the saved values
    ?>

    <div class="container">
      <div class="row">
        <div class="col-12">
            <div class="form-group">
                <label><?php _e( 'Title', 'custom_title' );?></label>
                <input type="text" name="bc_location_title_metabox" id="bc_location_title_metabox" value="<?php echo esc_attr( $title ); ?>" class="form-control" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?php _e( 'City', 'custom_city' );?></label>
                <input type="text" name="bc_location_city_metabox" id="bc_location_city_metabox" value="<?php echo esc_attr( $city ); ?>" class="form-control" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?php _e( 'State', 'custom_state' );?></label>
                <?php $list_states = bc_location_us_state_list();?>
                <select style="height:38px;" class="form-control m-b" name="bc_location_state_metabox" id="bc_location_state_metabox" required>
                    <option>Select State</option>
                    <?php foreach ($list_states as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?= $key==$state ? 'selected="selected"':'' ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <input type="hidden" name="bc_location_showcoupon_metabox" value="false">
                <input type="checkbox" name="bc_location_showcoupon_metabox" value="true" <?php echo  ($isChecked != 'false' ? 'checked': '')?>> Show sidebar Coupons?
            </div>
        </div>
        <!-- <div class="col-12">
            <div class="updated notice">
            <p>Something has been updated, awesome</p>
            </div>
        </div> -->

      </div>
    </div>
    
    <?php
    // Security field
    // This validates that submission came from the
    // actual dashboard and not the front end or
    // a remote server.
    wp_nonce_field( 'bc_location_form_metabox_nonce', 'bc_location_form_metabox_process' );
}

/**
 * Save the metabox
 * @param  Number $post_id The post ID
 * @param  Array  $post    The post data
 */
function bc_location_save_metabox( $post_id, $post ) {
    /*echo "<pre>";
    print_r($post);
    print_r($_POST);
    die('ss');*/
    // Verify that our security field exists. If not, bail.
    if ( !isset( $_POST['bc_location_form_metabox_process'] ) ) return;
    // Verify data came from edit/dashboard screen
    if ( !wp_verify_nonce( $_POST['bc_location_form_metabox_process'], 'bc_location_form_metabox_nonce' ) ) {
        return $post->ID;
    }
    // Verify user has permission to edit post
    if ( !current_user_can( 'edit_post', $post->ID )) {
        return $post->ID;
    }
    // Check that our custom fields are being passed along
    // This is the `name` value array. We can grab all
    // of the fields and their values at once.
    if ( !isset( $_POST['bc_location_title_metabox'] ) ) {
        return $post->ID;
    }

    if ( !isset( $_POST['bc_location_city_metabox'] ) ) {
        return $post->ID;
    }
    if ( !isset( $_POST['bc_location_state_metabox'] ) ) {
        return $post->ID;
    }
    if ( !isset( $_POST['bc_location_showcoupon_metabox'] ) ) {
        return $post->ID;
    }
    
    
    /**
     * Sanitize the submitted data
     * This keeps malicious code out of our database.
     * `wp_filter_post_kses` strips our dangerous server values
     * and allows through anything you can include a post.
     */
    $sanitizedtitle = wp_filter_post_kses( $_POST['bc_location_title_metabox'] );
    // print_r($sanitizedtitle); die('ss');
    $sanitizedcity = wp_filter_post_kses( $_POST['bc_location_city_metabox'] );
    $sanitizedstate = wp_filter_post_kses( $_POST['bc_location_state_metabox'] );
    $sanitizedcoupon = wp_filter_post_kses( $_POST['bc_location_showcoupon_metabox'] );
    // Save our submissions to the database

    update_post_meta( $post->ID, 'custom_title', $sanitizedtitle );
    update_post_meta( $post->ID, 'custom_city', $sanitizedcity );
    update_post_meta( $post->ID, 'custom_state', $sanitizedstate );
    update_post_meta( $post->ID, 'custom_coupon', $sanitizedcoupon );


}
add_action( 'save_post', 'bc_location_save_metabox', 1, 2 );



 //This function initializes the meta box.
function bc_location_custom_editor_meta_box() {    
           add_meta_box (
             'custom-editor',
             ' ',
             'custom_editor',
             'bc_locations'
           );
}
//Displaying the meta box
function custom_editor($post) {          
    // echo "<h3>Add Your Content Here</h3>";
    $content = get_post_meta($post->ID, 'custom_editor', true);
    //This function adds the WYSIWYG Editor
    wp_editor (
    $content ,
    'custom_editor',
    array ( "media_buttons" => true )
    );
}
//This function saves the data you put in the meta box
function custom_editor_save_postdata($post_id) {
    if( isset( $_POST['custom_editor_nonce'] ) && isset( $_POST['bc_locations'] ) ) {
        //Not save if the user hasn't submitted changes
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
        }
        // Verifying whether input is coming from the proper form
        if ( ! wp_verify_nonce ( $_POST['custom_editor_nonce'] ) ) {
        return;
        }
        // Making sure the user has permission
        if( 'post' == $_POST['bc_locations'] ) {
               if( ! current_user_can( 'edit_post', $post_id ) ) {
                    return;
               }
        }
    }
    if (!empty($_POST['custom_editor'])) {
        $data = $_POST['custom_editor'];
        update_post_meta($post_id, 'custom_editor', $data);
    }
}
add_action('save_post', 'custom_editor_save_postdata');
add_action('admin_init', 'bc_location_custom_editor_meta_box');

/*function my_function( $post_id,$post ){
    // print_r($post);die();
    // echo "<pre>";
    // print_r($_POST);die();
    if ( ! wp_is_post_revision( $post_id ) ){
    
        // unhook this function so it doesn't loop infinitely
        remove_action('save_post', 'my_function');
        $sanitizedtitle = wp_filter_post_kses( $_POST['bc_location_title_metabox'] );
        $my_post = array(
              'ID'           => $post_id,
              'post_title'   => $sanitizedtitle,
          );

        // update the post, which calls save_post again
        wp_update_post( $my_post );

        // re-hook this function
        add_action('save_post', 'my_function');
    }
}
add_action('save_post', 'my_function', 1, 2);*/
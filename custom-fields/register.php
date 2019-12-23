<?php
add_action( 'add_meta_boxes', 'bc_location_create_metabox' );
function bc_location_create_metabox() {
    add_meta_box(
        'bc_location_metabox',
        'Location', // Title to display
        'bc_location_metabox', // Function to call that contains the metabox content
        'bc_locations', // Post type to display metabox on
        'test', // Where to put it (normal = main colum, side = sidebar, etc.)
        'high' // Priority relative to other metaboxes
    );
}

add_action( 'edit_form_after_title', 'bc_location_run_after_title_meta_boxes' );
function bc_location_run_after_title_meta_boxes() {
    global $post, $wp_meta_boxes;
    # Output the `below_title` meta boxes:
    do_meta_boxes( get_current_screen(), 'test', $post );
    unset($wp_meta_boxes['bc_locations']['test']);
}

function bc_location_metabox() {
    global $post;
    $city = get_post_meta( $post->ID, 'custom_city', true );
    $state = get_post_meta( $post->ID, 'custom_state', true );
    ?>
    <div>
        <div style="float: right;">
            <label><?php _e( 'State', 'custom_state' );?></label>
            <?php $list_states = bc_location_us_state_list();?>
            <select name="bc_location_state_metabox" id="bc_location_state_metabox" required>
                <option>Select State</option>
                <?php foreach ($list_states as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?= $key==$state ? 'selected="selected"':'' ?>>
                        <?php echo $value; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><?php _e( 'City', 'custom_city' );?></label>
            <input type="text" name="bc_location_city_metabox" id="bc_location_city_metabox" value="<?php echo esc_attr( $city ); ?>" required>
        </div>
    </div>
 <?php wp_nonce_field( 'bc_location_form_metabox_nonce', 'bc_location_form_metabox_process' );
}

add_action( 'save_post', 'bc_location_save_metabox', 1, 2 );
function bc_location_save_metabox( $post_id, $post ) {
    if ( !isset( $_POST['bc_location_form_metabox_process'] ) ) return;
    if ( !wp_verify_nonce( $_POST['bc_location_form_metabox_process'], 'bc_location_form_metabox_nonce' ) ) {
        return $post->ID;
    }
    if ( !current_user_can( 'edit_post', $post->ID )) { return $post->ID;}
    if ( !isset( $_POST['bc_location_city_metabox'] ) ) { return $post->ID;}
    if ( !isset( $_POST['bc_location_state_metabox'] ) ) { return $post->ID;}
    $sanitizedcity = wp_filter_post_kses( $_POST['bc_location_city_metabox'] );
    $sanitizedstate = wp_filter_post_kses( $_POST['bc_location_state_metabox'] );
    update_post_meta( $post->ID, 'custom_city', $sanitizedcity );
    update_post_meta( $post->ID, 'custom_state', $sanitizedstate );
}

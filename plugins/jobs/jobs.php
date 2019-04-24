<?php
/**
 * @package Job_Opportunities
 * @version 0.1
 */
/*
Plugin Name: Jobs
Plugin URI: http://andrewspittle.net/projects/reading-list
Description: Track and display job opportunities.
Author: Andrew Spittle; Adapted by Sarah Sidlow
Version: 0.1
Author URI: http://andrewspittle.net/
*/

/**
 * Start up our custom post type and hook it in to the init action when that fires.
 *
 * @since Reading List 0.1
 */

add_action( 'init', 'create_post_type' );

function create_post_type() {
	$labels = array(
		'name' 							=> __( 'Jobs', 'jobboard' ),
		'singular_name' 				=> __( 'Job', 'jobboard' ),
		'search_items'					=> __( 'Search Jobs', 'jobboard' ),
		'all_items'						=> __( 'All Jobs', 'jobboard' ),
		'edit_item'						=> __( 'Edit Job', 'jobboard' ),
		'update_item' 					=> __( 'Update Job', 'jobboard' ),
		'add_new_item' 					=> __( 'Add New Job', 'jobboard' ),
		'new_item_name' 				=> __( 'New Job', 'jobboard' ),
		'menu_name' 					=> __( 'Jobs', 'jobboard' ),
	);
	
	$args = array (
		'labels' 		=> $labels,
		'public' 		=> true,
		'publicly_queryable' => true,
		'menu_position' => 20,
		'has_archive' 	=> true,
		'rewrite'		=> array( 'slug' => 'jobs' ),
		'supports' 		=> array( 'title', 'thumbnail', 'editor' )
	);
	register_post_type( 'jobs', $args );
}


/**
 * Add custom meta box for tracking the job's employer.
 *
 * Props to Justin Tadlock: http://wp.smashingmagazine.com/2011/10/04/create-custom-post-meta-boxes-wordpress/
 *
 * @since Reading List 1.0
 *
*/

/* Fire our meta box setup function on the editor screen. */
add_action( 'load-post.php', 'rl_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'rl_post_meta_boxes_setup' );

/* Our meta box set up function. */
function rl_post_meta_boxes_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'rl_add_post_meta_boxes' );
	
	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', 'rl_employer_save_meta', 10, 2 );
}

/* Create one or more meta boxes to be displayed on the post editor screen. */
function rl_add_post_meta_boxes() {

	add_meta_box(
		'rl-job-info',								// Unique ID
		esc_html__( 'Job Information', 'example' ),		// Title
		'rl_employer_meta_box',					// Callback function
		'jobs',								// Add metabox to our custom post type
		'side',									// Context
		'default'								// Priority
	);
}

/* Display the post meta box. */
function rl_employer_meta_box( $object, $box ) { ?>

	<?php wp_nonce_field( basename( __FILE__ ), 'rl_employer_nonce' ); ?>

	<p class="howto"><label for="rl-employer"><?php _e( "Add the employer here.", 'example' ); ?></label></p>
	<p><input class="widefat" type="text" name="rl-employer" id="rl-employer" value="<?php echo esc_attr( get_post_meta( $object->ID, 'rl_employer', true ) ); ?>" size="30" /></p>

<?php }

/* Save the meta box's data. */
function rl_employer_save_meta( $post_id, $post ) {

	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['rl_employer_nonce'] ) || !wp_verify_nonce( $_POST['rl_employer_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	/* Get the posted data and sanitize it for use as an HTML class. */
	$new_meta_value = ( isset( $_POST['rl-employer'] ) ? sanitize_html_class( $_POST['rl-employer'] ) : '' );

	/* Get the meta key. */
	$meta_key = 'rl_employer';

	/* Get the meta value of the custom field key. */
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	/* If a new meta value was added and there was no previous value, add it. */
	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );

	/* If the new meta value does not match the old value, update it. */
	elseif ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $new_meta_value );

	/* If there is no new meta value but an old value exists, delete it. */
	elseif ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, $meta_key, $meta_value );
} 



?>
<?php
/**
 * @package Event_List
 * @version 0.1
 */
/*
Plugin Name: Events
Plugin URI: http://andrewspittle.net/projects/reading-list
Description: Create custom event posts.
Author: Andrew Spittle Adapted by Sarah Sidlow
Version: 0.1
Author URI: http://andrewspittle.net/
*/

/**
 * Start up our custom post type and hook it in to the init action when that fires.
 *
 * @since Reading List 0.1
 */

add_action( 'init', 'ev_create_post_type' );

function ev_create_post_type() {
	$labels = array(
		'name' 							=> __( 'Events', 'eventlist' ),
		'singular_name' 				=> __( 'Event', 'eventlist' ),
		'search_items'					=> __( 'Search Events', 'eventlist' ),
		'all_items'						=> __( 'All Events', 'eventlist' ),
		'edit_item'						=> __( 'Edit Event', 'eventlist' ),
		'update_item' 					=> __( 'Update Event', 'eventlist' ),
		'add_new_item' 					=> __( 'Add New Event', 'eventlist' ),
		'new_item_name' 				=> __( 'New Event', 'eventlist' ),
		'menu_name' 					=> __( 'Events', 'eventlist' ),
	);
	
	$args = array (
		'labels' 		=> $labels,
		'public' 		=> true,
		'menu_position' => 20,
		'has_archive' 	=> true,
		'rewrite'		=> array( 'slug' => 'events' ),
		'supports' 		=> array( 'title', 'thumbnail', 'editor' )
	);
	register_post_type( 'rl_event', $args );
}

/**
 * Create our custom taxonomies. One hierarchical one for genres and a flat one for authors.
 *
 * @since Reading List 0.1
 */

/* Hook in to the init action and call rl_create_event_taxonomies when it fires. */
add_action( 'init', 'ev_create_event_taxonomies', 0 );

function ev_create_event_taxonomies() {
	// Add new taxonomy, keep it non-hierarchical (like tags)
	$labels = array(
		'name' 							=> __( 'Keywords', 'readinglist' ),
		'singular_name' 				=> __( 'Keyword', 'readinglist' ),
		'search_items' 					=> __( 'Search Keywords', 'readinglist' ),
		'all_items' 					=> __( 'All Keywords', 'readinglist' ),
		'edit_item' 					=> __( 'Edit Keyword', 'readinglist' ), 
		'update_item' 					=> __( 'Update Keyword', 'readinglist' ),
		'add_new_item' 					=> __( 'Add New Keyword', 'readinglist' ),
		'new_item_name' 				=> __( 'New Keyword', 'readinglist' ),
		'separate_items_with_commas' 	=> __( 'Separate keywords with commas', 'readinglist' ),
		'choose_from_most_used' 		=> __( 'Choose from the most used keywords', 'readinglist' ),
		'menu_name' 					=> __( 'Keywords', 'readinglist' ),
	); 	
		
	register_taxonomy( 'keywords', array( 'rl_event' ), array(
		'hierarchical' 		=> false,
		'labels' 			=> $labels,
		'show_ui' 			=> true,
		'show_admin_column' => true,
		'query_var' 		=> true,
		'rewrite' 			=> array( 'slug' => 'keywords' ),
	));
}

/**
 * Add custom meta box for tracking the page numbers of the book.
 *
 * Props to Justin Tadlock: http://wp.smashingmagazine.com/2011/10/04/create-custom-post-meta-boxes-wordpress/
 *
 * @since Reading List 1.0
 *
*/

/* Fire our meta box setup function on the editor screen. */
add_action( 'load-post.php', 'ev_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'ev_post_meta_boxes_setup' );

/* Our meta box set up function. */
function ev_post_meta_boxes_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'ev_add_post_meta_boxes' );
	
	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', 'ev_date_save_meta', 10, 2 );
}

/* Create one or more meta boxes to be displayed on the post editor screen. */
function ev_add_post_meta_boxes() {

	add_meta_box(
		'ev-date',								// Unique ID
		esc_html__( 'Date', 'example' ),		// Title
		'ev_date_meta_box',					// Callback function
		'rl_event',								// Add metabox to our custom post type
		'side',									// Context
		'default'								// Priority
	);
}

/* Display the post meta box. */
function ev_date_meta_box( $object, $box ) { ?>

	<?php wp_nonce_field( basename( __FILE__ ), 'ev_date_nonce' ); ?>

	<p class="howto"><label for="ev-date"><?php _e( "Add the date of the event.", 'example' ); ?></label></p>
	<p><input class="widefat" type="text" name="ev-date" id="ev-date" value="<?php echo esc_attr( get_post_meta( $object->ID, 'ev_date', true ) ); ?>" size="30" /></p>
<?php }

/* Save the meta box's data. */
function ev_date_save_meta( $post_id, $post ) {

	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['ev_date_nonce'] ) || !wp_verify_nonce( $_POST['ev_date_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	/* Get the posted data and sanitize it for use as an HTML class. */
	$new_meta_value = ( isset( $_POST['ev-date'] ) ? sanitize_html_class( $_POST['ev-date'] ) : '' );

	/* Get the meta key. */
	$meta_key = 'ev_date';

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
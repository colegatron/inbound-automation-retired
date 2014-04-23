<?php
/**
 * Class for logging events and errors
 *
 * @package     Marketing Automation
 * @subpackage  Logging
 * @copyright   Copyright (c) 2014, Hudson Atwell
 * @honorables  Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Inbound_Logging Class
 *
 * A general use class for logging events and errors.
 *
 * @since 1.3.1
 */
class Inbound_Logging {

	/**
	 * Set up the EDD Logging Class
	 *
	 * @since 1.3.1
	 */
	public function __construct() {
		
		self::load_hooks();
	}

	public function load_hooks() {
	
		/* Register inbound_log Post Type */
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 1 );

		/* Register inbound_log_type Taxonomy */
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ), 1 );
		
		/* Register Columns */
		add_filter( 'manage_inbound-log_posts_columns' , array( __CLASS__ , 'register_columns') );
		
		/* Prepare Column Data */
		add_action( "manage_posts_custom_column", array( __CLASS__ , 'prepare_column_data' ) , 10, 2 );
	
		/* Define Sortable Columns */
		add_filter( 'manage_edit-inbound-log_sortable_columns', array( __CLASS__ , 'define_sortable_columns' ) );
		
		/* Setup Default Query */
		add_filter( 'parse_query', array( __CLASS__ , 'set_post_order' ) );
		
		/* Auto Prune Logs */
		add_action( 'admin_init' , array( __CLASS__ , 'auto_prune_posts' ) );
		
	}
	
	/**
	 * Registers the inbound-log Post Type
	 *
	 * @access public
	 * @since 1.3.1
	 * @return void
	 */
	public static function register_post_type() {
		/* Logs post type */
		$log_args = array(
			'labels'			  => array( 'name' => __( 'Logs', 'ma' ) ),
			'public'			  => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu' 		  => 'edit.php?post_type=automation',
			'query_var'			  => false,
			'rewrite'			  => false,
			'capability_type'	  => 'post',
			'supports'			  => array( 'title', 'editor' ),
			'can_export'		  => true,
			'hierarchical ' 	  => false
		);

		register_post_type( 'inbound-log', $log_args );
	}

	/*
	* Registers the Type Taxonomy
	*/
	public static function register_taxonomy() {
		
		register_taxonomy( 'inbound_log_type', 'inbound-log', array( 'public' => true ) );
		
	}

	/* Register Columns */
	public static function register_columns( $cols ) {

		$cols = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => __( 'Log' , 'ma' ),
			"parent_rule" => __( 'Rule' , 'ma' ),			
			"post_date" => __( 'Date Time' , 'ma' )
		);

		$cols = apply_filters('inbound_logs_change_columns',$cols);

		return $cols;
	}
	
	/* Prepare Column Data */
	public static function prepare_column_data( $column , $post_id ) {
	
		$post_type = get_post_type( $post_id );

		if ( $post_type !='inbound-log' ){
			return $column;
		}

		switch ( $column ) {
			case "title":
				echo get_the_title( $post_id );
			  break;
			case "category":
				$terms = wp_get_post_terms( $post_id, 'email_template_category' );
				foreach ($terms as $term) {
					$term_link = get_term_link( $term , 'email_template_category' );
					echo '<a href="'.$term_link.'">'.$term->name.'</a> ';
				}
			  break;
			case "parent_rule":
				$rule_id = get_post_meta( $post_id , 'rule_id' , true );
				$rule_title = get_the_title( $rule_id );

				echo '<a href="'.get_edit_post_link( $rule_id ) .'" target=_blank>'.$rule_title.'</a>';
			  break;
			case "post_date":
				$rule_id = get_post_meta( $post_id , 'rule_id' , true );
				$publish_date = get_the_time('Y-m-d g:i a', $post_id );

				echo $publish_date;
			  break;

		}

		do_action('email_template_custom_columns',$column, $post_id);
	}
	
	/* Define Sortable Columns */
	public static function define_sortable_columns($columns) {

		$columns = apply_filters('',$columns);

		return $columns;
	}
	
	public static function filter_row_actions( $actions , $post ) {
		
		if ($post->post_type =="email-template"){
			unset($actions['trash']);
		}
		return $actions;
	}

	public static function preview_template() {
		global $post;

		if ( isset($post) && $post->post_type =='email-template' ){
			$body = get_post_meta( $post->ID , 'inbound_email_body_template' , true );
			echo $body;
			exit;
		}
	}
		
	/*
	* Define Log types
	*/
	public static function log_types() {
		
		$terms = array(
			'trigger_event', 'schedule_event', 'evaluation_event' , 'wait_event' , 'action_event', 'fail_event' , 'message_event' , 'debug'
		);

		return apply_filters( 'inbound_log_types', $terms );
	}

	/*
	* Check if a log type is valid
	*/
	function valid_type( $type ) {
		
		return in_array( $type, self::log_types() );
		
	}

	/*
	* Create new log entry
	*/
	public function add( $title = '', $message = '', $parent = 0, $type = null ) {
		
		$log_data = array(
			'post_title' 	=> $title,
			'post_content'	=> $message,
			'post_parent'	=> $parent,
			'log_type'		=> $type
		);

		return self::insert_log( $log_data );
	}

	/*
	* Easily retrieves log items for a particular object ID
	*/
	public function get_logs( $object_id = 0, $type = null, $paged = null ) {
		return self::get_connected_logs( array( 'post_parent' => $object_id, 'paged' => $paged, 'log_type' => $type ) );
	}

	/*
	* Stores a log entry
	*/
	function insert_log( $log_data = array(), $log_meta = array() ) {
		$defaults = array(
			'post_type' 	=> 'inbound-log',
			'post_status'	=> 'publish',
			'post_parent'	=> 0,
			'post_content'	=> '',
			'log_type'		=> false
		);

		$args = wp_parse_args( $log_data, $defaults );

		do_action( 'inbound_pre_insert_log', $log_data, $log_meta );

		// Store the log entry
		$log_id = wp_insert_post( $args );

		// Set the log type, if any
		if ( $log_data['log_type'] && self::valid_type( $log_data['log_type'] ) ) {
			wp_set_object_terms( $log_id, $log_data['log_type'], 'inbound_log_type', false );
		}

		// Set log meta, if any
		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				update_post_meta( $log_id, '_inbound_log_' . sanitize_key( $key ), $meta );
			}
		}

		do_action( 'inbound_post_insert_log', $log_id, $log_data, $log_meta );

		return $log_id;
	}

	/*
	* Update and existing log item
	*/
	public function update_log( $log_data = array(), $log_meta = array() ) {
		do_action( 'inbound_pre_update_log', $log_id, $log_data, $log_meta );

		$defaults = array(
			'post_type' 	=> 'inbound-log',
			'post_status'	=> 'publish',
			'post_parent'	=> 0
		);

		$args = wp_parse_args( $log_data, $defaults );

		// Store the log entry
		$log_id = wp_update_post( $args );

		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				if ( ! empty( $meta ) )
					update_post_meta( $log_id, '_inbound_log_' . sanitize_key( $key ), $meta );
			}
		}

		do_action( 'inbound_post_update_log', $log_id, $log_data, $log_meta );
	}

	/*
	* Retrieve all connected logs
	*/
	public function get_connected_logs( $args = array() ) {
		$defaults = array(
			'post_type'      => 'inbound-log',
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			'paged'          => get_query_var( 'paged' ),
			'log_type'       => false
		);

		$query_args = wp_parse_args( $args, $defaults );

		if ( $query_args['log_type'] && self::valid_type( $query_args['log_type'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' 	=> 'inbound_log_type',
					'field'		=> 'slug',
					'terms'		=> $query_args['log_type']
				)
			);
		}

		$logs = get_posts( $query_args );

		if ( $logs )
			return $logs;

		// No logs found
		return false;
	}

	/*
	* Retrieves number of log entries connected to particular object ID
	*/
	public function get_log_count( $object_id = 0, $type = null, $meta_query = null, $date_query = null ) {
		
		global $pagenow, $typenow;

		$query_args = array(
			'post_parent' 	   => $object_id,
			'post_type'		   => 'inbound-log',
			'posts_per_page'   => -1,
			'post_status'	   => 'publish',
			'fields'           => 'ids',
		);

		if ( ! empty( $type ) && self::valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' 	=> 'inbound_log_type',
					'field'		=> 'slug',
					'terms'		=> $type
				)
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}

		$logs = new WP_Query( $query_args );

		return (int) $logs->post_count;
	}

	/*
	* Delete a log
	*/
	public function delete_logs( $object_id = 0, $type = null, $meta_query = null  ) {
		$query_args = array(
			'post_parent' 	=> $object_id,
			'post_type'		=> 'inbound-log',
			'posts_per_page'=> -1,
			'post_status'	=> 'publish',
			'fields'        => 'ids'
		);

		if ( ! empty( $type ) && self::valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' 	=> 'inbound_log_type',
					'field'		=> 'slug',
					'terms'		=> $type,
				)
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$logs = get_posts( $query_args );

		if ( $logs ) {
			foreach ( $logs as $log ) {
				wp_delete_post( $log, true );
			}
		}
	}
	
	/*
	* Setup Post Order 
	*/
	public static function set_post_order( $query ) {
			
		if( is_admin() && isset($query->query['post_type']) && $query->query['post_type'] == 'inbound-log' ) {
			$query->set( 'order','desc' );
			$query->set( 'orderby', 'ID');
		}
	}
	
	/**
	 * Automatically Delete Posts Older than 10 Days 
	 */
	public static function auto_prune_posts() {
		
		$transient = get_transient('inbound-prune-logs-last-run-date');

		if (false === $transient) {
			
			$i = 0;
			$logs = get_posts('post_type=inbound-log&order=ASC&orderby=post_date');
	
			foreach ($logs as $log) {
				
				/* Create Date Time That Is 10 Days Ahead of post_date */
				$expire_date = strtotime($log->post_date . " +" . 60*60*24*10);
				
				/* Get Current Time */
				$today = strtotime(current_time('Y-m-d H:i:s'));
				
				if ($expire_date < $today) {

					 wp_delete_post($log->ID, true );
					 $i ++;
					 
				}
			}				
			
			/* Create Log For Clearing Logs */
			inbound_record_log(  'Clearning Logs Older Than 10 Days' , $i . ' logs cleared.' , 0 , 'action_event' );
			
			/* Set Transient to Expire Every 12 Hours */
			set_transient( 'inbound-prune-logs-last-run-date', current_time('Y-m-d H:i:s') , 12 * HOUR_IN_SECONDS ); 
		}
	}
}

/* Initiate the logging system */
$GLOBALS['inbound_logs'] = new Inbound_Logging();

/*
 * Record a log entry
 * This is just a simple wrapper function for the log class add() function
*/
function inbound_record_log( $title = '', $message = '', $parent = 0, $type = null ) {
	global $inbound_logs;
	$log_id = $inbound_logs->add( $title, $message, $parent, $type );
	update_post_meta( $log_id , 'rule_id' , $parent );
	return $log_id;
}
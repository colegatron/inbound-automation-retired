<?php

if ( !class_exists('Inbound_Email_Templates_Post_Type') ) {

	class Inbound_Email_Templates_Post_Type {

		function __construct() {
			self::load_hooks();
		}

		private function load_hooks() {
			/* Register Email Templates Post Type */
			add_action( 'init' , array( __CLASS__ , 'register_post_type' ), 11);
			
			/* Create Default Leads Email Templates on Activate */
			
			/* Load Admin Only Hooks */
			if (is_admin()) {
			
				/* Register Columns */
				add_filter( 'manage_email_template_posts_columns' , array( __CLASS__ , 'register_columns') );
				
				/* Prepare Column Data */
				//add_action( "manage_posts_custom_column", array( __CLASS__ , 'prepare_column_data' ) , 10, 2 );
			
				/* Define Sortable Columns */
				//add_filter( 'manage_edit-email_template_sortable_columns', array( __CLASS__ , 'define_sortable_columns' ) );
			}
		}

		public static function register_post_type() {

			$labels = array(
				'name' => __('Email Templates', 'leads'),
				'singular_name' => __( 'Email Templates', 'leads' ),
				'add_new' => __( 'Add New Email Templates', 'leads' ),
				'add_new_item' => __( 'Create New Email Templates' , 'leads' ),
				'edit_item' => __( 'Edit Email Templates' , 'leads' ),
				'new_item' => __( 'New Email Templatess' , 'leads' ),
				'view_item' => __( 'View Email Templatess' , 'leads' ),
				'search_items' => __( 'Search Email Templatess' , 'leads' ),
				'not_found' =>  __( 'Nothing found' , 'leads' ),
				'not_found_in_trash' => __( 'Nothing found in Trash' , 'leads' ),
				'parent_item_colon' => ''
			);

			$args = array(
				'labels' => $labels,
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'query_var' => true,
				'menu_icon' => WPL_URL . '/images/email_template.png',
				'show_in_menu'  => 'edit.php?post_type=wp-lead',
				'capability_type' => 'post',
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => array('title' )
			);

			register_post_type( 'email-template' , $args );

		}
		
		/* Register Columns */
		public function register_columns( $cols ) {
		
			$cols = array(
				"cb" => "<input type=\"checkbox\" />",
				"title" => __( 'Email Templates' , 'leads' ),
				"ma-email_template-status" => __( 'Email Templates Status' , 'leads' )
			);

			$cols = apply_filters('email_template_change_columns',$cols);

			return $cols;
		}
		
		/* Prepare Column Data */
		public function prepare_column_data( $column , $post_id ) {
			
			global $post;

			if ($post->post_type !='email_template'){
				return $column;
			}

			switch ( $column ) {
				case "title":
					$email_template_name = get_the_title( $post_id );

					$email_template_name = apply_filters('email_template_name',$email_template_name);

					echo $email_template_name;
				  break;

				case "ma-email_template-status":
					$status = get_post_meta($post_id,'email_template_active',true);
					echo $status;
				  break;

			}

			do_action('email_template_custom_columns',$column, $post_id);
		}
		
		/* Define Sortable Columns */
		public function define_sortable_columns($columns) {

			$columns = apply_filters('',$columns);

			return $columns;
		}
	}
	
	/* Load Email Templates Post Type Pre Init */
	$Inbound_Email_Templates_Post_Type = new Inbound_Email_Templates_Post_Type();
}

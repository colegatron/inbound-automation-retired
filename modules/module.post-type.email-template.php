<?php

if ( !class_exists('Inbound_Email_Templates_Post_Type') ) {

	class Inbound_Email_Templates_Post_Type {

		function __construct() {
			self::load_hooks();
		}

		private function load_hooks() {
			/* Register Email Templates Post Type */			
			add_action( 'init' , array( __CLASS__ , 'register_post_type' ), 11);
			add_action( 'init' , array( __CLASS__ , 'register_category_taxonomy' ), 11);
			
			
			/* Load Admin Only Hooks */
			if (is_admin()) {
			
				/* Register Activation */
				register_activation_hook( INBOUND_MARKETING_AUTOMATION_FILE , array( __CLASS__ , 'register_activation') );
				
				/* Register Columns */
				add_filter( 'manage_email-template_posts_columns' , array( __CLASS__ , 'register_columns') );
				
				/* Prepare Column Data */
				add_action( "manage_posts_custom_column", array( __CLASS__ , 'prepare_column_data' ) , 10, 2 );
			
				/* Define Sortable Columns */
				add_filter( 'manage_edit-email_template_sortable_columns', array( __CLASS__ , 'define_sortable_columns' ) );
				
				/* Filter Row Actions */
				add_filter( 'post_row_actions' , array( __CLASS__ , 'filter_row_actions' ) , 10 , 2 );
				
			
			} else {	
			
				/* Setup Preview */
				add_action( 'wp' , array( __CLASS__ , 'preview_template' ));
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
				'public' => true,
				'publicly_queryable' => true,
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
		
		/* Register Category Taxonomy */
		public static function register_category_taxonomy() {
			$args = array(
				'hierarchical' => true,
				'label' => __( 'Categories' , 'leads'),
				'singular_label' => __( 'Email Template Category' , 'leads'),
				'show_ui' => true,
				'query_var' => true,
				"rewrite" => true
    		);

			register_taxonomy('email_template_category', array('email-template'), $args);
		}
		
		/* Register Columns */
		public static function register_columns( $cols ) {

			$cols = array(
				"cb" => "<input type=\"checkbox\" />",
				"title" => __( 'Email Templates' , 'leads' ),
				"category" => __( 'Category' , 'leads' ),
				"description" => __( 'Description' , 'leads' )
			);

			$cols = apply_filters('email_template_change_columns',$cols);

			return $cols;
		}
		
		/* Prepare Column Data */
		public static function prepare_column_data( $column , $post_id ) {
		
			$post_type = get_post_type( $post_id );

			if ( $post_type !='email-template' ){
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
				case "description":
					$description = get_post_meta( $post_id , 'inbound_email_description' , true );
					echo $description;
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
		
		public static function register_activation() {
			
			self::register_post_type();
			self::register_category_taxonomy();
			
			/* Create inbound-core Category Term */
			if ( !term_exists( 'inbound-core' , 'email_template_category' ) ) {
				wp_insert_term( 'inbound-core' , 'email_template_category' , array( 'description'=> 'Belongs to Inbound Now\'s set of core templates. Can be edited but not deleted.' , 'slug' => 'inbound-core' ) );
			}
			
			/* Create Default New Lead Notification Template */
			$template = get_page_by_title ( __( 'New Lead Notification' , 'leads') , OBJECT , 'email-template' );
			
			if ( !$template ) {
			
				$template_id = wp_insert_post(
					array(
						'post_title'     => __( 'New Lead Notification' , 'leads'),
						'post_status'    => 'publish',
						'post_type'      => 'email-template'
					)
				);
				
				$email_body_template = file_get_contents( INBOUND_MARKETING_AUTOMATION_PATH . 'includes/email-templates/new-lead-notification/new-lead-notification.html' );
					
				add_post_meta( $template_id , 'inbound_email_subject_template', __( '{{site-name}} - {{form-name}} - New Lead Conversion' , 'leads') );
				add_post_meta( $template_id , 'inbound_email_body_template', $email_body_template );
				add_post_meta( $template_id , 'inbound_email_description', 'Designed for notifying administer of new lead conversion when an Inbound Form is submitted.' );
				add_post_meta( $template_id , 'inbound_is_core', true );
				
				$term = get_term_by( 'slug' , 'inbound-core' , 'email_template_category' , OBJECT );
				
				$result = wp_set_post_terms( $template_id , $term->term_id , 'email_template_category' );

			}	 
			
		}
	}
	
	/* Load Email Templates Post Type Pre Init */
	$Inbound_Email_Templates_Post_Type = new Inbound_Email_Templates_Post_Type();
}

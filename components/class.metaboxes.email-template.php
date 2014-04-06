<?php

if ( !class_exists( 'Inbound_Metaboxes_Email_Templates' ) ) {

	class Inbound_Metaboxes_Email_Templates {
		
		static $Inbound_Email_Templates;
		static $post_type;
		static $is_core_template;
		
		public function __construct() {
			self::$post_type = 'email-template';
			self::load_hooks();
		}
		
		public static function load_hooks() {
			/* Setup Variables */
			add_action( 'posts_selection' , array( __CLASS__ , 'load_variables') );
			
			/* Add Metaboxes */
			add_action( 'add_meta_boxes' , array( __CLASS__ , 'define_metaboxes') );
			
			/* Replace Default Title Text */
			add_filter( 'enter_title_here' , array( __CLASS__ , 'change_title_text' ) , 10, 2 );
	
			/* Add Save Actions */			
			add_action( 'save_post' , array( __CLASS__ , 'save_markup' ) );			
			
			/* Enqueue JS */
			add_action( 'admin_enqueue_scripts', array( __CLASS__ , 'enqueue_admin_scripts' ) ); 
			add_action( 'admin_print_footer_scripts', array( __CLASS__ , 'print_admin_scripts' ) ); 
		}
		
		public static function load_variables() {
			global $post;

			if ( !isset($post) || $post->post_type != self::$post_type ) {
				return;
			}

			self::$is_core_template = get_post_meta( $post->ID , 'inbound_is_core', true );
		}
		
		public static function define_metaboxes()
		{
			global $post;

			if ( $post->post_type != self::$post_type ) {
				return;
			}

			/* Template Select Metabox */
			add_meta_box(
				'inbound_email_templates_metabox_select_template', // $id
				__( 'Template Options', 'leads' ),
				array( __CLASS__ , 'display_markup' ), // $callback
				self::$post_type , 
				'normal', 
				'high'
			); 
			
			/* Template Select Metabox */
			add_meta_box(
				'inbound_email_templates_metabox_email_tokens', // $id
				__( 'Core Tokens', 'leads' ),
				array( __CLASS__ , 'display_tokens' ), // $callback
				self::$post_type , 
				'side', 
				'low'
			); 
			
		}
		
		public static function display_markup() {
			global $post; 
			
			$subject = get_post_meta( $post->ID , 'inbound_email_subject_template' , true );
			$body = get_post_meta( $post->ID , 'inbound_email_body_template' , true );
			
			$line_count = substr_count( $body , "\n" );

			($line_count) ? $line_count : $line_count = 5;

			echo '<h2>Subject-Line Template:</h2>';
			echo '<input type="text" name="inbound_email_subject_template"  style="width:100%;" value="'. str_replace( '"', '\"', $subject ) .'">';		
			
			echo '<h2>Email Body Template:</h2>';
			echo '<textarea name="inbound_email_body_template"  id="inbound_email_body_template" rows="'.$line_count.'" cols="30" style="width:100%;">'.$body.'</textarea>';			
			
		}

		public static function display_tokens() {
			?>
			<div class='inbound_email_templates_core_tokens'>
				<span class='core_token' title='First name of recipient' style='cursor:pointer;'>{{lead-first-name}}</span><br>
				<span class='core_token' title='Last name of recipient' style='cursor:pointer;'>{{lead-last-name}}</span><br>
				<span class='core_token' title='Email address of recipient' style='cursor:pointer;'>{{lead-email}}</span><br>
				<span class='core_token' title='Email address of sender' style='cursor:pointer;'>{{admin-email}}</span><br>
				<span class='core_token' title='Name of this website' style='cursor:pointer;'>{{site-name}}</span><br>
				<span class='core_token' title='Name of Inbound Now form user converted on' style='cursor:pointer;'>{{form-name}}</span><br>
				<span class='core_token' title='Datetime of Sent Email.' style='cursor:pointer;'>{{date-time}}</span><br>
				<span class='core_token' title='Page the visitor singed-up on.' style='cursor:pointer;'>{{converted-page-url}}</span><br>
			</div>
			
			<?php
		}
		
		public static function save_markup( $post_id )
		{
			global $post;
			
			if ( !isset( $post ) ) {
				return;
			}
			
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ){
				return;
			}
			
				if ($post->post_type!=self::$post_type)	{
				return;
			}
			

			if ( isset ( $_POST[ 'inbound_email_subject_template' ] ) ) {
				update_post_meta( $post_id, 'inbound_email_subject_template', $_POST[ 'inbound_email_subject_template' ] );
			}

			if ( isset ( $_POST[ 'inbound_email_body_template' ] ) ) {
				update_post_meta( $post_id, 'inbound_email_body_template', $_POST[ 'inbound_email_body_template' ] );
			}

		}


		public static function change_title_text( $text, $post ) {
			if ($post->post_type==self::$post_type) {
				return __( 'Email Template Name' , 'leads' );
			} else {
				return $text;
			}
		}

		
		/* Enqueue Admin Scripts */
		public static function enqueue_admin_scripts( $hook ) {
			global $post;

			if ( !isset($post) || $post->post_type != self::$post_type ) {
				return;
			}
			
			if ( $hook == 'post-new.php' ) {				
			}
			
			if ( $hook == 'post.php' ) {			
			}
			
			if ($hook == 'post-new.php' || $hook == 'post.php') {				
			}
		}
		
		/* Print Admin Scripts */
		public static function print_admin_scripts() {
			global $post;

			if ( !isset($post) || $post->post_type != self::$post_type ) {
				return;
			}

			if ( self::$is_core_template ) {
				?>
				<script>
				jQuery(document).ready(function($) { 	
					jQuery('#delete-action').remove();
				});
				</script>	
				<?php
			}
			
		}
	}
	
	
	$Inbound_Metaboxes_Email_Templates = new Inbound_Metaboxes_Email_Templates;
}
<?php
/*
Class Name: Inbound_WP_Core_Email_Templates
Class Description: Opens up core WordPress Email Templates to modification through the Inbound Now email templating system.
Class Author: Hudson Atwell
*/

if ( !class_exists( 'Inbound_WP_Core_Email_Templates' ) ) {

	class Inbound_WP_Core_Email_Templates {

		public static function __construct() {

			self::load_hooks();
			
		}
		
		public static function load_hooks() {
		
			/* New User Notifications */
			add_action( 'wp_new_user_notification' , array( __CLASS__ , 'new_user_notification' ) , 2 , 2 );
			
		}
		
		/* Get Email Template By meta_value $template_name where meta_key is _inbound_template_id */
		public static function get_template( $template_name ) {
		
			$email_template = array();

			$templates = get_posts(array(
				'post_type' => 'email-template',
				'posts_per_page' => 1,
				'meta_key' => '_inbound_template_id',
				'meta_value' => $template_name
			));

			foreach ( $templates as $template ) {
				$email_template['ID'] = $template->ID;
				$email_template['subject'] = get_post_meta( $template->ID , 'inbound_email_subject_template' , true );
				$email_template['body'] = get_post_meta( $template->ID , 'inbound_email_body_template' , true );
			}

			return $email_template;
			
		}
		
		public static function new_user_notification( $user_id , $plaintext_pass ) {
			
			$Inbound_Templating_Engine = Inbound_Templating_Engine();
			
			$template = self::get_template( 'wp-new-user-notification' );
			
			$user = new WP_User($user_id);

			$args = array(
				array(
					'wp_user_id' => $user_id,
					'wp_user_login' => stripslashes($user->user_login),
					'wp_user_email' => stripslashes($user->user_email),
					'wp_user_first_name' => stripslashes($user->first_name),
					'wp_user_last_name' => stripslashes($user->last_name),
					'wp_user_password' => stripslashes($user->user_pass),
					'wp_user_nice_name' => stripslashes($user->nice_name),
					'wp_user_display_name' => stripslashes($user->display_name)
				)
			);
			
			$subject = $Inbound_Templating_Engine->replace_tokens( $template['subject'] , $args  );
			$body = $Inbound_Templating_Engine->replace_tokens( $template['body'] , $args  );
			
			wp_mail( stripslashes($user->user_email) , $subject , $body );
				 
		}
		

	}

	/* Load Class */
	$Inbound_WP_Core_Email_Templates = new Inbound_WP_Core_Email_Templates();

	
	/* Overwrite Core Pluggable Functions With Our Own */
	function wp_new_user_notification( $user_id , $plaintext_pass ) {
		do_action( 'wp_new_user_notification' , $user_id , $plaintext_pass);
	}
	
}


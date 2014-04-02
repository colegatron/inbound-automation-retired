<?php
/*
Action Name: Lead Data
Action Description: Data array passed trough inbound_now_store_lead_post hook. Matches data map.
Action Author: Inbound Now
Contributors: Hudson Atwell, David Wells
*/

if ( !class_exists( 'Inbound_Automation_Action_Send_Email' ) ) {

	class Inbound_Automation_Action_Send_Email {

		function __construct() {

			add_filter( 'inbound_automation_actions' , array( __CLASS__ , 'define_filter' ) , 1 , 1);
		}

		/* Build Action Definitions */
		public static function define_filter( $actions ) {

			/* Get Available Email Templates */
			$email_templates = self::get_email_templates();

			/* Build Action */
			$actions['send_email'] = array (
				'id' => 'send_email',
				'label' => 'Send Email',
				'description' => 'Send an email using available filter data.',
				'settings' => array (
								array (
									'id' => 'to_address',
									'label' => 'To:',
									'type' => 'text',
									'default' => '{{lead-email}}',
									),
								array (
									'id' => 'from_address',
									'label' => 'From:',
									'type' => 'text',
									'default' => '{{admin-email}}',
									),
								array (
									'id' => 'email_template',
									'label' => 'Select Template',
									'type' => 'dropdown',
									'options' => $email_templates
									)
								)

			);

			return $actions;
		}

		public static function get_email_templates() {
		
			$templates = get_posts(array(
									'post_type' => 'email-template',
									'posts_per_page' => -1
								));
										
			
			
			foreach ( $templates as $template ) {
				$email_templates[$template->ID] = $template->post_title;
			}
			
			( $email_templates ) ? $email_templates : array();
			
			return $email_templates;

		}

	}

	/* Load Action */
	$Inbound_Automation_Action_Send_Email = new Inbound_Automation_Action_Send_Email();

}
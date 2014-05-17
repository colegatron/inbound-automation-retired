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

			add_filter( 'inbound_automation_actions' , array( __CLASS__ , 'define_action' ) , 1 , 1);
		}

		/* Build Action Definitions */
		public static function define_action( $actions ) {

			
			/* Get Available Email Templates */
			$email_templates = self::get_email_templates();

			/* Build Action */
			$actions['send_email'] = array (
				'class_name' => get_class(),
				'id' => 'send_email',
				'label' => 'Send Email',
				'description' => 'Send an email using available filter data.',
				'settings' => array (
					array (
						'id' => 'to_address',
						'label' => 'To Address:',
						'type' => 'text',
						'default' => '{{lead-email-address}}'
						),
					array (
						'id' => 'from_address',
						'label' => 'From Address:',
						'type' => 'text',
						'default' => '{{admin-email-address}}'
						),
					array (
						'id' => 'from_name',
						'label' => 'From Name:',
						'type' => 'text',
						'default' => '{{site-name}}'
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
			
			$email_templates = ( isset($email_templates) ) ? $email_templates : array();
			
			return $email_templates;

		}
		
		/*
		* Sends the Email 
		*/
		public static function run_action( $action , $arguments ) {
			
			$Inbound_Templating_Engine = Inbound_Templating_Engine();
			
			$template = get_post( $action['email_template'] );
			$subject = get_post_meta( $template->ID , 'inbound_email_subject_template' , true );
			$body = get_post_meta( $template->ID , 'inbound_email_body_template' , true );
			
			$to_address = $Inbound_Templating_Engine->replace_tokens( $action['to_address'] , $arguments );
			$from_address = $Inbound_Templating_Engine->replace_tokens( $action['from_address'] , $arguments  );
			$from_name = $Inbound_Templating_Engine->replace_tokens( $action['from_name'] , $arguments  );
			$subject = $Inbound_Templating_Engine->replace_tokens( $subject , $arguments  );
			$body = $Inbound_Templating_Engine->replace_tokens( $body , $arguments  );
			
			$headers = 'From: '. $from_name .' <'. $from_email .'>' . "\r\n";
			$result = wp_mail( $to_address , $subject, $body , $headers );
			
			$action_encoded = json_encode($action) ;
			inbound_record_log(  'Action Event - Send Email' , '<h2>To Address</h2>' . $to_address . '<h2>From Address</h2>' . $from_address .'<h2>From Name</h2>' . $from_name .'<h2>Subject</h2>' . $subject .'<h2>Body</h2><pre>' . $body .'</pre><h2>Settings</h2><pre>'. $action_encoded.'</pre> <h2>Arguments</h2><pre>' . json_encode($arguments , JSON_PRETTY_PRINT ) . '</pre>', $action['rule_id'] , 'action_event' );
			
		}

	}

	/* Load Action */
	$Inbound_Automation_Action_Send_Email = new Inbound_Automation_Action_Send_Email();

}

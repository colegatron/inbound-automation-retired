<?php
/*
Trigger Name: Form Submission Event
Trigger Description: This trigger fires whenever a tracked Form is submitted.
Trigger Author: Inbound Now
Contributors: Hudson Atwell
*/


if ( !class_exists( 'Inbound_Automation_Trigger_Form_Submission' ) ) {

	class Inbound_Automation_Trigger_Form_Submission {
		
		function __construct() {
			add_filter( 'inbound_automation_triggers' , array( __CLASS__ , 'define_trigger' ) , 1 , 1);
		}
		
		/* Build Trigger Definitions */
		public static function define_trigger( $triggers ) {
			
			/* Set & Extend Trigger Argument Filters */
			$arguments = apply_filters('inbound_automation_trigger_arguments-save-lead' , array( 
					array( 
						'id' => 'lead_data',
						'label' => 'Lead Data'
					)
			) );
			
			/* Set & Extend Action DB Lookup Filters */	
			$db_lookup_filters = apply_filters( 'inbound_automation_db_lookup_filters-form-submission' , array (
				array( 
						'id' => 'lead_data',
						'label' => 'Lead Lookup',
						'class_name' => 'Inbound_Automation_Query_Lead'				
					)
			));
			
			/* Set & Extend Available Actions */
			$actions = apply_filters('inbound_automation_trigger_actions-form-submission' , array( 
				'send_email' , 'wait' , 'relay_data' , 'add_lead_to_list'
			) );
			
			$triggers['inbound_store_lead_post'] = array (
				'label' => 'On Tracked Form Submission',
				'description' => 'This trigger fires whenever a lead submits validated data to a tracked form.',
				'action_hook' => 'inbound_store_lead_post',
				'scheduling' => false,
				'arguments' => $arguments,
				'db_lookup_filters' => $db_lookup_filters,
				'actions' => $actions
			);
			
			return $triggers;
		}
	}
	
	/* Load Trigger */
	$Inbound_Automation_Trigger_Form_Submission = new Inbound_Automation_Trigger_Form_Submission;

}
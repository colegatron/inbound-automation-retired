<?php
/*
Trigger Name: Save Lead
Trigger Description: This trigger fires whenever a new lead is saved into the sytem.
Trigger Author: Inbound Now
Contributors: Hudson Atwell, David Wells
*/


if ( !class_exists( 'Inbound_Automation_Trigger_Store_Lead' ) ) {

	class Inbound_Automation_Trigger_Store_Lead {
		
		function __construct() {
			add_filter( 'inbound_automation_triggers' , array( __CLASS__ , 'define_trigger' ) , 1 , 1);
		}
		
		/* Build Trigger Definitions */
		public static function define_trigger( $triggers ) {
			
			/* Extend Argument Setup */
			$arguments = apply_filters('store_lead_arguments' , array( 
					array( 
						'id' => 'lead_data',
						'label' => 'Lead Data'
					)
			) );
			
			/* Extend Action Setup */
			$actions = apply_filters('store_lead_actions' , array( 
				'send_email' , 'wait'
			) );
			
			$triggers['wpleads_existing_lead_insert'] = array (
				'label' => 'On Lead Update',
				'description' => 'This trigger fires whenever a lead is updated.',
				'action_hook' => 'wpleads_existing_lead_insert',
				'scheduling' => false,
				'arguments' => $arguments,
				'actions' => $actions
			);
			
			return $triggers;
		}
	}
	
	/* Load Trigger */
	$Inbound_Automation_Trigger_Store_Lead = new Inbound_Automation_Trigger_Store_Lead;

}
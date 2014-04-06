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
			
			/* Make Filters & Actions Extendable for this Trigger */
			$filters = apply_filters('automation_save_lead_filters' , array( 
				array( 
					'id' => 'lead_data',
					'label' => 'Lead Data'
				)
			) );
			
			$actions = apply_filters('automation_save_lead_actions' , array( 
				'send_email' 
			) );
			
			$triggers['inbound_store_lead_post'] = array (
				'label' => 'On Lead Save',
				'description' => 'This trigger fires whenever a new lead is saved into the sytem.',
				'action_hook' => 'inbound_store_lead_post',
				'scheduling' => false,
				'filters' => $filters,
				'actions' => $actions
			);
			
			return $triggers;
		}
	}
	
	/* Load Trigger */
	$Inbound_Automation_Trigger_Store_Lead = new Inbound_Automation_Trigger_Store_Lead;

}
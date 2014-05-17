<?php
/*
Trigger Name: Save Lead
Trigger Description: This trigger fires whenever a new lead is saved into the sytem.
Trigger Author: Inbound Now
Contributors: Hudson Atwell, David Wells
*/


if ( !class_exists( 'Inbound_Automation_Trigger_Save_Lead' ) ) {

	class Inbound_Automation_Trigger_Save_Lead {
		
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
			$db_lookup_filters = apply_filters( 'inbound_automation_db_lookup_filters-save-lead' , array (
				array( 
						'id' => 'lead_data',
						'label' => 'Lead Lookup',
						'class_name' => 'Inbound_Automation_Query_Lead'
					)
			));
			
			/* Set & Extend Available Actions */
			$actions = apply_filters('inbound_automation_trigger_actions-save-lead' , array( 
				'send_email' , 'wait'
			) );
			
			$triggers['wpleads_new_lead_insert'] = array (
				'label' => 'On Lead Save',
				'description' => 'This trigger fires whenever a new lead is saved into the sytem.',
				'action_hook' => 'wpleads_new_lead_insert',
				'scheduling' => false,
				'arguments' => $arguments,
				'db_lookup_filters' => $db_lookup_filters,
				'actions' => $actions
			);
			
			return $triggers;
		}
	}
	
	/* Load Trigger */
	$Inbound_Automation_Trigger_Save_Lead = new Inbound_Automation_Trigger_Save_Lead;

}
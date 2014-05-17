<?php
/*
Trigger Name: Update Lead
Trigger Description: This trigger fires whenever a lead is updated.
Trigger Author: Inbound Now
Contributors: Hudson Atwell
*/


if ( !class_exists( 'Inbound_Automation_Trigger_Update_Lead' ) ) {

	class Inbound_Automation_Trigger_Update_Lead {
		
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
			$db_lookup_filters = apply_filters( 'inbound_automation_db_lookup_filters-update-lead' , array (
				array( 
						'id' => 'lead_data',
						'label' => 'Lead Lookup',
						'class_name' => 'Inbound_Automation_Query_Lead',
						'arguments' => array(
							'lead_data' /* tells db lookup which trigger arguments to help with db calls */
						),						
					)
			));
			
			/* Set & Extend Available Actions */
			$actions = apply_filters('inbound_automation_trigger_actions-update-lead' , array( 
				'send_email' , 'wait'
			) );
			
			$triggers['wpleads_existing_lead_update'] = array (
				'label' => 'On Lead Update',
				'description' => 'This trigger fires whenever a new lead is updated.',
				'action_hook' => 'wpleads_existing_lead_update',
				'scheduling' => false,
				'arguments' => $arguments,
				'db_lookup_filters' => $db_lookup_filters,
				'actions' => $actions
			);
			
			return $triggers;
		}
	}
	
	/* Load Trigger */
	$Inbound_Automation_Trigger_Update_Lead = new Inbound_Automation_Trigger_Update_Lead;

}
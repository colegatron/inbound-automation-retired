<?php
/*
Filter Name: Lead Data
Filter Description: Data array passed trough inbound_now_store_lead_post hook. Matches data map.
Filter Author: Inbound Now
Contributors: Hudson Atwell, David Wells
*/


if ( !class_exists( 'Inbound_Automation_Filter_Lead_Data' ) ) {

	class Inbound_Automation_Filter_Lead_Data {
		
		function __construct() {
			add_filter( 'inbound_automation_filters' , array( __CLASS__ , 'define_filter' ) , 1 , 1);
		}
		
		/* Build Filter Definitions */
		public static function define_filter( $filters ) {
			
			/* Get Lead Data Map */
			$keys = self::build_lead_keys();
			
			/* Make Filters & Actions Extendable for this Filter */
			$compare_args 	= 	array(	'greater-than' =>'Greater Than',
							'less-than' => 'Less Than' ,
							'contains' =>'Contains',
							'equals' => 'Equals',
						);
						
			$compare = apply_filters('automation_filter_lead_data_supports' , $compare_args );
			
			/* Build Filter */
			$filters['lead_data'] = array (
				'id' => 'lead_data',
				'label' => 'Lead Data',
				'description' => 'This data is available during a new lead save.',
				'key_type' => 'dropdown',
				'keys' => $keys ,
				'compare' => $compare,
				'value_type' => 'text',
				'values' => false
			);
			
			return $filters;
		}
		
		public static function build_lead_keys() {
			
			$lead_fields = wp_leads_get_lead_fields();
			$lead_map = array();
			
			foreach ($lead_fields as $value) {
				$lead_map[$value['key']] = $value['label'];
			}
			
			return $lead_map;			
		}
	}
	
	/* Load Filter */
	$Inbound_Automation_Filter_Lead_Data = new Inbound_Automation_Filter_Lead_Data();

}
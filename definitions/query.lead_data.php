<?php
/*
Query Name: Lead Queries
Query Description: Definitions and Lookup Maps for Lead Data
Query Author: Inbound Now
Contributors: Hudson Atwell
*/


if ( !class_exists( 'Inbound_Automation_Query_Lead' ) ) {

	class Inbound_Automation_Query_Lead {
		
		/* Build Query Definitions */
		public static function get_key_map( ) {
			
			$queries['page_views'] = __( 'Page Views' , 'ma' );
			$queries['conversions'] = __( 'Conversions' , 'ma' );
			
			return $queries;
		}
	}
}
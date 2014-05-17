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
			$queries['conversion_rate'] = __( 'Conversions Rate (Use decimal format. eg: 5% = .05)' , 'ma' );
			
			return $queries;
		}
		
		/* Get target value from array of arguments sent by trigger given a key to search 
		* @param arguments ARRAY( ARRAY , ARRAY ) 
		*
		* @return lead_id INT
		*/
		public static function get_target_from_arguments( $target , $arguments ) {
			
			foreach ( $arguments as  $argument ) {
				if ( array_key_exists( $target , $argument) ) {
					return $argument[ $target ];
				}
			}
			
			return false;
		}
		
		
		/* Gets Page View Count for Lead 
		* @param arguments ARRAY of arguments sent over by trigger. One of the arguments must contain key 'lead_id' 
		*
		* @return page_views INT
		*/ 
		
		public static function query_page_views( $arguments ) {
			
			$lead_id = Inbound_Automation_Query_Lead::get_target_from_arguments( 'lead_id' , $arguments );
				
			if ( !$lead_id ) {
				return null;
			}
			
			
			$page_views = get_post_meta( $lead_id ,'wpleads_page_view_count', true );
			
			if ( !is_numeric($page_views) ) {
				$page_views = 0;
			}
			
			return $page_views;
		}
		
		/* Gets Page Conversion Count for Lead 
		* @param arguments ARRAY of arguments sent over by trigger. One of the arguments must contain key 'lead_id' 
		*
		* @return conversions INT
		*/
		public static function query_conversions(  $arguments ) {
			
			$lead_id = get_target_from_arguments( 'lead_id' , $arguments );
			
			if ( !$lead_id ) {
				return null;
			}
			
			
			$conversions = get_post_meta( $lead_id ,'wpleads_conversion_count', true );
			
			if ( !is_numeric($conversions) ) {
				$conversions = 0;
			}
			
			return $conversions;
		}
		
		/* Gets Page Conversion Rate for Lead 
		* @param lead_data ARRAY of arguments sent over by trigger. One of the arguments must contain key 'lead_id' 
		*
		* @return page_views INT
		*/
		public static function query_conversion_rate( $arguments ) {
			
			$lead_id = get_target_from_arguments( 'lead_id' , $arguments );
			
			if ( !$lead_id ) {
				return null;
			}
			
			$page_views = get_post_meta( $lead_id ,'wpleads_page_view_count', true );
			$conversions = get_post_meta( $lead_id ,'wpleads_conversion_count', true );
			
			if ( !is_numeric($page_views) || !is_numeric($conversions) ) {
				$conversion_rate = 0;
			} else {
				$conversion_rate = $conversions / $page_views ;
			}
			
			
			return $conversion_rate;
		}
		
	}
}
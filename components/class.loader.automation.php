<?php

if ( !class_exists( 'Inbound_Automation_Load_Extensions' ) ) {

add_action( 'plugins_loaded' , 'Inbound_Automation_Load_Extensions' , 1 );
function Inbound_Automation_Load_Extensions() {
	return Inbound_Automation_Load_Extensions::instance();
}

class Inbound_Automation_Load_Extensions {

	public static $instance;
	public static $Processor;
	public $triggers;
	public $rules;
	public $filters;
	public $actions;
	
	public static function instance() {	
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Inbound_Automation_Load_Extensions ) )
		{
			self::$instance = new Inbound_Automation_Load_Extensions;

			/* Load Backend Processing Engine */
			self::$Processor = Inbound_Automation_Processing();
			
			/* Load Rules */
			self::load_rules();
			
			/* Load Automation Setting Definitions */
			self::define_triggers();
			self::define_filters();
			self::define_actions();

			/* Add Trigger Listeners to Hooks */
			self::add_trigger_listeners(); 
		}

		return self::$instance;
	}
	
	/* Load Rules from CPT */
	public static function load_rules() {
	
		$rules = get_posts( array (
				'post_type'=> 'automation',
				'post_status' => 'published',
				'posts_per_page' => -1
			)
		);
							
		self::$instance->rules = $rules;
	}
	
	public static function define_triggers() {
		self::$instance->triggers = apply_filters( 'inbound_automation_triggers' , array() );
	}
	
	/* Define Action Filters */
	public static function define_filters() {
		$filters = array();
	
		/* Build Compare Options */
		$compare_args 	= 	array(	
			'greater-than' =>'Greater Than',
			'less-than' => 'Less Than' ,
			'contains' =>'Contains',
			'equals' => 'Equals',
		);			
		
		$compare = apply_filters('automation_filter_lead_data_supports' , $compare_args );
		
		foreach ( self::$instance->triggers as $hook =>$trigger) {
			
			foreach ($trigger['filters'] as $key => $filter ) {
				$keys = get_option( $hook . '_' . $filter['id']);
				
				if ( !$keys ) {
					$keys = array( '-1' => 'No Options Detected' );
				} 
				
				$filters[$filter['id']] = array(
					'id' => $filter['id'],
					'label' => $filter['label'],
					'key_type' => 'dropdown',
					'keys' => $keys,
					'compare' => $compare,
					'value_type' => 'text',
					'values' => false
				);			
			}
		}
		
		self::$instance->filters = apply_filters( 'inbound_automation_filters' , $filters );

	}
	
	public static function define_actions() {
		self::$instance->actions = apply_filters( 'inbound_automation_actions' , array() );
	}
	
	/* Adds Listener Hooks for Building Filters and Rule Processings */
	public static function add_trigger_listeners() {
		
		foreach (self::$instance->triggers as $id => $trigger) {
			if ( isset($trigger['action_hook']) ) {
				add_action( $trigger['action_hook'] , array( __CLASS__ , 'generate_filters' ) , 10 , count($trigger['filters']) ) ;
				add_action( $trigger['action_hook'] , array( __CLASS__ , 'process_trigger' ) , 10 , count($trigger['filters']) ) ;
			}
		}
	}
	
	/* Check Trigger for Rule Match */
	public static function process_trigger() {
		
		foreach (self::$instance->rules  as $rule) {
			
			$trigger = get_post_meta( $rule->ID , 'automation_trigger' , true );
			
			if ( $trigger == current_filter() ) {
				
				$arguments = json_encode( func_get_args() );
				
				self::$Processor->add_job_to_queue( $rule , $arguments );
				
			}
		}
		
	}
	
	
	/* Update the Filter */
	public static function generate_filters() {
		
		$triggers = self::$instance->triggers;
		$arguments = func_get_args();
		$action_hook =  current_filter();
			
		foreach ($arguments as $key => $argument) {
			
			if ( is_array($argument) ) { 


				$filter = array();
				$filter_name = $triggers[$action_hook]['filters'][$key]['id'];
				
				foreach ($argument as $key => $value) {
					if ( !is_array($value) ) {
						$filter[$key] = $key;
					}
				}
				
				self::update_filter( $action_hook , $filter_name , $filter );
			}			
		}
	}
	
	
	
	/* Builds Filter Keys from Paramater Data Associated with Hook and Stores them in wp_options Table */
	public static function update_filter( $action_hook , $filter_name , $filter ) {
		$option_name = $action_hook . '_' . $filter_name ;

		if (  $filter_keys = get_option( 'inbound_store_lead_post_lead_data' )  ) {
			$filter = array_merge( $filter_keys , $filter );
			ksort($filter);
			update_option( $option_name, $filter );

		} else {
			$deprecated = null;
			$autoload = 'no';
			ksort($filter);
			add_option( $option_name, $filter, $deprecated, $autoload );
		}
		
	}
	
}
}

<?php

if ( !class_exists( 'Inbound_Automation_Load_Extensions' ) ) {

add_action( 'plugins_loaded' , 'Inbound_Automation_Load_Extensions' , 1 );
function Inbound_Automation_Load_Extensions() {
	return Inbound_Automation_Load_Extensions::instance();
}

class Inbound_Automation_Load_Extensions {

	private static $instance;
	private $triggers;
	private $filters;
	private $actions;
	
	public static function instance() {	
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Inbound_Automation_Load_Extensions ) )
		{
			self::$instance = new Inbound_Automation_Load_Extensions;

			/* Load Automation Setting Definitions */
			self::define_triggers();
			self::define_filters();
			self::define_actions();

			//self::apply_triggers();
		}
		
		return self::$instance;
	}
	
	private static function define_triggers() {
		self::$instance->triggers = apply_filters( 'inbound_automation_triggers' , array() );
	}
	
	private static function define_filters() {
		self::$instance->filters = apply_filters( 'inbound_automation_filters' , array() );
	}
	
	private static function define_actions() {
		self::$instance->actions = apply_filters( 'inbound_automation_actions' , array() );
	}
	
	private function apply_filters() {
		
		foreach (self::$instance->triggers as $id => $trigger) {
			if ( isset($trigger['action_hook']) ) {
				//add_action( $trigger['action_hook'] ;
			}
		}
	}
	
}
}
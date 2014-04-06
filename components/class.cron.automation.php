<?php

if ( !class_exists( 'Inbound_Automation_Processing' ) ) {

/* Build Class */
class Inbound_Automation_Processing {

	public static $instance;
	public $event_hook_name;
	
	public static function instance() {	
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Inbound_Automation_Processing ) )
		{
			/* Load This Class into Single Instance */
			self::$instance = new Inbound_Automation_Processing;
		
			/* Load Static Variables */
			self::load_static_variables();
			
			/* Load Hooks */
			self::load_hooks();
			
		}

		return self::$instance;
	}
	
	/* Load Static Variables */
	public static function load_static_variables() {
	
		self::$instance->event_hook_name = 'inbound_automation';
	
	}
	
	public static function load_hooks() {		

		//set_time_limit ( 0 );
		//ignore_user_abort ( true );

		/* Adds Cron Hook to System on Activation */
		
		
		
		/* Adds 'Every Two Minutes' to System Cron */
		add_filter( 'cron_schedules', array( __CLASS__ , 'define_ping_interval' ) );
				
	}
	
	/* Adds Cron Hook to System on Activation */
	public static function add_cron_hook() {
		wp_schedule_event( time(), 'every_two_minutes', self::$instance->event_hook_name );
	}	
	
	/* Adds Cron Hook to System on Activation */
	public static function remove_cron_hook() {
		wp_clear_scheduled_hook( self::$instance->event_hook_name );
	}

	/* Adds 'Every Two Minutes' to System Cron */
	public static function define_ping_interval( $schedules ) 
	{
		$schedules['every_two_minutes'] = array(
			'interval' => 60 * 2,
			'display' => 'Every Two Minutes'
		);
		
		return $schedules;
	}	
	
	public static function add_job_to_queue( $rule , $arguments ) {
	
	}
}


/* Load Singleton */
function Inbound_Automation_Processing() {
	return Inbound_Automation_Processing::instance();
}

$Inbound_Automation_Processing = Inbound_Automation_Processing();

/* Register Activation Hooks */
register_activation_hook( INBOUND_MARKETING_AUTOMATION_FILE , array( $Inbound_Automation_Processing , 'add_cron_hook' ) );
register_deactivation_hook( INBOUND_MARKETING_AUTOMATION_FILE , array( $Inbound_Automation_Processing , 'remove_cron_hook' ) );
}
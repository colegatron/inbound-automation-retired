<?php

if ( !class_exists( 'Inbound_Automation_Processing' ) ) {

/* Build Class */
class Inbound_Automation_Processing {

	public static $instance;
	public $event_hook_name;
	public $definitions;
	public $queue;
	
	public static function instance() {	
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Inbound_Automation_Processing ) )
		{
			/* Load Inbound_Automation_Processing Class into Single Instance */
			self::$instance = new Inbound_Automation_Processing;
		
			/* Load Static Variables */
			self::load_static_variables();
			
			/* Load Debug Tools */
			self::load_debug_tools();
			
			/* Load Hooks */
			self::load_hooks();
			
		}

		return self::$instance;
	}
	
	/* 
	* Load Static Variables 
	*/
	public static function load_static_variables() {
	
		self::$instance->event_hook_name = 'inbound_automation';
		self::$instance->definitions = Inbound_Automation_Load_Extensions();

	}
	
	/*
	* Debug Commands
	*/
	public static function load_debug_tools() {

		if ( isset($_GET['ma_delete_rules']) ) {
			/* Empty Job Queue */
			update_option('inbound_automation_queue' , '');
		}	
		if ( isset($_GET['ma_view_rules']) ) {
			/* Empty Job Queue */
			self::$instance->queue = json_decode( get_option('inbound_automation_queue' , '') , true);
			print_r(self::$instance->queue);
		}		
	}
	
	/* 
	* Load Hooks & Filters 
	*/
	public static function load_hooks() {		

		//set_time_limit ( 0 );
		//ignore_user_abort ( true );

		/* Adds 'Every Two Minutes' to System Cron */
		add_filter( 'cron_schedules', array( __CLASS__ , 'define_ping_interval' ) );
		
		add_action('init' , array( __CLASS__ , 'load_queue_hooks') );
		
		/* Add debug options to menu */
		if ( is_admin() ) {
			add_filter('inbound_menu_debug' , array( __CLASS__ , 'add_debug_items_to_menu' ) , 10 ,2);
		}
				
	}
	
	/*
	* Filters Debug Menu Items when Logged In
	* @param ARRAY 
	* @param STRING 
	*/
	public static function add_debug_items_to_menu( $menu_items , $parent_key ) {
	
		/* Remove Automation Jobs */
		$menu_items['inbound-debug-clear-job-queue'] = array(
		  'parent' => $parent_key,
		  'title'  => __( 'Automation: Clear Job Queue', 'ma' ),
		  'href'   =>  $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '&ma_delete_rules=1',
		  'meta'   => array( 'title' =>  __( 'Click here to empty rules queue', 'ma' ) )
		);
		
		/* View Automation Job Data */
		$menu_items['inbound-debug-view-job-queue'] = array(
		  'parent' => $parent_key,
		  'title'  => __( 'Automation: View Job Debug Data', 'ma' ),
		  'href'   =>  $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '&ma_view_rules=1',
		  'meta'   => array( 'title' =>  __( 'Click here to empty rules queue', 'ma' ) )
		);
		
		return $menu_items;
		
	}
	
	/* 
	* Load the Job Queue And Process All Scheduled Jobs 
	*/
	public static function load_queue_hooks() {

		self::$instance->queue = json_decode( get_option('inbound_automation_queue' , '') , true);

		if ( !self::$instance->queue || !is_array(self::$instance->queue) ) {
			return;
		}

		foreach (self::$instance->queue as $job_id => $job) {
			//print_r($job['rule']);exit;
			add_action( 'inbound_automation' , function() use( $job_id , $job) {			
				
				$rule_id = $job['rule']['ID'];
				$job = self::run_job( $job );
				
				/* Tell Log The Job Has Completed */
				$remaining_actions = self::json_indent( $job['rule']['meta']['automation_action_blocks'][0] );
				inbound_record_log(  'End Job' , '<h2>Actions Left</h2> <pre>' . $remaining_actions .'</pre><h2>Raw Job Data</h2><pre>' . self::json_indent( json_encode($job) ) . '</pre>', $rule_id , 'processing_event' );
				
				/* Remove Job from Rule Queue if Empty */
				if (!$job) {				
					unset( self::$instance->queue[ $job_id ] );
					inbound_record_log(  'Job Completed' , 'This job has successfully completed all it\'s tasks.' , $rule_id , 'processing_event' );
				} 
				
				/* Update Job Object If Not */
				else {
					self::$instance->queue[ $job_id ] = $job;
				}
				
				/* Update Rule Queue After Completed Job */
				self::$instance->queue = update_option('inbound_automation_queue' , json_encode( self::$instance->queue ) );
			
			} );
		}
		
	}
	
	/*
	* Run Schedule Job
	*/
	public static function run_job( $job ) {

		$action_blocks = json_decode( $job['rule']['meta']['automation_action_blocks'][0] , true );
		
		/* Tell Log We Are Running An Job */
		$job_encoded = self::json_indent( json_encode($job) );
		inbound_record_log(  'Start Job' , '<pre>' . $job_encoded . '</pre>', $job['rule']['ID'] , 'processing_event' );
		
		foreach ( $action_blocks as $block_id => $block ) {
			
			/* Assign Extra Data to $block */
			$block['job'] = $job;
			
			/* Filter Action Block */
			$evaluate = self::evaluate_action_block( $block );
			
			
			/* If Evaluation Fails */
			if ( !$evaluate ) {	
				
				/* Run 'Else' Actions & Unset Action Block*/
				if ( isset($block['actions']['else']) ) {
				
					$action_blocks[ $block_id ] = self::run_actions( $block , 'else' );
					
				} 
				
				/* If 'While' Action Block & Evalute Equals False Then Unset Action Block */
				else if ( isset($block['actions']['while']) ) {
				
					unset( $action_blocks[$block_id] );
					
				} 
				
				/* Continue to Next Action Block If Above Coditions are False & Unset Action Block */
				else {	
				
					unset( $action_blocks[$block_id] );
					continue;
					
				}		
			} 
			
			/* If Evaluates to True */
			else {
				
				/* Run 'Then' Actions */
				if ( isset($block['actions']['then']) ) {
				
					$action_blocks[ $block_id ] = self::run_actions( $block , 'then' );			
					
				} 
				
				/* Run 'While' Actions and Do Not Unset Data Block */
				else if ( isset($block['actions']['while']) ) {
				
					$action_blocks[ $block_id ] = self::run_actions( $block , 'while'  );	
					
				}
			}
			
		}
		
		/* Loop Through Action Blocks and Remove Completed Actions & Action Blocks With No More Actions */		
		foreach ( $action_blocks as $block_id => $block ) {
			
			/* Remove Action Lists that Are Empty */
			foreach ($block['actions'] as $type => $actions) {
				
				/* Unset Historical Data For Accurate Measurements */
				unset( $actions['meta'] );
				
				if ( count($actions) < 1 ) {
					unset( $action_blocks[ $block_id ]['actions'][ $type ] );					
				} 
				
			}
			
			/* Remove Actionless Action Blocks */
			if ( count($action_blocks[ $block_id ]['actions']) < 1 ) {
			
				unset( $action_blocks[ $block_id ] );
				
			}
			
		}
		
		/* Set $job to Null Value If It Has No More Action Blocks */
		if ( count( $action_blocks ) < 1 ) {
			
			$job = array();
			
		} 
		
		/* Update Job Meta if Action Blocks Still Contain Actions */
		else {
		
			$job['rule']['meta']['automation_action_blocks'][0] = json_encode( $action_blocks );	
			
		}
		
		return $job;
	}
	
	/* Run Action Block Actions */
	public static function run_actions( $block , $type ) {

		if ( !isset( $block['actions'][ $type ] ) ) {
			return;
		}
		
		foreach ($block['actions'][ $type ] as $action_id => $action) {
			
			/* Check if Action Has Memory Set - Advance to Next Action if Necessary */
			if ( isset($block['actions'][ $type ]['meta']['pointer'])  && $block['actions'][ $type ]['meta']['pointer'] > $action_id ) {
				continue;
			}			
			
			/* Set Current Action Id Into Memory */
			$block['actions'][ $type ]['meta']['pointer'] = $action_id;
			
			/* Check if Current Actions Meta Has Schedule Set Abandon Actions if Time Condition Not Met */
			if ( isset($block['actions'][ $type ]['meta']['run_date']) && ( strtotime($block['actions'][ $type ]['meta']['run_date']) > strtotime( current_time('Y-m-d H:i:s') ) ) ) {
				inbound_record_log(  'Action Delayed' , 'Action Set to Be Performed on ' . $block['actions'][ $type ]['meta']['run_date'] . ' <pre>' . $remaining_actions .'</pre><h2>Raw Action Block Data</h2><pre>' . self::json_indent( json_encode($block) ) . '</pre>', $block['job']['rule']['ID'] , 'delay_event' );
				break;
			} 
			
			/* Set Additional Data into Action Settings Array */
			$block['actions'][ $type ][ $action_id ]['rule_id'] = $block['job']['rule']['ID'];
			
			/* Run Action */
			$block['actions'][ $type ][ $action_id ] = self::run_action( $block['actions'][ $type ][ $action_id ] , $block['job']['arguments'] );
			
			/* Check to see if Wait Command Was Returned For Next Action */
			if ( isset( $block['actions'][ $type ][ $action_id ]['run_date'] ) ) {
				
				/* Update Actions Meta With Schedule Date */
				$block['actions'][ $type ]['meta']['run_date'] = $block['actions'][ $type ][ $action_id ]['run_date'];

			}
			
			/* Remove Action from Block */
			unset( $block['actions'][ $type ][ $action_id ] );
			
		}
		
		return $block;
		
	}

	
	/*
	* Run Action
	*/
	public static function run_action( $action , $arguments  ) {
				
		$class = new $action['action_class_name'];
		$action = $class->run_action( $action , $arguments );
		
		return $action;
	}
	

	/*
	* Evaluate Action Block 
	*/
	public static function evaluate_action_block( $block ) {
	
		/* Automatically Evaluate True When There Is No Conditionals */
		if ( $block['action_block_type'] == 'actions' ) {
			return true;
		}
			
		/* Check Trigger Filters */
		if ( isset( $block['filters'] )  && $block['filters'] && $filters = $block['filters'] ) {

			/* Check How Many Conditions as True */
			foreach($filters as $filter) {
			
				$arguments = $block['job']['arguments'];
				$key = self::get_argument_key_from_filter( $filter , $block );
				
				$target_argument = $arguments[ $key ];
				$evals[] = self::evaluate_filter( $filter , $target_argument );

			}			
		
			/* Return Final Evaluation Decision Based On Eval Nature */
			$evaluate = self::evaluate_filters( $block['action_block_filters_evaluate'] , $evals );
			
			/* Add Extra Data to $block for Log Event */			
			$block['arguments'] = $arguments;
			$block['argument_pointer'] = $key;
			$block['evaluated'] = $evaluate;
			$block['evals'] = $evals;
			
			
			/* Log Evaluation Attempt */
			$block_encoded = self::json_indent( json_encode($block) );
			inbound_record_log(  'Evaluating Action Block ' , '<pre>'.$block_encoded.'</pre>' , $block['job']['rule']['ID'] , 'evaluation_event' );
			
			return $evaluate;			
			
		} else {
			/* No Filters Detected */
			return true;
		}
		
	}
	
	/*
	* Get Argument Key From Filter
	*/
	public static function get_argument_key_from_filter( $filter , $block ) {
		
		$filter_id = $block['job']['rule']['meta']['automation_trigger'][0];
	
		foreach ( self::$instance->definitions->triggers[$filter_id]['arguments'] as $key => $argument ) {
	
			if ( $filter['filter_id'] == $argument['id'] ) {
				return $key;
			}
			
		}	

	}
	
	/*
	* Evaluate Filter By Comparing Filter with Corresponding Incoming Data
	*/
	public static function evaluate_filter( $filter , $target_argument ) {
			
		switch ($filter['action_filter_compare']) {

			case 'greater-than' :

				if ( $filter['action_filter_value'] > $target_argument[ $filter['action_filter_key'] ] ) {
					return true;
				}

				BREAK;

			case 'greater-than-equal-to' :

				if ( $filter['action_filter_value'] >= $target_argument[ $filter['action_filter_key'] ] ) {
					return true;
				}

				BREAK;

			case 'less-than' :

				if ( $filter['action_filter_value'] < $target_argument[ $filter['action_filter_key'] ] ) {
					return true;
				}

				BREAK;


			case 'less-than-equal-to' :

				if ( $filter['action_filter_value'] <= $target_argument[ $filter['action_filter_key'] ] ) {
					return true;
				}

				BREAK;

			case 'contains' :

				if ( stristr( $target_argument[ $filter['action_filter_key'] ] , $filter['action_filter_value'] ) ) {
					return true;
				}

				BREAK;

			case 'equals' :
			
				if (  $filter['action_filter_value'] == $target_argument[ $filter['action_filter_key'] ] ) {
					return true;
				}

				BREAK;

		}

		return false;

	}
	
	/*
	* Evaluate All Filters Based on Evaluation Condition
	*/
	public static function evaluate_filters( $eval_nature , $evals ) {

		switch ($eval_nature) {
			case 'match-any' :
				foreach ( $evals as $eval ) {
					if ($eval) {
						$evaluate = true;
						break;
					} else {
						$evaluate = false;
					}
				}
				
				BREAK;
				
			case 'match-all' :
				foreach ( $evals as $eval ) {
					if ($eval) {
						$evaluate = true;
					} else {
						$evaluate = false;
					}
				}
				
				BREAK;
				
			case 'match-none' :
				foreach ( $evals as $eval ) {
					if ($eval) {
						$evaluate = false;
						break;
					} else {
						$evaluate = true;
						
					}
				}
				
				BREAK;
		}
		
		return $evaluate;
	}
	
	/* Adds 'Every Two Minutes' to System Cron */
	public static function define_ping_interval( $schedules ) {
		$schedules['every_two_minutes'] = array(
			'interval' => 60 * 1,
			'display' => 'Every Two Minutes'
		);
		
		return $schedules;
	}	
	
	
	/*
	* Adds Job to Processing Queue 
	*/
	public static function add_job_to_queue( $rule , $arguments ) {
		
		/* Debug Mode - Log Event */
				
		Inbound_Automation_Processing::$instance->queue = json_decode( get_option('inbound_automation_queue' , '') , true);
		
		Inbound_Automation_Processing::$instance->queue[] = array( 'rule' => $rule , 'arguments' => $arguments );
		
		update_option( 'inbound_automation_queue' , json_encode( Inbound_Automation_Processing::$instance->queue ) );
		
	}
	
	
	
	/* 
	* Adds Cron Hook to System on Activation  - Create inbound_automation_queue in wp_options
	*/
	public static function add_cron_hook() {
		wp_schedule_event( time(), 'every_two_minutes', self::$instance->event_hook_name );
		add_option( 'inbound_automation_queue' , null , null , 'no' );
	}	
	
	/* 
	* Adds Cron Hook to System on Activation 
	*/
	public static function remove_cron_hook() {
		wp_clear_scheduled_hook( self::$instance->event_hook_name );
	}
	
	/**
	 * Indents a flat JSON string to make it more human-readable.
	 *
	 * @param string $json The original JSON string to process.
	 *
	 * @return string Indented version of the original JSON string.
	 */
	public static function json_indent($json) {

		$result      = '';
		$pos         = 0;
		$strLen      = strlen($json);
		$indentStr   = '  ';
		$newLine     = "\n";
		$prevChar    = '';
		$outOfQuotes = true;

		for ($i=0; $i<=$strLen; $i++) {

			// Grab the next character in the string.
			$char = substr($json, $i, 1);

			// Are we inside a quoted string?
			if ($char == '"' && $prevChar != '\\') {
				$outOfQuotes = !$outOfQuotes;

			// If this character is the end of an element,
			// output a new line and indent the next line.
			} else if(($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos --;
				for ($j=0; $j<$pos; $j++) {
					$result .= $indentStr;
				}
			}

			// Add the character to the result string.
			$result .= $char;

			// If the last character was the beginning of an element,
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
				$result .= $newLine;
				if ($char == '{' || $char == '[') {
					$pos ++;
				}

				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}

			$prevChar = $char;
		}

		return $result;
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

	//print_r(json_decode( get_option('inbound_automation_queue' , '') , true));
}
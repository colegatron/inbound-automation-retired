<?php

/**
*  Processes auotomation jobs
*/


class Inbound_Automation_Processing {

	static $definitions;
	static $queue;
	
	/**
	*  Initializes class
	*/
	public function __construct() {	
	
		/* Load Hooks */
		self::load_hooks();

	}
	
	/**
	*  Loads hooks & filters
	*/
	public static function load_hooks() {
	
		/* Adds automation processing to Inbound heartbeat */
		add_filter( 'inbound_heartbeat', array( __CLASS__ , 'process_rules' ) );
		
	}
	
	/* 
	* Load the Job Queue And Process All Scheduled Jobs 
	*/
	public static function process_rules() {

		self::load_queue();
		
		/* If queue empty quit automation processing */
		if ( !self::$queue || !is_array(self::$queue) ) {
			return;
		}

		/* Loop through queue and process job */
		foreach (self::$queue as $job_id => $job) {
			
			add_action( 'inbound_automation' , function() use( $job_id , $job) {			
				
				$job = Inbound_Automation_Processing::run_job( $job );			
				
				/* unset completed job from queue */
				self::unset_job( $job , $job_id );
				
				
				/* Update Rule Queue After Completed Job */
				Inbound_Automation_Processing::$queue = update_option('inbound_automation_queue' , Inbound_Automation_Processing::$queue  );
			
			} );
		}
	}
	
	/**
	*  Load rule queue
	*/
	public static function load_queue() {
		Inbound_Automation_Processing::$queue = get_option('inbound_automation_queue' , array());
	}
	
	/**
	*  Update rule queue
	*/
	public static function update_queue() {
		if ( get_option( 'inbound_automation_queue' ) !== false ) {			
			update_option( 'inbound_automation_queue' , Inbound_Automation_Processing::$queue );
		} else {
			add_option( 'inbound_automation_queue' , Inbound_Automation_Processing::$queue , null,  'no' );
		}
	}
	
	/**
	*  Unset job from queue
	*  @param $ARRAY $job dataset 
	*  @param INT $job_id id of job to remove 
	*/
	public static function unset_job( $job , $job_id ) {
		/* Remove Job from Rule Queue if Empty */
		if (!$job) {				
			unset( Inbound_Automation_Processing::$queue[ $job_id ] );
			inbound_record_log(  __( 'Job Completed' , 'marketing-automation' ) , 'This job has successfully completed all it\'s tasks.' , $rule_id , 'processing_event' );
		} else {
			/* Tell Log The Job Has Completed */
			$remaining_actions = $job['rule']['meta']['automation_action_blocks'][0] ;
			inbound_record_log(  __( 'End Current Job Taks' , 'marketing-automation' ) , '<h2>Actions Left</h2> <pre>' . print_r( $remaining_actions , true ) .'</pre><h2>Raw Job Data</h2><pre>' . print_r( $job , true ) . '</pre>', $job['rule']['ID'] , 'processing_event' );
			Inbound_Automation_Processing::$queue[ $job_id ] = $job;
		}				
	
	}
	
	/**
	* Run Scheduled Job
	* @param ARRAY $job dataset containing information about the scheduled job
	* @returns ARRAY $job updated dataset
	*/
	public static function run_job( $job ) {

		$action_blocks =  $job['rule']['meta']['automation_action_blocks'][0];
		
		/* Tell Log We Are Running An Job */
		inbound_record_log(  'Start Job' , '<pre>' . print_r( $job_encoded , true ) . '</pre>', $job['rule']['ID'] , 'processing_event' );
		
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
		
			$job['rule']['meta']['automation_action_blocks'][0] = $action_blocks ;	
			
		}
		
		return $job;
	}
	
	/**
	*  	Run Action Block Actions 
	*/
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
				inbound_record_log(  'Action Delayed' , 'Action Set to Be Performed on ' . $block['actions'][ $type ]['meta']['run_date'] . '<h2>Raw Action Block Data</h2><pre>' . print_r($block , true ) . '</pre>', $block['job']['rule']['ID'] , 'delay_event' );
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

	
	/**
	* Run Action
	*/
	public static function run_action( $action , $arguments  ) {
				
		$class = new $action['action_class_name'];
		$action = $class->run_action( $action , $arguments );
		
		return $action;
	}
	

	/**
	* Evaluate Action Block 
	*/
	public static function evaluate_action_block( $block ) {
	
		/* Automatically Evaluate True When There Is No Conditionals */
		if ( $block['action_block_type'] == 'actions' ) {
			return true;
		}
			
		/* Check Action Filters */
		if ( isset( $block['filters'] )  && $block['filters'] && $filters = $block['filters'] ) {
			
			$evaluate = true;
			$evals = array();
			
			/* Check How Many Conditions as True */
			foreach($filters as $filter) {
			
				$arguments = $block['job']['arguments'];
				$db_lookup_filter = self::$definitions->db_lookup_filters[ $filter['filter_id'] ];
				
				$evals[] = self::evaluate_filter( $db_lookup_filter , $filter , $arguments );
				
			}			
		
			/* Return Final Evaluation Decision Based On Eval Nature */
			$evaluate = self::evaluate_filters( $block['action_block_filters_evaluate'] , $evals );
			
			/* Add Extra Data to $block for Log Event */			
			$block['arguments'] = $arguments;
			$block['evaluated'] = $evaluate;
			$block['evals'] = $evals;
			
			
			/* Log Evaluation Attempt */
			inbound_record_log(  
				__( 'Evaluating Action Block' , 'inboun-pro' ) , 
				'<h2>'. __( 'Action Filter Evaluation' , 'inbound-pro' ) .'</h2><p>'. __( 'Action Evaluation Result:' , 'inbound-pro' ) . $evaluate .'</p><p>'. __( 'Action Evaluation Nature:' , 'inbound-pro' ) .'<br> ' . $block['action_block_filters_evaluate'] . '</p><p>' . __( 'Action Evaluation Debug Data:' , 'inbound-pro' ) .'<br> <pre>' . print_r( $evals , true )  . '</pre></p><pre>'.print_r( $block , true ).'</pre>' 
				, $block['job']['rule']['ID'] 
				, 'evaluation_event' 
			);
			
			return $evaluate;			
			
		} else {
			/* No Filters Detected */
			return true;
		}
		
	}
	
	
	/*
	* Evaluate Filter By Comparing Filter with Corresponding Incoming Data
	* @param db_lookup_filter ARRAY contains db lookup data related to action filter being evaluated
	* @param filter ARRAY contains data related to filter being evaluated
	* @param arguments ARRAY contains arguments passed from trigger 
	*
	* @returns ARRAY of evaluation result data
	*/
	public static function evaluate_filter( $db_lookup_filter , $filter , $arguments ) {
		
		$eval = false;
		$class_name = $db_lookup_filter['class_name'];
		$function_name = 'query_' . $filter['action_filter_key'] ;

		$db_lookup = $class_name::$function_name( $arguments );
	
		if ( $db_lookup===null ) {
			
			return array( 
				'filter_key' => $filter['action_filter_key'] ,
				'filter_compare' => $filter['action_filter_compare'],
				'filter_value' => $filter['action_filter_value'],
				'db_lookup_value' => 'EMPTY',
				'eval' => false
			);
			
		}
		
		switch ($filter['action_filter_compare']) {

			case 'greater-than' :

				if ( $filter['action_filter_value'] < $db_lookup ) {
					$eval = true;
				}

				BREAK;

			case 'greater-than-equal-to' :

				if ( $filter['action_filter_value'] <= $db_lookup ) {
					$eval = true;
				}

				BREAK;

			case 'less-than' :

				if ( $filter['action_filter_value'] > $db_lookup ) {
					$eval = true;
				}

				BREAK;


			case 'less-than-equal-to' :

				if ( $filter['action_filter_value'] >= $db_lookup ) {
					$eval = true;
				}

				BREAK;

			case 'contains' :

				if ( stristr( $db_lookup , $filter['action_filter_value'] ) ) {
					$eval = true;
				}

				BREAK;

			case 'equals' :
			
				if (  $filter['action_filter_value'] == $db_lookup ) {
					$eval = true;
				}

				BREAK;

		}

		return array( 
			'filter_key' => $filter['action_filter_key'] ,
			'filter_compare' => $filter['action_filter_compare'],
			'filter_value' => $filter['action_filter_value'],
			'db_lookup_value' => $db_lookup,
			'eval' => $eval
		);

	}
	
	/*
	* Evaluate All Filters Based on Evaluation Condition
	* @param eval_nature STRING contains instructions on how to process filters
	* @param evals ARRAY of indivual filter evaluation results
	*
	* @returns BOOL for overall evaluation result
	*/
	public static function evaluate_filters( $eval_nature , $evals ) {

		switch ($eval_nature) {
			case 'match-any' :
				foreach ( $evals as $eval ) {
					if ($eval['eval']) {
						$evaluate = true;
						break;
					} else {
						$evaluate = false;
					}
				}
				
				BREAK;
				
			case 'match-all' :
				foreach ( $evals as $eval ) {
					if ($eval['eval']) {
						$evaluate = true;
					} else {
						$evaluate = false;
					}
				}
				
				BREAK;
				
			case 'match-none' :
				foreach ( $evals as $eval ) {
					if ($eval['eval']) {
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
	
	/*
	* Adds Job to Processing Queue 
	*/
	public static function add_job_to_queue( $rule , $arguments ) {
		
		Inbound_Automation_Processing::$queue = get_option('inbound_automation_queue' , array() );
		
		if ( !is_array(Inbound_Automation_Processing::$queue) ) {
			Inbound_Automation_Processing::$queue = array();
		}
		
		Inbound_Automation_Processing::$queue[] = array( 'rule' => $rule , 'arguments' => $arguments );
		
		update_option( 'inbound_automation_queue' , json_encode( Inbound_Automation_Processing::$queue ) );
		
	}
	
}

	
/** 
*  Loads automation processing into init
*/
function inbound_automation_processing() {
	$Inbound_Automation_Processing =  new Inbound_Automation_Processing();
}
add_action('init' , 'inbound_automation_processing' , 11 );
	



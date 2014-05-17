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
	public $arguments;
	public $db_lookup_filters;
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
			self::define_arguments();
			self::define_actions();
			self::define_db_lookup_filters();

			/* Add Trigger Listeners to Hooks */
			self::add_trigger_listeners();
		}

		return self::$instance;
	}

	/* Load Rules from CPT and set them into static variatiable */
	public static function load_rules() {

		$rules = get_posts( array (
				'post_type'=> 'automation',
				'post_status' => 'published',
				'posts_per_page' => -1
			)
		);

		self::$instance->rules = $rules;
	}

	/* Load Triggers into Static Variable */
	public static function define_triggers() {
		self::$instance->triggers = apply_filters( 'inbound_automation_triggers' , array() );
	}

	/* Define Trigger Filters */
	public static function define_arguments() {
	
		$arguments = array();

		/* Build Compare Options */
		$compare_args 	= 	array(
			'greater-than' =>'Greater Than',
			'greater-than-equal-to' =>'Greater Than Or Equal To',
			'less-than' => 'Less Than' ,
			'less-than-equal-to' => 'Less Than Or Equal To' ,
			'contains' =>'Contains',
			'equals' => 'Equals',
		);

		/* Extend Compare Options */
		$compare = apply_filters('inbound_automation_compare_args' , $compare_args );

		/* Loop Through Trigger Arguments & Build Filter Setting Array */
		foreach ( self::$instance->triggers as $hook =>$trigger) {

			foreach ($trigger['arguments'] as $key => $argument ) {
				
				/* Load Historic Arugment Keys Associated With Trigger Hook */
				$keys = get_option( $hook . '_' . $argument['id']);

				if ( !$keys ) {
					$keys = array( '-1' => 'No Options Detected' );
				}
				
				/* Build Trigger Filter Data */ 
				$arguments[$argument['id']] = array(
					'id' => $argument['id'],
					'label' => $argument['label'],
					'key_input_type' => 'dropdown',
					'keys' => $keys,
					'compare' => $compare,
					'value_input_type' => 'text',
					'values' => false
				);
			}
		}

		self::$instance->arguments = apply_filters( 'inbound_automation_arguments' , $arguments );

	}
	
	
	/* Define DB Lookup Filters */
	public static function define_db_lookup_filters() {
	
		$db_lookup_filters = array();
		$loaded = array();
		
		/* Build Compare Options */
		$compare_args 	= 	array(
			'greater-than' =>'Greater Than',
			'greater-than-equal-to' =>'Greater Than Or Equal To',
			'less-than' => 'Less Than' ,
			'less-than-equal-to' => 'Less Than Or Equal To' ,
			'contains' =>'Contains',
			'equals' => 'Equals',
		);

		/* Extend Compare Options */
		$compare = apply_filters('inbound_automation_compare_args' , $compare_args );

		/* Loop Through Trigger Arguments & Build Filter Setting Array */
		foreach ( self::$instance->triggers as $hook =>$trigger) {


			if ( !isset($trigger['db_lookup_filters']) ) {
				continue;
			}
			
			foreach ($trigger['db_lookup_filters'] as $db_lookup_filter ) {
				
				/* Make sure a class referrence exists in db lookup options */
				if ( !isset($db_lookup_filter['class_name']) ) {
					continue;
				}
				
				/* Get Class Name */
				$class = $db_lookup_filter['class_name'];
				
				/* Load Queries Only Once */
				if ( array_key_exists( $db_lookup_filter['id'] , $db_lookup_filters ) ) {
					continue;
				}

				/* Load Available Queries DB Lookup Class */
				$keys = $class::get_key_map();

				if ( !$keys ) {
					$keys = array( '-1' => 'No Options Detected' );
				}
				
				/* Build Trigger Filter Data */ 
				$db_lookup_filters[$db_lookup_filter['id']] = array(
					'class_name' => $db_lookup_filter['class_name'],
					'id' => $db_lookup_filter['id'],
					'label' => $db_lookup_filter['label'],
					'key_input_type' => 'dropdown',
					'keys' => $keys,
					'compare' => $compare,
					'value_input_type' => 'text',
					'values' => false
				);
				
			}
		}

		self::$instance->db_lookup_filters = apply_filters( 'inbound_automation_db_lookup_filters' , $db_lookup_filters );

	}

	public static function define_actions() {
		self::$instance->actions = apply_filters( 'inbound_automation_actions' , array() );
	}

	/* Adds Listener Hooks for Building Filters and Rule Processings */
	public static function add_trigger_listeners() {

		foreach (self::$instance->triggers as $hook_name => $trigger) {
			if ( isset($trigger['action_hook']) ) {
				add_action( $trigger['action_hook'] , array( __CLASS__ , 'generate_arguments' ) , 10 , count($trigger['arguments']) ) ;
				add_action( $trigger['action_hook'] , array( __CLASS__ , 'process_trigger' ) , 10 , count($trigger['arguments']) ) ;
			}
		}
	}

	/* Check Trigger for Rule Match */
	public static function process_trigger() {

		$trigger = current_filter();

		foreach (self::$instance->rules  as $rule) {

			$rule_meta = get_post_meta( $rule->ID );
			$rule->meta = $rule_meta;

			if ( $rule_meta['automation_trigger'][0] == $trigger ) {

				$evaluate = true;
				$evals = array();
				$arguments =  func_get_args();

				/* Check Trigger Filters */
				if ( isset( $rule_meta['automation_argument_filters'][0] )  && $rule_meta['automation_argument_filters'][0] && $argument_filters = json_decode( $rule_meta['automation_argument_filters'][0] , true ) ) {
					
					foreach($argument_filters as $filter) {
						$key = self::get_argument_key_from_trigger( $filter , $trigger );
						$target_argument = $arguments[ $key ];
						$evals[] = self::evaluate_trigger_filter( $filter , $target_argument );
					}
					
					/* Check Evalaution Nature for Final Decision */
					$evaluate = self::evaluate_arguments( $rule_meta['automation_trigger_filters_evaluate'][0] , $evals );
				}

				
				/* Log Event */
				self::record_trigger_event( $rule , $arguments , $trigger , $evaluate , $evals , $rule_meta['automation_trigger_filters_evaluate'][0] );
				
				/* Add Job to Queue if Passes Trigger Filters */
				if ( $evaluate  ) {
					/* Log Evaluation Message */
					self::record_schedule_event( $rule , $arguments , $trigger , $evaluate );
				
					self::$Processor->add_job_to_queue( $rule , $arguments );
				}
			}
		}

	}

	/*
	* Evaluate All Filters Based on Evaluation Condition
	*/
	public static function evaluate_arguments( $eval_nature , $evals ) {

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
	* Get Argument Key From Trigger
	*/
	public static function get_argument_key_from_trigger( $argument , $trigger ) {

		foreach ( self::$instance->triggers[$trigger]['arguments'] as $key => $arg ) {
			if ( $argument['filter_id'] == $arg['id'] ) {
				return $key;
			}
		}

	}

	/*
	* Evaluate Filter By Comparing Filter with Corresponding Incoming Data
	*/
	public static function evaluate_trigger_filter( $filter , $target_argument ) {
		
		$eval = false;
		
		switch ($filter['trigger_filter_compare']) {

			case 'greater-than' :

				if ( $filter['trigger_filter_value'] < $target_argument[ $filter['trigger_filter_key'] ] ) {
					$eval = true;
				}

				BREAK;

			case 'greater-than-equal-to' :

				if ( $filter['trigger_filter_value'] <= $target_argument[ $filter['trigger_filter_key'] ] ) {
					$eval = true;
				}

				BREAK;

			case 'less-than' :

				if ( $filter['trigger_filter_value'] > $target_argument[ $filter['trigger_filter_key'] ] ) {
					$eval = true;
				}

				BREAK;


			case 'less-than-equal-to' :

				if ( $filter['trigger_filter_value'] >= $target_argument[ $filter['trigger_filter_key'] ] ) {
					$eval = true;
				}

				BREAK;

			case 'contains' :

				if ( stristr( $target_argument[ $filter['trigger_filter_key'] ] , $filter['trigger_filter_value'] ) ) {
					$eval = true;
				}

				BREAK;

			case 'equals' :

				if (  $filter['trigger_filter_value'] == $target_argument[ $filter['trigger_filter_key'] ] ) {
					$eval = true;
				}

				BREAK;

		}

		return array( 
			'filter_key' => $filter['trigger_filter_key'] ,
			'filter_compare' => $filter['trigger_filter_compare'],
			'filter_value' => $filter['trigger_filter_value'],
			'eval' => $eval
		);

	}

	/*
	* Record Trigger Event in Logs
	*/
	public static function record_trigger_event( $rule , $arguments , $trigger , $evaluate , $evals , $eval_nature ) {

		$rule_data = self::json_indent(json_encode($rule));
		$argument_data = self::json_indent(json_encode($arguments));
		$evaluate = (!$evaluate) ? __( 'Blocked' , 'ma' ) : __( 'Passed' , 'ma' );
		$evals = self::json_indent(json_encode($evals));

		$message = "<p>Action Hook: ". $trigger . '</p>';
		$message .= "<h2>Trigger Filter Evaluation</h2><p>Trigger Evaluation Result: ". $evaluate .'</p><p>Trigger Evaluation Nature: <br> ' . $eval_nature . '</p><p>Trigger Evaluation Debug Data: <br> ' . $evals . '</p>';
		$message .= "<p><h2>Rule Data:</h2> <br> <pre>". $rule_data . '</pre></p>';
		$message .= "<p><h2>Argument Data:</h2> <br> <pre>". $argument_data . '</pre></p>';


		inbound_record_log( 'Trigger Event' , $message , $rule->ID , 'trigger_event' );
	}

	/*
	* Record Schedule Event in Logs
	*/
	public static function record_schedule_event( $rule , $arguments ) {

		$rule_data = self::json_indent(json_encode($rule));
		$argument_data = self::json_indent(json_encode($arguments));

		$message = "<p><h2>Rule Data:</h2> <br> <pre>". $rule_data . '</pre></p>';
		$message .= "<p><h2>Argument Data:</h2> <br> <pre>". $argument_data . '</pre></p>';


		inbound_record_log( 'Schedule Event' , $message , $rule->ID , 'schedule_event' );

	}

	/*
	* Generate Filter Data Map
	* Checks the Argument Keys and Saves them to Memory for Rule Setup UI
	*/
	public static function generate_arguments() {

		$triggers = self::$instance->triggers;
		$arguments = func_get_args();
		$action_hook =  current_filter();

		foreach ($arguments as $key => $argument) {

			if ( is_array($argument) ) {


				$filter = array();
				$filter_name = $triggers[$action_hook]['arguments'][$key]['id'];

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
}

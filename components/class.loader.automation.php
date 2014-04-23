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
			'greater-than-equal-to' =>'Greater Than Or Equal To',
			'less-than' => 'Less Than' ,
			'less-than-equal-to' => 'Less Than Or Equal To' ,
			'contains' =>'Contains',
			'equals' => 'Equals',
		);

		/* Extend Compare Options */
		$compare = apply_filters('automation_filter_lead_data_supports' , $compare_args );

		/* Loop Through Trigger Arguments & Build Filter Setting Array */
		foreach ( self::$instance->triggers as $hook =>$trigger) {

			foreach ($trigger['arguments'] as $key => $argument ) {
				$keys = get_option( $hook . '_' . $argument['id']);

				if ( !$keys ) {
					$keys = array( '-1' => 'No Options Detected' );
				}

				$filters[$argument['id']] = array(
					'id' => $argument['id'],
					'label' => $argument['label'],
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

		foreach (self::$instance->triggers as $hook_name => $trigger) {
			if ( isset($trigger['action_hook']) ) {
				add_action( $trigger['action_hook'] , array( __CLASS__ , 'generate_filters' ) , 10 , count($trigger['arguments']) ) ;
				add_action( $trigger['action_hook'] , array( __CLASS__ , 'process_trigger' ) , 10 , count($trigger['arguments']) ) ;
			}
		}
	}

	/* Check Trigger for Rule Match */
	public static function process_trigger() {

		$current_filter = current_filter();

		foreach (self::$instance->rules  as $rule) {

			$rule_meta = get_post_meta( $rule->ID );
			$rule->meta = $rule_meta;

			if ( $rule_meta['automation_trigger'][0] == $current_filter ) {

				$evaluate = false;
				$evals = array();
				$arguments =  func_get_args();

				/* Check Trigger Filters */
				if ( isset( $rule_meta['automation_trigger_filters'][0] )  && $rule_meta['automation_trigger_filters'][0] && $trigger_filters = json_decode( $rule_meta['automation_trigger_filters'][0] , true ) ) {

					foreach($trigger_filters as $filter) {
						$key = self::get_argument_key_from_filter( $filter , $current_filter );
						$target_argument = $arguments[ $key ];
						$evals[] = self::evaluate_trigger_filter( $filter , $target_argument );

					}
				}

				/* Check Evalaution Nature for Final Decision */
				$evaluate = self::evaluate_filters( $rule_meta['automation_trigger_filters_evaluate'][0] , $evals );

				/* Log Event */
				self::record_trigger_event( $rule , $arguments , $current_filter , $evaluate );

				/* Add Job to Queue if Passes Trigger Filters */
				if ( $evaluate  ) {
					usleep(900000);
					$log_id = self::record_schedule_event( $rule , $arguments , $current_filter , $evaluate );
					self::$Processor->add_job_to_queue( $rule , $arguments , $log_id );
				}
			}
		}

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


	/*
	* Get Argument Key From Filter
	*/
	public static function get_argument_key_from_filter( $filter , $current_filter ) {

		foreach ( self::$instance->triggers[$current_filter]['arguments'] as $key => $argument ) {
			if ( $filter['filter_id'] == $argument['id'] ) {
				return $key;
			}
		}

	}

	/*
	* Evaluate Filter By Comparing Filter with Corresponding Incoming Data
	*/
	public static function evaluate_trigger_filter( $filter , $target_argument ) {

		switch ($filter['trigger_filter_compare']) {

			case 'greater-than' :

				if ( $filter['trigger_filter_value'] > $target_argument[ $filter['trigger_filter_key'] ] ) {
					return true;
				}

				BREAK;

			case 'greater-than-equal-to' :

				if ( $filter['trigger_filter_value'] >= $target_argument[ $filter['trigger_filter_key'] ] ) {
					return true;
				}

				BREAK;

			case 'less-than' :

				if ( $filter['trigger_filter_value'] < $target_argument[ $filter['trigger_filter_key'] ] ) {
					return true;
				}

				BREAK;


			case 'less-than-equal-to' :

				if ( $filter['trigger_filter_value'] <= $target_argument[ $filter['trigger_filter_key'] ] ) {
					return true;
				}

				BREAK;

			case 'contains' :

				if ( stristr( $target_argument[ $filter['trigger_filter_key'] ] , $filter['trigger_filter_value'] ) ) {
					return true;
				}

				BREAK;

			case 'equals' :

				if (  $filter['trigger_filter_value'] == $target_argument[ $filter['trigger_filter_key'] ] ) {
					return true;
				}

				BREAK;

		}

		return false;

	}

	/*
	* Record Trigger Event in Logs
	*/
	public static function record_trigger_event( $rule , $arguments , $current_filter , $evaluate ) {

		$rule_data = self::json_indent(json_encode($rule));
		$argument_data = self::json_indent(json_encode($arguments));

		$message = "<p>Action Hook: ". $current_filter . '</p>';
		$message .= "<p>Trigger Filter Evaluation: ". $evaluate . '</p>';
		$message .= "<p><h2>Rule Data:</h2> <br> <pre>". $rule_data . '</pre></p>';
		$message .= "<p><h2>Argument Data:</h2> <br> <pre>". $argument_data . '</pre></p>';


		$log_id = inbound_record_log( 'Trigger Event' , $message , $rule->ID , 'trigger_event' );

		return $log_id;
	}

	/*
	* Record Schedule Event in Logs
	*/
	public static function record_schedule_event( $rule , $arguments ) {

		$rule_data = self::json_indent(json_encode($rule));
		$argument_data = self::json_indent(json_encode($arguments));

		$message = "<p><h2>Rule Data:</h2> <br> <pre>". $rule_data . '</pre></p>';
		$message .= "<p><h2>Argument Data:</h2> <br> <pre>". $argument_data . '</pre></p>';


		$log_id = inbound_record_log( 'Schedule Event' , $message , $rule->ID , 'schedule_event' );

		return $log_id;
	}

	/*
	* Generate Filter Data Map
	* Checks the Argument Keys and Saves them to Memory for Rule Setup UI
	*/
	public static function generate_filters() {

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

//$rule_meta = get_post_meta( '92682' );
//print_r($rule_meta);
//$json = '{"rule":{"ID":92682,"post_author":"2","post_date":"2014-03-19 14:19:31","post_date_gmt":"2014-03-19 21:19:31","post_content":"","post_title":"Test Rule","post_excerpt":"","post_status":"publish","comment_status":"closed","ping_status":"closed","post_password":"","post_name":"test-rule","to_ping":"","pinged":"","post_modified":"2014-04-15 15:32:12","post_modified_gmt":"2014-04-15 22:32:12","post_content_filtered":"","post_parent":0,"guid":"http://localhost/inboundsoon/?post_type=automation&p=92682","menu_order":0,"post_type":"automation","post_mime_type":"","comment_count":"0","filter":"raw","meta":{"_edit_last":["2"],"_edit_lock":["1397611607:2"],"automation_trigger":["inbound_store_lead_post"],"automation_trigger_filters":["{"1":{"filter_id":"lead_data","trigger_filter_key":"wpleads_first_name","trigger_filter_compare":"equals","trigger_filter_value":"Hudson"}}"],"automation_trigger_filters_evaluate":["match-any"],"automation_action_blocks":["{"2":{"action_block_id":"2","action_block_type":"if-then","action_block_filters_evaluate":"match-none","filters":[],"actions":{"then":{"1":{"action_name":"send_email","to_address":"{{lead-email}}","from_address":"{{admin-email}}","email_template":"92676"}}}},"3":{"action_block_id":"3","action_block_type":"if-then-else","action_block_filters_evaluate":"match-all","filters":[],"actions":{"then":{"1":{"action_name":"send_email","to_address":"{{lead-email}} two","from_address":"{{admin-email}} two","email_template":"92676"}},"else":{"1":{"action_name":"send_email","to_address":"{{lead-email}} else","from_address":"{{admin-email}} else","email_template":"92676"}}}},"4":{"action_block_id":"4","action_block_type":"while","action_block_filters_evaluate":"match-none","filters":[],"actions":{"then":{"1":{"action_name":"send_email","to_address":"{{lead-email}} while","from_address":"{{admin-email}} while","email_template":"92676"}}}},"5":{"action_block_id":"5","action_block_type":"actions","action_block_filters_evaluate":null,"filters":[],"actions":{"then":{"1":{"action_name":"send_email","to_address":"{{lead-email}} just actions","from_address":"{{admin-email}} just actions","email_template":"92676"}}}}}"]}},"arguments":[{"user_ID":2,"wordpress_date_time":"2014-04-15 18:26:59 UTC","wpleads_email_address":"atwell.publishing@gmail.com","page_views":"{\"92545\":[\"2014-04-15 18:26:12 UTC\"]}","form_input_values":"{\"wpleads_first_name\":\"Hudson\",\"wpleads_email_address\":\"atwell.publishing@gmail.com\",\"wp_cta_id\":\"92838\",\"wp_cta_vid\":\"0\"}","Mapped_Data":"{\"page_view_count\":1,\"leads_list\":\"\",\"source\":\"http://inboundsoon.dev/testing/\",\"page_id\":\"92545\",\"page_views\":\"{\\\"92545\\\":[\\\"2014-04-15 18:26:12 UTC\\\"]}\",\"name\":\"Hudson\",\"email\":\"atwell.publishing@gmail.com\",\"address\":\"atwell.publishing@gmail.com\",\"form_name\":\"Auto Responder Form\",\"first_name\":\"Hudson\",\"phone\":false,\"company\":false,\"variation\":0,\"post_type\":\"post\",\"wp_lead_uid\":\"65GnIXUWoNZDDiQrcNX5JL0keauyTfghgG3\",\"ip_address\":\"127.0.0.1\",\"search_data\":\"null\"}","page_view_count":1,"source":"http://inboundsoon.dev/testing/","page_id":"92545","variation":0,"post_type":"post","wp_lead_uid":"65GnIXUWoNZDDiQrcNX5JL0keauyTfghgG3","lead_lists":[""],"ip_address":"127.0.0.1","search_data":null,"wpleads_full_name":"","wpleads_first_name":"Hudson","wpleads_last_name":"","wpleads_company_name":"false","wpleads_mobile_phone":"false","wpleads_address_line_1":"atwell.publishing@gmail.com","wpleads_address_line_2":"","wpleads_city":"","wpleads_region_name":"","wpleads_zip":"","lead_id":"92864","referral_data":"{"1":{"source":"NA","datetime":"2014-04-02 17:08:15 UTC","original_source":1},"2":{"source":"NA","datetime":"2014-04-03 23:41:50 UTC"},"3":{"source":"NA","datetime":"2014-04-03 23:42:16 UTC"},"4":{"source":"NA","datetime":"2014-04-03 23:42:42 UTC"},"5":{"source":"NA","datetime":"2014-04-03 23:43:09 UTC"},"6":{"source":"NA","datetime":"2014-04-03 23:44:04 UTC"},"7":{"source":"NA","datetime":"2014-04-03 23:45:15 UTC"},"8":{"source":"NA","datetime":"2014-04-03 23:45:56 UTC"},"9":{"source":"NA","datetime":"2014-04-10 22:11:00 UTC"},"10":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:06:20 UTC"},"11":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:16:41 UTC"},"12":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:18:49 UTC"},"13":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:19:07 UTC"},"14":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:22:30 UTC"},"15":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:24:36 UTC"},"16":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:27:24 UTC"},"17":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:27:42 UTC"},"18":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:30:31 UTC"},"19":{"source":"http:\/\/inboundsoon.dev\/wp-admin\/edit.php","datetime":"2014-04-15 16:33:40 UTC"},"20":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:39:44 UTC"},"21":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:40:53 UTC"},"22":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:47:38 UTC"},"23":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:48:56 UTC"},"24":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:49:18 UTC"},"25":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:49:43 UTC"},"26":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:50:03 UTC"},"27":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:50:20 UTC"},"28":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:50:36 UTC"},"29":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:52:36 UTC"},"30":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 16:57:33 UTC"},"31":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 17:36:09 UTC"},"32":{"source":"NA","datetime":"2014-04-15 18:12:57 UTC"},"33":{"source":"http:\/\/inboundsoon.dev\/testing\/","datetime":"2014-04-15 18:26:59 UTC"}}"}],"log_id":93000}';
//print_r(json_decode($json , true) );
//exit;
//update_option('inbound_automation_queue' , '');
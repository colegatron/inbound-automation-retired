<?php

if ( !class_exists( 'Inbound_Metaboxes_Automation' ) ) {

	class Inbound_Metaboxes_Automation {

		static $Inbound_Automation;

		static $triggers;

		static $post_type;
		static $trigger;
		static $trigger_evaluate;
		static $trigger_filters;
		static $action_blocks;

		public function __construct() {
			self::$post_type = 'automation';
			self::load_hooks();
		}

		public static function load_hooks() {
			/* Setup Variables */
			add_action( 'posts_selection' , array( __CLASS__ , 'load_variables') );

			/* Add Metaboxes */
			add_action( 'add_meta_boxes' , array( __CLASS__ , 'define_metaboxes') );

			/* Replace Default Title Text */
			add_filter( 'enter_title_here' , array( __CLASS__ , 'change_title_text' ) , 10, 2 );

			/* Add Save Actions */
			add_action( 'save_post' , array( __CLASS__ , 'save_automation' ) );

			/* Enqueue JS */
			add_action( 'admin_enqueue_scripts', array( __CLASS__ , 'enqueue_admin_scripts' ) );
			add_action( 'admin_print_footer_scripts', array( __CLASS__ , 'print_admin_scripts' ) );

			/* Setup Ajax Listeners - Get Trigger Argument Filters*/
			add_action( 'wp_ajax_nopriv_automation_get_trigger_arguments', array( __CLASS__ , 'ajax_load_trigger_arguments' ) );
			add_action( 'wp_ajax_automation_get_trigger_arguments', array( __CLASS__ , 'ajax_load_trigger_arguments' ) );
			
			/* Setup Ajax Listeners - Get Action DB Lookup Filters*/
			add_action( 'wp_ajax_nopriv_automation_get_db_lookup_filters', array( __CLASS__ , 'ajax_load_db_lookup_filters' ) );
			add_action( 'wp_ajax_automation_get_db_lookup_filters', array( __CLASS__ , 'ajax_load_db_lookup_filters' ) );

			/* Setup Ajax Listeners - Get Actions */
			add_action( 'wp_ajax_nopriv_automation_get_actions', array( __CLASS__ , 'ajax_load_action_definitions' ) );
			add_action( 'wp_ajax_automation_get_actions', array( __CLASS__ , 'ajax_load_action_definitions' ) );

			/* Setup Ajax Listeners - Build Trigger Filter Settings*/
			add_action( 'wp_ajax_nopriv_automation_build_trigger_filter', array( __CLASS__ , 'ajax_build_trigger_filter' ) );
			add_action( 'wp_ajax_automation_build_trigger_filter', array( __CLASS__ , 'ajax_build_trigger_filter' ) );
			
			/* Setup Ajax Listeners - Build DB Lookup Filter Settings*/
			add_action( 'wp_ajax_nopriv_automation_build_db_lookup_filter', array( __CLASS__ , 'ajax_build_db_lookup_filter' ) );
			add_action( 'wp_ajax_automation_build_db_lookup_filter', array( __CLASS__ , 'ajax_build_db_lookup_filter' ) );

			/* Setup Ajax Listeners - Build Action Settings*/
			add_action( 'wp_ajax_nopriv_automation_build_action', array( __CLASS__ , 'ajax_build_action' ) );
			add_action( 'wp_ajax_automation_build_action', array( __CLASS__ , 'ajax_build_action' ) );


			/* Setup Ajax Listeners - Build Action Block*/
			add_action( 'wp_ajax_nopriv_automation_build_action_block', array( __CLASS__ , 'ajax_build_action_block' ) );
			add_action( 'wp_ajax_automation_build_action_block', array( __CLASS__ , 'ajax_build_action_block' ) );

		}

		public static function load_variables() {
			global $post;

			if ( !isset($post) || $post->post_type != self::$post_type ) {
				return;
			}

			/* Load Automation Definitions */
			self::$Inbound_Automation =	Inbound_Automation_Load_Extensions();
			self::$triggers = self::$Inbound_Automation->triggers;

			/* Load Automation Meta */
			self::$trigger = get_post_meta( $post->ID , 'automation_trigger', true );
			self::$trigger_evaluate = get_post_meta( $post->ID , 'automation_trigger_filters_evaluate', true );
			self::$trigger_filters = json_decode( get_post_meta( $post->ID , 'automation_trigger_filters', true ) , true );
			self::$action_blocks = json_decode( get_post_meta( $post->ID , 'automation_action_blocks', true ) , true );

			//print_r(self::$action_blocks);
		}

		public static function define_metaboxes()
		{
			global $post;

			if ( $post->post_type != self::$post_type ) {
				return;
			}

			/* Template Select Metabox */
			add_meta_box(
				'inbound_automation_setup', // $id
				__( 'Setup Automation Rule', 'leads' ),
				array( __CLASS__ , 'display_container' ), // $callback
				self::$post_type ,
				'normal',
				'high'
			);


		}

		public static function display_container() {
			global $post;

			self::print_nav();
			self::print_trigger_container();
			self::print_actions_container();
			self::print_logs_container();

		}

		public static function print_nav() {

			$nav_elements = array(
				array(
					'id' => 'trigger-container',
					'label' => 'Trigger',
					'class' => 'nav_trigger',
					'default' => true
					),
				array(
					'id' => 'actions-container',
					'label' => 'Actions',
					'class' => 'nav_actions',
					),
				array(
					'id' => 'logs-container',
					'label' => 'Logs',
					'class' => 'nav_logs',
					)
			);

			$nav_elements = apply_filters( 'inbound_automation_nav_elements' , $nav_elements );

			echo '<ul class="nav nav-pills">';

			foreach ($nav_elements as $nav) {
				echo '<li class="'.( isset($nav['default']) && $nav['default']	? 'active' : '' ).' '.( isset($nav['class']) ? $nav['class'] : '' ) .'" >';
				echo '<a href="#" class="navlink" id="'.$nav['id'].'">' . $nav['label'] . '</a>';
				echo '</li>';
			}

			echo '</ul>';
		}

		public static function print_trigger_container() {

			?>
			<div class='nav-container nav-reveal trigger-container' id='trigger-container'>
				<table class='table-trigger-container'>
					<tr class='tr-trigger-select'>
						<td>
							<h2>Select Trigger </h2>
							<select class='trigger-dropdown form-control' id='trigger-dropdown' name='trigger'>
							<?php
							echo '<option value="-1" class="">Select Trigger</option>';
							foreach ( self::$triggers as $hook => $trigger ) {
								echo '<option
											value="'.$hook.'"
											class="'.( isset($trigger['icon_class']) ? $trigger['icon_class'] : '' ) .'"
											'.( isset(self::$trigger) && self::$trigger == $hook ? 'selected="selected"' : '' ) .'
											>'.$trigger['label'].'</option>';
							}

							?>
							</select>
						</td>
					</tr>
					<tr class="tr-filter-select">
						<td class='td-filter-add-dropdown' id='argument-filters-container'>
							<h2> Define Conditions
							<div class='trigger-filter-evaluate <?php if ( !isset( self::$trigger_filters ) ||	count(self::$trigger_filters) < 1 ) { echo 'nav-hide'; } ?>'>
								<div class="btn-group" data-toggle="buttons">
								<label class="btn btn-default  <?php if (	!self::$trigger_evaluate || self::$trigger_evaluate == 'match-all' ){ echo 'active'; } ?>">
									<input type='radio' name='trigger_filters_evaluate' value='match-all' <?php if (	!self::$trigger_evaluate || self::$trigger_evaluate == 'match-all' ){ echo 'checked="checked"'; } ?>> Match All
								</label>	
								<label class="btn btn-default  <?php if ( self::$trigger_evaluate == 'match-any' ){ echo 'active'; } ?>">
									<input type='radio' name='trigger_filters_evaluate' value='match-any' <?php if ( self::$trigger_evaluate == 'match-any' ){ echo 'checked="checked"'; } ?>> Match Any
								</label>
								<label class="btn btn-default  <?php if ( self::$trigger_evaluate == 'match-none' ){ echo 'active'; } ?>">
									<input type='radio' name='trigger_filters_evaluate' value='match-none' <?php if ( self::$trigger_evaluate == 'match-none' ) { echo 'checked="checked"'; } ?>> Match None
								</label>
							</div>
							</h2>
							<br>
							
							
							
							
							<div class="btn-group add-filter">
							  <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" >
								Add Filter <span class="caret"></span>
							  </button>
							  <ul class="dropdown-menu trigger-filters" role="menu" >
								<li><a >Select a trigger...</a></li>
							  </ul>
							</div>
							
							
							<?php
							/* Load Trigger Filters if available */
							if ( isset( self::$trigger_filters ) ) {

								foreach (self::$trigger_filters as $child_id => $filter) {

									$args = array(
										'filter_id' => $filter['filter_id'],
										'action_block_id' => 0,
										'child_id' => $child_id,
										'input_name_filter_id' => 'trigger_argument_id',
										'input_name_filter_key' => 'trigger_filter_key',
										'input_name_filter_compare' => 'trigger_filter_compare',
										'input_name_filter_value' => 'trigger_filter_value',
										'defaults' => $filter
									);

									$html = self::ajax_build_trigger_filter( $args );
									echo $html;

								}

							}

							?>

						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		public static function print_actions_container() {
			
			?>
			<div class='nav-container nav-hide actions-container' id='actions-container' >
				<table class='table-trigger-container'>
					<tr class='tr-button-block'>
						<a href="#" id="actions" class="add-action-block button button-secondary full-row-button">Add Actions</a>
						<a href="#" id="if-then" class="add-action-block button button-secondary full-row-button">Add If/Then Actions</a>
						<a href="#" id="if-then-else" class="add-action-block button button-secondary full-row-button">Add If/Then/Else Actions</a>
						<a href="#" id="while" class="add-action-block button button-secondary full-row-button">Add While Actions</a>
					</tr>
				</table>

				<?php

				/* Load Action Blocks If Available */
				if ( isset(self::$action_blocks) ) {

					foreach ( self::$action_blocks as $block_id => $block ) {

						$html = self::ajax_build_action_block( $block['action_block_type'] , $block['action_block_id'] , $action_priority = null, $block );
						echo $html;

					}

				}

				?>

			</div>
			<?php
		}

		public static function print_logs_container() {
			global $inbound_automation_logs, $post;

			$logs = array_reverse( $inbound_automation_logs->get_logs( $post->ID ) , true );
			
			?>
			<style>
				.tr-log-entry-content {
					display:none;
				}
				
				#th-log-id, #th-log-expand {
					width:50px;
				}
				
				.tablesorter {
					table-layout:fixed;
					background:#fff;
					max-width:100%;
					border-spacing:0;
					width:100%;
					margin:10px 0;
					border:1px solid #ddd;
					border-collapse:separate;
					*border-collapse:collapsed;
					-webkit-box-shadow:0 0 4px rgba(0,0,0,0.10);
					-moz-box-shadow:0 0 4px rgba(0,0,0,0.10);
							box-shadow:0 0 4px rgba(0,0,0,0.10);
				}
				.tablesorter th, .tablesorter td {
					padding:8px;
					line-height:18px;
					text-align:left;
					border-top:1px solid #ddd;
					word-wrap:break-word;
				}
				.tablesorter th {
					background:#eee;
					background:-webkit-gradient(linear, left top, left bottom, from(#f6f6f6), to(#eee));
					background:-moz-linear-gradient(top, #f6f6f6, #eee);
					text-shadow:0 1px 0 #fff;
					font-weight:bold;
					vertical-align:bottom;
				}
				.tablesorter td {
					vertical-align:top;
				}
				.tablesorter thead:first-child tr th,
				.tablesorter thead:first-child tr td {
					border-top:0;
				}
				
				.tablesorter tbody + tbody {
					border-top:2px solid #ddd;
				}
				.tablesorter th + th,
				.tablesorter td + td,
				.tablesorter th + td,
				.tablesorter td + th {
					border-left:1px solid #ddd;
				}
				.tablesorter thead:first-child tr:first-child th,
				.tablesorter tbody:first-child tr:first-child th,
				.tablesorter tbody:first-child tr:first-child td {
					border-top:0;
				}
			</style>
			<div class='nav-container nav-hide logs-container' id='logs-container' >
				<table class='tablesorter'>
					<tr> 
						<th class=" sort-header" id='th-log-id'>Log ID</th> 
						<th class=" sort-header" id='th-log-title'>Log Title</th> 
						<th class=" sort-header" id='th-log-date'>Log Date</th> 						
						<th class=" sort-header" id='th-log-date'>Log Type</th> 						
						<th class=" sort-header" id='th-log-expand'>Expand</th> 						
					</tr>
					<?php 
					
					foreach ($logs as $key => $log) {
						
						echo '<tr class="tr-log-entry"	data-id="'.$key.'">';
						echo '	<td class="td-log log-title">'.$key.'</td>';
						echo '	<td class="td-log log-title">'.$log['log_title'].'</td>';
						echo '	<td class="td-log log-datetime">'.$log['log_datetime'].'</td>';
						echo '	<td class="td-log log-datetime">'.$log['log_type'].'</td>';
						echo '	<td class="td-log log-datetime"><a href="#" class="toggle-log-content" data-id="'.$key.'">+/-</a></td>';
						echo '</tr>';
						echo '<tr class="tr-log-entry-content" id="log-content-'.$key.'" data-id="'.$key.'">';
						echo '	<td colspan=4 class="td-log-entry-content"data-id="'.$key.'" >';
						echo 		stripslashes(base64_decode($log['log_content']));
						echo '	</td>';
						echo '</td>';
						echo '</tr>';
					}
					
					?>
				</table>
			</div>
			<?php
		}

		public static function save_automation( $post_id )
		{
			global $post;

			if ( !isset( $post ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ){
				return;
			}

			if ($post->post_type!=self::$post_type)	{
				return;
			}

			/* Save Automation Trigger */
			if ( isset ( $_POST[ 'trigger' ] ) ) {
				update_post_meta( $post_id, 'automation_trigger', $_POST[ 'trigger' ] );
			}

			/* Save Trigger Filters */
			if ( isset ( $_POST[ 'trigger_filter_value' ] ) ) {

				$filters = array();

				foreach ( $_POST['trigger_filter_key'] as $id => $value ) {

					( isset( $_POST['trigger_argument_id'][$id] ) ) ? $filters[$id]['filter_id'] = $_POST['trigger_argument_id'][$id] : $filters[$id]['filter_id'] = null;
					( isset( $_POST['trigger_filter_key'][$id] ) ) ? $filters[$id]['trigger_filter_key'] = $_POST['trigger_filter_key'][$id] : $filters[$id]['key'] = null;
					( isset( $_POST['trigger_filter_compare'][$id] ) ) ? $filters[$id]['trigger_filter_compare'] = $_POST['trigger_filter_compare'][$id] : $filters[$id]['trigger_filter_compare'] = null;
					( isset( $_POST['trigger_filter_value'][$id] ) ) ? $filters[$id]['trigger_filter_value'] = $_POST['trigger_filter_value'][$id] : $filters[$id]['value'] = null;

				}

				$filters = json_encode( $filters );

				update_post_meta( $post_id, 'automation_trigger_filters', $filters );

			} else {
				update_post_meta( $post_id, 'automation_trigger_filters', '' );
			}

			/* Save Trigger Filter Evaulation Nature */
			if ( isset ( $_POST[ 'trigger_filters_evaluate' ] ) ) {
				update_post_meta( $post_id, 'automation_trigger_filters_evaluate', $_POST[ 'trigger_filters_evaluate' ] );
			}

			/* Save Action Blocks */
			if ( isset ( $_POST['action_block_id'] ) ) {

				$action_blocks = array();

				foreach ( $_POST['action_block_id'] as $block_id ) {


					if ( isset( $_POST['action_block_type'][$block_id] ) ) {
						$action_blocks[$block_id]['action_block_id'] = $_POST['action_block_id'][$block_id];
						$action_blocks[$block_id]['action_block_type'] = $_POST['action_block_type'][$block_id];
						$action_blocks[$block_id]['action_block_filters_evaluate'] = $_POST['action_block_filters_evaluate'][$block_id];
					}

					/* Get Action Filter Conditions for this block if they exist */
					$filters = array();

					if ( isset( $_POST['action_filter_value'][$block_id] ) ) {

						foreach ( $_POST['action_filter_value'][$block_id] as $id => $filter_value ) {

							( isset( $_POST['action_filter_id'][$block_id][$id] ) ) ? $filters[$id]['filter_id'] = $_POST['action_filter_id'][$block_id][$id] : $filters[$id]['filter_id'] = null;
							( isset( $_POST['action_filter_key'][$block_id][$id] ) ) ? $filters[$id]['action_filter_key'] = $_POST['action_filter_key'][$block_id][$id] : $filters[$id]['key'] = null;
							( isset( $_POST['action_filter_compare'][$block_id][$id] ) ) ? $filters[$id]['action_filter_compare'] = $_POST['action_filter_compare'][$block_id][$id] : $filters[$id]['action_filter_compare'] = null;
							( isset( $_POST['action_filter_value'][$block_id][$id] ) ) ? $filters[$id]['action_filter_value'] = $_POST['action_filter_value'][$block_id][$id] : $filters[$id]['value'] = null;

						}

					}

					/* Add Filters to $action_blocks */
					$action_blocks[$block_id]['filters'] = $filters;

					/* Get Then Actions For This Block If They Exist */
					$actions = array();
					if ( isset( $_POST['action_name'][$block_id]['then'] ) ) {

						/* Get Action Definitions */
						$Inbound_Automation =	Inbound_Automation_Load_Extensions();
						$actions_definitions = $Inbound_Automation->actions;

						foreach ( $_POST['action_name'][$block_id]['then'] as $child_id => $action_name ) {

							$this_action = $actions_definitions[ $action_name ];

							if ( !isset( $this_action['settings']) ) {
								continue;
							}
							
							$actions[$child_id]['action_name'] = $action_name;
							$actions[$child_id]['action_class_name'] = $this_action['class_name'];
							
							foreach ( $this_action['settings'] as $setting ) {

								if ( isset( $_POST[$setting['id']][$block_id]['then'][$child_id] ) ) {
									$actions[$child_id][$setting['id']] = $_POST[$setting['id']][$block_id]['then'][$child_id];
								}

							}

						}

					}

					/* Add Actions to Action Block */
					$action_blocks[$block_id]['actions']['then'] = $actions;
					
					
					/* Get Else Actions For This Block If They Exist */
					$actions = array();
					if ( isset( $_POST['action_name'][$block_id]['else'] ) ) {

						/* Get Action Definitions */
						$Inbound_Automation =	Inbound_Automation_Load_Extensions();
						$actions_definitions = $Inbound_Automation->actions;

						foreach (	$_POST['action_name'][$block_id]['else'] as $child_id => $action_name ) {

							$this_action = $actions_definitions[ $action_name ];

							if ( !isset( $this_action['settings']) ) {
								continue;
							}
							
							$actions[$child_id]['action_name'] = $action_name;
							
							foreach ( $this_action['settings'] as $setting ) {

								if ( isset( $_POST[$setting['id']][$block_id]['else'][$child_id] ) ) {
									$actions[$child_id][$setting['id']] = $_POST[$setting['id']][$block_id]['else'][$child_id];
								}

							}

						}

					}

					/* Add Actions to Action Block */
					if ($actions) {
						$action_blocks[$block_id]['actions']['else'] = $actions;
					}
				}
		
				//print_r($action_blocks);exit;
				/* Save Actions */
				$action_blocks = json_encode( $action_blocks );
				update_post_meta( $post_id, 'automation_action_blocks' , $action_blocks );

			}
		}


		public static function change_title_text( $text, $post ) {
			if ($post->post_type==self::$post_type) {
				return __( 'Enter Rule Name Here' , 'leads' );
			} else {
				return $text;
			}
		}


		/* Enqueue Admin Scripts */
		public static function enqueue_admin_scripts( $hook ) {
			global $post;

			if ( !isset($post) || $post->post_type != self::$post_type ) {
				return;
			}

			if ( $hook == 'post-new.php' ) {
			}

			if ( $hook == 'post.php' ) {
			}

			if ($hook == 'post-new.php' || $hook == 'post.php') {
			
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-accordion');
				
				wp_enqueue_script(
					'custom-accordion',
					get_stylesheet_directory_uri() . '/includes/accordion.js',
					array('jquery')
				);
				
				//wp_enqueue_script('jquery-ui-sortable');
				wp_enqueue_script( 'jquery-effects-core' );
				wp_enqueue_script( 'jquery-effects-highlight' );
				
				wp_enqueue_style( 'inbound_automation_admin_css' ,	INBOUND_MARKETING_AUTOMATION_URLPATH . 'css/automation/admin.post-edit.css' );
				wp_enqueue_style('inbound-automation-admin-jquery-ui-css',	INBOUND_MARKETING_AUTOMATION_URLPATH . 'css/automation/jquery-ui.css');
				
				/* load BootStrap */
				wp_register_script( 'bootstrap-js' , INBOUND_MARKETING_AUTOMATION_URLPATH .'includes/libraries/BootStrap/js/bootstrap.min.js');
				wp_enqueue_script( 'bootstrap-js' );
			
				wp_register_style( 'bootstrap-css' , INBOUND_MARKETING_AUTOMATION_URLPATH . 'includes/libraries/BootStrap/css/bootstrap.css');
				wp_enqueue_style( 'bootstrap-css' );
				
				/* Load FontAwesome */
				wp_register_style( 'font-awesome' , INBOUND_MARKETING_AUTOMATION_URLPATH . 'includes/libraries/FontAwesome/css/font-awesome.min.css');
				wp_enqueue_style( 'font-awesome' );
				
				/* Load Select2 
				wp_register_script( 'select2-js' , INBOUND_MARKETING_AUTOMATION_URLPATH .'includes/libraries/Select2/select2.min.js');
				wp_enqueue_script( 'select2-js' );
			
				wp_register_style( 'select2-css' , INBOUND_MARKETING_AUTOMATION_URLPATH . 'includes/libraries/Select2/select2.css');
				wp_enqueue_style( 'select2-css' );
				
				wp_register_style( 'select2-bootstrap-css' , INBOUND_MARKETING_AUTOMATION_URLPATH . 'includes/libraries/Select2/select2-bootstrap.css');
				wp_enqueue_style( 'select2-bootstrap-css' );
				*/
			}
		}

		/* Print Admin Scripts */
		public static function print_admin_scripts() {
			global $post;

			if ( !isset($post) || $post->post_type != self::$post_type ) {
				return;
			}

			?>
			<script>

			/* Get Trigger Argument Filters by Trigger ID */
			function populate_trigger_trigger_filters() {

				var trigger = jQuery('#trigger-dropdown').find(":selected").val();

				/* Hide Evaluation Options When 'Select Trigger' is Selected */
				if (trigger == '-1') {
					jQuery('.trigger-filter-evaluate').addClass('nav-hide');
				}

				/* disable filter dropdown momentarily */
				jQuery('.trigger-filter-select-dropdown').prop( 'disabled' , true );

				/* rumble time */
				//jQuery('.tr-filter-select').effect('highlight');

				jQuery.ajax({
					type: "GET",
					url: ajaxurl,
					dataType: "json",
					async:true,
					data : {
						'action' : 'automation_get_trigger_arguments',
						'trigger' : trigger
					},
					success: function(filters) {
						/* clear old options */
						jQuery('.trigger-filters').empty();

						/* populate new options */
						var html = '';
						var len = filters.length;

						for (var i = 0; i< len; i++) {
							html += '<li><a class="add-trigger-filter" id="' + filters[i].id + '">' + filters[i].label + '</a></li>';
						}
						
						jQuery('.trigger-filters').append(html);


						/* enable select box */
						jQuery('.trigger-filter-select-dropdown').prop( 'disabled' , false );

					}
				});
			}
			
			/* Get Action DB Lookup Filters by Trigger ID */
			function populate_action_db_lookup_filters() {

				var trigger = jQuery('#trigger-dropdown').find(":selected").val();

				/* Hide Evaluation Options When 'Select Trigger' is Selected */
				if (trigger == '-1') {
					jQuery('.trigger-filter-evaluate').addClass('nav-hide');
				}

				/* disable filter dropdown momentarily */
				jQuery('.action-filter-select-dropdown').prop( 'disabled' , true );

				/* rumble time */
				jQuery('.tr-filter-select').effect('highlight');

				jQuery.ajax({
					type: "GET",
					url: ajaxurl,
					dataType: "json",
					async:true,
					data : {
						'action' : 'automation_get_db_lookup_filters',
						'trigger' : trigger
					},
					success: function(filters) {
					
						jQuery('.add-action-block').show();
						
						/* Hide Conditional Action Block Options When No DB Lookups Present */
						if ( filters[0].id == 0 ) {							
							jQuery('#if-then').hide();
							jQuery('#if-then-else').hide();
							jQuery('#while').hide();
						}
						
						/* clear old options */
						jQuery('.action-filter-select-dropdown option:gt(0)').remove();

						/* populate new options */
						var html = '';
						var len = filters.length;

						for (var i = 0; i< len; i++) {
							html += '<option value="' + filters[i].id + '">' + filters[i].label + '</option>';
						}	

						/* add html to selet box */
						jQuery('.action-filter-select-dropdown').append(html);

						/* enable select box */
						jQuery('.action-filter-select-dropdown').prop( 'disabled' , false );

					}
				});
			}

			/* Get Actions by Trigger ID */
			function populate_actions() {

				var trigger = jQuery('#trigger-dropdown').find(":selected").val();

				/* disable filter dropdown momentarily */
				jQuery('.action-select-dropdown').prop( 'disabled' , true );

				/* hilight time */
				//jQuery('.action-block-actions').effect('highlight');

				jQuery.ajax({
					type: "GET",
					url: ajaxurl,
					dataType: "json",
					async:true,
					data : {
						'action' : 'automation_get_actions',
						'trigger' : trigger
					},
					success: function(actions) {
						/* clear old options */
						jQuery('.action-select-dropdown option:gt(0)').remove();

						/* populate new options */
						var html = '';
						var len = actions.length;

						for (var i = 0; i< len; i++) {
							html += '<option value="' + actions[i].id + '">' + actions[i].label + '</option>';
						}
						jQuery('.action-select-dropdown').append(html);


						/* enable select box */
						jQuery('.action-select-dropdown').prop( 'disabled' , false );

					}
				});
			}

			jQuery(document).ready(function() {
				
				/* Initialize Select 2 on Select Elements */
				//jQuery('select').select2();
				
				
				jQuery(".actions-container").accordion({
					header: '> div > h3',
					collapsible: true,
					heightStyle: "content"
				}).sortable({
					axis: "y",
					handle: "h3",
					stop: function( event, ui ) {
					  ui.item.children( "h3" ).triggerHandler( "focusout" );
					  jQuery( this ).accordion( "refresh" );
					}
				});
				
				jQuery('.action-block-actions-container').accordion({
					header: '> div > h4',
					collapsible: true,
					heightStyle: "content"
				}).sortable({
					axis: "y",
					handle: "h4",
					stop: function( event, ui ) {
					  ui.item.children( "h4" ).triggerHandler( "focusout" );
					  jQuery( this ).accordion( "refresh" );
					}
				});
				
				jQuery(".action-block-filters-container").accordion({
					header: '> div > h4',
					collapsible: true,
					heightStyle: "content"
				}).sortable({
					axis: "y",
					handle: "h4",
					stop: function( event, ui ) {
					  ui.item.children( "h3" ).triggerHandler( "focusout" );
					  jQuery( this ).accordion( "refresh" );
					}
				});
				
				
				jQuery('#minor-publishing').hide();
				jQuery('#publish').val('Save Rule');

				/* Switch Nav Containers on Tab Click */
				jQuery('body').on( 'click' , '.navlink' , function() {

					var container_id = this.id;
					 
					/* Toggle correct nav tab */
					jQuery('.nav-pills li').removeClass('active');
					jQuery(this).parent('li').addClass('active');
					
					/* Toggle correct UI container */
					jQuery( '.nav-container' ).removeClass('nav-reveal');
					jQuery( '.nav-container' ).addClass('nav-hide');
					jQuery( '.'+container_id ).addClass('nav-reveal');

				});

				/* Set Initial Trigger Filters Select Values */
				var trigger =	jQuery('#trigger-dropdown').find(":selected").val();
				if ( trigger != '-1' ) {
					populate_trigger_trigger_filters();
					populate_actions();
					populate_action_db_lookup_filters();
				}

				/* Update Trigger Condition */
				jQuery('body').on('change', '#trigger-dropdown' , function() {

					/* remove trigger filters on trigger change*/
					jQuery('.filter-container').remove();

					/* repopulate trigger filter dropdown */
					populate_trigger_trigger_filters();
					populate_actions();
					populate_action_db_lookup_filters();

				});

				/* Adds Trigger Argument Filters to Trigger Conditions */
				jQuery('body').on( 'click' , '.add-trigger-filter' , function() {
					
					var filter_id = jQuery(this).attr('id');

					var target_container = 'argument-filters-container';
					var filter_input_filter_id_name = 'trigger_argument_id';
					var filter_input_key_name = 'trigger_filter_key';
					var filter_input_compare_name = 'trigger_filter_compare';
					var filter_input_value_name = 'trigger_filter_value';

					var child_id = jQuery( "body" ).find( '#' + target_container + ' .table-filter:last' ).attr( 'data-child-id' );


					/* Create Original Filter Id */
					if ( typeof child_id == 'undefined' ) {
						child_id = 1;
					} else {
						child_id = parseInt(child_id) + 1;
					}

					/* AJAX - Get Filter HTML */
					jQuery.ajax({
						type: "GET",
						url: ajaxurl,
						dataType: "html",
						async:true,
						data : {
							'action' : 'automation_build_trigger_filter',
							'filter_id' : filter_id,
							'action_block_id' : null,
							'child_id' : child_id,
							'filter_input_filter_id_name' : filter_input_filter_id_name,
							'filter_input_key_name' : filter_input_key_name,
							'filter_input_compare_name' : filter_input_compare_name,
							'filter_input_value_name' : filter_input_value_name,
							'defaults' : null
						},
						success: function(html) {
							/* Reveal Trigger Evaluation Options */
							jQuery('.trigger-filter-evaluate').removeClass('nav-hide');
							jQuery('#'+target_container).append(html);
						}
					});

				});

				/* Adds Action DB Lookup Filters to Action Conditions */
				jQuery('body').on( 'click' , '.add-action-filter' , function() {
					
					var dropdown_id = jQuery(this).attr('data-dropdown-id');
					var filter_id = jQuery( '#' + dropdown_id ).find( ":selected" ).val();

					var target_container = jQuery(this).attr('data-filter-container');
					var filter_input_filter_id_name = jQuery(this).attr('data-filter-input-filter-id');
					var filter_input_key_name = jQuery(this).attr('data-filter-input-key-name');
					var filter_input_compare_name = jQuery(this).attr('data-filter-input-compare-name');
					var filter_input_value_name = jQuery(this).attr('data-filter-input-value-name');

					var child_id = jQuery( "body" ).find( '#' + target_container + ' .table-filter:last' ).attr( 'data-child-id' );

					/* Create Original Filter Id */
					if ( typeof child_id == 'undefined' ) {
						child_id = 1;
					} else {
						child_id = parseInt(child_id) + 1;
					}

					/* AJAX - Get Filter HTML */
					jQuery.ajax({
						type: "GET",
						url: ajaxurl,
						dataType: "html",
						async:true,
						data : {
							'action' : 'automation_build_db_lookup_filter',
							'filter_id' : filter_id,
							'action_block_id' : null,
							'child_id' : child_id,
							'filter_input_filter_id_name' : filter_input_filter_id_name,
							'filter_input_key_name' : filter_input_key_name,
							'filter_input_compare_name' : filter_input_compare_name,
							'filter_input_value_name' : filter_input_value_name,
							'defaults' : null
						},
						success: function(html) {
							jQuery('#'+target_container).append(html);
							var index = 0;
							index = jQuery("#" +target_container +" h4").length - 1; 
							jQuery(".action-block-filters-container").accordion({
								header: '> div > h4',
								collapsible: true,
								active: index,
								heightStyle: "content"
							}).sortable({
								axis: "y",
								handle: "h4",
								stop: function( event, ui ) {
								  ui.item.children( "h3" ).triggerHandler( "focusout" );
								  jQuery( this ).accordion( "refresh" );
								}
							});
							jQuery('.action-block-filters-container').accordion('refresh');
						}
					});

				});

				/* Adds Actions to Action Contaner*/
				jQuery('body').on( 'click' , '.add-action' , function() {

					var dropdown_id = jQuery(this).attr('data-dropdown-id');
					var action_name = jQuery( '#' + dropdown_id ).find( ":selected" ).val();
					var dropdown_id = jQuery(this).attr('data-action-type');

					var target_container = jQuery(this).attr('data-action-container');
					var input_action_name_name = jQuery(this).attr('data-input-action-type-name');

					var action_block_id = jQuery(this).attr('data-action-block-id');
					var action_type = jQuery(this).attr('data-action-type');
					var child_id = jQuery( "body" ).find( '#' + target_container + ' .table-action:last' ).attr( 'data-child-id' );


					/* Create Original Filter Id */
					if ( typeof child_id == 'undefined' ) {
						child_id = 1;
					} else {
						child_id = parseInt(child_id) + 1;
					}

					/* AJAX - Get Filter HTML */
					jQuery.ajax({
						type: "GET",
						url: ajaxurl,
						dataType: "html",
						async:true,
						data : {
							'action' : 'automation_build_action',
							'action_name' : action_name,
							'action_type' : action_type,
							'action_block_id' : action_block_id,
							'child_id' : child_id,
							'input_action_name_name' : input_action_name_name,
							'defaults' : null
						},
						success: function(html) {
							/* Reveal Trigger Evaluation Options */
							jQuery('#'+target_container).append(html);
							var index = 0;
							index = jQuery('#'+target_container +" h4").length - 1; 
							jQuery('.action-block-actions-container').accordion({
								header: '> div > h4',
								collapsible: true,
								active: index,
								heightStyle: "content"
							}).sortable({
								axis: "y",
								handle: "h4",
								stop: function( event, ui ) {
								  ui.item.children( "h4" ).triggerHandler( "focusout" );
								  jQuery( this ).accordion( "refresh" );
								}
							});
							jQuery(".action-block-actions-container").accordion("refresh");
						}
					});

				});

				/* Deletes Filter */
				jQuery('body').on( 'click' , '.delete-filter' , function() {
					if(confirm('Are sure want to delete this filter?')){
						var del_id = jQuery(this).attr('id');
						del_id = del_id.replace('delete-filter-','');
						jQuery(this).parent().parent().parent().parent().remove();
						jQuery('h4#header-filter-' + del_id).remove();
					} else {	
						return false;
					}
				});

				/* Deletes Action */
				jQuery('body').on( 'click' , '.delete-action' , function() {
					if(confirm('Are sure want to delete this sub-action?')){
						var del_id = jQuery(this).attr('id');
						del_id = del_id.replace('delete-sublock-','');
						jQuery(this).parent().parent().parent().parent().parent().parent().remove();
						jQuery('h4#header-sublock-' + del_id).remove();
					} else {	
						return false;
					}
				});
				
				/* Deletes Action Block */
				jQuery('body').on( 'click' , '.delete-action-block' , function() {
					if(confirm('Are sure want to delete this action block?')){
						var del_id = jQuery(this).attr('id');
						del_id = del_id.replace('delete-action-block-','');
						jQuery(this).parent().parent().remove();
						jQuery('h3#header-block-' + del_id).remove();
					} else {	
						return false;
					}
				});
				
				/* Deletes Action Block */
				jQuery('body').on( 'hover' , '.delete-action-block' , function() {
					jQuery(this).parent().parent().addClass('highlight-container');
				});

				/* Deletes Action Block */
				jQuery('body').on( 'mouseout' , '.delete-action-block' , function() {
					jQuery(this).parent().parent().removeClass('highlight-container');
				});


				/* Adds Action Block*/
				jQuery('body').on( 'click' , '.add-action-block' , function() {

					var action_block_type = this.id;
					var action_block_id = jQuery("body").find('.action-block:last').attr('data-action-block-id');

					/* Create Original Filter Id */
					if ( typeof action_block_id == 'undefined' ) {
						action_block_id = 1;
					} else {
						action_block_id = parseInt(action_block_id) + 1;
					}

					/* AJAX - Get Filter HTML */
					jQuery.ajax({
						type: "GET",
						url: ajaxurl,
						dataType: "html",
						async:true,
						data : {
							'action' : 'automation_build_action_block',
							'action_block_type' : action_block_type,
							'action_block_id' : action_block_id
						},
						success: function(html) {
							jQuery('.actions-container').append(html);
							var index = 0;
							index = jQuery(".actions-container h3").length - 1; 
							jQuery(".actions-container").accordion({
								header: '> div > h3',
								collapsible: true,
								active: index,
								heightStyle: "content"
							}).sortable({
								axis: "y",
								handle: "h3",
								stop: function( event, ui ) {
								  ui.item.children( "h3" ).triggerHandler( "focusout" );
								  jQuery( this ).accordion( "refresh" );
								}
							}); 
							jQuery('.actions-container').accordion('refresh');
							populate_trigger_trigger_filters();
							populate_actions();
							populate_action_db_lookup_filters();
						}
					});

				});
				
				/* Toggle Log Content */
				jQuery('body').on( 'click' , '.toggle-log-content' , function() {
					var log_id = jQuery(this).attr('data-id');
					jQuery('#log-content-' + log_id ).toggle();
				});
			});

			</script>
			<?php

		}

		/* Builds Action Block HTML 
		* @param action_block_type STRING 
		* @param action_block_id STRING Which Action Block Placement Spot Is Next In Line
		* @param action_priority STRING Which Action Queue placement spot is next in line within the action block
		* @param block ARRAY data for generating historic action blocks
		*
		* @returns html STRING constructed html string 
		*/
		public static function ajax_build_action_block( $action_block_type = null , $action_block_id = null , $action_priority = null, $block = null ) {

			if ( !isset($_REQUEST['action_block_type']) && !$action_block_type ) {
				exit;
			}
				
			( isset($_REQUEST['action_block_type']) ) ? $action_block_type = $_REQUEST['action_block_type'] : $action_block_type;
			( isset($_REQUEST['action_block_id']) ) ? $action_block_id = $_REQUEST['action_block_id'] : $action_block_id;
			( isset($_REQUEST['action_block_priority']) ) ? $action_block_id = $_REQUEST['action_block_priority'] : $action_block_id;

			$html = '';
			switch ($action_block_type) {
				case 'actions' :
					$html .= "<div class='action-wrapper'><h3 id='header-block-".$action_block_id."'>Actions Block</h3>";
					$html .= "<div class='action-block' data-action-block-id='".$action_block_id."'>";
					$html .= "<div class='action-block-delete'><button class='btn btn-inverse btn-small delete-action-block' title='Delete Action Block' id='delete-action-block-".$action_block_id."'><span class='glyphicon glyphicon-remove-sign'></span></button></div>";
					$html .= "<fieldset id='action-block-if-then' class='action-block-fieldset' data-action-block-priority='".$action_priority."'>";
					$html .= "	<input type='hidden' name='action_block_id[".$action_block_id."]' value='".$action_block_id."'>";
					$html .= "	<input type='hidden' name='action_block_type[".$action_block_id."]' value='".$action_block_type."'>";
					$html .= "	";				
					$html .= "		<fieldset id='action-block-if-then' class='action-block-actions'>";
					$html .= "			<legend>Actions:</legend>";
					$html .= "				<select class='action-select-dropdown select2' id='action-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Action</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action' id='add-action' data-dropdown-id='action-select-dropdown-".$action_block_id."' data-action-container='action-block-actions-container-".$action_block_id."'	data-action-type='then' data-input-action-type-name='action_name' data-action-block-id='".$action_block_id."'>";
					$html .= "					Add Action";
					$html .= "				</span>";
					$html .= "				<div class='action-block-actions-container' id='action-block-actions-container-".$action_block_id."' >";
					
					/* Prepare Actions if Action Block Manually Evoked */
					if ( isset( $block['actions']['then'] ) ) {

						//print_r($block);
						foreach ( $block['actions']['then'] as $child_id => $action ) {

							$args = array(
								'action_name' => $action['action_name'],
								'action_type' => 'then',
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_action_name_name' => 'action_name',
								'defaults' => $action
							);

							$html .= self::ajax_build_action( $args );

						}
					}
					
					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "</fieldset>";
					$html .= "</div></div>";
					//$html .= "<hr class='action-block-separator'>";
					break;
					
				case 'if-then' :
					$html .= "<div class='action-wrapper action-wrapper-if-then'><h3 id='header-block-".$action_block_id."'>IF/Then Action Block</h3>";
					$html .= "<div class='action-block' data-action-block-id='".$action_block_id."' >";
					$html .= "<div class='action-block-delete'><button class='btn btn-inverse btn-small delete-action-block' title='Delete Action Block' id='delete-action-block-".$action_block_id."'><span class='glyphicon glyphicon-remove-sign'></span></button></div>";
					$html .= "<fieldset id='action-block-if-then' class='action-block-fieldset' data-action-block-priority='".$action_priority."'>";
					$html .= "	<input type='hidden' name='action_block_id[".$action_block_id."]' value='".$action_block_id."'>";
					$html .= "	<input type='hidden' name='action_block_type[".$action_block_id."]' value='".$action_block_type."'>";
					$html .= "	";				
					$html .= "		<fieldset id='action-block-if-then-conditions' class='action-block-conditions'>";
					$html .= "			<legend>Conditions:</legend>";
					$html .= "				<select class='action-filter-select-dropdown' id='action-filter-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Filter</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action-filter' id='add_filter' data-dropdown-id='action-filter-select-dropdown-".$action_block_id."' data-filter-container='action-block-filters-container-".$action_block_id."'	data-filter-input-filter-id='action_filter_id[".$action_block_id."]' data-filter-input-key-name='action_filter_key[".$action_block_id."]' data-filter-input-compare-name='action_filter_compare[".$action_block_id."]' data-filter-input-value-name='action_filter_value[".$action_block_id."]'>";
					$html .= "					Add Lookup";
					$html .= "				</span>";
					$html .= "				<div class='action-block-filter-evaluate' style='display:inline;'>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-all' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] || !isset($block['action_block_filters_evaluate']) == 'match-all' ? 'checked="checked"' : '' ) ."> Match All</span>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-any' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] == 'match-any' ? 'checked="checked"' : '' ) ."> Match Any</span>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-none' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] == 'match-none' ? 'checked="checked"' : '' ) ."> Match None</span>";
					$html .= "				</div>";
					$html .= "				<div class='action-block-filters-container' id='action-block-filters-container-".$action_block_id."' >";

					/* Prepare Filters if Action Block Manually Evoked */
					if ( isset( $block['filters'] ) ) {

						//print_r($block);
						foreach ( $block['filters'] as $child_id => $filter ) {

							$args = array(
								'filter_id' => $filter['filter_id'],
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_name_filter_id' => 'action_filter_id',
								'input_name_filter_key' => 'action_filter_key',
								'input_name_filter_compare' => 'action_filter_compare',
								'input_name_filter_value' => 'action_filter_value',
								'defaults' => $filter
							);

							$html .= self::ajax_build_db_lookup_filter( $args );

						}
					}

					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "		<fieldset id='action-block-if-then' class='action-block-actions'>";
					$html .= "			<legend>Actions:</legend>";
					$html .= "				<select class='action-select-dropdown' id='action-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Action</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action' id='add-action' data-dropdown-id='action-select-dropdown-".$action_block_id."' data-action-container='action-block-actions-container-".$action_block_id."'	data-action-type='then' data-input-action-type-name='action_name' data-action-block-id='".$action_block_id."'>";
					$html .= "					Add Action";
					$html .= "				</span>";
					$html .= "				<div class='action-block-actions-container' id='action-block-actions-container-".$action_block_id."' >";
					
					/* Prepare Actions if Action Block Manually Evoked */
					if ( isset( $block['actions']['then'] ) ) {

						//print_r($block);
						foreach ( $block['actions']['then'] as $child_id => $action ) {


							$args = array(
								'action_name' => $action['action_name'],
								'action_type' => 'then',
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_action_name_name' => 'action_name',
								'defaults' => $action
							);

							$html .= self::ajax_build_action( $args );

						}
					}
					
					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "</fieldset>";
					$html .= "</div></div>";
					//$html .= "<hr class='action-block-separator'>";
					break;
					
				case 'if-then-else' :
					$html .= "<div class='action-wrapper action-wrapper-if-then-else'><h3 id='header-block-".$action_block_id."'>IF/Then/Else Action Block</h3>";	
					$html .= "<div class='action-block' data-action-block-id='".$action_block_id."' >";
					$html .= "<div class='action-block-delete'><button class='btn btn-inverse btn-small delete-action-block' title='Delete Action Block' id='delete-action-block-".$action_block_id."'><span class='glyphicon glyphicon-remove-sign'></span></button></div>";
					$html .= "<fieldset id='action-block-if-then' class='action-block-fieldset' data-action-block-priority='".$action_priority."'>";
					$html .= "	<input type='hidden' name='action_block_id[".$action_block_id."]' value='".$action_block_id."'>";
					$html .= "	<input type='hidden' name='action_block_type[".$action_block_id."]' value='".$action_block_type."'>";
					$html .= "		<fieldset id='action-block-if-then-else-conditions' class='action-block-conditions'>";
					$html .= "			<legend>Conditions:</legend>";
					$html .= "				<select class='action-filter-select-dropdown' id='action-filter-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Filter</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action-filter' id='add_filter' data-dropdown-id='action-filter-select-dropdown-".$action_block_id."' data-filter-container='action-block-filters-container-".$action_block_id."'	data-filter-input-filter-id='action_filter_id[".$action_block_id."]' data-filter-input-key-name='action_filter_key[".$action_block_id."]' data-filter-input-compare-name='action_filter_compare[".$action_block_id."]' data-filter-input-value-name='action_filter_value[".$action_block_id."]'>";
					$html .= "					Add Lookup";
					$html .= "				</span>";
					$html .= "				<div class='action-block-filter-evaluate' style='display:inline;'>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-all' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] || !isset($block['action_block_filters_evaluate']) == 'match-all' ? 'checked="checked"' : '' ) ."> Match All</span>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-any' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] == 'match-any' ? 'checked="checked"' : '' ) ."> Match Any</span>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-none' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] == 'match-none' ? 'checked="checked"' : '' ) ."> Match None</span>";
					$html .= "				</div>";
					$html .= "				<div class='action-block-filters-container' id='action-block-filters-container-".$action_block_id."' >";

					/* Prepare Filters if Action Block Manually Evoked */
					if ( isset( $block['filters'] ) ) {

						//print_r($block);
						foreach ( $block['filters'] as $child_id => $filter ) {

							$args = array(
								'filter_id' => $filter['filter_id'],
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_name_filter_id' => 'action_filter_id',
								'input_name_filter_key' => 'action_filter_key',
								'input_name_filter_compare' => 'action_filter_compare',
								'input_name_filter_value' => 'action_filter_value',
								'defaults' => $filter
							);

							$html .= self::ajax_build_db_lookup_filter( $args );

						}
					}

					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "		<fieldset id='action-block-if-then' class='action-block-actions'>";
					$html .= "			<legend>Actions:</legend>";
					$html .= "				<select class='action-select-dropdown' id='action-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Action</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action' id='add-action' data-dropdown-id='action-select-dropdown-".$action_block_id."' data-action-container='action-block-actions-container-".$action_block_id."' data-action-type='then' data-input-action-type-name='action_name' data-action-block-id='".$action_block_id."'>";
					$html .= "					Add Action";
					$html .= "				</span>";
					$html .= "				<div class='action-block-actions-container' id='action-block-actions-container-".$action_block_id."' >";
					
					/* Prepare Actions if Action Block Manually Evoked */
					if ( isset( $block['actions']['then'] ) ) {

						//print_r($block);
						foreach ( $block['actions']['then'] as $child_id => $action ) {

							$args = array(
								'action_name' => $action['action_name'],
								'action_type' => 'then',
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_action_name_name' => 'action_name',
								'defaults' => $action
							);

							$html .= self::ajax_build_action( $args );

						}
					}
					
					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "		<fieldset id='action-block-if-then-else' class='action-block-actions'>";
					$html .= "			<legend>Else Actions:</legend>";
					$html .= "				<select class='action-select-dropdown' id='else-action-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Action</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action' id='add-action' data-dropdown-id='else-action-select-dropdown-".$action_block_id."' data-action-container='action-block-else-actions-container-".$action_block_id."'	data-action-type='else'	data-input-action-type-name='action_name' data-action-block-id='".$action_block_id."'>";
					$html .= "					Add Action";
					$html .= "				</span>";
					$html .= "				<div class='action-block-else-actions-container action-block-actions-container' id='action-block-else-actions-container-".$action_block_id."' >";
					
					/* Prepare Actions if Action Block Manually Evoked */
					if ( isset( $block['actions']['else'] ) ) {

						//print_r($block);
						foreach ( $block['actions']['else'] as $child_id => $action ) {

							$args = array(
								'action_name' => $action['action_name'],
								'action_type' => 'else',
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_action_name_name' => 'action_name',
								'defaults' => $action
							);

							$html .= self::ajax_build_action( $args );

						}
					}
					
					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "</fieldset>";
					$html .= "</div></div>";
					//$html .= "<hr class='action-block-separator'>";
					break;
					
					
					
				case 'while':
					$html .= "<div class='action-wrapper  action-wrapper-while'><h3 id='header-block-".$action_block_id."'>While Action Block</h3>";	
					$html .= "<div class='action-block' data-action-block-id='".$action_block_id."' >";
					$html .= "<div class='action-block-delete'><button class='btn btn-inverse btn-small delete-action-block' title='Delete Action Block' id='delete-action-block-".$action_block_id."'><span class='glyphicon glyphicon-remove-sign'></span></button></div>";
					$html .= "<fieldset id='action-block-while' class='action-block-fieldset' data-action-block-priority='".$action_priority."'>";
					$html .= "	<input type='hidden' name='action_block_id[".$action_block_id."]' value='".$action_block_id."'>";
					$html .= "	<input type='hidden' name='action_block_type[".$action_block_id."]' value='".$action_block_type."'>";
					$html .= "	";				
					$html .= "		<fieldset id='action-block-while-conditions' class='action-block-conditions'>";
					$html .= "			<legend>Conditions:</legend>";
					$html .= "				<select class='action-filter-select-dropdown' id='action-filter-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Filter</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action-filter' id='add_filter' data-dropdown-id='action-filter-select-dropdown-".$action_block_id."' data-filter-container='action-block-filters-container-".$action_block_id."'	data-filter-input-filter-id='action_filter_id[".$action_block_id."]' data-filter-input-key-name='action_filter_key[".$action_block_id."]' data-filter-input-compare-name='action_filter_compare[".$action_block_id."]' data-filter-input-value-name='action_filter_value[".$action_block_id."]'>";
					$html .= "					Add While Condition";
					$html .= "				</span>";
					$html .= "				<div class='action-block-filter-evaluate' style='display:inline;'>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-all' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] || !isset($block['action_block_filters_evaluate']) == 'match-all' ? 'checked="checked"' : '' ) ."> Match All</span>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-any' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] == 'match-any' ? 'checked="checked"' : '' ) ."> Match Any</span>";
					$html .= "					<span class='label-evaluate'><input type='radio' name='action_block_filters_evaluate[".$action_block_id."]' value='match-none' ". ( isset($block['action_block_filters_evaluate']) && $block['action_block_filters_evaluate'] == 'match-none' ? 'checked="checked"' : '' ) ."> Match None</span>";
					$html .= "				</div>";
					$html .= "				<div class='action-block-filters-container' id='action-block-filters-container-".$action_block_id."' >";

					/* Prepare Filters if Action Block Manually Evoked */
					if ( isset( $block['filters'] ) ) {

						//print_r($block);
						foreach ( $block['filters'] as $child_id => $filter ) {

							$args = array(
								'filter_id' => $filter['filter_id'],
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_name_filter_id' => 'action_filter_id',
								'input_name_filter_key' => 'action_filter_key',
								'input_name_filter_compare' => 'action_filter_compare',
								'input_name_filter_value' => 'action_filter_value',
								'defaults' => $filter
							);

							$html .= self::ajax_build_db_lookup_filter( $args );

						}
					}

					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "		<fieldset id='action-block-while' class='action-block-actions'>";
					$html .= "			<legend>Actions:</legend>";
					$html .= "				<select class='action-select-dropdown' id='action-select-dropdown-".$action_block_id."' >";
					$html .= "					<option value='-1' class=''>Select Action</option>";
					$html .= "				</select>";
					$html .= "				<span class='button add-action' id='add-action' data-dropdown-id='action-select-dropdown-".$action_block_id."' data-action-container='action-block-actions-container-".$action_block_id."'	data-action-type='then' data-input-action-type-name='action_name' data-action-block-id='".$action_block_id."'>";
					$html .= "					Add While Action";
					$html .= "				</span>";
					$html .= "				<div class='action-block-actions-container' id='action-block-actions-container-".$action_block_id."' >";
					
					/* Prepare Actions if Action Block Manually Evoked */
					if ( isset( $block['actions']['then'] ) ) {

						//print_r($block);
						foreach ( $block['actions']['then'] as $child_id => $action ) {

							$args = array(
								'action_name' => $action['action_name'],
								'action_type' => 'then',
								'action_block_id' => $action_block_id,
								'child_id' => $child_id,
								'input_action_name_name' => 'action_name',
								'defaults' => $action
							);

							$html .= self::ajax_build_action( $args );

						}
					}
					
					$html .= "				</div>";
					$html .= "		</fieldset>";
					$html .= "</fieldset>";
					$html .= "</div></div>";
					//$html .= "<hr class='action-block-separator'>";
					break;
			}

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				echo $html;
				die();
			} else {
				return $html;
			}
		}

		/* AJAX - Build Trigger Filter HTML */
		public static function ajax_build_trigger_filter( $args ) {

			if ( !isset($_REQUEST['filter_id']) && !isset($args['filter_id']) )	{
				exit;
			}

			/* Get Parameters */
			( isset( $args['filter_id'] ) ) ? $args['filter_id'] :	$args['filter_id'] = $_REQUEST['filter_id'];
			( isset( $args['action_block_id'] ) ) ?	$args['action_block_id'] :	$args['action_block_id'] = $_REQUEST['action_block_id'];
			( isset( $args['child_id'] ) ) ?	$args['child_id'] :	$args['child_id'] = $_REQUEST['child_id'];
			( isset( $args['input_name_filter_id'] ) ) ? $args['input_name_filter_id'] : $args['input_name_filter_id'] = $_REQUEST['filter_input_filter_id_name'];
			( isset( $args['input_name_filter_key'] ) ) ? $args['input_name_filter_key'] : $args['input_name_filter_key'] = $_REQUEST['filter_input_key_name'];
			( isset( $args['input_name_filter_compare'] ) ) ? $args['input_name_filter_compare'] : $args['input_name_filter_compare'] = $_REQUEST['filter_input_compare_name'];
			( isset( $args['input_name_filter_value'] ) ) ? $args['input_name_filter_value'] : $args['input_name_filter_value'] = $_REQUEST['filter_input_value_name'];
			( isset( $args['defaults'] ) ) ? $args['defaults'] : $args['defaults'] = $_REQUEST['defaults'];

			/* Get Filter Definitions */
			$Inbound_Automation =	Inbound_Automation_Load_Extensions();
			$arguments = $Inbound_Automation->arguments;
			$this_arg =	$args['filter_id'];

			/* Die If No Filter Selected */
			if ( $this_arg == '-1'	&&	defined( 'DOING_AJAX' ) && DOING_AJAX	) {
				die();
			} else if ($this_arg == '-1' ) {
				return '';
			}

			$key_args = array(
							'name' => $args['input_name_filter_key'],
							'type' => $arguments[$this_arg]['key_input_type'],
							'options' => $arguments[$this_arg]['keys'],
							'action_block_id' => $args['action_block_id'],
							'child_id' => $args['child_id'],
							'default' => $args['defaults'],
							'class' => 'trigger-filter-key'
							);

			$compare_args = array(
							'name' => $args['input_name_filter_compare'],
							'type' => 'dropdown',
							'options' => $arguments[$this_arg]['compare'],
							'action_block_id' => $args['action_block_id'],
							'child_id' => $args['child_id'],
							'default' => $args['defaults'],
							'class' => 'trigger-filter-compare'
							);

			$value_args = array(
							'name' => $args['input_name_filter_value'] ,
							'type' => $arguments[$this_arg]['value_input_type'],
							'options' => $arguments[$this_arg]['values'],
							'action_block_id' => $args['action_block_id'],
							'child_id' => $args['child_id'],
							'default' => $args['defaults'],
							'class' => 'trigger-filter-value'
							);

			$key_input = self::build_input( $key_args );
			$compare_input = self::build_input( $compare_args );
			$value_input = self::build_input( $value_args );
			$header_name = str_replace('_', ' ', $this_arg);
			$header_name = ucfirst(strtolower($header_name)); 	

			$html = "<div class='filter-container'>";
			$html .= "<h4 id='header-filter-".$args['action_block_id']."-".$args['child_id']."' class='filter-header'".$header_name."</h4>";
			$html .= "<div><table class='table-filter' data-child-id='".$args['child_id']."'>";
			$html .= "	<tr class='tr-filter'>";
			$html .= "		<td class='td-filter-key'>";
			$html .= "			<input type='hidden' name='".$args['input_name_filter_id']. "[".$args['child_id']."]' value='".$this_arg."'>";
			$html .= 			$key_input;
			$html .= "		</td>";
			$html .= "		<td class='td-filter-compare'>";
			$html .= 			$compare_input;
			$html .= "		</td>";
			$html .= "		<td class='td-filter-value'>";
			$html .= 			$value_input;
			$html .= "		</td>";
			$html .= "		<td class='td-filter-delete'>";
			$html .= "			<button class='btn btn-default btn-mini delete-filter' title='Delete Trigger Filter' id='delete-filter-".$args['child_id']."'><i class='fa fa-times'></i></button>";
			$html .= "		</td>";
			$html .= "	</tr>";
			$html .= "</table>";
			$html .= "</div></div>";

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				echo $html;
				die();
			} else {
				return $html;
			}
		}
		
		/* AJAX - Build DB Lookup Filter HTML */
		public static function ajax_build_db_lookup_filter( $args ) {

			if ( !isset($_REQUEST['filter_id']) && !isset($args['filter_id']) )	{
				exit;
			}

			/* Get Parameters */
			( isset( $args['filter_id'] ) ) ? $args['filter_id'] :	$args['filter_id'] = $_REQUEST['filter_id'];
			( isset( $args['action_block_id'] ) ) ?	$args['action_block_id'] :	$args['action_block_id'] = $_REQUEST['action_block_id'];
			( isset( $args['child_id'] ) ) ?	$args['child_id'] :	$args['child_id'] = $_REQUEST['child_id'];
			( isset( $args['input_name_filter_id'] ) ) ? $args['input_name_filter_id'] : $args['input_name_filter_id'] = $_REQUEST['filter_input_filter_id_name'];
			( isset( $args['input_name_filter_key'] ) ) ? $args['input_name_filter_key'] : $args['input_name_filter_key'] = $_REQUEST['filter_input_key_name'];
			( isset( $args['input_name_filter_compare'] ) ) ? $args['input_name_filter_compare'] : $args['input_name_filter_compare'] = $_REQUEST['filter_input_compare_name'];
			( isset( $args['input_name_filter_value'] ) ) ? $args['input_name_filter_value'] : $args['input_name_filter_value'] = $_REQUEST['filter_input_value_name'];
			( isset( $args['defaults'] ) ) ? $args['defaults'] : $args['defaults'] = $_REQUEST['defaults'];

			/* Get Filter Definitions */
			$Inbound_Automation =	Inbound_Automation_Load_Extensions();
			$db_lookup_filters = $Inbound_Automation->db_lookup_filters;
			$this_filter =	$args['filter_id'];

			/* Die If No Filter Selected */
			if ( $this_filter == '-1'	&&	defined( 'DOING_AJAX' ) && DOING_AJAX	) {
				die();
			} else if ($this_filter == '-1' ) {
				return '';
			}

			$key_args = array(
							'name' => $args['input_name_filter_key'],
							'type' => $db_lookup_filters[$this_filter]['key_input_type'],
							'options' => $db_lookup_filters[$this_filter]['keys'],
							'action_block_id' => $args['action_block_id'],
							'child_id' => $args['child_id'],
							'default' => $args['defaults'],
							'class' => 'action-filter-key'
							);

			$compare_args = array(
							'name' => $args['input_name_filter_compare'],
							'type' => 'dropdown',
							'options' => $db_lookup_filters[$this_filter]['compare'],
							'action_block_id' => $args['action_block_id'],
							'child_id' => $args['child_id'],
							'default' => $args['defaults'],
							'class' => 'action-filter-compare'
							);

			$value_args = array(
							'name' => $args['input_name_filter_value'] ,
							'type' => $db_lookup_filters[$this_filter]['value_input_type'],
							'options' => $db_lookup_filters[$this_filter]['values'],
							'action_block_id' => $args['action_block_id'],
							'child_id' => $args['child_id'],
							'default' => $args['defaults'],
							'class' => 'action-filter-value'
							);

			$key_input = self::build_input( $key_args );
			$compare_input = self::build_input( $compare_args );
			$value_input = self::build_input( $value_args );
			$header_name = str_replace('_', ' ', $this_filter);
			$header_name = ucfirst(strtolower($header_name)); 	

			$html = "<div class='filter-container'>";
			$html .= "<h4 id='header-filter-".$args['action_block_id']."-".$args['child_id']."' class='filter-header'>".$header_name."</h4>";
			$html .= "<div><table class='table-filter' data-child-id='".$args['child_id']."'>";
			$html .= "	<tr class='tr-filter'>";
			$html .= "		<td class='td-filter-key'>";
			$html .= "			<input type='hidden' name='".$args['input_name_filter_id']. ( isset($args['action_block_id'] ) && $args['action_block_id'] ? '['.$args['action_block_id'].']' : '' ) . "[".$args['child_id']."]' value='".$this_filter."'>";
			$html .= 			$key_input;
			$html .= "		</td>";
			$html .= "		<td class='td-filter-compare'>";
			$html .= 			$compare_input;
			$html .= "		</td>";
			$html .= "		<td class='td-filter-value'>";
			$html .= 			$value_input;
			$html .= "		</td>";
			$html .= "		<td class='td-filter-delete'>";
			$html .= "			<button class='btn btn-danger btn-small delete-filter' title='Delete Action Filter' id='delete-filter-".$args['action_block_id']."-".$args['child_id']."'><span class='glyphicon glyphicon-remove-sign'></span></button>";
			$html .= "		</td>";
			$html .= "	</tr>";
			$html .= "</table>";
			$html .= "</div></div>";

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				echo $html;
				die();
			} else {
				return $html;
			}
		}
		

		/* AJAX - Build Action HTML */
		public static function ajax_build_action( $args ) {

			if ( !isset($_REQUEST['action_name']) && !isset($args['action_name']) )	{
				exit;
			}

			/* Get Parameters */
			( isset( $args['action_name'] ) ) ? $args['action_name'] :	$args['action_name'] = $_REQUEST['action_name'];
			( isset( $args['action_type'] ) ) ? $args['action_type'] :	$args['action_type'] = $_REQUEST['action_type'];
			( isset( $args['action_block_id'] ) ) ?	$args['action_block_id'] :	$args['action_block_id'] = $_REQUEST['action_block_id'];
			( isset( $args['child_id'] ) ) ?	$args['child_id'] :	$args['child_id'] = $_REQUEST['child_id'];
			( isset( $args['input_action_name_name'] ) ) ?	$args['input_action_name_name'] :	$args['input_action_name_name'] = $_REQUEST['input_action_name_name'];
			( isset( $args['defaults'] ) ) ? $args['defaults'] : $args['defaults'] = $_REQUEST['defaults'];

			/* Get Action Definitions */
			$Inbound_Automation =	Inbound_Automation_Load_Extensions();
			$actions = $Inbound_Automation->actions;
			$this_action =	$args['action_name'];

			/* Die If No Action Selected */
			if ( $this_action == '-1'	&&	defined( 'DOING_AJAX' ) && DOING_AJAX	) {
				die();
			} else if ($this_action == '-1' ) {
				return '';
			}
			$header_name = str_replace('_', ' ', $this_action);
			$header_name = ucfirst(strtolower($header_name)); 	

			$html = "<div class='action-sub-wrapper'><h4 id='header-sublock-".$args['action_block_id']."-".$args['child_id']."'>".$header_name."</h4><div class='action-container'>";
			$html .= "<table class='table-action' data-child-id='".$args['child_id']."'>";
			$html .= "	<tr class='tr-action'>";
			$html .= "		<td class='td-action-setting-label' colspan=2>";
			$html .= "			";
			$html .= "			<input type='hidden' name='".$args['input_action_name_name']. ( isset($args['action_block_id'] ) && $args['action_block_id'] ? '['.$args['action_block_id'].']' : '' ) . ( isset($args['action_type'] ) && $args['action_block_id'] ? '['.$args['action_type'].']' : '' ) . "[".$args['child_id']."]' value='".$this_action."'>";
			$html .= "			<div class='action-delete'><img src='".INBOUND_MARKETING_AUTOMATION_URLPATH."images/close.png' class='delete-action delete-img' id='delete-sublock-".$args['action_block_id']."-".$args['child_id']."'></div>";
			$html .= "		</td>";
			$html .= "	</tr>";

			/* Build Settings for this Action */
			foreach ( $actions[ $this_action ]['settings'] as $setting ) {

				/* Build Arguments for Generating Action Setting */
				$setting_args = array(
							'name' => $setting['id'],
							'action_name' => $args['action_name'],
							'action_type' => $args['action_type'],
							'action_block_id' => $args['action_block_id'],
							'type' => $setting['type'],
							'child_id' => $args['child_id'],
							'class' => $setting['id']
				);

				/* Setup Default Values */
				if ( isset( $args['defaults'][$setting['id']] ) ) {	/* If Generating From History Use Historic Value */

					$setting_args['default'][$setting['id']] = $args['defaults'][$setting['id']];

				} else if ( isset( $setting['default'] ) ) { /* Else Use Default Action Value as defined in action definition If Available */

					$setting_args['default'][$setting['id']] = $setting['default'];
				}

				/* Set Options if Available */
				( isset( $setting['options'] ) && is_array( $setting['options'] ) ) ? $setting_args['options'] = $setting['options'] : $settings_args['options'] = null;

				//print_r($setting_args);

				/* Generate Action Setting HTML */
				$setting_html = self::build_input( $setting_args );

				/* Print Action Setting Label and Input */
				$html .= "	<tr class='tr-action-child'>";
				$html .= "		<td class='td-action-setting-label'>";
				$html .= "			".$setting['label'];
				$html .= "		</td>";
				$html .= "		<td class='td-action-setting-value'>";
				$html .= "			".$setting_html;
				$html .= "		</td>";
				$html .= "	</tr>";

			}


			$html .= "</table>";
			$html .= "</div></div>";

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				echo $html;
				die();
			} else {
				return $html;
			}
		}

		/* AJAX - Get Trigger Arguments by Trigger ID */
		public static function ajax_load_trigger_arguments() {

			if ( !isset($_REQUEST['trigger']) ) {
				exit;
			}

			$target_trigger = $_REQUEST['trigger'];


			$Inbound_Automation =	Inbound_Automation_Load_Extensions();
			$triggers = $Inbound_Automation->triggers;

			$filter_whitelist = array();

			if ( !isset($triggers[$target_trigger]['arguments']) ) {
				echo json_encode( array( array( 'id' => '0' , 'label' => 'Error: No Filters for Selected Trigger' ) ) );
				exit;
			}

			foreach ($triggers[$target_trigger]['arguments'] as $filter) {
				$filter_whitelist[] = array(
					'id' => $filter['id'],
					'label' => $filter['label']
				);	
			}

			echo json_encode( $filter_whitelist );
			die();
		}

		/* AJAX - Get DB Lookup Filters by Selected Trigger */
		public static function ajax_load_db_lookup_filters() {

			if ( !isset($_REQUEST['trigger']) ) {
				exit;
			}

			$target_trigger = $_REQUEST['trigger'];


			$Inbound_Automation =	Inbound_Automation_Load_Extensions();
			$triggers = $Inbound_Automation->triggers;

			$filter_whitelist = array();


			if ( !isset($triggers[$target_trigger]['db_lookup_filters']) ) {
				echo json_encode( array( array( 'id' => '0' , 'label' => 'Error: No Filters for Selected Trigger' ) ) );
				exit;
			}

			foreach ($triggers[$target_trigger]['db_lookup_filters'] as $filter) {
				$filter_whitelist[] = array(
					'id' => $filter['id'],
					'label' => $filter['label']
				);	
			}

			echo json_encode( $filter_whitelist );
			die();
		}


		/* Build Filter Input HTML - Generates ( Key , Compare , Value ) */
		public static function build_input( $args ) {
			$html = '';
			//print_r($args['default']);exit;
			switch ($args['type']) {
				case 'dropdown' :
					//echo $args['name'].':'.$args['default'][$args['name']].'<br>';
					$html .= '<select name="'.$args['name'] . ( isset($args['action_block_id'] ) && $args['action_block_id'] ? '['.$args['action_block_id'].']' : '' ) .( isset($args['action_type'] ) && $args['action_type'] ? '['.$args['action_type'].']' : '' ) . '['.$args['child_id'].']" class="'.$args['class'].'">';
					foreach ($args['options'] as $id => $label) {
						$html .= '<option value="'.$id.'" '. ( isset($args['default'][$args['name']]) && $args['default'][$args['name']] == $id ? 'selected="selected"' : '' ) .'>'.$label.'</option>';
					}
					$html .= '</select>';
					break;
				case 'text':
					$html .= '<input type="text" name="'.$args['name'] . ( isset($args['action_block_id'] ) && $args['action_block_id'] ? '['.$args['action_block_id'].']' : '' ) . ( isset($args['action_type'] ) && $args['action_type'] ? '['.$args['action_type'].']' : '' ) . '['.$args['child_id'].']" '. ( isset($args['default'][$args['name']]) ? 'value="'.$args['default'][$args['name']].'"' : 'value=""' ) .'>';
					break;
				case 'checkbox':
					//print_r($args);exit;
						$html .=  "<table >";
						
						foreach ($args['options'] as $id=>$label) {
								$html .= '<tr>';
								$html .= '<td data-field-type="checkbox">';
								$html .=  '<input type="checkbox"  name="'.$args['name'] . ( isset($args['action_block_id'] ) && $args['action_block_id'] ? '['.$args['action_block_id'].']' : '' ) .( isset($args['action_type'] ) && $args['action_type'] ? '['.$args['action_type'].']' : '' ) . '['.$args['child_id'].'][]" class="'.$args['class'].'" value="'.$id.'" '. ( isset($args['default'][$args['name']]) && in_array( $id , $args['default'][$args['name']] ) ? 'checked="checked"' : '' ) .'>';
								$html .=  '<label for="'.$id.'">&nbsp;&nbsp;'.$label.'</label>';
								$html .=  '</td>';
								$html .=  "</tr>";			
						}
						
						$html .=  "</table>";
					//$html .=  '<div class="lp_tooltip tool_checkbox" title="'.$field['description'].'"></div>';
					break;
			}

			return $html;
		}


		/* AJAX - Get Actions by Selected Trigger */
		public static function ajax_load_action_definitions() {

			if ( !isset($_REQUEST['trigger']) ) {
				exit;
			}

			$target_trigger = $_REQUEST['trigger'];


			$Inbound_Automation =	Inbound_Automation_Load_Extensions();
			$triggers = $Inbound_Automation->triggers;
			$actions = $Inbound_Automation->actions;
			$action_whitelist = array();

			if ( !isset($triggers[$target_trigger]['actions']) ) {
				echo json_encode( array( array( 'id' => '0' , 'label' => 'Error: No Filters for Selected Trigger' ) ) );
				exit;
			}

			foreach ($actions as $action) {
				if ( in_array( $action['id'] , $triggers[$target_trigger]['actions'] ) ) {
					$action_whitelist[] = array(
												'id' => $action['id'],
												'label' => $action['label']
												);
				}
			}

			echo json_encode( $action_whitelist );
			die();
		}
	}


	$Inbound_Metaboxes_Automation = new Inbound_Metaboxes_Automation;
}
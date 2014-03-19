<?php

if ( !class_exists( 'Inbound_Metaboxes_Email_Templates' ) ) {

	class Inbound_Metaboxes_Email_Templates {
		
		static $Inbound_Email_Templates;
		static $post_type;
		
		public function __construct() {
			self::load_variables();
			self::load_hooks();
		}
		
		public static function load_hooks() {
			/* Add Metaboxes */
			add_action('add_meta_boxes', array( __CLASS__ , 'define_metaboxes') );
			
			
			/* Replace Default Title Text */
			add_filter( 'enter_title_here', array( __CLASS__ , 'change_title_text' ) , 10, 2 );
	
				
			/* Add Save Actions */			
			add_action( 'save_post' , array( __CLASS__ , 'save_template_meta' ) );
			add_action( 'save_post' , array( __CLASS__ , 'save_notes' ) );
			
			
			/* Add Select Template Container */
			add_action('admin_notices', array( __CLASS__ , 'display_template_select_container' ) );
			
			
			/* Enqueue JS */
			add_action( 'admin_enqueue_scripts', array( __CLASS__ , 'enqueue_admin_scripts' ) ); 
		}
		
		public static function load_variables() {
			self::$Inbound_Email_Templates = Inbound_Email_Templates();
			self::$post_type = 'email-template';
		}
		
		public static function define_metaboxes()
		{
			global $post;

			if ( $post->post_type != self::$post_type ) {
				return;
			}

			/* Template Select Metabox */
			add_meta_box(
				'inbound_email_templates_metabox_select_template', // $id
				__( 'Template Options', 'leads' ),
				array( __CLASS__ , 'display_select_template' ), // $callback
				self::$post_type , 
				'normal', 
				'high'); 


			
			$template_data = self::$Inbound_Email_Templates->template_definitions;
			
			/* Load Correct Template Settings Metabox */
			$current_template = get_post_meta( $post->ID , 'inbound-email-selected-template' , true);
			$current_template = apply_filters( 'inbound_email_selected_template' , $current_template , $post);

			foreach ($template_data as $key=>$data)
			{
				if ( ( isset($data['info']['data_type'] ) &&  $data['info']['data_type'] =='email-template' && $key==$current_template )  )
				{

					$template_name = ucwords(str_replace('-',' ',$key));
					$id = strtolower(str_replace(' ','-',$key));
					add_meta_box(
						"inbound_email_templates_{$id}_custom_meta_box", // $id
						"<small>$template_name ".__('Options:' , 'leads' ). "</small>",
						array( __CLASS__ , 'display_template_settings' ), // $callback
						self::$post_type, // post-type
						'normal', // $context
						'default',// $priority
						array('key'=>$key)
						); //callback args
				}
			}

		}

		/* Select Template Metabox */
		public static function display_select_template() {
			global $post;

			$template =  get_post_meta($post->ID, 'inbound-email-selected-template', true);
			$template = apply_filters('inbound_email_templates_selected_template',$template);

			if (!isset($template)||isset($template)&&!$template){ $template = 'default';}

			$name = apply_filters('inbound_email_templates_selected_template_id','inbound-email-selected-template');

			// Use nonce for verification
			echo "<input type='hidden' name='inbound_email_templates_inbound-email-templates_custom_fields_nonce' value='".wp_create_nonce('inbound-email-nonce')."' />";
			?>

			<div id="inbound_email_templates_template_change"><h2><a class="button" id="inbound-email-templates-change-template-button"><?php _e( 'Choose Another Template' , 'leads' ); ?></a></div>
			<input type='hidden' id='inbound_email_templates_select_template' name='<?php echo $name; ?>' value='<?php echo $template; ?>'>
			<div id="template-display-options"></div>

			<?php
		}

		/* Template Settings Metabox */
		public static function display_template_settings($post,$key)
		{
			$template_data = self::$Inbound_Email_Templates->template_definitions;
			
			$key = $key['args']['key'];

			$inbound_email_templates_custom_fields = $template_data[$key]['settings'];

			$inbound_email_templates_custom_fields = apply_filters('inbound_email_templates_show_metabox',$inbound_email_templates_custom_fields, $key);

			inbound_template_metabox_render( 'email-template' , $key, $inbound_email_templates_custom_fields, $post);
		}


		/* Email Subject */
		public static function display_subject_line_input()
		{
		   global $post;

			$inbound_email_templates_variation = (isset($_GET['inbound-email-variation-id'])) ? $_GET['inbound-email-variation-id'] : '0';

			$variation_notes = apply_filters('inbound_email_templates_edit_variation_notes', ''  );


			if ( empty ( $post ) || self::$post_type !== get_post_type( $GLOBALS['post'] ) ) {
				return;
			}

			echo '<span id="cta_shortcode_form" style="display:none; font-size: 13px;margin-left: 15px;">
				 Shortcode: <input type="text" style="width: 130px;" class="regular-text code short-shortcode-input" readonly="readonly" id="shortcode" name="shortcode" value=\'[cta id="'.$post->ID.'"]\'>
				<div class="inbound_email_templates_tooltip" style="margin-left: 0px;" title="'. __( 'You can copy and paste this shortcode into any page or post to render this call to action. You can also insert CTAs from the wordpress editor on any given page' , 'leads' ) .'"></div></span>';


			echo "<div id='inbound-email-notes-area' data-field-type='text'>";
			self::display_notes('inbound-email-variation-notes',$variation_notes);
			echo '</div>';

			// Set frontend editor params
			if(isset($_REQUEST['frontend']) && $_REQUEST['frontend'] == 'true') {
				echo('<input type="hidden" name="frontend" id="frontend-on" value="true" />');
			}

		}


		public static function display_notes($id , $variation_notes)
		{
			//echo $id;
			$id = apply_filters('inbound_email_templates_display_notes_input_id',$id);

			echo "<span id='add-inbound-email-notes'>". __( 'Notes:' , 'leads' ) ."</span><input placeholder='". __( 'Add Notes to your email template.' , 'leads' ) ."' type='text' class='inbound-email-notes' name='{$id}' id='{$id}' value='{$variation_notes}' size='30'>";
		}


		
		public static function save_notes( $post_id )
		{
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ){
				return;
			}

			$key = 'inbound-email-variation-notes';
			$key = apply_filters( 'inbound_email_templates_display_notes_input_id' , $key );

			if ( isset ( $_POST[ $key ] ) ){
				return update_post_meta( $post_id, $key, $_POST[ $key ] );
			}

		}


		public static function change_title_text( $text, $post ) {
			if ($post->post_type==self::$post_type) {
				return __( 'Enter Email Template Description' , 'leads' );
			} else {
				return $text;
			}
		}

		
		public static function save_template_meta($post_id) {
			global $post;
			
			$template_data = self::$Inbound_Email_Templates->template_definitions;

			if (!isset($post)) {
				return;
			}

			if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ) {
				return;
			}

			if ($post->post_type!=self::$post_type)	{
				return;
			}
			

			foreach ($template_data as $key=>$data)
			{
				foreach ($template_data[$key]['settings'] as $field)
				{
					( isset($field['global']) && $field['global'] ) ? $field['id'] : $field['id'] = $key."-".$field['id'];

					if($field['type'] == 'tax_select'){
						continue;
					}

					$field['id'] = apply_filters( 'inbound_email_templates_ab_field_id' , $field['id'] );

					$old = get_post_meta($post_id, $field['id'], true);

					( isset($_POST[$field['id']]) ) ? $new = $_POST[$field['id']] : $new = null;

					/*
					echo $field['id'].' old:'.$old.'<br>';
					echo $field['id'].' new:'.$new.'<br>';
					*/

					if (isset($new) && $new != $old ) {
						update_post_meta($post_id, $field['id'], $new);
					}
					
				}
			}
		}

		// Render select template box
		public static function display_template_select_container() {
			global $inbound_email_templates_data, $post, $current_url;

			if (isset($post)&&$post->post_type!=self::$post_type||!isset($post)){ return false; }

			( !strstr( $current_url, 'post-new.php')) ?  $toggle = "display:none" : $toggle = "";

			$template_data = self::$Inbound_Email_Templates->template_definitions;
			
			$template =  get_post_meta($post->ID, 'inbound-email-selected-template', true);
			$template = apply_filters('inbound_email_templates_selected_template',$template);

			echo "<div class='inbound-email-templates-selector-container' style='{$toggle}'>";
			echo "<div class='inbound-email-selection-heading'>";
			echo "<h1>". __( 'Select Email Template!' , 'leads' ) ."</h1>";
			echo '<a class="button-secondary" style="display:none;" id="inbound-email-cancel-selection">Cancel Template Change</a>';
			echo "</div>";
				echo '<ul id="template-filter" >';
					echo '<li><a href="#" data-filter=".template-item-boxes">All</a></li>';
					$categories = array();
					foreach ( self::$Inbound_Email_Templates->template_categories as $cat)
					{

						$category_slug = str_replace(' ','-',$cat['value']);
						$category_slug = strtolower($category_slug);
						$cat['value'] = ucwords($cat['value']);
						
						if (!in_array($cat['value'],$categories))
						{
							echo '<li><a href="#" data-filter=".'.$category_slug.'">'.$cat['value'].'</a></li>';
							$categories[] = $cat['value'];
						}

					}
				echo "</ul>";
				echo '<div id="templates-container" >';

				foreach ($template_data as $this_template=>$data)
				{

					if (isset($data['info']['data_type'])&&$data['info']['data_type']!='email-template'){
						continue;
					}

					$cats = explode( ',' , $data['info']['category'] );
					foreach ($cats as $key => $cat)
					{
						$cat = trim($cat);
						$cat = str_replace(' ', '-', $cat);
						$cats[$key] = trim(strtolower($cat));
					}

					$cat_slug = implode(' ', $cats);

					// Get Thumbnail
					if (file_exists(INBOUND_EMAIL_TEMPLATES_PATH.$this_template."/thumbnail.png"))
					{
						$thumbnail = INBOUND_MARKETING_AUTOMATION_URLPATH.'email-templates/'.$this_template."/thumbnail.png";
					}
					else
					{
						$thumbnail = INBOUND_EMAIL_UPLOADS_URLPATH.$this_template."/thumbnail.png";
					}
					?>
					<div id='template-item' class="<?php echo $cat_slug; ?> template-item-boxes">
						<div id="template-box">
							<div class="inbound_email_templates_tooltip_templates" title="<?php echo $data['info']['description']; ?>"></div>
						<a class='inbound_email_templates_select_template' href='#' label='<?php echo $data['info']['label']; ?>' id='<?php echo $this_template; ?>'>
							<img src="<?php echo $thumbnail; ?>" class='template-thumbnail' alt="<?php echo $data['info']['label']; ?>" id='inbound_email_templates_<?php echo $this_template; ?>'>
						</a>

							<div id="template-title" style="text-align: center;	font-size: 14px; padding-top: 10px;"><?php echo $data['info']['label']; ?></div>
					
						</div>
					</div>
					<?php
				}
			echo '</div>';
			echo "<div class='clear'></div>";
			echo "</div>";
			echo "<div style='display:none;' class='currently_selected'>". __( 'This is Currently Selected' , 'leads' ) ."</a></div>";
		}
		
		/* Enqueue Admin Scripts */
		public static function enqueue_admin_scripts( $hook ) {
			global $post;

			if ( !isset($post) || $post->post_type != self::$post_type ) {
				return;
			}
			
			if ( $hook == 'post-new.php' ) {
				/* Enqueue JS */
				wp_register_script('inbound-email-templates-admin-postnew-js', INBOUND_MARKETING_AUTOMATION_URLPATH . 'js/email-templates/admin.post-new.js', array('jquery'));
				wp_enqueue_script('inbound-email-templates-admin-postnew-js');
				
				/* Enqueue CSS */
				wp_enqueue_style('inbound-email-templates-admin-postnew-css', INBOUND_MARKETING_AUTOMATION_URLPATH.'/css/email-templates/admin.post-new.css');
			}
			
			if ( $hook == 'post.php' ) {
				/* Enqueue JS */
				wp_register_script('inbound-email-templates-admin-editor-js', INBOUND_MARKETING_AUTOMATION_URLPATH . 'js/email-templates/admin.editor.js', array('jquery'));
				wp_enqueue_script('inbound-email-templates-admin-editor-js');
				
				/* Enqueue CSS */
				wp_enqueue_style('inbound-email-templates-admin-postedit-css', INBOUND_MARKETING_AUTOMATION_URLPATH.'/css/email-templates/admin.post-edit.css');
			}
			
			if ($hook == 'post-new.php' || $hook == 'post.php') {
				
				/* Load*/
				wp_enqueue_script('inbound-email-templates-post-edit-ui', INBOUND_MARKETING_AUTOMATION_URLPATH . 'js/email-templates/admin.post-edit.js');
				wp_localize_script( 'inbound-email-templates-post-edit-ui', 'inbound_email_templates_post_edit_ui', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'post_id' => $post->ID , 'inbound_email_templates_meta_nonce' => wp_create_nonce('inbound-email-templates-meta-nonce'), 'inbound_email_templates_template_nonce' => wp_create_nonce('inbound-email-templates-nonce') ) );
				
				/* Load Template Selector JS */
				$template_data = self::$Inbound_Email_Templates->template_definitions;				
				$template_data = json_encode($template_data);
				
				$template = strtolower(get_post_meta($post->ID, 'inbound-email-templates-selected-template', true));
				$template = apply_filters('inbound_email_selected_template',$template);
				
				$params = array('selected_template'=>$template, 'templates'=>$template_data);				
				
				/* Enqueue */
				wp_register_script('inbound-email-templates-admin-templateselector-js', INBOUND_MARKETING_AUTOMATION_URLPATH . 'js/email-templates/admin.templateselector.js', array('jquery'));
				wp_enqueue_script('inbound-email-templates-admin-templateselector-js');
				wp_localize_script('inbound-email-templates-admin-templateselector-js', 'data', $params);
				
				/* Load TINYMCE */
				wp_dequeue_script('jquery-tinymce');
				wp_enqueue_script('jquery-tinymce', INBOUND_MARKETING_AUTOMATION_URLPATH . 'js/email-templates/tiny_mce/jquery.tinymce.js');
				
				
			}
		}
	}
	
	
	$Inbound_Metaboxes_Email_Templates = new Inbound_Metaboxes_Email_Templates;
}
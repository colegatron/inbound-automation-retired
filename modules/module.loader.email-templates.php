<?php
/* Loads email templates from core and uploads directory */

function Inbound_Email_Templates()
{
	return Inbound_Email_Templates::instance();
}

class Inbound_Email_Templates
{
	private static $instance;
	public $template_definitions;
	public $template_categories;

	public static function instance()
	{
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Inbound_Email_Templates ) )
		{
			self::$instance = new Inbound_Email_Templates;

			/* if frontend load transient data - this data will update on every wp-admin call so you can use an admin call as a cache clear */
			if ( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX &&  isset($_POST['action']) && $_POST['action'] != 'inbound_get_email_template_settings' )  ) 
			{				
				self::$instance->template_definitions = get_transient('inbound_email_template_definitions');
				
				if ( self::$instance->template_definitions ) {
					return self::$instance;
				}
			}

			self::$instance->include_template_files();
			self::$instance->add_core_definitions();
			self::$instance->read_template_categories();
		}

		return self::$instance;
	}

	/* Include All Template config.php Files */
	function include_template_files()
	{
		/* Load Core Templates */
		$core_templates = self::$instance->get_core_templates();

		foreach ($core_templates as $name)
		{
			if ($name != ".svn"){
				include_once( INBOUND_EMAIL_TEMPLATES_PATH."$name/config.php" );
			}
		}

		/* Load Uploaded Templates */
		$uploaded_templates = self::$instance->get_uploaded_templates();

		foreach ($uploaded_templates as $name)
		{
			include_once( INBOUND_EMAIL_UPLOADS_PATH."$name/config.php");
		}

		self::$instance->template_definitions = $inbound_email_templates;
		
	}

	/* Loads Template Names Included Within The Core Plugin */
	function get_core_templates()
	{
		$core_templates = array();
		$results = scandir( INBOUND_EMAIL_TEMPLATES_PATH );

		//scan through templates directory and pull in name paths
		foreach ($results as $name) {
			if ($name === '.' or $name === '..' or $name === '__MACOSX') {
				continue;
			}

			if ( is_dir( INBOUND_EMAIL_TEMPLATES_PATH . $name) ) {
				$core_templates[] = $name;
			}
		}

		return $core_templates;
	}

	/* Loads Template Names Discovered in Uploads Folder */
	function get_uploaded_templates()
	{
		$uploaded_templates = array();

		if (!is_dir( INBOUND_EMAIL_UPLOADS_PATH ))
		{
			wp_mkdir_p( INBOUND_EMAIL_UPLOADS_PATH );
		}

		$templates = scandir( INBOUND_EMAIL_UPLOADS_PATH );


		//scan through templates directory and pull in name paths
		foreach ($templates as $name) {
			if ($name === '.' or $name === '..' or $name === '__MACOSX') continue;

			if ( is_dir( INBOUND_EMAIL_UPLOADS_PATH . '/' . $name ) ) {
				$uploaded_templates[] = $name;
			}
		}

		return $uploaded_templates;
	}

	/* filters to add in core definitions to the calls to action extension definitions array */
	function add_core_definitions()
	{
		add_filter('save_post' , array( $this , 'store_template_data_as_transient') , 1  );
		add_filter('inbound_email_extension_data' , array( $this , 'add_default_metaboxes') , 1  );
		add_filter('inbound_email_extension_data' , array( $this , 'add_default_advanced_settings') , 1  );
		
	}

	function store_template_data_as_transient( $post_id )
	{
		global $post;

		if (!isset($post)) {
			return;
		}
		if ($post->post_type=='revision' ||  'trash' == get_post_status( $post_id )) {
			return;
		}

		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )||( isset($_POST['post_type']) && $_POST['post_type']=='revision' )) {
			return;
		}

		if ($post->post_type=='email-template') {
			set_transient('inbound_email_template_definitions' , self::$instance->template_definitions  , 60*60*24 );
		}
	}

	/* adds default metabox to all calls to action */
	public static function add_default_metaboxes($inbound_email_templates)
	{
		/* this is a special key that targets CTA metaboxes */
		$parent_key = 'wp-cta';

		$inbound_email_templates[$parent_key]['settings'][] = array(
					'data_type'  => 'metabox',
					'id'  => 'selected-template',
					'label' => __( 'Select Template' , 'cta' ),
					'description' => __( 'This option provides a placeholder for the selected template data.' , 'cta' ),
					'type'  => 'radio', // this is not honored. Template selection setting is handled uniquely by core.
					'default'  => 'blank-template',
					'options' => null // this is not honored. Template selection setting is handled uniquely by core.
				);

		//IMPORT ALL EXTERNAL DATA

		return $inbound_email_templates;

	}

	/* adds default settings to Advanced Settings metabox */
	public static function add_default_advanced_settings($inbound_email_templates)
	{
		/* this is a special key that targets CTA metaboxes */
		$parent_key = 'wp-cta';

		/*
		$inbound_email_templates[$parent_key]['settings']['advanced-core-options-header'] =   array(
			'datatype' => 'setting',
			'region' => 'advanced',
			'description'  => __( '<h3>CTA Settings</h3>' , 'cta' ),
			'id'    => 'advanced-core-options-header',
			'type'  => 'html-block'
		);

		$inbound_email_templates[$parent_key]['settings']['link-open-option'] = array(
				'data_type'  => 'metabox',
				'region' => 'advanced',
				'label' => __( 'Open Links' , 'cta' ),
				'description' => __( 'How do you want links on the call to action to work?' , 'cta' ),
				'id'  => 'link-open-option', // called in template's index.php file with lp_get_value($post, $key, 'checkbox-id-here');
				'type'  => 'dropdown',
				'default'  => 'this_window',
				'options' => array('this_window' => __('Open Links in Same Window (default)' , 'cta' ) ,'new_tab'=> __( 'Open Links in New Tab' , 'cta' )),
				'context'  => 'normal'
				);
		*/

		return $inbound_email_templates;
	}

	/* Reads Category of Each Template and Builds a Special Array Definition */
	public static function read_template_categories()
	{

		$template_cats = array();

		if ( !isset(self::$instance->template_definitions ) ) {
			return;
		}

		//print_r($extension_data);
		foreach (self::$instance->template_definitions as $key=>$val)
		{

			/* allot for older lp_data model */
			if (isset($val['category']))
			{
				$cats = $val['category'];
			}
			else
			{
				if (isset($val['info']['category']))
				{
					$cats = $val['info']['category'];
				}
			}

			$cats = explode(',',$cats);

			foreach ($cats as $cat_value)
			{
				$cat_value = trim($cat_value);
				$name = str_replace(array('-','_'),' ',$cat_value);
				$name = ucwords($name);

				if (!isset($template_cats[$cat_value]))
				{
					$template_cats[$cat_value]['count'] = 1;
				}
				else
				{
					$template_cats[$cat_value]['count']++;
				}

				$template_cats[$cat_value]['value'] = $cat_value;
				$template_cats[$cat_value]['label'] = "$name";
			}
		}

		self::$instance->template_categories = $template_cats;
	}
}



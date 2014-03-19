<?php
/*
Plugin Name: Marketing Automation
Plugin URI: http://www.inboundnow.com/
Description: Automate Everything.

*/

define( 'INBOUND_MARKETING_AUTOMATION_URLPATH' , WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
define( 'INBOUND_EMAIL_TEMPLATES_PATH' , WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/email-templates/' );
define( 'INBOUND_EMAIL_UPLOADS_PATH' , $uploads['basedir'].'/email/templates/' );
define( 'INBOUND_EMAIL_UPLOADS_URLPATH' , $uploads['baseurl'].'/email/templates/' );

/* load core files */
switch (is_admin()) :
	case true :

		/* Load Post Type(s) */
		include_once('modules/module.post-type.automation.php');
		include_once('modules/module.post-type.email-template.php');

		/* Load Settings */
		include_once('modules/module.loader.automation.php');
		include_once('modules/module.loader.email-templates.php');

		/* Load Metaboxes */
		include_once('modules/module.metaboxes.email-template.php');

		/* Include Triggers */
		include_once('definitions/trigger.save_lead.php');

		/* Include Filters */
		include_once('definitions/filter.lead_data.php');

		/* Include Actions */
		include_once('definitions/action.send_email.php');
		BREAK;

	case false :


		BREAK;
endswitch;

/* Load Cronjob Engine */
<?php
/*
Plugin Name: Automation, Email
Plugin URI: http://www.inboundnow.com/
Description: Automate Everything.

*/

define( 'INBOUND_MARKETING_AUTOMATION_FILE' ,  __FILE__ );
define( 'INBOUND_MARKETING_AUTOMATION_URLPATH' , WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
define( 'INBOUND_MARKETING_AUTOMATION_PATH' , WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );

/* load core files */
switch (is_admin()) :
	case true :

		/* Load Post Type(s) */
		include_once('components/class.post-type.automation.php');
		include_once('components/class.post-type.email-template.php');

		/* Load Settings */
		include_once('components/class.loader.automation.php');

		/* Load Metaboxes */
		include_once('components/class.metaboxes.email-template.php');
		include_once('components/class.metaboxes.automation.php');

		/* Include Triggers */
		include_once('definitions/trigger.save_lead.php');

		/* Include Actions */
		include_once('definitions/action.send_email.php');
		BREAK;

	case false :
		/* Load Post Type(s) */
		include_once('components/class.post-type.email-template.php');

		BREAK;
endswitch;

/* Load Cronjob Engine */
include_once('components/class.cron.automation.php');
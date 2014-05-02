<?php
/*
Plugin Name: Automation, Email
Plugin URI: http://www.inboundnow.com/
Description: Automate Everything.

*/

define( 'INBOUND_MARKETING_AUTOMATION_FILE' ,  __FILE__ );
define( 'INBOUND_MARKETING_AUTOMATION_URLPATH' ,  plugins_url( '/' , __FILE__ )  );
define( 'INBOUND_MARKETING_AUTOMATION_PATH' , WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );



include_once('components/class.post-type.automation.php');
include_once('components/class.post-type.email-template.php');
include_once('components/class.post-type.inbound-log.php');
include_once('components/class.loader.automation.php');
include_once('components/class.templating-engine.php');
include_once('components/class.metaboxes.email-template.php');
include_once('components/class.metaboxes.automation.php');
include_once('components/class.shortcodes.email-template.php');
include_once('components/class.wordpress-core.email.php');

include_once('definitions/trigger.save_lead.php');
include_once('definitions/action.wait.php');
include_once('definitions/action.send_email.php');




/* Load Cronjob Engine */
include_once('components/class.cron.automation.php');


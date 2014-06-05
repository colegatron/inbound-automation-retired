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
include_once('components/class.logs.automation.php');
include_once('components/class.loader.automation.php');
include_once('components/class.metaboxes.automation.php');

include_once('definitions/trigger.save_lead.php');
include_once('definitions/trigger.update_lead.php');
include_once('definitions/action.wait.php');
include_once('definitions/action.send_email.php');
include_once('definitions/action.relay_data.php');
include_once('definitions/query.lead_data.php');




/* Load Cronjob Engine */
include_once('components/class.cron.automation.php');


<?php
/**
* WordPress: Inbound Now Email Template
* Template Name:  New Lead Notification
* @package  TBA
* @author 	InboundNow
*/

//gets template directory name to use as identifier - do not edit - include in all template files
$key = basename(dirname(__FILE__));
$this_path = INBOUND_EMAIL_TEMPLATES_PATH.$key.'/';

$inbound_email_templates[$key]['info'] =
array(
	'data_type' => 'email-template', // Template Data Type
	'version' => "1.0", // Version Number
	'label' => "New Lead Notification", // Nice Name
	'category' => 'InboundNow', // Template Category
	'demo' => '', // Demo Link
	'description'  => 'Template designed for admin notifications of new emails.', // template description
	'path' => $this_path //path to template folder
);


/* Define Meta Options for template */
$inbound_email_templates[$key]['settings'] =
array(
    array(
        'label' => 'Instructions', // Name of field
        'description' => "Instructions for this call to action template go here", // what field does
        'id' => 'description', // metakey. $key Prefix is appended from parent in array loop
        'type'  => 'description-block', // metafield type
        'default'  => 'Variable Settings TBA', // default content
        )
    );


/* define dynamic template markup */
$inbound_email_templates[$key]['markup'] = file_get_contents($this_path . 'index.php');

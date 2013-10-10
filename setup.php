<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2013 Super-Visions BVBA                                   |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 */

define("AUTOM8_RULE_TYPE_THOLD_MATCH", 7);
define("AUTOM8_RULE_TYPE_THOLD_ACTION", 8);

define("AUTOM8_ACTION_THOLD_DUPLICATE", 1);
define("AUTOM8_ACTION_THOLD_ENABLE", 2);
define("AUTOM8_ACTION_THOLD_DISABLE", 3);
define("AUTOM8_ACTION_THOLD_DELETE", 99);

# non-gw-cacti compatibility
global $database_idquote;
if(empty($database_idquote)) $database_idquote = '`';

/**
 * plugin_autom8thold_install    - Initialize the plugin and setup all hooks
 */
function plugin_autom8thold_install() {	

	#api_plugin_register_hook('PLUGINNAME', 'HOOKNAME', 'CALLBACKFUNCTION', 'FILENAME');
	#api_plugin_register_realm('PLUGINNAME', 'FILENAMETORESTRICT', 'DISPLAYTEXT', true);

	# setup all arrays needed
	api_plugin_register_hook('autom8thold', 'config_arrays', 'autom8thold_config_arrays', 'setup.php');
	# setup all forms needed
	api_plugin_register_hook('autom8thold', 'config_settings', 'autom8thold_config_settings', 'setup.php');
	api_plugin_register_hook('autom8thold', 'config_form', 'autom8thold_config_form', 'setup.php');
	# graph provide navigation texts
	api_plugin_register_hook('autom8thold', 'draw_navigation_text', 'autom8thold_draw_navigation_text', 'setup.php');
	
	# register all php modules required for this plugin
	api_plugin_register_realm('autom8thold', 'autom8_thold_rules.php', 'Plugin Automate -> Maintain Threshold Rules', true);
	
	# add plugin_autom8_thold_rules table
	$data = array();
	$data['columns'][] = array('name' => 'id', 				'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name',	 		'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'thold_template_id',	'type' => 'int(11)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'snmp_query_id',	'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => true, 'default' => 0);
	$data['columns'][] = array('name' => 'enabled', 		'type' => 'char(2)', 'NULL' => true,  'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name'=> 'thold_template_id', 'columns' => 'thold_template_id');
	$data['keys'][] = array('name'=> 'snmp_query_id', 'columns' => 'snmp_query_id');
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Autom8 Threshold Rules';
	api_plugin_db_table_create ('autom8thold', 'plugin_autom8_thold_rules', $data);
	
	# add plugin_autom8_thold_rule_items table
	$data = array();
	$data['columns'][] = array('name' => 'id', 			'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'rule_id',		'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'sequence',	'type' => 'smallint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'operation',	'type' => 'smallint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'field',		'type' => 'varchar(255)', 'NULL' => true,  'default' => '');
	$data['columns'][] = array('name' => 'operator',	'type' => 'smallint(3)', 'unsigned' => 'unsigned', 'NULL' => true, 'default' => 0);
	$data['columns'][] = array('name' => 'pattern', 	'type' => 'varchar(255)', 'NULL' => true,  'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name'=> 'rule_id', 'columns' => 'rule_id');
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Autom8 Threshold Rule Items';
	api_plugin_db_table_create ('autom8thold', 'plugin_autom8_thold_rule_items', $data);
	
}

/**
 * plugin_autom8thold_check_config - Check if dependencies are met
 * 
 * @return boolean
 */
function plugin_autom8thold_check_config(){
	return db_fetch_cell("SELECT status FROM plugin_config WHERE directory = 'autom8thold';") == 0 || api_plugin_is_enabled('autom8');
}

/**
 * plugin_autom8thold_version    - define version information
 */
function plugin_autom8thold_version() {
	return autom8thold_version();
}

/**
 * autom8thold_version    - Version information (used by update plugin)
 */
function autom8thold_version() {
    return array(
    	'name'		=> 'Autom8-Thold',
		'version'	=> '0.1.0',
		'longname'	=> 'Automate threshold creation for data sources',
		'author'	=> 'Thomas Casteleyn',
		'email'		=> 'thomas.casteleyn@super-visions.com',
		'homepage'	=> 'https://github.com/Super-Visions/cacti-plugin-autom8thold'
    );
}

/**
 * autom8thold_draw_navigation_text    - Draw navigation texts
 * @param array $nav            - all current navigation texts
 * returns array                - updated navigation texts
 */
function autom8thold_draw_navigation_text($nav) {
	// Displayed navigation text under the blue tabs of Cacti
	$nav["autom8_thold_rules.php:"] 			= array("title" => "Threshold Rules", "mapping" => "index.php:", "url" => "autom8_thold_rules.php", "level" => "1");
	$nav["autom8_thold_rules.php:edit"] 		= array("title" => "(Edit)", "mapping" => "index.php:,autom8_thold_rules.php:", "url" => "", "level" => "2");
	$nav["autom8_thold_rules.php:actions"] 	= array("title" => "Actions", "mapping" => "index.php:,autom8_thold_rules.php:", "url" => "", "level" => "2");
	$nav["autom8_thold_rules.php:item_edit"]	= array("title" => "Threshold Rule Items", "mapping" => "index.php:,autom8_thold_rules.php:,autom8_thold_rules.php:edit", "url" => "", "level" => "3");
	
    return $nav;
}

/**
 * autom8thold_config_arrays    - Setup arrays needed for this plugin
 */
function autom8thold_config_arrays() {
	
	# menu titles
	global $menu;
	$menu["Templates"]['plugins/autom8thold/autom8_thold_rules.php'] = "Threshold Rules";

}

/**
 * autom8thold_config_form	- Setup forms needed for this plugin
 */
function autom8thold_config_form () {
	
	global $fields_autom8_thold_rules_create, $fields_autom8_thold_rules_edit;
	
	$fields_autom8_thold_rules_create = array(
		"name" => array(
			"method" => "textbox",
			"friendly_name" => "Name",
			"description" => "A useful name for this Rule.",
			"value" => "|arg1:name|",
			"max_length" => "255",
			"size" => "60"
		),
		"thold_template_id" => array(
			"method" => "drop_sql",
			"friendly_name" => "REQUIRED: Threshold Template",
			"description" => "Choose a Threshold Template to apply to this rule.",
			"value" => "|arg1:thold_template_id|",
			"on_change" => "applyTemplateIdChange(document.form_autom8_rule_edit)",
			"sql" => "SELECT id, name FROM thold_template ORDER BY name;"
		),
	);

	$fields_autom8_thold_rules_edit = array(
		"snmp_query_id" => array(
			"method" => "drop_sql",
			"friendly_name" => "Data Query",
			"description" => "Choose a Data Query to apply to this rule.",
			"value" => "|arg1:snmp_query_id|",
			"none_value" => "None",
			"on_change" => "applySNMPQueryIdChange(document.form_autom8_rule_edit)",
			"sql" => "SELECT 
	sq.id, 
	sq.name 
FROM snmp_query sq 
JOIN snmp_query_graph sqg 
	ON (sqg.snmp_query_id = sq.id) 
JOIN snmp_query_graph_rrd_sv sqgrs 
	ON (sqgrs.snmp_query_graph_id = sqg.id ) 
JOIN thold_template tt
	USING (data_template_id)
WHERE 
	tt.id = |arg1:thold_template_id| 
GROUP BY sq.id, sq.name 
ORDER BY sq.name;"
		),
		"enabled" => array(
			"method" => "checkbox",
			"friendly_name" => "Enable Rule",
			"description" => "Check this box to enable this rule.",
			"value" => "|arg1:enabled|",
			"default" => "",
			"form_id" => false
		),
	);
}

/**
 * autom8thold_config_settings    - configuration settings for this plugin
 */
function autom8thold_config_settings() {
	global $tabs, $settings, $plugins;

	if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) != 'settings.php' || isset($plugins['autom8']))
		return;

	$temp = array(
		"autom8_thold_enabled" => array(
			"method" => "checkbox",
			"friendly_name" => "Enable Autom8 Threshold creation",
			"description" => "When disabled, Autom8 will not actively create Thresholds.<br>" .
				"This will be useful when fiddeling around with Hosts and Graphs to avoid creating Thresholds each time you save an object.<br>" .
				"Invoking Rules manually will still be possible.",
			"default" => "",
		),
	);

	// find position of other autom8 settings
	$lastpos = 0;
	foreach(array_keys($settings["misc"]) as $pos => $key){
		if(substr($key, 0, 6) == 'autom8') $lastpos = $pos;
	}

	// merge own settings
	if($lastpos > 0){
		$settings["misc"] = array_merge(array_slice($settings["misc"], 0, $lastpos+1), $temp, array_slice($settings["misc"], $lastpos+1));
	}

}

?>

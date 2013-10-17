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
	# setup actions
	api_plugin_register_hook('autom8thold', 'autom8_data_source_action', 'autom8thold_data_source_action', 'setup.php');
	
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

/**
 * Perform rule actions to selected data sources
 * 
 * @param array $selected_items
 * @return array
 */
function autom8thold_data_source_action($selected_items){
	global $config, $cnn_id, $database_idquote, $autom8_op_array;
	
	include_once($config['base_path'].'/plugins/autom8thold/autom8_utilities.php');
	include_once($config['base_path'].'/plugins/thold/thold_functions.php');
	
	$id_list = implode(',', $selected_items);
	
	// return if we have wrong data
	if(!preg_match('#^([0-9]+,)*[0-9]+$#', $id_list)) return $selected_items;
	
	// find possibly matching thold rules
	$thold_rule_settings_sql = sprintf("SELECT DISTINCT 
	thold_rule.id AS rule_id, 
	thold_rule.thold_template_id, 
	thold_template.data_source_id 
FROM data_template_data 
JOIN thold_template 
	USING(data_template_id) 
JOIN thold_data thold 
	ON(thold.template = thold_template.id) 
JOIN plugin_autom8_thold_rules thold_rule 
	ON(thold_rule.thold_template_id = thold_template.id) 
WHERE thold_rule.enabled = 'on' 
	AND local_data_id IN(%s);", $id_list );
	$thold_rule_settings = db_fetch_assoc($thold_rule_settings_sql);
	
	// execute every rule to find matching DS
	foreach ($thold_rule_settings as &$thold_rule) {
		
		// get all used data query fields
		$dq_fields = get_rule_dq_fields($thold_rule['rule_id'], 'plugin_autom8_thold_rule_items');
		
		// get rule items
		$rule_items_where = build_rule_item_filter(get_rule_items($thold_rule['rule_id'], 'plugin_autom8_thold_rule_items'));
		
		// get match items
		$match_items_where = build_matching_objects_filter($thold_rule['rule_id'], AUTOM8_RULE_TYPE_THOLD_MATCH);
		
		// build SQL query WHERE part
		$sql_where = sprintf('dl.id IN(%s) ' . PHP_EOL . '	AND ( %s ) ' . PHP_EOL, $id_list, $match_items_where);
		$sql_where .= empty($rule_items_where)? '	AND (1 ' . $autom8_op_array['op'][AUTOM8_OP_MATCHES_NOT] . ' 1) ' . PHP_EOL : '	AND ( ' . $rule_items_where . ' ) '.PHP_EOL;
		$sql_where .= '	AND td.rra_id IS NULL '.PHP_EOL;

		// build SQL query FROM part
		$sql_from = sprintf('data_template_data AS dtd 
LEFT JOIN thold_data AS td 
	ON( dtd.local_data_id = td.rra_id AND td.template = %d ) 
JOIN data_local AS dl 
	ON( dl.id = dtd.local_data_id ) 
JOIN data_template_rrd AS dtr 
	ON( dtr.local_data_id = dl.id AND dtr.local_data_template_rrd_id = %d ) 
JOIN graph_templates_item AS gti 
	ON( gti.task_item_id = dtr.id ) 
JOIN ' . $database_idquote . 'host' . $database_idquote .' 
	ON( host.id = dl.host_id ) 
JOIN host_template 
	ON ( host.host_template_id = host_template.id )	
', $thold_rule['thold_template_id'], $thold_rule['data_source_id'] );
	
		// build SQL query SELECT part
		$sql_select = '
	dl.rra_id, 
	dl.host_id, 
	dtr.id AS data_id, 
	gti.local_graph_id, 
	gti.graph_template_id, 
	dtd.name_cache '.PHP_EOL;
	
		// add some dynamical fields
		foreach ($dq_fields as $dq_field){

			$sql_from .= sprintf('
LEFT JOIN host_snmp_cache AS hsc_%1$s 
	ON( 
		hsc_%1$s.host_id = dl.host_id AND 
		hsc_%1$s.snmp_query_id = dl.snmp_query_id AND 
		hsc_%1$s.snmp_index =  dl.snmp_index AND 
		hsc_%1$s.field_name = \'%1$s\' 
	) ' . PHP_EOL, $dq_field['field']);
		
		}
		
		// find matching DS
		$data_item_list_sql = 'SELECT DISTINCT ' . $sql_select . 'FROM ' . $sql_from . 'WHERE ' . $sql_where . 'ORDER BY dtd.name_cache ASC;';
		$data_item_list = db_fetch_assoc($data_item_list_sql);
		
		// do action with data items
		foreach($data_item_list as $data_item){
			
			$insert_sql = sprintf("INSERT INTO thold_data (name, rra_id, data_id, graph_id, graph_template, host_id, template) VALUES ('%s', %d, %d, %d, %d, %d, %d);",
				'test '. rand(100, 999), 
				$data_item['rra_id'],
				$data_item['data_id'],
				$data_item['local_graph_id'],
				$data_item['graph_template_id'],
				$data_item['host_id'],
				$thold_rule['thold_template_id']
			);
						
			// create threshold
			if(db_execute($insert_sql) && $id = $cnn_id->Insert_ID()){				
				// update threshold with template defaults
				thold_template_update_threshold($id, $thold_rule['thold_template_id']);
				// log creation
				plugin_thold_log_changes($id, 'created');
			}
		}
	}
	
	return $selected_items;
}

?>

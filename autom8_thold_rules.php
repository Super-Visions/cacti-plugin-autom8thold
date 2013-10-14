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

chdir('../../');
include('./include/auth.php');
include_once('./lib/data_query.php');

define('MAX_DISPLAY_PAGES', 21);

$script_url = $config['url_path'].'plugins/autom8thold/autom8_thold_rules.php';

$thold_rule_actions = array(
	AUTOM8_ACTION_THOLD_DUPLICATE => 'Duplicate',
	AUTOM8_ACTION_THOLD_ENABLE => 'Enable',
	AUTOM8_ACTION_THOLD_DISABLE => 'Disable',
	AUTOM8_ACTION_THOLD_DELETE => 'Delete',
);

switch (get_request_var_request('action')) {
	case 'save':
		autom8_thold_rules_form_save();

		break;
	case 'actions':
		autom8_thold_rules_form_actions();

		break;
	case 'item_movedown':
		autom8_thold_rules_item_movedown();

		header('Location: ' . $script_url . '?action=edit&id=' . (int) get_request_var_request('id', 0));
		break;
	case 'item_moveup':
		autom8_thold_rules_item_moveup();

		header('Location: ' . $script_url . '?action=edit&id=' . (int) get_request_var_request('id', 0));
		break;
	case 'item_remove':
		autom8_thold_rules_item_remove();

		header('Location: ' . $script_url . '?action=edit&id=' . (int) get_request_var_request('id', 0));
		break;
	case 'item_edit':
		include_once($config['include_path'] . '/top_header.php');

		autom8_thold_rules_item_edit();

		include_once($config['include_path'] . '/bottom_footer.php');
		break;
 	case 'remove':
		autom8_thold_rules_remove();

		header('Location: '.$script_url);
		break;
	case 'edit':
		include_once($config['include_path'] . '/top_header.php');

		autom8_thold_rules_edit();

		include_once($config['include_path'] . '/bottom_footer.php');
		break;
	default:
		include_once($config['include_path'] . '/top_header.php');

		autom8_thold_rules();

		include_once($config['include_path'] . '/bottom_footer.php');
		break;
}

/* --------------------------
 The Save Function
 -------------------------- */

function autom8_thold_rules_form_save() {
	global $script_url;

	if(get_request_var_post('save_component_autom8_thold_rule', 0)){

		$rule_id = (int) get_request_var_post('id', 0);

		$save['id'] = $rule_id;
		$save['name'] = form_input_validate(get_request_var_post('name'), 'name', '', false, 3);
		$save['thold_template_id'] = form_input_validate(get_request_var_post('thold_template_id'), 'snmp_query_id', '^[0-9]+$', false, 3);
		$save['snmp_query_id'] = form_input_validate(get_request_var_post('snmp_query_id', 0), 'snmp_query_id', '^[0-9]+$', false, 3);
		$save['action'] = form_input_validate(get_request_var_post('ds_action', 0), 'ds_action', '^[0-9]$', false, 3);
		$save['enabled'] = get_request_var_post('enabled') ? 'on' : '';
		if (!is_error_message()) {
			$rule_id = sql_save($save, 'plugin_autom8_thold_rules');

			if ($rule_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		header('Location: ' . $script_url . '?action=edit&id=' . $rule_id);
		
	}elseif(get_request_var_post('save_component_autom8_thold_rule_item', 0)){

		$item_id = (int) get_request_var_post('item_id', 0);
		$rule_id = (int) get_request_var_post('id', 0);
		$field = (get_request_var_post('field') == '0')? '' : get_request_var_post('field');
		
		$save['id'] = form_input_validate($item_id, 'item_id', '^[0-9]+$', false, 3);
		$save['rule_id'] = form_input_validate($rule_id, 'id', '^[0-9]+$', false, 3);
		$save['sequence'] = form_input_validate(get_request_var_post('sequence', 0), 'sequence', '^[0-9]+$', false, 3);
		$save['operation'] = form_input_validate(get_request_var_post('operation', 0), 'operation', '^[-0-9]+$', true, 3);
		$save['field'] = form_input_validate($field, 'field', '', true, 3);
		$save['operator'] = form_input_validate(get_request_var_post('operator', 0), 'operator', '^[0-9]+$', true, 3);
		$save['pattern'] = form_input_validate(get_request_var_post('pattern'), 'pattern', '', true, 3);

		if (!is_error_message()) {
			$item_id = sql_save($save, 'plugin_autom8_thold_rule_items');

			if ($item_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header('Location: ' . $script_url . '?action=item_edit&id=' . $rule_id . '&item_id=' . $item_id . '&rule_type=' . AUTOM8_RULE_TYPE_THOLD_ACTION);
		}else{
			header('Location: ' . $script_url . '?action=edit&id=' . $rule_id . '&rule_type=' . AUTOM8_RULE_TYPE_THOLD_ACTION);
		}
	}elseif(get_request_var_post('save_component_autom8_match_item', 0)){
		
		$item_id = (int) get_request_var_post('item_id', 0);
		$rule_id = (int) get_request_var_post('id', 0);
		$field = (get_request_var_post('field') == '0')? '' : get_request_var_post('field');

		$save['id'] = form_input_validate($item_id, 'item_id', '^[0-9]+$', false, 3);
		$save['rule_id'] = form_input_validate($rule_id, 'id', '^[0-9]+$', false, 3);
		$save['rule_type'] = AUTOM8_RULE_TYPE_THOLD_MATCH;
		$save['sequence'] = form_input_validate(get_request_var_post('sequence', 0), 'sequence', '^[0-9]+$', false, 3);
		$save['operation'] = form_input_validate(get_request_var_post('operation', 0), 'operation', '^[-0-9]+$', true, 3);
		$save['field'] = form_input_validate($field, 'field', '', true, 3);
		$save['operator'] = form_input_validate(get_request_var_post('operator', 0), 'operator', '^[0-9]+$', true, 3);
		$save['pattern'] = form_input_validate(get_request_var_post('pattern'), 'pattern', '', true, 3);

		if (!is_error_message()) {
			$item_id = sql_save($save, 'plugin_autom8_match_rule_items');

			if ($item_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header('Location: ' . $script_url . '?action=item_edit&id=' . $rule_id . '&item_id=' . $item_id . '&rule_type=' . AUTOM8_RULE_TYPE_THOLD_MATCH);
		}else{
			header('Location: ' . $script_url . '?action=edit&id=' . $rule_id . '&rule_type=' . AUTOM8_RULE_TYPE_THOLD_MATCH);
		}
	}else{
		raise_message(2);
		header('Location: ' . $script_url);
	}
}

/* ------------------------
 The Actions function
 ------------------------ */

function autom8_thold_rules_form_actions() {
	global $thold_rule_actions, $script_url;
	global $config, $colors;
	include_once($config['base_path'].'/plugins/autom8thold/autom8_utilities.php');
	
	$action = get_request_var_post('drp_action', 0);
	$selected_items = get_request_var_post('selected_items');

	/* if we are to save this form, instead of display it */
	if (preg_match('#^([0-9]+,)*[0-9]+$#', $selected_items)){
		
		switch($action){
			case AUTOM8_ACTION_THOLD_DELETE:
				db_execute(sprintf('DELETE FROM plugin_autom8_thold_rule_items WHERE rule_id IN(%s);', $selected_items));
				db_execute(sprintf('DELETE FROM plugin_autom8_match_rule_items WHERE rule_id IN(%s) AND rule_type = %d;', $selected_items, AUTOM8_RULE_TYPE_THOLD_MATCH));
				db_execute(sprintf('DELETE FROM plugin_autom8_thold_rules WHERE id IN(%s);', $selected_items));
				break;
			case AUTOM8_ACTION_THOLD_DUPLICATE:
				duplicate_autom8_thold_rules(explode(',',$selected_items), get_request_var_post('name_format', '<rule_name> (1)'));
				break;
			case AUTOM8_ACTION_THOLD_ENABLE:
				db_execute(sprintf("UPDATE plugin_autom8_thold_rules SET enabled = 'on' WHERE id IN(%s);", $selected_items));
				break;
			case AUTOM8_ACTION_THOLD_DISABLE:
				db_execute(sprintf("UPDATE plugin_autom8_thold_rules SET enabled = '' WHERE id IN(%s);", $selected_items));
				break;
		}

		header('Location: ' . $script_url);
		exit;
	}
	
	// loop through each of the items selected on the previous page and get more info about them
	$thold_rules = array();
	foreach(array_keys($_POST) as $var) if(preg_match('/^chk_([0-9]+)$/', $var, $matches)){
		$thold_rules[] = (int) $matches[1];
	}
	$thold_rule_ids = implode(',', $thold_rules);
	
	// get list of names
	$thold_rule_list = ''; 
	$thold_rule_names_sql = sprintf('SELECT name FROM plugin_autom8_thold_rules WHERE id IN(%s) ORDER BY name;', $thold_rule_ids);
	$thold_rule_names = db_fetch_assoc($thold_rule_names_sql);
	foreach($thold_rule_names as $rule) $thold_rule_list .= '<li>' . $rule['name'] . '</li>';

	include_once($config['include_path'] . '/top_header.php');
	#print '<pre>'; print_r($_POST); print_r($_GET); print_r($_REQUEST); print '</pre>';

	print '<form name="autom8_thold_rules" action="' . $script_url . '" method="post">';

	html_start_box('<strong>' . $thold_rule_actions[$action] . '</strong>', '100%', $colors['header_panel'], 3, 'center', '');
	
	switch ($action) {
		case AUTOM8_ACTION_THOLD_DELETE:
			print '	<tr>
				<td class="textArea" bgcolor="#' . $colors['form_alternate1']. '">
					<p>Are you sure you want to delete the following Rules?</p>
					<p><ul>' . $thold_rule_list . '</ul></p>
				</td>
			</tr>
			';
			break;
		case AUTOM8_ACTION_THOLD_DUPLICATE:
			print '	<tr>
				<td class="textArea" bgcolor="#' . $colors['form_alternate1']. '">
					<p>When you click save, the following Rules will be duplicated. You can
					optionally change the title format for the new Rules.</p>
					<p><ul>' . $thold_rule_list . '</ul></p>
					<p><strong>Title Format:</strong><br>'; form_text_box('name_format', '<rule_name> (1)', '', 255, 30, 'text'); print '</p>
				</td>
			</tr>
			';
			break;
		case AUTOM8_ACTION_THOLD_ENABLE:
			print '	<tr>
				<td class="textArea" bgcolor="#' . $colors['form_alternate1']. '">
					<p>When you click save, the following Rules will be enabled.</p>
					<p><ul>' . $thold_rule_list . '</ul></p>
					<p><strong>Make sure, that those rules have successfully been tested!</strong></p>
				</td>
			</tr>
			';
			break;
		case AUTOM8_ACTION_THOLD_DISABLE:
			print '	<tr>
				<td class="textArea" bgcolor="#' . $colors['form_alternate1']. '">
					<p>When you click save, the following Rules will be disabled.</p>
					<p><ul>' . $thold_rule_list . '</ul></p>
				</td>
			</tr>
			';
			break;
	}

	if (empty($thold_rules)) {
		print '<tr><td bgcolor="#' . $colors['form_alternate1']. '"><span class="textError">You must select at least one Rule.</span></td></tr>';
		$save_html = '<input type="button" value="Return" onClick="window.history.back()">';
	}else {
		$save_html = '<input type="button" value="Return" onClick="window.history.back()">&nbsp;<input type="submit" value="Apply" title="Apply requested action">';
	}

	print '	<tr>
		<td align="right" bgcolor="#eaeaea">
			<input type="hidden" name="action" value="actions">
			<input type="hidden" name="selected_items" value="' . $thold_rule_ids . '">
			<input type="hidden" name="drp_action" value="' . $action . '">
			' . $save_html . '
		</td>
	</tr>';

	html_end_box();

	include_once($config['include_path'] . '/bottom_footer.php');
}

/* --------------------------
 Rule Item Functions
 -------------------------- */

function autom8_thold_rules_item_movedown() {
	
	$item_id = (int) get_request_var_request('item_id', 0);
	$rule_type = (int) get_request_var_request('rule_type', 0);
	$rule_id = (int) get_request_var_request('id', 0);

	if ( $rule_type == AUTOM8_RULE_TYPE_THOLD_MATCH) {
		move_item_down('plugin_autom8_match_rule_items', $item_id, 'rule_id=' . $rule_id . ' AND rule_type=' . $rule_type);
	} elseif ($rule_type == AUTOM8_RULE_TYPE_THOLD_ACTION) {
		move_item_down('plugin_autom8_thold_rule_items', $item_id, 'rule_id=' . $rule_id);
	}
}

function autom8_thold_rules_item_moveup() {
	
	$item_id = (int) get_request_var_request('item_id', 0);
	$rule_type = (int) get_request_var_request('rule_type', 0);
	$rule_id = (int) get_request_var_request('id', 0);


	if ( $rule_type == AUTOM8_RULE_TYPE_THOLD_MATCH) {
		move_item_up('plugin_autom8_match_rule_items', $item_id, 'rule_id=' . $rule_id . ' AND rule_type=' . $rule_type);
	} elseif ($rule_type == AUTOM8_RULE_TYPE_THOLD_ACTION) {
		move_item_up('plugin_autom8_thold_rule_items', $item_id, 'rule_id=' . $rule_id);
	}
}

function autom8_thold_rules_item_remove() {
	
	$item_id = (int) get_request_var_request('item_id', 0);
	$rule_type = (int) get_request_var_request('rule_type', 0);

	if ( $rule_type == AUTOM8_RULE_TYPE_THOLD_MATCH) {
		db_execute(sprintf('DELETE FROM plugin_autom8_match_rule_items WHERE id = %d LIMIT 1;', $item_id));
	} elseif ($rule_type == AUTOM8_RULE_TYPE_THOLD_ACTION) {
		db_execute(sprintf('DELETE FROM plugin_autom8_thold_rule_items WHERE id= %d LIMIT 1;', $item_id));
	}

}

function autom8_thold_rules_item_edit() {
	global $config, $script_url;
	global $fields_autom8_match_rule_item_edit, $fields_autom8_graph_rule_item_edit;
	
	include_once($config['base_path'].'/plugins/autom8thold/autom8_utilities.php');

	$item_id = (int) get_request_var_request('item_id', 0);
	$rule_type = (int) get_request_var_request('rule_type', 0);
	$rule_id = (int) get_request_var_request('id', 0);
	
	switch ($rule_type) {
		case AUTOM8_RULE_TYPE_THOLD_MATCH:
			$title = 'Host Match Rule';
			$item_table = 'plugin_autom8_match_rule_items';
			$sql_and = sprintf(' AND rule_type = %d ', $rule_type);
			$tables = array ('host', 'host_templates');
			$autom8_rule = db_fetch_row(sprintf('SELECT * FROM plugin_autom8_thold_rules WHERE id = %d LIMIT 1;', $rule_id));
			$_fields_rule_item_edit = $fields_autom8_match_rule_item_edit;
			$query_fields  = get_query_fields('host_template', array('id', 'hash'));
			$query_fields += get_query_fields('host', array('id', 'host_template_id'));
			$_fields_rule_item_edit['field']['array'] = $query_fields;
			break;

		case AUTOM8_RULE_TYPE_THOLD_ACTION:
			$title = 'Create Threshold Data Source Rule';
			$tables = array(AUTOM8_RULE_TABLE_XML);
			$item_table = 'plugin_autom8_thold_rule_items';
			$sql_and = '';
			$autom8_rule = db_fetch_row(sprintf('SELECT * FROM plugin_autom8_thold_rules WHERE id = %d LIMIT 1;', $rule_id));
			$_fields_rule_item_edit = $fields_autom8_graph_rule_item_edit;
			$xml_array = get_data_query_array($autom8_rule['snmp_query_id']);
			reset($xml_array['fields']);
			$fields = array();
			if(sizeof($xml_array)) {
				foreach($xml_array['fields'] as $key => $value) {
					# ... work on all input fields
					if(isset($value['direction']) && (strtolower($value['direction']) == 'input')) {
						$fields[$key] = $key . ' - ' . $value['name'];
					}
				}
				$_fields_rule_item_edit['field']['array'] = $fields;
			}
			break;
	}
	
	if (empty($item_id)){
		$autom8_item = array();
		$autom8_item['sequence'] = get_sequence('', 'sequence', $item_table, 'rule_id=' . $rule_id . $sql_and);
	}else $autom8_item = db_fetch_row('SELECT * FROM ' . $item_table . ' WHERE id = ' . $item_id . $sql_and);
	
	
	display_item_edit_form($autom8_rule, $autom8_item, $title, $script_url, $_fields_rule_item_edit);
	
	form_hidden_box('rule_type', $rule_type, $rule_type);
	form_hidden_box('id', $rule_id, '');
	form_hidden_box('item_id', $item_id, '');
	if($rule_type == AUTOM8_RULE_TYPE_THOLD_MATCH) {
		form_hidden_box('save_component_autom8_match_item', 1, '');
	} else {
		form_hidden_box('save_component_autom8_thold_rule_item', 1, '');
	}
	form_save_button(htmlspecialchars($script_url . '?action=edit&id=' . $rule_id . '&rule_type='. $rule_type ));

}

/* ---------------------
 Rule Functions
 --------------------- */

function autom8_thold_rules_remove() {
	global $script_url;
	
	$rule_id = (int) get_request_var_request('id', 0);

	if(read_config_option('deletion_verification') == 'on' && !isset($_GET['confirm']) ){
		include('./include/top_header.php');
		form_confirm('Are You Sure?', 'Are you sure you want to delete the Rule <strong>"' . db_fetch_cell(sprintf('SELECT name FROM plugin_autom8_thold_rules WHERE id = %d;', $rule_id)) . '"</strong>?', 'plugins/autom8thold/autom8_thold_rules.php', 'plugins/autom8thold/autom8_thold_rules.php?action=remove&id=' . $rule_id );
		include('./include/bottom_footer.php');
		exit;
	}

	if(read_config_option('deletion_verification') != 'on' || isset($_GET['confirm']) ){
		db_execute(sprintf('DELETE FROM plugin_autom8_match_rule_items WHERE rule_id = %d AND rule_type = %d;', $rule_id, AUTOM8_RULE_TYPE_THOLD_MATCH));
		db_execute(sprintf('DELETE FROM plugin_autom8_graph_rule_items WHERE rule_id = %d;', $rule_id));
		db_execute(sprintf('DELETE FROM plugin_autom8_thold_rules WHERE id = %d;', $rule_id));
	}
}

function autom8_thold_rules_edit() {
	global $colors, $config, $script_url;
	global $fields_autom8_thold_rules_create, $fields_autom8_thold_rules_edit;
	include_once($config['base_path'].'/plugins/autom8thold/autom8_utilities.php');

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request('id'));
	/* ==================================================== */
	#print '<pre>'; print_r($_POST); print_r($_GET); print_r($_REQUEST); print '</pre>';

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('show_hosts', 'autom8_thold_rules_show_hosts', false);
	load_current_session_value('show_ds', 'autom8_thold_rules_show_ds', false);
	
	
	$rule_id = (int) get_request_var_request('id', 0);
	$show_hosts = (bool) get_request_var_request('show_hosts');
	$show_ds = (bool) get_request_var_request('show_ds');

	/*
	 * display the rule -------------------------------------------------------------------------------------
	 */
	$rule = array();
	if (!empty($rule_id)) {
		$rule_sql = sprintf('SELECT * FROM plugin_autom8_thold_rules where id=%d;',$rule_id);
		$rule = db_fetch_row($rule_sql);
		# setup header
		$header_label = '[edit: ' . $rule['name'] . ']';
	}else{
		$header_label = '[new]';
	}
	
	# set fields for display
	if(get_request_var('name')) $rule['name'] = sanitize_search_string(get_request_var('name'));
	if(get_request_var_request('thold_template_id', 0)) $rule['thold_template_id'] = (int) get_request_var_request('thold_template_id');
	if(get_request_var_request('snmp_query_id') != '') $rule['snmp_query_id'] = (int) get_request_var_request('snmp_query_id');
	
	/*
	 * show hosts? ------------------------------------------------------------------------------------------
	 */
	if (!empty($rule_id)) {
		echo '
<table width="100%" align="center">
	<tr>
		<td class="textInfo" align="right" valign="top"><span style="color: #c16921;">*
			<a href="'.htmlspecialchars($script_url.'?action=edit&id=' . $rule_id . '&show_hosts=' . (int) !$show_hosts ) . '">
				<b>'. ($show_hosts ? "Don't Show" : 'Show') . '</b> Matching Hosts.
			</a></span>
		</td>
	</tr>';
		
	} else $show_hosts = false;

	/*
	 * show graphs? -----------------------------------------------------------------------------------------
	 */
	if (!empty($rule['snmp_query_id']) && $rule['snmp_query_id'] > 0) {
		echo '
	<tr>
		<td class="textInfo" align="right" valign="top"><span style="color: #c16921;">*
			<a href="'.htmlspecialchars($script_url.'?action=edit&id=' . $rule_id . '&show_ds=' . (int) !$show_ds ).'">
				<b>'.($show_ds ? "Don't Show" : 'Show'). '</b> Matching Data Sources.
			</a></span>
		</td>
	</tr>
</table>
<br>
		';
	} else $show_ds = false;

	print '<form method="post" action="' . $script_url . '" name="form_autom8_rule_edit">';
	html_start_box('<strong>Rule Selection</strong> ' . $header_label, '100%', $colors['header'], 3, 'center', '');
	#print '<pre>'; print_r($_POST); print_r($_GET); print_r($_REQUEST); print '</pre>';

	if (!empty($rule_id)) {
		/* display whole rule */
		$form_array = $fields_autom8_thold_rules_create + $fields_autom8_thold_rules_edit;
	} else {
		/* display first part of rule only and request user to proceed */
		$form_array = $fields_autom8_thold_rules_create;
	}

	draw_edit_form(array(
		'config' => array('no_form_tag' => true),
		'fields' => inject_form_variables($form_array, $rule),
	));

	html_end_box();
	form_hidden_box('id', $rule_id, '');
//	form_hidden_box('item_id', (isset($rule['item_id']) ? $rule['item_id'] : '0'), '');
	form_hidden_box('save_component_autom8_thold_rule', 1, '');

	/*
	 * display the rule items -------------------------------------------------------------------------------
	 */
	if (!empty($rule['id'])) {
		# display graph rules for host match
		display_match_rule_items('Rule Items => Eligible Hosts',
			$rule['id'],
			AUTOM8_RULE_TYPE_THOLD_MATCH,
			$script_url);

		# fetch graph action rules
		display_ds_rule_items('Rule Items => Add Data Sources',
			$rule['id'],
			AUTOM8_RULE_TYPE_THOLD_ACTION,
			$script_url);
	}

	form_save_button($script_url);
	print '<br>';

	if (!empty($rule['id'])) {
		/* display list of matching hosts */
		if ($show_hosts) display_matching_hosts($rule, AUTOM8_RULE_TYPE_THOLD_MATCH, $script_url . '?action=edit&id=' . $rule_id);
		
		/* display list of new graphs */
		if ($show_ds) display_ds_list($rule);
	}

	?>
<script type="text/javascript">
	<!--
	function applyTemplateIdChange(objForm) {
		strURL = '?action=edit&id=' + objForm.id.value;
		strURL = strURL + '&name=' + objForm.name.value;
		strURL = strURL + '&thold_template_id=' + objForm.thold_template_id.value;
		//alert('Url: ' + strURL);
		document.location = strURL;
	}
	function applySNMPQueryIdChange(objForm) {
		strURL = '?action=edit&id=' + objForm.id.value;
		strURL = strURL + '&name=' + objForm.name.value;
		strURL = strURL + '&thold_template_id=' + objForm.thold_template_id.value;
		strURL = strURL + '&snmp_query_id=' + objForm.snmp_query_id.value;
		//alert('Url: ' + strURL);
		document.location = strURL;
	}
	-->
</script>
	<?php
}

function autom8_thold_rules() {
	global $colors, $thold_rule_actions, $script_url, $item_rows;
	#print '<pre>'; print_r($_POST); print_r($_GET); print_r($_REQUEST); print '</pre>';

	$sort_options = array(
		'name' 				=> array('Rule Title', 'ASC'),
		'id' 				=> array('Rule Id', 'ASC'),
		'thold_name'		=> array('Threshold Name', 'ASC'),
		'snmp_query_name' 	=> array('Data Query', 'ASC'),
		'enabled' 			=> array('Enabled', 'ASC'),
	);

	/* if the user pushed the 'clear' button */
	if (get_request_var_request('clear_x')) {
		kill_session_var('sess_autom8_thold_rules_filter');
		kill_session_var('sess_autom8_thold_rules_sort_column');
		kill_session_var('sess_autom8_thold_rules_sort_direction');
		kill_session_var('sess_autom8_thold_rules_status');
		kill_session_var('sess_autom8_thold_rules_rows');
		kill_session_var('sess_autom8_thold_rules_snmp_query_id');

		unset($_REQUEST['filter']);
		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['rule_status']);
		unset($_REQUEST['per_page']);
		unset($_REQUEST['snmp_query_id']);

	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('per_page', 'sess_autom8_thold_rules_rows', read_config_option('num_rows_device'));
	load_current_session_value('filter', 'sess_autom8_thold_rules_filter', '');
	load_current_session_value('sort_column', 'sess_autom8_thold_rules_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_autom8_thold_rules_sort_direction', 'ASC');
	load_current_session_value('rule_status', 'sess_autom8_thold_rules_status', -1);
	load_current_session_value('snmp_query_id', 'sess_autom8_thold_rules_snmp_query_id', 0);
	
	
	// load page and sort settings
	$page = (int) get_request_var_request('page', 1);
	$per_page = (int) get_request_var_request('per_page');
	if(isset($sort_options[get_request_var_request('sort_column')])) $sort_column = get_request_var_request('sort_column');
	if(in_array(get_request_var_request('sort_direction'), array('ASC','DESC'))) $sort_direction = get_request_var_request('sort_direction');
	
	// filter settings
	$filter = sanitize_search_string(get_request_var_request('filter'));
	$preg_pattern = '#' . str_replace(array('%','_'), array('.*','.'), preg_quote($filter)) . '#i';
	$preg_replace = '<span style="background-color: #F8D93D;">$0</span>';
	
	$rule_status = (int) get_request_var_request('rule_status');
	$snmp_query_id = (int) get_request_var_request('snmp_query_id');
	
	
	// extra validation
	if($page < 1) $page = 1;
	
	$available_data_queries = db_fetch_assoc("SELECT DISTINCT " .
		"plugin_autom8_thold_rules.snmp_query_id, " .
		"snmp_query.name " .
		"FROM plugin_autom8_thold_rules " .
		"LEFT JOIN snmp_query ON (plugin_autom8_thold_rules.snmp_query_id=snmp_query.id) " .
		"ORDER BY snmp_query.name");
	
	
	/* form the 'where' clause for our main sql query */
	$sql_where = "rule.name LIKE '%" . $filter . "%'";

	if($rule_status == -2) {
		$sql_where .= " AND rule.enabled = 'on'";
	}elseif($rule_status == -3) {
		$sql_where .= " AND rule.enabled <> 'on'";
	}

	if (!empty($snmp_query_id)) {
		$sql_where .= ' AND rule.snmp_query_id = ' . $snmp_query_id;
	}
	
	$total_rows_sql = sprintf('SELECT COUNT(*) FROM plugin_autom8_thold_rules rule WHERE %s;',$sql_where);
	$total_rows = db_fetch_cell($total_rows_sql);

	$rules_list_sql = sprintf('SELECT 
	rule.id, 
	rule.name, 
	rule.enabled, 
	thold.name AS thold_name, 
	snmp_query.name AS snmp_query_name 
FROM plugin_autom8_thold_rules rule 
LEFT JOIN thold_template thold 
	ON( thold.id = thold_template_id ) 
LEFT JOIN snmp_query 
	ON (snmp_query.id = snmp_query_id ) 
WHERE %s 
ORDER BY %s %s 
LIMIT %d OFFSET %d;',
		$sql_where ,
		$sort_column,
		$sort_direction,
		$per_page, ($page-1)*$per_page);
	
	$rules_list = db_fetch_assoc($rules_list_sql);
	
	
	# filter box

	print ('<form name="form_autom8_thold_rules" method="post" action="'.$script_url.'">');

	html_start_box('<strong>Threshold Rules</strong>', '100%', $colors['header'], '3', 'center', $script_url.'?action=edit');

	$filter_html = '<tr bgcolor="' . $colors['panel'] . '">
					<td>
					<table width="100%" cellpadding="0" cellspacing="0">
						<tr>
							<td nowrap style="white-space: nowrap;" width="50">
								Search:&nbsp;
							</td>
							<td width="1"><input type="text" name="filter" size="40" onChange="applyViewRuleFilterChange(document.form_autom8_thold_rules)" value="' . $filter . '">
							</td>
							<td nowrap style="white-space: nowrap;" width="50">
								&nbsp;Status:&nbsp;
							</td>
							<td width="1">
								<select name="rule_status" onChange="applyViewRuleFilterChange(document.form_autom8_thold_rules)">
									<option value="-1"'.($rule_status == -1 ?' selected':'').'>Any</option>
									<option value="-2"'.($rule_status == -2 ?' selected':'').'>Enabled</option>
									<option value="-3"'.($rule_status == -3 ?' selected':'').'>Disabled</option>
								</select>
							</td>
							<td nowrap style="white-space: nowrap;" width="50">
								&nbsp;Rows per Page:&nbsp;
							</td>
							<td width="1">
								<select name="per_page" onChange="applyViewRuleFilterChange(document.form_autom8_thold_rules)">
									<option value="'. read_config_option('num_rows_device') .'">Default</option>';
	foreach ($item_rows as $key => $value) {
		$filter_html .= PHP_EOL.'<option value="' . $key . '"'.($per_page == $key ? ' selected':'').'>' . $value . '</option>';
	}
	$filter_html .= '
								</select>
							</td>
							<td nowrap style="white-space: nowrap;">&nbsp;<input type="submit"
								name"go" value="Go"><input type="button"
								name="clear_x" value="Clear"></td>
						</tr>
						<tr>
							<td nowrap style="white-space: nowrap;" width="50">
								Data Query:&nbsp;
							</td>
							<td width="1">
								<select name="snmp_query_id" onChange="applyViewRuleFilterChange(document.form_autom8_thold_rules)">
									<option value="0"'.(empty($snmp_query_id) ? ' selected' : '' ).'>Any</option>';
	foreach ($available_data_queries as $data_query) {
		$filter_html .= PHP_EOL.'<option value="' . $data_query['snmp_query_id'] . '"'.($snmp_query_id == $data_query['snmp_query_id'] ? ' selected':'').'>' . $data_query['name'] . '</option>';
	}
	$filter_html .= '
								</select>
							</td>
						</tr>
					</table>
					</td>
					<td><input type="hidden" name="page" value="1"></td>
				</tr>';

	print $filter_html;

	html_end_box();

	print "</form>\n";

	
	print '<form name="chk" method="post" action="'.$script_url.'">';
	html_start_box('', '100%', $colors['header'], '3', 'center', '');
	
	
	/* generate page list */
	$url_page_select = get_page_list($page, MAX_DISPLAY_PAGES, $per_page, $total_rows, $script_url.'?');

	$nav = '<tr bgcolor="#' . $colors["header"] . '">
		<td colspan="11">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td align="left" class="textHeaderDark">
						<strong>&lt;&lt; ';
	// previous page
	if ($page > 1) $nav .= '<a class="linkOverDark" href="'.$script_url.'?page=' . ($page-1) . '">';
	$nav .= 'Previous'; 
	if ($page > 1) $nav .= '</a>';

	$nav .= '</strong>
					</td>
					<td align="center" class="textHeaderDark">
						Showing Rows ' . (($per_page*($page-1))+1) .' to '. ((($total_rows < $per_page) || ($total_rows < ($per_page*$page))) ? $total_rows : ($per_page*$page)) .' of '. $total_rows .' ['. $url_page_select .']
					</td>
					<td align="right" class="textHeaderDark">
						<strong>'; 
	// next page
	if (($page * $per_page) < $total_rows) $nav .= '<a class="linkOverDark" href="'.$script_url.'?page=' . ($page+1) . '">';
	$nav .= 'Next'; 
	if (($page * $per_page) < $total_rows) $nav .= '</a>';

	$nav .= ' &gt;&gt;</strong>
					</td>
				</tr>
			</table>
		</td>
	</tr>';

	print $nav;

	
	// display column names
	html_header_sort_checkbox($sort_options, $sort_column, $sort_direction, false);

	$i = 0;
	if (sizeof($rules_list) > 0) {
		foreach ($rules_list as $thold_rule) {
			
			form_alternate_row_color($colors['alternate'], $colors['light'], $i, 'line' . $thold_rule['id']); $i++;
			
			// rule name
			$thold_rule_name = title_trim($thold_rule['name'], read_config_option('max_title_graph'));
			$thold_rule_title = '<a class="linkEditMain" href="' . htmlspecialchars($script_url.'?action=edit&id=' . $thold_rule['id'] . '&page=1') . '" title="' . $thold_rule['name'] . '">' . 
					(!empty($filter) ? preg_replace($preg_pattern, $preg_replace, $thold_rule_name) : $thold_rule_name) . 
					'</a>';
			form_selectable_cell($thold_rule_title, $thold_rule['id']);
			
			form_selectable_cell($thold_rule['id'], $thold_rule['id']);
			form_selectable_cell((!empty($filter) ? preg_replace($preg_pattern, $preg_replace, $thold_rule['thold_name']) : $thold_rule['thold_name']), $thold_rule['id']);
			
			$snmp_query_name = empty($thold_rule['snmp_query_name']) ? '<em>None</em>' : $thold_rule['snmp_query_name'];			
			form_selectable_cell((!empty($filter) ? preg_replace($preg_pattern, $preg_replace, $snmp_query_name) : $snmp_query_name), $thold_rule['id']);
			form_selectable_cell($thold_rule['enabled'] == 'on' ? 'Enabled' : 'Disabled', $thold_rule['id']);
			form_checkbox_cell($thold_rule['name'], $thold_rule['id']);

			form_end_row();
		}
		print $nav;
	}else{
		print '<tr><td><em>No Threshold Rules</em></td></tr>';
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($thold_rule_actions);

	print "</form>\n";
	?>
	<script type="text/javascript">
	<!--

	function applyViewRuleFilterChange(objForm) {
		strURL = '?rule_status=' + objForm.rule_status.value;
		strURL = strURL + '&rule_rows=' + objForm.rule_rows.value;
		strURL = strURL + '&snmp_query_id=' + objForm.snmp_query_id.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}

	-->
	</script>
	<?php
}

?>

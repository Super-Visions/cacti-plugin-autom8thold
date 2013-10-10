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

include_once($config['base_path'].'/plugins/autom8/autom8_utilities.php');

function display_ds_list($rule) {
	global $config, $colors, $database_idquote;
	global $script_url, $autom8_op_array;
	
	load_current_session_value('dspage', 'sess_autom8_thold_ds_page', 1);
	
	$page = (int) get_request_var_request('dspage');
	$per_page = (int) read_config_option('num_rows_data_source');
	$debug = (int) get_request_var_request('debug_sql', 0);
	
	// extra validation
	if($page < 1) $page = 1;

	// load data query settings
	$snmp_query_sql = sprintf('SELECT id, name, xml_path FROM snmp_query WHERE id = %d;', $rule['snmp_query_id']);
	$snmp_query = db_fetch_row($snmp_query_sql);
	$xml_array = get_data_query_array($rule['snmp_query_id']);
	
	// get all used data query fields
	$dq_fields = get_rule_dq_fields($rule['id'], 'plugin_autom8_thold_rule_items');
	
	// load header items
	$header_items = array('ID', 'Data Source Name**', 'Thold?');
	foreach($dq_fields as $dq_field){
		$header_items[] = $xml_array['fields'][$dq_field['field']]['name'];
	}
	
	// get rule items
	$rule_items_where = build_rule_item_filter(get_rule_items($rule['id'], 'plugin_autom8_thold_rule_items'));
	
	// get match items
	$match_items_where = build_matching_objects_filter($rule['id'], AUTOM8_RULE_TYPE_THOLD_MATCH);
	
	// get thold settings
	$thold_sql = sprintf('SELECT data_template_id, thold_template.id 
FROM thold_template 
JOIN plugin_autom8_thold_rules AS thold_rule 
	ON(thold_rule.thold_template_id = thold_template.id) 
WHERE thold_rule.id = %d 
LIMIT 1;', $rule['id']);
	$thold = db_fetch_row($thold_sql);

	// build SQL query WHERE part
	$sql_where = sprintf('dtd.data_template_id = %d ' . PHP_EOL . '	AND ( %s ) ' . PHP_EOL, $thold['data_template_id'], $match_items_where);
	$sql_where .= empty($rule_items_where)? '	AND (1 ' . $autom8_op_array['op'][AUTOM8_OP_MATCHES_NOT] . ' 1) ' . PHP_EOL : '	AND ( ' . $rule_items_where . ' ) '.PHP_EOL;
	
	// build SQL query FROM part
	$sql_from = sprintf('data_template_data AS dtd 
LEFT JOIN thold_data AS td 
	ON( dtd.local_data_id = td.rra_id AND td.template = %d ) 
JOIN data_local AS dl 
	ON( dl.id = dtd.local_data_id ) 
JOIN ' . $database_idquote . 'host' . $database_idquote .' 
	ON( host.id = dl.host_id ) 
JOIN host_template 
	ON ( host.host_template_id = host_template.id )	
', $thold['id'] );
	
	// build SQL query SELECT part
	$sql_select = '
	dl.id, 
	IFNULL(td.rra_id = dl.id, 0) AS present, 
	dtd.name_cache';
	
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
		
		$sql_select .= sprintf(', 
	hsc_%1$s.field_value AS %1$s', $dq_field['field']);
		
	}
	$sql_select .= ' ' . PHP_EOL;
	
	// count total matching rows
	$total_rows_sql = 'SELECT COUNT(*) FROM ' . $sql_from . 'WHERE ' . $sql_where . ';';
	$total_rows = db_fetch_cell($total_rows_sql, '', false);
	
	// load ds list
	$ds_list_sql = 'SELECT ' . $sql_select . 'FROM ' . $sql_from . 'WHERE ' . $sql_where . 'ORDER BY dtd.name_cache ASC LIMIT ' . $per_page . ' OFFSET ' . ($page-1)*$per_page . ';';
	$ds_list = db_fetch_assoc($ds_list_sql, false);
	
	if($debug > 0) echo '<pre>'.print_r ($ds_list_sql, true).'</pre>';
	
	// display items
	html_start_box('<strong>Data Query</strong> [' . $snmp_query['name'] . ']', '100%', $colors['header'], 3, 'center', '');
	
	/* generate page list */
	$url_page_select = get_page_list($page, MAX_DISPLAY_PAGES, $per_page, $total_rows, $script_url.'?action=edit&id='.$rule['id'], 'dspage');

	$nav = '<tr bgcolor="#' . $colors["header"] . '">
		<td colspan="11">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td align="left" class="textHeaderDark">
						<strong>&lt;&lt; ';
	// previous page
	if ($page > 1) $nav .= '<a class="linkOverDark" href="'.$script_url.'?action=edit&id='.$rule['id'].'&dspage=' . ($page-1) . '">';
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
	if (($page * $per_page) < $total_rows) $nav .= '<a class="linkOverDark" href="'.$script_url.'?action=edit&id='.$rule['id'].'&dspage=' . ($page+1) . '">';
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
	html_header($header_items);

	$i = 0;
	$present = array('No','Yes');
	if (sizeof($ds_list) > 0) {
		foreach ($ds_list as $data_source) {
			
			form_alternate_row_color($colors['alternate'], $colors['light'], $i++, 'line' . $data_source['id']);
			$text_color = $data_source['present'] == 1 ? ' style="color: #999999;"':'';
			
			print '<td' . $text_color . '>' . $data_source['id'] . '</td>';
			if(api_user_realm_auth('data_sources.php'))
				print '<td' . $text_color . '><a href="'.htmlspecialchars($config['url_path'].'data_sources.php?action=ds_edit&id='.$data_source['id']).'">'.$data_source['name_cache'].'</a></td>';
			else print '<td' . $text_color . '>'.$data_source['name_cache'].'</td>';
			print '<td' . $text_color . '>' . $present[$data_source['present']] . '</td>';
			
			foreach($dq_fields as $dq_field){
				print '<td' . $text_color . '>' . $data_source[$dq_field['field']] . '</td>';
			}
			print PHP_EOL;
			
			form_end_row();
		}
	}
	
	print $nav;
	
	html_end_box();
}

function display_ds_rule_items($title, $rule_id, $rule_type, $module) {
	global $colors, $autom8_op_array, $autom8_oper;

	$items = db_fetch_assoc("SELECT * " .
					"FROM plugin_autom8_thold_rule_items " .
					"WHERE rule_id=" . $rule_id .
					" ORDER BY sequence");

	html_start_box("<strong>$title</strong>", "100%", $colors["header"], "3", "center", $module . "?action=item_edit&id=" . $rule_id . "&rule_type=" . $rule_type);

	print "<tr bgcolor='#" . $colors["header_panel"] . "'>";
	DrawMatrixHeaderItem("Item",$colors["header_text"],1);
	DrawMatrixHeaderItem("Sequence",$colors["header_text"],1);
	DrawMatrixHeaderItem("Operation",$colors["header_text"],1);
	DrawMatrixHeaderItem("Field",$colors["header_text"],1);
	DrawMatrixHeaderItem("Operator",$colors["header_text"],1);
	DrawMatrixHeaderItem("Pattern",$colors["header_text"],1);
	DrawMatrixHeaderItem("&nbsp;",$colors["header_text"],2);
	print "</tr>";

	$i = 0;
	if (sizeof($items) > 0) {
		foreach ($items as $item) {
			#print "<pre>"; print_r($item); print "</pre>";
			$operation = ($item["operation"] != 0) ? $autom8_oper{$item["operation"]} : "&nbsp;";

			form_alternate_row_color($colors["alternate"],$colors["light"],$i); $i++;
			$form_data = '<td><a class="linkEditMain" href="' . htmlspecialchars($module . "?action=item_edit&id=" . $rule_id. "&item_id=" . $item["id"] . "&rule_type=" . $rule_type) . '">Item#' . $i . '</a></td>';
			$form_data .= '<td>' . 	$item["sequence"] . '</td>';
			$form_data .= '<td>' . 	$operation . '</td>';
			$form_data .= '<td>' . 	$item["field"] . '</td>';
			$form_data .= '<td>' . 	(($item["operator"] > 0 || $item["operator"] == "") ? $autom8_op_array["display"]{$item["operator"]} : "") . '</td>';
			$form_data .= '<td>' . 	$item["pattern"] . '</td>';
			$form_data .= '<td><a href="' . htmlspecialchars($module . '?action=item_movedown&item_id=' . $item["id"] . '&id=' . $rule_id .	'&rule_type=' . $rule_type) .
							'"><img src="../../images/move_down.gif" border="0" alt="Move Down"></a>' .
							'<a	href="' . htmlspecialchars($module . '?action=item_moveup&item_id=' . $item["id"] .	'&id=' . $rule_id .	'&rule_type=' . $rule_type) .
							'"><img src="../../images/move_up.gif" border="0" alt="Move Up"></a>' . '</td>';
			$form_data .= '<td align="right"><a href="' . htmlspecialchars($module . '?action=item_remove&item_id=' . $item["id"] .	'&id=' . $rule_id .	'&rule_type=' . $rule_type) .
							'"><img src="../../images/delete_icon.gif" border="0" width="10" height="10" alt="Delete"></a>' . '</td></tr>';
			print $form_data;
		}
	} else {
		print "<tr><td><em>No Rule Items</em></td></tr>\n";
	}

	html_end_box(true);

}

function duplicate_autom8_thold_rules($thold_ids, $name_format) {
	global $fields_autom8_thold_rules_create, $fields_autom8_thold_rules_edit;
	
	// find needed fields
	$fields_autom8_thold_rules = $fields_autom8_thold_rules_create + $fields_autom8_thold_rules_edit;
	$save_fields = array();
	foreach($fields_autom8_thold_rules as $field => &$array ){
		if (!preg_match('/^hidden/', $array['method'])) {
			if(preg_match('/\|arg1:(\w+)\|/', $array['value'], $value))
				$save_fields[] = $value[1];
			else $save_fields[] = $field;
		}
	}
	
	// prepare queries
	$rule_sql = 'SELECT ' . implode(', ', $save_fields) . ' FROM plugin_autom8_thold_rules WHERE id = %d LIMIT 1;';
	$match_items_sql = 'SELECT * FROM plugin_autom8_match_rule_items WHERE rule_id = %d AND rule_type = %d;';
	$rule_items_sql = 'SELECT * FROM plugin_autom8_thold_rule_items WHERE rule_id = %d;';
		
	foreach($thold_ids as $id){
		
		// get current rule details
		$rule = db_fetch_row(sprintf($rule_sql, $id));
		$match_items = db_fetch_assoc(sprintf($match_items_sql, $id, AUTOM8_RULE_TYPE_THOLD_MATCH));
		$rule_items = db_fetch_assoc(sprintf($rule_items_sql, $id, AUTOM8_RULE_TYPE_THOLD_ACTION));
		
		// apply some changes
		$rule['name'] = str_replace('<rule_name>', $rule['name'], $name_format);
		$rule['enabled'] = '';
		$rule['id'] = 0;
		
		// save rule
		$rule_id = sql_save($rule, 'plugin_autom8_thold_rules');
				
		// save rule match items
		foreach ($match_items as $match_item) {
			$match_item['id'] = 0;
			$match_item['rule_id'] = $rule_id;
			
			sql_save($match_item, 'plugin_autom8_match_rule_items');
		}
		
		// save rule items
		foreach ($rule_items as $rule_item) {
			$rule_item['id'] = 0;
			$rule_item['rule_id'] = $rule_id;
			
			sql_save($rule_item, 'plugin_autom8_thold_rule_items');
		}
	}
}

function display_item_edit_form($autom8_rule, $autom8_item, $title, $module, $fields){
	global $colors;

	if (!empty($autom8_item['id'])) {
		$header_label = '[edit rule item for ' . $title . ': ' . $autom8_rule['name'] . ']';
	}else{
		$header_label = '[new rule item for ' . $title . ': ' . $autom8_rule['name'] . ']';
	}

	print '<form method="post" action="' . $module . '" name="form_autom8_global_item_edit">';
	html_start_box('<strong>Rule Item</strong> ' . $header_label, '100%', $colors['header'], 3, 'center', '');
	#print '<pre>'; print_r($_POST); print_r($_GET); print_r($_REQUEST); print '</pre>';
	#print '<pre>'; print_r($_fields_rule_item_edit); print '</pre>';

	draw_edit_form(array(
		'config' => array('no_form_tag' => true),
		'fields' => inject_form_variables($fields, $autom8_item, $autom8_rule),
	));

	html_end_box();
	
	//Now we need some javascript to make it dynamic
?>
<script type="text/javascript">

toggle_operation();
toggle_operator();

function toggle_operation() {
	// right bracket ")" does not come with a field
	if (document.getElementById('operation').value == '<?php print AUTOM8_OPER_RIGHT_BRACKET;?>') {
		//alert("Sequence is '" + document.getElementById('sequence').value + "'");
		document.getElementById('field').value = '';
		document.getElementById('field').disabled='disabled';
		document.getElementById('operator').value = 0;
		document.getElementById('operator').disabled='disabled';
		document.getElementById('pattern').value = '';
		document.getElementById('pattern').disabled='disabled';
	} else {
		document.getElementById('field').disabled='';
		document.getElementById('operator').disabled='';
		document.getElementById('pattern').disabled='';
	}
}

function toggle_operator() {
	// if operator is not "binary", disable the "field" for matching strings
	if (document.getElementById('operator').value == '<?php print AUTOM8_OPER_RIGHT_BRACKET;?>') {
		//alert("Sequence is '" + document.getElementById('sequence').value + "'");
	} else {
	}
}
</script>
<?php
}

if(!function_exists('get_rule_dq_fields')){
	
	/**
	 * 
	 * @param integer $rule_id Id of the rule
	 * @param string $table The rule items table
	 * @return array
	 */
	function get_rule_dq_fields($rule_id, $table){

		$dq_fields_sql = sprintf('SELECT DISTINCT field FROM %s WHERE rule_id = %d AND CHAR_LENGTH(field) > 0 ORDER BY field;', $table, $rule_id);
		$dq_fields = db_fetch_assoc($dq_fields_sql);

		return $dq_fields;
	}
}

if(!function_exists('get_rule_items')){
	
	/**
	 * 
	 * @param integer $rule_id Id of the rule
	 * @param string $table The rule items table
	 * @return array
	 */
	function get_rule_items($rule_id, $table){

		$rule_items_sql = sprintf("SELECT 
	operation, 
	IF(field='',field,CONCAT('hsc_',field,'.field_value')) AS field, 
	operator, 
	pattern 
FROM %s 
WHERE rule_id = %d 
ORDER BY sequence;", $table, $rule_id);
		$rule_items = db_fetch_assoc($rule_items_sql);

		return $rule_items;
	}
}
?>

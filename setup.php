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
		'version'	=> '0.0.1',
		'longname'	=> 'Automate threshold creation for data sources',
		'author'	=> 'Thomas Casteleyn',
		'email'		=> 'thomas.casteleyn@super-visions.com',
		'homepage'	=> 'https://github.com/Super-Visions/cacti-plugin-autom8thold'
    );
}

?>

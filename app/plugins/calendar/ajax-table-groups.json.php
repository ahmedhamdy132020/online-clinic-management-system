<?php
	/*
	 Returns an array of user groups defined in the current app
	 */

	define('PREPEND_PATH', '/../../');
	@header('Content-type: application/json');

	$plugin_dir = dirname(__FILE__);
	include($plugin_dir . PREPEND_PATH . 'defaultLang.php');
	include($plugin_dir . PREPEND_PATH . 'language.php');
	include($plugin_dir . PREPEND_PATH . 'lib.php');
	
	/* check access */
	$mi = getMemberInfo();
	if($mi['group'] != 'Admins') {
		@header('HTTP/1.0 403 Forbidden');
		echo json_encode(array());
		exit;
	}

	$groups_tables = get_table_groups();
	$groups = array(array('id' => '', 'text' => $Translation['none'])); $i = 1;
	foreach($groups_tables as $title => $tables) $groups[] = array(
		'id' => $i++, 
		'text' => ($title == 'None' ? 'Default' : $title)
	);

	exit(json_encode($groups));

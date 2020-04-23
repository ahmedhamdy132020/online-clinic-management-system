<?php
	
	/**
	 * Ajax-callable file to delete a summary report
	 *
	 * @param   string  axp    MD5 hash of AXP file to modify
	 * @param   string  table_name   Name of the table containing the report to delete
	 * @param   string  node_index   Numeric index OR hash of report to delete
	 *
	 * @return  string  updated reports of the given table, JSON-encoded string
	 */

	include(dirname(__FILE__).'/summary_reports.php');

	$summary_reports = new summary_reports(
		array(
		  'title' => 'Summary Reports',
		  'name' => 'summary_reports', 
		  'logo' => 'summary_reports-logo-lg.png' 
		)
	);
	
	$summary_reports->reject_non_admin('Access denied');

	$axp_md5 = makeSafe($_REQUEST['axp']);
	$table_name = $_REQUEST['table_name'];
	$node_index = $_REQUEST['node_index'];
	$project_filename = '';

	$xmlFile = $summary_reports->get_xml_file($axp_md5, $project_filename);
	$tables = $xmlFile->table;
	
	$table_index = -1;
	foreach($tables as $table) {
		$table_index++;
		if($table->name != $table_name) continue;

		$table_reports_string = $table->plugins->summary_reports->report_details;
		break;
	}
	 
	$table_reports_array = json_decode($table_reports_string, true);
	
	// node_index could be numeric array index or report hash
	$array_index = $node_index;
	if(!isset($table_reports_array[$array_index])) {
		// node_index is report hash ... find index
		foreach($table_reports_array as $index => $report) {
			if($report['report_hash'] != $node_index) continue;
			
			$array_index = $index;
			break;
		}
	}
	unset($table_reports_array[$array_index]);

	$newnode = array();
	foreach($table_reports_array as $index => $value) {
		$newnode[] = $value;
	}
	$table_reports_string = json_encode($newnode); 

	/* update the node */
	$nodeData = array(
		'projectName' => $project_filename,
		'tableIndex' => $table_index,
		'nodeName' => 'report_details',
		'pluginName' => 'summary_reports',
		'data' => $table_reports_string
	);
	 
	$summary_reports->update_project_plugin_node($nodeData);
	echo $table_reports_string;
	 
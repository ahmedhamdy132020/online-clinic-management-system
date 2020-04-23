<?php
	include(dirname(__FILE__).'/summary_reports.php');

	/*
		Ajax-callable file to update a summary report

		$_REQUEST includes the following:
		axp: md5 hash of project
		report-title
		table_name: source table (the table containing the report) 
		table-index: index of source table
		group-table
		previous-reports: json-encoded list of other reports for this table
		label: field name used as label field
		label-field-index: index of label field
		first-caption: label of group-by column
		second-caption: label of value column
		how-to-summarize: grouping function
		group-array: names of groups allowed to access report, one per line
		look-up-table: in csae label field is a lookup field, this is its parent table
		look-up-value: parentCaption1 fieldname of look-up-table
		date-field
		date-field-index
		report-header-url
		report-footer-url
		report-id: index of current report OR report hash
		
		@return  string  updated reports of the given table, JSON-encoded string
	*/
	
	$summary_reports = new summary_reports(
	array(
		  'title' => 'Summary Reports',
		  'name' => 'summary_reports', 
		  'logo' => 'summary_reports-logo-lg.png' 
	));
	
	$summary_reports->reject_non_admin('Access denied');
	
	$axp_md5 = $_REQUEST['axp'];
	$projectFile = '';
	$xmlFile = $summary_reports->get_xml_file($axp_md5, $projectFile);
	
	$report = new stdClass();
	$report->report_hash = $_REQUEST['report-hash'];
	if(!$report->report_hash) $report->report_hash = $summary_reports->random_hash();
	$report->title = strip_tags($_REQUEST['report-title']);
	$report->table = $_REQUEST['table_name'];

	// TODO: don't rely on request for table_index ... get it from table_name instead
	$report->table_index = intval($_REQUEST['table-index']);
	$table_index = intval($_REQUEST['table-index']);
	
	$group_table = $_REQUEST['group-table'];
	if(in_array($group_table, $summary_reports->get_table_names($xmlFile))) {
		$report->parent_table = $group_table;	
	}

	// retrieve previous reports from request, parsing the json
	// srting to an array of reports
	//
	// TODO: don't rely on request, retrieve the reports from xmlFile instead?
	$previous_reports = '[]';
	if(isset($_REQUEST['previous-reports'])) {
		$previous_reports = $_REQUEST['previous-reports'];	
	}
	$all_reports = @json_decode($previous_reports, true);
	if($all_reports === null || !is_array($all_reports)) $all_reports = array();

	// report-id could be numeric index of report OR report hash ...
	// in case it's report hash, we need to determine the report index
	$report_id = $_REQUEST['report-id'];
	if($report_id && !is_numeric($report_id)) {
		// report_id is hash .. we need to convert it to report index
		$index_found = false;
		foreach($all_reports as $report_index => $rep) {
			if($rep['report_hash'] != $report_id) continue;

			$index_found = true;
			$report_id = $report_index;
		}
		if(!$index_found) $report_id = false;
	}
	
	// label field for report is by default the first field in table,
	// or parent table if report is grouped by field from another table,
	// unless request specifies a valid label field to be used instead
	$table_fields = $summary_reports->get_table_fields($report->table);
	if($report->parent_table) {
		$table_fields = $summary_reports->get_table_fields($report->parent_table);
	}
	$report->label = $table_fields[0]; 
	if(in_array($_REQUEST['label'] , $table_fields)) $report->label = $_REQUEST['label']; 

	$report->caption1 = $_REQUEST['first-caption'];
	$report->caption2 = $_REQUEST['second-caption'];
	$report->group_function = $_REQUEST['how-to-summarize'];
	$report->group_function_field = $_REQUEST['summarized-value'];

	// retrieve groups allowed to access the report
	// by parsing request>group-array, converting contents of
	// each line to an array item
	$report->group_array = array();
	if(isset($_REQUEST['group-array'])) {
		$group_str = $_REQUEST['group-array'];
		$group_str = str_replace(array("\r", "\n"), '%GS%', $group_str);
		$group_array = explode('%GS%', $group_str);
		for($i = 0; $i < count($group_array); $i++) {
			if(strlen($group_array[$i])) $report->group_array[] = trim($group_array[$i]);
		}
	}

	if(isset($_REQUEST['look-up-table'])) {
		$report->look_up_table = $_REQUEST['look-up-table'];
	}
	 
	if(isset($_REQUEST['look-up-value'])) {
		$report->look_up_value = $_REQUEST['look-up-value'];
	}

	if(isset($_REQUEST['label-field-index'])) {
		$report->label_field_index = $_REQUEST['label-field-index'];
	}

	if(isset($_REQUEST['date-field']) && $_REQUEST['date-field'] != ''){
		$report->date_field = $_REQUEST['date-field'];
		if(isset($_REQUEST['date-field-index'])) {	
			$report->date_field_index = $_REQUEST['date-field-index'];
		} 
	}
	
	if(isset($_REQUEST['report-header-url'])) {
		$report->report_header_url = $_REQUEST['report-header-url'];
	}
	
	if(isset($_REQUEST['report-footer-url'])) {
		$report->report_footer_url = $_REQUEST['report-footer-url'];
	}
	
	$report->data_table_section = isset($_REQUEST['data_table_section']) ? 1 : 0;
	$report->barchart_section = isset($_REQUEST['barchart_section']) ? 1 : 0;
	$report->piechart_section = isset($_REQUEST['piechart_section']) ? 1 : 0;
	
	$report->join_statment = '';
	$all_lookup_fields = '';
	$date_separators = array(
		'1' => '-',
		'2' => ' ',
		'3' => '.',
		'4' => '/',
		'5' => ','
	);	
	 
	$date_separator_index = (string)$xmlFile->dateSeparator;
	$report->date_separator = $date_separators[ $date_separator_index ];
	
	$path = array();
	if(isset($report->parent_table)) {
		$path = $summary_reports->find_path($report->table, $report->parent_table);
	}
 
	for($i=0; $i < count($path) - 1; $i++) { 
		$report->join_statment .= ' JOIN ' . $summary_reports->get_join_statment($path[$i], $path[$i + 1]);
	}
 
	$report->join_statment = $path[0] . $report->join_statment;

	// updating an existing report, or creating a new one?
	if($report_id > 0 || $report_id === '0' || $report_id === 0) {
		// update given report_id with node changes
		$all_reports[$report_id] = $report;
	} else {
		// save node as a new report
		$all_reports[] = $report;
	}

	$json_nodes = json_encode($all_reports);
	 
	/* update node */
	$summary_reports->update_project_plugin_node(array(
		'projectName' => $projectFile,
		'tableIndex' => $table_index,
		'nodeName' => 'report_details',
		'pluginName' => 'summary_reports',
		'data' => $json_nodes
	));

	echo $json_nodes;

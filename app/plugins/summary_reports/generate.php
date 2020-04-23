<?php include(dirname(__FILE__) . '/header.php'); 

$summary_reports = new summary_reports(array(
        'title' => 'Summary Reports',
        'name' => 'summary_reports',
        'logo' => 'summary_reports-logo-lg.png',
        'output_path' => $_REQUEST['path']
    ));
	
	$axp_md5 = $_REQUEST['axp'];
	$projectFile = '';
	$xmlFile = $summary_reports->get_xml_file($axp_md5, $projectFile);
?>

<?php echo $summary_reports->header_nav(); ?>

<?php echo $summary_reports->breadcrumb(array(
	'index.php' => 'Projects',
	'project.php?axp=' . urlencode($axp_md5) => substr($projectFile, 0, -4),
	'output-folder.php?axp=' . urlencode($axp_md5) => 'Output folder',
	'' => 'Generating files'
)); ?>

<?php
	function check_group_array_type($group_array){
		if(is_object($group_array)){
			return $array = json_decode(json_encode($group_array), True);
		}
		return $group_array; 	
	}
	
	$path = $_REQUEST['path'];
	
	if(!$summary_reports->is_appgini_app($path)){
		echo $summary_reports->error_message('Invalid application path!');
		include(dirname(__FILE__) . '/footer.php');
		exit;
	}
	
	/* retrieve date format and separator */
	$date_format = intval($xmlFile->dateFormat[0]); 
	
	$date_seps = array('-', '-', ' ', '.', '/', ',');
	$date_separator = $date_seps[intval($xmlFile->dateSeparator[0])];
	
	$date_formats = array(
		"1" => "yyyy{$date_separator}mm{$date_separator}dd",
		"2" => "dd{$date_separator}mm{$date_separator}yyyy",
		"3" => "mm{$date_separator}dd{$date_separator}yyyy"
	);
	
	/* initial date ranges when report first loads */
	$initial_date_format = array(
		"1" => array(
			"from" => "Y{$date_separator}m{$date_separator}01", 
			"to" => "Y{$date_separator}m{$date_separator}d"
		),
		"2" => array(
			"from" => "01{$date_separator}m{$date_separator}Y", 
			"to" => "d{$date_separator}m{$date_separator}Y"
		),
		"3" => array(
			"from" => "m{$date_separator}01{$date_separator}Y", 
			"to" => "m{$date_separator}d{$date_separator}Y"
		)
	);
	
	$initial_from = $initial_date_format[$date_format]["from"];
	$initial_to = $initial_date_format[$date_format]["to"];
	
	/* Copying SummaryReports Class */
	$source_class = dirname(__FILE__) . '/app-resources/SummaryReport.php';
	$dest_class = $path.'/hooks/SummaryReport.php';
	$summary_reports->copy_file($source_class, $dest_class, true);
	
	/* Copying language-summary-reports.php File */
	$source_langfile = dirname(__FILE__) . '/app-resources/language-summary-reports.php';
	$dest_langfile = $path.'/hooks/language-summary-reports.php';
	$summary_reports->copy_file($source_langfile, $dest_langfile, true);
	
	/* Copying Chartist */
	$src = dirname(__FILE__) . '/app-resources/chartist';
	$dst = $path . '/resources/chartist'; 
	$summary_reports->recurse_copy($src, $dst, true); 
	
	/* Copying SummaryReports Logo */
	$logo_source_file = dirname(__FILE__)."/summary_reports-logo-md.png";
	$logo_destination_file = $path.'/hooks/summary_reports-logo-md.png';
	$summary_reports->copy_file($logo_source_file, $logo_destination_file, true);
	
	/* Generating summary-reports-list.php File */
	$summary_reports_file = '<' . '?php
	define("PREPEND_PATH", "../");
	$hooks_dir = dirname(__FILE__);
	include("{$hooks_dir}/../defaultLang.php");
	include("{$hooks_dir}/../language.php");
	include("{$hooks_dir}/language-summary-reports.php");
	include("{$hooks_dir}/../lib.php");

	$x = new StdClass;
	$x->TableTitle = "Summary Reports";
	include_once("{$hooks_dir}/../header.php");
	$user_data = getMemberInfo();
	$user_group = strtolower($user_data["group"]);' .
	"\n?>\n\n";
	
	$summary_reports_file .= '<div class="page-header"><h1>Summary Reports</h1></div>';
	
	/* Iterating over the tables to generate reports files */
	$summary_reports_links_groups = array();
	for($i = 0; $i < count($xmlFile->table); $i++) {
		/* acess report_details node and convert it into obj*/
		$json_node = $xmlFile->table[$i]->plugins->summary_reports->report_details;
		if(empty($json_node)) continue;
		$table_caption = $xmlFile->table[$i]->caption;
		$reports = json_decode($json_node);
		
	 	$table_fields = $xmlFile->table[$i]->field;
		$filterable_fields = array();
		foreach($table_fields as $field) {
			if($field->notFiltered == "True") continue ;
			if($field->tableImage == "True") continue ;
			if($field->detailImage == "True") continue ;
			$filterable_fields[] = (string) $field->name;
		}

		ob_start();
		?>

<div class="row">
	<div class="col-sm-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<div class="text-center text-bold" style="font-size: 1.5em; line-height: 2em;"><?php echo $table_caption; ?></div>
			</div>
			<div class="panel-body">
				<div class="panel-body-description">
					<div class="row">
						<?php
						$summary_reports_file .= ob_get_clean();
						 
						for($j = 0; $j < count($reports); $j++) {
							
							$table_name = $xmlFile->table[$i]->name;
							$report_title = $reports[$j]->title;
							if(empty($reports[$j]->report_hash)) $reports[$j]->report_hash = $summary_reports->random_hash();
							
							$generated_report = "summary-reports-{$table_name}-{$j}.php";
							$summary_reports->progress_log->add("Generating {$generated_report} ", 'text-info');
							/* Data will be written in report files */
							$generated_report_content = 
	'<' . '?php
	/* Include Requeried files */
	define("PREPEND_PATH", "../");
	$hooks_dir = dirname(__FILE__);
	include("{$hooks_dir}/../defaultLang.php");
	include("{$hooks_dir}/../language.php");
	include("{$hooks_dir}/../lib.php");
	include("{$hooks_dir}/language-summary-reports.php");
	include("{$hooks_dir}/SummaryReport.php");
	@header("Content-Type: text/html; charset=" . datalist_db_encoding);
 	
	$x = new StdClass;
	$x->TableTitle = "' . html_attr($report_title) . '";
	include_once("{$hooks_dir}/../header.php");
	
	$filterable_fields = ' .
	str_replace(
		array("\n  ", "\n"), 
		array("\n\t", "\n\t"), 
		var_export($filterable_fields, true)
	) . ";\n\n"; 
							
							$groups_classes = "all-groups";					
							if(count($reports[$j]->group_array) == 0){
								array_push($summary_reports_links_groups, '*');
							}
							if(count($reports[$j]-> group_array) > 0) {
								$group_array = check_group_array_type($reports[$j]-> group_array);
								$summary_reports_links_groups = array_merge(
									$summary_reports_links_groups, $group_array);
								$generated_report_content .= "\t" . '$groups_array = ' .
									str_replace(
										array("\n  ", "\n"), 
										array("\n\t", "\n\t"), 
										var_export($group_array, true)
									) . ";";	
								
								$groups_classes = '';
								foreach($group_array as $group){
									$groups_classes .= " " . strtolower($group);
								}
						 
							}
							
							ob_start();
							?><div class ="col-xs-12 col-md-4 col-lg-4">
							<a href ="<?php echo $generated_report; ?>" class="btn btn-success <?php echo $groups_classes; ?> btn-block btn-lg vspacer-lg summary-reports" style="padding-top: 1em; padding-bottom: 1em;">
								<i class ="glyphicon glyphicon-th"></i> <?php echo $report_title ?>
							</a>
						</div>
							<?php
							$summary_reports_file .= ob_get_clean();
							 
							//if(isset($reports[$j]->date_field)){
							//}

							$generated_report_content .= "\n\t\$config_array = array(
		'reportHash' => '{$reports[$j]->report_hash}',
		'request' => \$_REQUEST,
		'groups_array' => \$groups_array,
		'title' => '" . addslashes($reports[$j]->title) . "',
		'table' => '{$reports[$j]->table}',
		'label' => '{$reports[$j]->label}',
		'group_function' => '{$reports[$j]->group_function}',
		'label_title' => '{$reports[$j]->caption1}',
		'value_title' => '{$reports[$j]->caption2}',
		'thousands_separator' => ',',
		'decimal_point' => '.',

		// show data table section?
		'data_table_section' => {$reports[$j]->data_table_section},

		// max number of data points to show on charts
		'chart_data_points' => 20,
		
		// barchart options
		'barchart_section' => {$reports[$j]->barchart_section},
		'barchart_options' => array(
			// see https://gionkunz.github.io/chartist-js/api-documentation.html#chartistbar-declaration-defaultoptions
			'axisX' => array(
				'offset' => 30,
				'position' => 'end',
				'labelOffset' => array('x' => 0, 'y' => 0),
				'showLabel' => true,
				'showGrid' => true,
				'scaleMinSpace' => 30,
				'onlyInteger' => false
			),
			'axisY' => array(
				'offset' => 40,
				'position' => 'start',
				'labelOffset' => array('x' => 0, 'y' => 0),
				'showLabel' => true,
				'showGrid' => true,
				'scaleMinSpace' => 20,
				'onlyInteger' => false
			),
			// 'width' => false,
			// 'height' => false,
			// 'high' => false,
			// 'low' => false,
			'referenceValue' => 0,
			'chartPadding' => array('top' => 15, 'right' => 15, 'bottom' => 5, 'left' => 10),
			'seriesBarDistance' => 15,
			'stackBars' => false,
			'stackMode' => 'accumulate',
			'horizontalBars' => false,
			'distributeSeries' => false,
			'reverseData' => false,
			'showGridBackground' => false,
			'classNames' => array(
				'chart' => 'ct-chart-bar',
				'horizontalBars' => 'ct-horizontal-bars',
				'label' => 'ct-label',
				'labelGroup' => 'ct-labels',
				'series' => 'ct-series',
				'bar' => 'ct-bar',
				'grid' => 'ct-grid',
				'gridGroup' => 'ct-grids',
				'gridBackground' => 'ct-grid-background',
				'vertical' => 'ct-vertical',
				'horizontal' => 'ct-horizontal',
				'start' => 'ct-start',
				'end' => 'ct-end'
			)
		),

		// piechart options
		'piechart_section' => {$reports[$j]->piechart_section},
		'piechart_options' => array(
			// see https://gionkunz.github.io/chartist-js/api-documentation.html#chartistpie-declaration-defaultoptions
			// 'width' => false,
			// 'height' => false,
			'chartPadding' => 5,
			'classNames' => array(
				'chartPie' => 'ct-chart-pie',
				'chartDonut' => 'ct-chart-donut',
				'series' => 'ct-series',
				'slicePie' => 'ct-slice-pie',
				'sliceDonut' => 'ct-slice-donut',
				'sliceDonutSolid' => 'ct-slice-donut-solid',
				'label' => 'ct-label'
			),
			'startAngle' => 0,
			// 'total' => false,
			'donut' => false,
			'donutSolid' => false,
			'donutWidth' => 60,
			'showLabel' => true,
			'labelOffset' => '50',
			'labelPosition' => 'center',
			'labelDirection' => 'neutral',
			'reverseData' => false,
			'ignoreEmptyValues' => true
		),
		'piechart_classes' => 'ct-square',

		'date_format' => '{$initial_to}',
		'date_separator' => '{$date_separator}',
		'jsmoment_date_format' => '" . strtoupper($date_formats[$date_format]) . "'";
					
							if(isset($reports[$j]->group_function_field)){
								$generated_report_content .= ",\n\t\t'group_function_field' => '{$reports[$j]->group_function_field}'";
							}
							
							if($summary_reports->field(
								$reports[$j]->table,
								$reports[$j]->date_field
							)) {
								$generated_report_content .= ",\n\t\t'date_field' => '{$reports[$j]->date_field}'";
							}

							if(!isset($reports[$j]->parent_table)) {
								if($summary_reports->field(
									$reports[$j]->table,
									$reports[$j]->date_field
								)) {
									$generated_report_content .= ",\n\t\t'date_field_index' => '{$reports[$j]->date_field_index}'";
								}
								$generated_report_content .= ",\n\t\t'label_field_index' => {$reports[$j]->label_field_index}";
								$generated_report_content .= ",\n\t\t'filterable_fields' => \$filterable_fields";

								if($reports[$j]->look_up_table) {								
									$generated_report_content .= ",\n\t\t'look_up_table' => '{$reports[$j]->look_up_table}'";
								}
							}

							if(isset($reports[$j]->parent_table)) {
								$generated_report_content .= ",\n\t\t'parent_table' => '{$reports[$j]->parent_table}'";

								// is label field a lookup?
								$parent_field_index = $summary_reports->lookup_field_index($reports[$j]->parent_table, $reports[$j]->label) + 1; // because label_field_index is 1-based
								if($parent_field_index) {
									$generated_report_content .= ",\n\t\t'parent_label_is_lookup' => true";
									$generated_report_content .= ",\n\t\t'label_field_index' => {$parent_field_index}";
								}
							}
							

							if($reports[$j]->report_header_url) {	
								$generated_report_content .= ",\n\t\t'report_header_url' => '" . addslashes($reports[$j]->report_header_url) . "'";
							}
							if($reports[$j]->report_footer_url) {
								$generated_report_content .= ",\n\t\t'report_footer_url' => '" . addslashes($reports[$j]->report_footer_url) . "'";
							}

							$generated_report_content .= "\n\t)".";\n";
							$generated_report_content .= "\t".'$report = new SummaryReport($config_array);'."\n";
							$generated_report_content .= "\t".'echo $report->render();'."\n\n";
							
							$generated_report_content .= "\t".'include_once("{$hooks_dir}/../footer.php");'."\n";	
							$generated_report_link = $path.'/hooks/'.$generated_report;
							
							file_put_contents($generated_report_link, $generated_report_content);
							
							if(file_exists($path . '/hooks/' . $generated_report)) {
								$summary_reports->progress_log->ok();
							} else {
								$summary_reports->progress_log->failed();
							}
						}
		ob_start(); ?>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
		<?php
		$summary_reports_file .= ob_get_clean();	 
	}
	
	/* Adding summary-reports-list.php links in homepage and navigation menu */
	$table_groups = explode(",",$xmlFile->groups);
	$table_group = $table_groups[0];
	$summary_reports_links_groups=array_unique($summary_reports_links_groups);
	
	if (in_array('*', $summary_reports_links_groups)) {
		$summary_reports_links_groups=array('*');
	}
 
	$summary_reports->add_link('links-home', array(
		'url' => 'hooks/summary-reports-list.php', 
		'title' => 'Summary Reports', 
		'groups'=>$summary_reports_links_groups,
		'table_group' => $table_group,
		'description' => '',
		'grid_column_classes' => 'col-xs-12 col-sm-6 col-md-6 col-lg-6',
		'panel_classes' => '',
		'link_classes' => '',
		'icon' => 'hooks/summary_reports-logo-md.png',
	));
	$summary_reports->add_link('links-navmenu', array(
		'url' => 'hooks/summary-reports-list.php',
		'title' => 'Summary Reports',
		'groups'=>$summary_reports_links_groups,
		'icon' => 'hooks/summary_reports-logo-md.png',
	));
	
	
	
	ob_start();?> 
	<script>

		var user_group= [?php echo json_encode($user_group) ?]  ;

		$j(function(){ 
			$j( ".panel a" ).not('.'+user_group).not('.all-groups').parent().remove();
			$j('.panel').each(function(){
				if($j(this).find('a').length == 0){
					$j(this).remove();
				}
 
			}) 
		})

	</script>
	<?php
	$summary_reports_file .= ob_get_clean();	
	$summary_reports_file =  str_replace('[?php','<?php',$summary_reports_file);
	$summary_reports_file =  str_replace('?]','?>',$summary_reports_file);
		 
		
	
	$summary_reports_file .= "\n\n".'<?php include_once("$hooks_dir/../footer.php"); ?>';
	$summary_reports->progress_log->add('Generating summary-reports-list.php  ... ', 'text-info');
	if(file_put_contents($path.'/hooks/summary-reports-list.php', $summary_reports_file)){
		$summary_reports->progress_log->ok();
	}else{
		$summary_reports->progress_log->failed();
	}	
	echo $summary_reports->progress_log->show();
?>
<?php include(dirname(__FILE__) . '/footer.php'); ?>
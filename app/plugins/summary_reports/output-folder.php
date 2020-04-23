<?php include(dirname(__FILE__) . '/header.php'); ?>

<?php
	$axp_md5 = $_REQUEST['axp'];
	$projectFile = '';
	$xmlFile = $summary_reports->get_xml_file($axp_md5 , $projectFile);
?>

<?php echo $summary_reports->header_nav(); ?>

<?php echo $summary_reports->breadcrumb(array(
	'index.php' => 'Projects',
	'project.php?axp=' . urlencode($axp_md5) => substr($projectFile, 0, -4),
	'' => 'Output folder'
)); ?>

<?php
	echo $summary_reports->show_select_output_folder(array(
		'next_page' => 'generate.php?axp=' . urlencode($axp_md5),
		'extra_options' => array(
			/* 'option1' => 'Option 1 label', */
			/* 'option2' => 'Option 2 label' */
		)
	));
?>

<?php include(dirname(__FILE__) . '/footer.php'); ?>

<?php
	include(dirname(__FILE__) . '/header.php');

	$axp_md5 = $_REQUEST['axp'];
	$projectFile = '';
	$xmlFile = $spm->get_xml_file($axp_md5 , $projectFile);
?>

<?php echo $spm->header_nav(); ?>

<?php echo $spm->breadcrumb(array(
	'index.php' => 'Projects',
	'project.php?axp=' . urlencode($axp_md5) => substr($projectFile, 0, -4),
	'' => 'Output folder'
)); ?>

<?php
	echo $spm->show_select_output_folder(array(
		'next_page' => 'generate.php?axp=' . urlencode($_REQUEST['axp']),
		'extra_options' => array(
			'dont_write_to_hooks' => 'Only show me the hooks code without actually writing it to existing hook files.'
		)
	));
?>

<?php include(dirname(__FILE__) . "/footer.php"); ?>
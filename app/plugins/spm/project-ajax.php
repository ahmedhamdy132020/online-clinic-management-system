<?php
@include(dirname(__FILE__) . "/../plugins-resources/loader.php");

$spm = new AppGiniPlugin(array(
	'title' => 'Search Page Maker for AppGini',
	'name' => 'spm',
	'logo' => 'spm-logo-lg.png'
));

$spm->reject_non_admin();

//filter POST data
$spm->filter_inputs($_POST);

 /**
  *  Save project modifications in project file
  */

if( isset($_POST['data']) && isset($_POST['tableNumber']) && isset($_POST['projFile']) ) {

	$nodeData = array(
			'projectName'=> $_POST['projFile'],
            'tableIndex' => intval( $_POST['tableNumber']) ,
            'pluginName' =>  'spm',
            'nodeName'   => 'spm_fields',
            'data'		 => $_POST['data']
	);

	//validate data 
	if (! preg_match('/^[0-9:]*$/i', $_POST['data'] )){
			die("");
	}

	//update node with new data after validating it
	if ($spm->update_project_plugin_node($nodeData)){
		echo  "ok";
	}


/**
  *  validate the given project folder
  **/

} else if( isset($_POST['actionName']) && $_POST['actionName'] == 'validatePath') {

	$path = $_POST['path'];

	try{
		if (! is_dir($path)){
			throw new RuntimeException('Invalid path.');
		}
		
		if ( ! ( file_exists("$path/lib.php") && file_exists("$path/db.php") && file_exists("$path/index.php") ) ){
			throw new RuntimeException('The given path is not a valid AppGini application path.');
		}
		if (! is_writable($path."/hooks")){
			throw new RuntimeException('The hooks folder is not writable. Please set its permissions to allow writing -- try "chmod 755" or "chmod 777".');
		}
		if (! is_writable($path."/resources") ){
			throw new RuntimeException('The resources folder is not writable. Please set its permissions to allow writing -- try "chmod 755" or "chmod 777".');
		}

	} catch (RuntimeException $e){
		echo  $e->getMessage();
		exit;
	}
	

	echo "ok";
}

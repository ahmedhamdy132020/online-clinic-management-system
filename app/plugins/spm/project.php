<?php
	include(dirname(__FILE__) . "/header.php");

	// validate project name
	if (!isset($_REQUEST['axp']) || !preg_match('/^[a-f0-9]{32}$/i', $_REQUEST['axp'])){
		echo "<br>" . $spm->error_message('Project file not found.', 'index.php');
		exit;
	}
	
	$axp_md5 = $_REQUEST['axp'];
	$projectFile = '';
	$xmlFile = $spm->get_xml_file($axp_md5 , $projectFile);
//-----------------------------------------------------------------------------------------
?>

<?php echo $spm->header_nav(); ?>

<?php echo $spm->breadcrumb(array(
	'index.php' => 'Projects',
	'' => substr($projectFile, 0, -4)
)); ?>

<a id="btn-output-folder" href="output-folder.php?axp=<?php echo $axp_md5; ?>" class="pull-right btn btn-primary btn-sm" style="padding: 0.25em 3em;"><span class="language" data-key="CREATE_SEARCH_PAGE"></span> <span class="glyphicon glyphicon-chevron-right"></span></a>
<div class="clearfix"></div>

<div class="row">
	<?php
		echo $spm->show_tables(array(
			'axp' => $xmlFile,
			'click_handler' => 'showFields',
			'classes' => 'col-md-3 col-xs-12'
		)); 
	?>

	<div class="col-md-6 col-xs-12">
		<h4><b>Fields in search page (drag to re-order)</b></h4>
		<div id="choosenFields" class="list-group" >
		</div>
	</div>

	<div  class="col-md-3 col-xs-12">
		<h4><b>Available fields/options</b></h4>
		<div id="fields" class="list-group">
		</div>
	</div>
</div>

<p id="bottom-links"><a href="./index.php"><i class="glyphicon glyphicon-chevron-left"></i>Or open another project</a></p>

<?php
	$xmlFile = json_encode($xmlFile);
?>



<script>	
	$j(function() {

		// sort divs by id in $fields section
		$j.fn.sortDivs = function sortDivs() {
		    $j("> div", this[0]).sort(custom_sort).appendTo(this[0]);
		    function custom_sort(a, b){ return (parseInt($j(b).data("sort")) < parseInt($j(a).data("sort"))) ? 1 : -1; }
		}

		//add resize event
		$j(window).resize(function() {
  			$j("#tables-list").height( $j(window).height() - $j("#tables-list").offset().top -  $j("#bottom-links").height() - 30);
			$j("#choosenFields, #fields").height( $j("#tables-list").height() - $j("h4").first().height() - 20 );		
		});
		
		$j(window).resize();

	    $j( "#choosenFields" ).sortable({
	        connectWith: "#fields",
	        cursor: "move",
			stop: function (event, ui) {
	        	updateList()
			},
			receive: function (event, ui) {
	        	updateList()
			},
			remove: function (event, ui) {
	        	updateList()
			}
	    }).disableSelection();


	    $j( "#fields" ).sortable({
			cursor: "move",
			//stop ordering the fields
			beforeStop: function (event, ui) {
				if($j(ui.helper).parent().attr('id') === 'fields' && $j(ui.placeholder).parent().attr('id') === 'fields'){
				   return false; 
				}
			},
			tolerance: 'pointer',
			receive: function (event, ui) {
				$j("#fields").sortDivs();
			},
			connectWith: "#choosenFields",
	    }).disableSelection();

	    /* place output folder button inside breadcrumb */
	    $j('#btn-output-folder').appendTo('.breadcrumb:first');
	});

	function updateList(){
			var ids='';
        	var tableNumber = $j("#choosenFields").data('table');

        	//update array 
        	$j("#choosenFields").find("div").each(function() {
   				 ids+=( $j(this).attr("data-sort") )+":";
			});

        	//one/many tables in project
			currentTable = ( (typeof tableNumber != 'undefined')?xmlFile.table[tableNumber]:xmlFile.table);
			if (! currentTable['plugins'] ){
				currentTable['plugins']=[];
			}
			if (! currentTable['plugins']['spm'] ){
				currentTable['plugins']['spm'] =[];
			}
			currentTable['plugins']['spm']['spm_fields'] =  ids;

			//update project file
			$j.ajax({
			  type: "POST",
			  url: "project-ajax.php",
			  data: {
			  	projFile: "<?php echo $projectFile; ?>",
			  	tableNumber: (tableNumber?tableNumber:0),
			  	data: (ids.length==0?":":ids)
			  },
			  success: function(response){
			  },
			});
	}

	var xmlFile = <?php echo $xmlFile; ?>;
	
	//sava fields' data types
	var tableData = [];

	function showFields(tableNum){
		$j("#fields, #choosenFields").html('');
		var field, type={} ,currentType,table;
		

		//check number of tables
		if ($j.isArray(xmlFile.table)){      				//>1 table
			table = xmlFile.table[tableNum];
			$j("#fields, #choosenFields").data('table',tableNum );
		}else{     											//1 table only
			table = xmlFile.table;
		}
		var chosenElements;
		if ( table.plugins && table.plugins.spm && table.plugins.spm.spm_fields ){
			chosenElements = new Array(table.plugins.spm.spm_fields.split(":").length);
		}

		//get data types ( only for the first time the table is clicked )
		if (!tableData[tableNum]){
			tableData[tableNum] = {};
			for (var i = 0 ; i< table.field.length ; i++){
				field = table.field[i];

				//checks if the field is filtered, not auto-filled, not youtube/googlemap(embed is empty), not img/any file (allowImageUpload)
				if ( (field.notFiltered == "False") && (field.autoFill=="False")  && ($j.isEmptyObject(field.embed))  && (field.allowImageUpload=="False")){
					currentType = parseInt (field.dataType);
					node = getType( currentType , field);
					tableData[tableNum][String(i)]=node;
				}
			}
		}

		//display data
		
		//convert ids string into array
		var spmDataArray = [];

		if( table.plugins && table.plugins.spm && table.plugins.spm.spm_fields ){
			var spmDataArray = table.plugins.spm.spm_fields.split(":");	
		}

		$j.each(tableData[tableNum], function( key, value ) {
			position = $j.inArray( key , spmDataArray );
			if ( position!== -1){
			  	chosenElements[position] = '<div class="list-group-item ui-state-default  item" data-sort='+key+'><span class="'+value.icon+'" ></span>     ' +value.caption +" ( "+value.name+" ) </div>";
			}else{
				$j("#fields").append('<div class="list-group-item ui-state-default  item" data-sort='+key+'><span class="'+value.icon+'" ></span>     ' +value.caption +" ( "+value.name+" ) </div>");	
			}
		});

		//fixed sections part
		i=9001;   //ORDER BY
		position = $j.inArray( String(i) , spmDataArray );
		if ( position !== -1){
			chosenElements[position] = '<div class="list-group-item ui-state-default  item" data-sort='+i+'><span class="glyphicon glyphicon-collapse-down" ></span>     Order by  ( section ) </div>';
		}else{
			$j("#fields").append('<div class="list-group-item ui-state-default  item" data-sort='+i+'><span class="glyphicon glyphicon-collapse-down" ></span>     Order by  ( section ) </div>');	
		}	
		i++;  //USER/GROUP/ALL
		position = $j.inArray( String(i) , spmDataArray );
		if ( position !== -1){
			chosenElements[position] = '<div class="list-group-item ui-state-default  item" data-sort='+i+'><span class="glyphicon glyphicon-user" ></span>     User/group/all  ( section ) </div>';
		}else{
			 $j("#fields").append('<div class="list-group-item ui-state-default  item" data-sort='+i+'><span class="glyphicon glyphicon-user" ></span>     User/group/all  ( section ) </div>');	
		}

		if ( chosenElements){
			$j("#choosenFields").html(chosenElements.join(" "));
		}
	}

	function getType( currentType , field ){
		var nodeData={};
		

		//lookup
		if (!  $j.isEmptyObject(field.parentTable) ){
			nodeData.name="drop down";
			nodeData.icon = "glyphicon glyphicon-align-justify";

		//options list
		}else if (!  $j.isEmptyObject(field.CSValueList)){
			nodeData.name="radio buttons / drop down";
			nodeData.icon = "glyphicon glyphicon-align-justify";
		
		//checkbox regardless the type
		}else if( field.checkBox == "True"){
			nodeData.name= "checkbox";
			nodeData.icon = "glyphicon glyphicon-check";
			
		}else if (currentType <9 ){  									//number
			nodeData.name= "number range";
			nodeData.icon = "glyphicon glyphicon-resize-horizontal";

		}else if (currentType == 9 || currentType == 13 ){		//date
			nodeData.name= "date range";
			nodeData.icon = "glyphicon glyphicon-calendar";

		}else if (currentType < 12 ){							//dateTime
			nodeData.name= "date/time range";
			nodeData.icon = "glyphicon glyphicon-calendar";

		}else if (currentType == 12 ){  						//time
			nodeData.name= "time range";
			nodeData.icon = "glyphicon glyphicon-time";

		}else{
			nodeData.name="text";
			nodeData.icon="glyphicon glyphicon-text-size";
		}

		
		nodeData.caption = field.caption;

		return nodeData;
	}


</script>

<style>
	#choosenFields,#fields{
		min-height: 50vh;
		overflow-Y: auto;
	}
	.item{
		cursor: pointer;
	}
</style>

<?php include(dirname(__FILE__) . "/footer.php"); ?>
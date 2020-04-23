<?php
	include(dirname(__FILE__) . '/header.php');

	// validate project name
	if (!isset($_REQUEST['axp']) || !preg_match('/^[a-f0-9]{32}$/i', $_REQUEST['axp'])) {
		echo '<br>' . $summary_reports->error_message('Project file not found.');
		exit;
	}
	
	$axp_md5 = $_REQUEST['axp'];
	$projectFile = '';
	$xmlFile = $summary_reports->get_xml_file($axp_md5, $projectFile);
//-----------------------------------------------------------------------------------------
?>

<script src="../plugins-resources/itemsList.js"></script>
<script src="../plugins-resources/form.js"></script>
<script src="../plugins-resources/project.js"></script>
<script>
	if(window.AppGiniPlugin === undefined) window.AppGiniPlugin = {};
	
	AppGiniPlugin.prj = <?php echo json_encode($xmlFile); ?>;
	AppGiniPlugin.axp_md5 = <?php echo json_encode($axp_md5); ?>;
	AppGiniPlugin.prependPath = <?php echo json_encode(PREPEND_PATH); ?>;

	$j(function() {
		/* place output folder button inside breadcrumb */
		$j('#btn-output-folder').appendTo('.breadcrumb:first');
	})
</script>
<script src="project.js"></script>

<?php echo $summary_reports->header_nav(); ?>

<?php echo $summary_reports->breadcrumb(array(
	'index.php' => 'Projects',
	'' => substr($projectFile, 0, -4)
)); ?>

<a id="btn-output-folder" href="output-folder.php?axp=<?php echo $axp_md5; ?>" class="pull-right btn btn-primary btn-sm" style="padding: 0.25em 3em;"><span class="language" data-key="SPECIFY_OUTPUT_FOLDER"></span> <span class="glyphicon glyphicon-chevron-right"></span></a>
<div class="clearfix"></div>

<div class="row">
	<!-- list of tables -->
	<div class="col-md-4"> 
		<?php 
			echo $summary_reports->show_tables(array(
				'axp' => $xmlFile,
				'click_handler' => 'AppGiniPlugin.listTableReports',
				'select_first_table' => true
			))	;
			$tables = $xmlFile->table;
		?>

		<div class="vspacer-lg">
			<button type="button" class="btn btn-danger btn-block" id="clear-summary-reports"><i class="glyphicon glyphicon-trash"></i> Clear all summary reports in this project</button>
		</div>
	</div>

	<!-- list of reports, as an itemsList -->
	<div class="col-md-8" id="reports-list"></div>
</div>

<!-- Modal for report editor and also help carousel -->
<div id="report-modal" class="modal fade">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 id="modal-title" class="modal-title"></h4>
			</div>
			<div class="modal-body">
				<div class="carousel slide" id="report-editor-carousel">
					<div class="carousel-inner">
						<div class="item active">
							<form id="report-editor-form" class="form-horizontal">
								<div class="row">
									<div id="report-sections" class="col-xs-5">
										<div class="h4 text-bold">Report sections</div>
									</div>
									<div id="report-data" class="col-xs-7">
										<div class="h4 text-bold" style="margin-left: 10%;">
											Report data
											<button type="button" class="btn btn-info help-launcher"><i class="glyphicon glyphicon-info-sign"></i> Help!</button>
										</div>
									</div>
								</div>
							</form>
						</div>

						<?php $num_slides = 4; ?>
						<?php for($slide = 1; $slide <= $num_slides; $slide++) { ?>
							<div class="item" style="min-height: 50rem;">
								<button type="button" class="btn btn-default help-closer"><i class="glyphicon glyphicon-remove"></i> Back to report settings</button>
						
								<div class="btn-group pull-right">
									<?php if($slide > 1) { ?>
										<button type="button" class="btn btn-default help-prev" data-goto="<?php echo $slide - 1; ?>"><i class="glyphicon glyphicon-chevron-left"></i> Previous</button>
									<?php } ?>
									<?php if($slide < $num_slides) { ?>
										<button type="button" class="btn btn-default help-next" data-goto="<?php echo $slide + 1; ?>"><i class="glyphicon glyphicon-chevron-right"></i> Next</button>
									<?php } ?>
								</div>
								<div class="clearfix" style="border-bottom: 2px solid black; margin: 1rem 0;"></div>

								<img class="img-responsive" src="images/report-editor-help-<?php echo ($slide < 10 ? "0{$slide}" : $slide); ?>.png">
							</div>
						<?php } ?>

					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-success" id="save-report">Save</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<!-- /Modal -->

<style>
	.page-header.row {
		margin: 0.3rem 0.3rem 2rem;
	}
	#tables-list {
		overflow-y: auto;
	}
	.btn.details-list:focus, .btn.summary-list:focus {
		outline: 0;
	}
	.items-list-header {
		margin-bottom: 1rem;
	}
	.panel tr:first-child th, .panel tr:first-child td {
		border-top: none !important;
	}
	.panel-title { font-weight: bold; }

	.item table { margin-bottom: 0 !important; }

	.item-left {
		width: 20rem !important;
		margin: 0 auto;
		position: relative;
		padding: 1rem 4rem !important;
	}
	.item-left .paper-mocker {
		height: 16rem;
		padding: 1rem;
		box-shadow: 0 0 9px 0 silver;
	}

	.item .item-details-key:last-child, .item .item-details-value:last-child {
		border-bottom: none;
	}
	.item .item-details-key, .item .item-details-value {
		padding: 1rem;
		border-bottom: solid 1px #ddd;
		height: 4.5rem !important;
		overflow: hidden;
	}
	.item .item-details-values {
		padding-left: 0 !important;
	}
	.item .item-details-keys {
		text-align: right;
		font-weight: bold;
		padding-right: 0 !important;
	}
	/* .item .form-group { margin-left: 0; margin-right: 0; } */

	.pointer {
		cursor: pointer;
	}

	#report-sections { padding-left: 4rem; }
	#report-sections label { text-align: left; }
	#report-sections .form-group { margin-left: 0; margin-right: 0; }
	#report-sections .img-responsive { width: 70%; }
</style>

<?php
include(dirname(__FILE__) . '/footer.php'); ?>

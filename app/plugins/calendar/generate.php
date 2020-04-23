<?php 
	$plugin_dir = dirname(__FILE__);
	include($plugin_dir . '/header.php');

	$app_path = realpath($plugin_dir . '/../../');

	$calendar = new calendar(array(
		'title' => 'Calendar',
		'name' => 'calendar',
		'logo' => 'calendar-logo-lg.png',
        'output_path' => $app_path
	));
	
	$axp_md5 = $_REQUEST['axp'];
	$projectFile = '';
	$project = $calendar->get_xml_file($axp_md5 , $projectFile);

	// output generate page top content
	echo $calendar->header_nav();
	echo $calendar->breadcrumb(array(
		'index.php' => 'Projects',
		'project.php?axp=' . urlencode($axp_md5) => substr($projectFile, 0, -4),
		'' => 'Generating files'
	));


	// validate provided path
	if(!$calendar->is_appgini_app($app_path)) {
		echo $calendar->error_message('Invalid application path!');
		include($plugin_dir . '/footer.php');
		exit;
	}

	if(!$cals = $calendar->calendars()) {
		echo $calendar->error_message('No calendars defined for this project. Nothing to output!');
		include($plugin_dir . '/footer.php');
		exit;
	}

	if(!$events = $calendar->events()) {
		echo $calendar->error_message('No events defined for this project. Nothing to output!');
		include($plugin_dir . '/footer.php');
		exit;
	}

	$calendar->progress_log->add('Generating calendar files into <i>' . $app_path . '</i>', 'info');

	$hooks_dir = "{$app_path}/hooks";

	// Generate hooks/calendar-* files (calendars)
	foreach($cals as $calId => $cal) {
		calendar_file($calendar, $hooks_dir, $cal);
	}

	$tables = array(); // we'll use this to group events by table 

	// Generate hooks/calendar-events-* files (event json files)
	foreach($events as $evId => $ev) {
		event_file($calendar, $hooks_dir, $ev);
		
		if(!isset($tables[$ev->table])) $tables[$ev->table] = array();
		$tables[$ev->table][] = $ev;
	}
	
	// Generate hooks/tablename-dv.js files
	foreach($tables as $table => $events) {
		table_dvhook($calendar, $hooks_dir, $table, $events);
	}

	// Copy fullcalendar and plugin common files to resources
	copy_resources($calendar, $hooks_dir);

	// Create calendar links in links-home and links-navmenu
	create_links($calendar, $cals);

	echo $calendar->progress_log->show();

	?>
	<div class="text-center">
		<a href="../../" class="btn btn-default"><i class="glyphicon glyphicon-home"></i> App Homepage</a>
	</div>
	<?php

	include($plugin_dir . '/footer.php');
	######################################################################################

	function calendar_file($pl, $path, $cal) {
		$calId = $cal->id;

		$cal_file = "{$path}/calendar-{$calId}.php";
		$cal_url = "../../hooks/" . basename($cal_file);
		
		$pl->progress_log->add("Generating <a href=\"{$cal_url}\"><i class=\"glyphicon glyphicon-calendar\"></i> {$cal->title} calendar</a> ", 'text-info');

		$replace = array(
			'[?php' => '<' . '?php',
			'?]' => '?>',
			'{calId}' => $calId,
		);

		ob_start();
		?>
			[?php
			﹣﹣define('PREPEND_PATH', '../');
			﹣﹣define('FULLCAL_PATH', PREPEND_PATH . 'resources/fullcalendar/');
			﹣﹣
			﹣﹣$hooks_dir = dirname(__FILE__);
			﹣﹣include("{$hooks_dir}/../defaultLang.php");
			﹣﹣include("{$hooks_dir}/../language.php");
			﹣﹣include("{$hooks_dir}/../lib.php");
			﹣﹣
			﹣﹣include_once("{$hooks_dir}/../header.php");
			﹣﹣
			﹣﹣/* check access */
			﹣﹣$mi = getMemberInfo();
			﹣﹣if(!in_array($mi['group'], array('<?php echo implode("', '", $cal->groups); ?>'))) {
			﹣﹣﹣﹣echo error_message("Access denied");
			﹣﹣﹣﹣include_once("{$hooks_dir}/../footer.php");
			﹣﹣﹣﹣exit;
			﹣﹣}

			﹣﹣?]

			﹣﹣<link href="[?php echo FULLCAL_PATH; ?]core/main.min.css" rel="stylesheet" />
			﹣﹣<link href="[?php echo FULLCAL_PATH; ?]daygrid/main.min.css" rel="stylesheet" />
			﹣﹣<link href="[?php echo FULLCAL_PATH; ?]timegrid/main.min.css" rel="stylesheet" />
			﹣﹣<link href="[?php echo FULLCAL_PATH; ?]list/main.min.css" rel="stylesheet" />
			﹣﹣
			﹣﹣<script src="[?php echo FULLCAL_PATH; ?]core/main.min.js"></script>
			﹣﹣<script src="[?php echo FULLCAL_PATH; ?]core/locales-all.min.js"></script>
			﹣﹣<script src="[?php echo FULLCAL_PATH; ?]interaction/main.min.js"></script>
			﹣﹣<script src="[?php echo FULLCAL_PATH; ?]daygrid/main.min.js"></script>
			﹣﹣<script src="[?php echo FULLCAL_PATH; ?]timegrid/main.min.js"></script>
			﹣﹣<script src="[?php echo FULLCAL_PATH; ?]list/main.min.js"></script>
			﹣﹣<script src="[?php echo PREPEND_PATH; ?]resources/plugin-calendar/calendar-common.js"></script>

			﹣﹣<script>
			﹣﹣﹣﹣$j(function() {
			﹣﹣﹣﹣﹣﹣var Cal = AppGini.Calendar;
			﹣﹣﹣﹣﹣﹣var calId = '{calId}';

			﹣﹣﹣﹣﹣﹣Cal.scrollTime = '08:00:00';

			﹣﹣﹣﹣﹣﹣Cal._fullCal = new FullCalendar.Calendar($j('#' + calId).get(0), {
			﹣﹣﹣﹣﹣﹣﹣﹣plugins: ['interaction', 'dayGrid', 'timeGrid', 'list'],
			﹣﹣﹣﹣﹣﹣﹣﹣customButtons: {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣reload: {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣text: 'Reload',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣icon: 'refresh',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣click: function() {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣Cal._fullCal.refetchEvents();
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣header: {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣left: 'prevYear,prev,next,nextYear reload today',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣center: 'title',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣eventLimit: true,
			﹣﹣﹣﹣﹣﹣﹣﹣},

			﹣﹣﹣﹣﹣﹣﹣﹣height: 'auto', // https://fullcalendar.io/docs/height
			﹣﹣﹣﹣﹣﹣﹣﹣contentHeight: 'auto', // https://fullcalendar.io/docs/contentHeight
			﹣﹣﹣﹣﹣﹣﹣﹣aspectRatio: 2.5, // https://fullcalendar.io/docs/aspectRatio

			﹣﹣﹣﹣﹣﹣﹣﹣defaultDate: Cal.urlDate('<?php echo $cal->{'initial-date'}; ?>'),
			﹣﹣﹣﹣﹣﹣﹣﹣defaultView: Cal.urlView('<?php echo $cal->{'initial-view'}; ?>'),
			﹣﹣﹣﹣﹣﹣﹣﹣views: {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣dayGridMonth: {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣eventLimit: 5,
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣eventLimitClick: 'day'
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣timeGridWeek : {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣nowIndicator: true,
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣scrollTime: Cal.scrollTime
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣timeGridDay: {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣nowIndicator: true,
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣scrollTime: Cal.scrollTime
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣},

			﹣﹣﹣﹣﹣﹣﹣﹣eventSources: [
							<?php foreach($cal->events as $evId) { ?>
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣{
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣url: 'calendar-events-<?php echo $evId; ?>.json.php',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣failure: function() {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣$j('#' + calId + '-events-loading-error').removeClass('hidden');
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣},
							<?php } ?>
			﹣﹣﹣﹣﹣﹣﹣﹣],
			﹣﹣﹣﹣﹣﹣﹣﹣eventRender: function (e) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣switch(e.view.type) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'dayGridMonth':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'timeGridWeek':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'timeGridDay':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣// this is necessary to render HTML titles, 
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣// https://github.com/fullcalendar/fullcalendar/issues/2919#issuecomment-459909185
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣e.el.firstChild.innerHTML = e.event.title;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣break;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'listWeek':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣e.el.lastChild.firstChild.innerHTML = e.event.title;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣break;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣eventClick: function(e) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣e.jsEvent.preventDefault();
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣if(e.event.url) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣// strip html from title, and shorten to 100 chars max
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣var title = $j('<span>' + e.event.title + '</span>').text(),
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣maxChars = 100;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣if(title.length > maxChars) title = title.substr(0, maxChars - 3) + '...';

			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣modal_window({
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣url: e.event.url,
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣size: 'full',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣title: title,
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣// on closing modal, reload events in calendar
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣close: function() {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣e.view.calendar.refetchEvents();
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣});
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣},

			﹣﹣﹣﹣﹣﹣﹣﹣/* Adding new events */
			﹣﹣﹣﹣﹣﹣﹣﹣selectable: true,
			﹣﹣﹣﹣﹣﹣﹣﹣select: function(i) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣Cal.newEventButtons.show(i);
			﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣unselect: function(e) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣Cal.newEventButtons.hide();
			﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣
			﹣﹣﹣﹣﹣﹣﹣﹣fixedWeekCount: false,
			﹣﹣﹣﹣﹣﹣﹣﹣loading: function(isLoading) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣var viewCont = $j('.fc-view-container');
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣if(isLoading) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣$j('#' + calId + '-loading')
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣.removeClass('hidden')
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣.offset({ top: viewCont.length ? viewCont.offset().top : null });
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣$j('#' + calId + '-events-loading-error').addClass('hidden');
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣return;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}

			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣// finished loading
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣(function(view) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣setTimeout(function() {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣Cal.fullCalendarFixes('#' + calId, view);
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣$j('#' + calId + '-loading').addClass('hidden');
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}, 100)
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣})(this.view);
			﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣datesRender: function(i) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣Cal.fullCalendarFixes('#' + calId, i.view);
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣Cal.updateUrlDate(moment(this.getDate()).format('YYYY-MM-DD'), i.view.type);

			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣switch(i.view.type) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'dayGridMonth':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'listWeek':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣setTimeout(Cal.fullHeight, 5);
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣break;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'timeGridWeek':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣case 'timeGridDay':
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣setTimeout(Cal.compactHeight, 5);
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣break;
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣}
			﹣﹣﹣﹣﹣﹣﹣﹣},
			﹣﹣﹣﹣﹣﹣﹣﹣viewSkeletonRender: function(i) {
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣Cal.fullCalendarFixes('#' + calId, i.view);
			﹣﹣﹣﹣﹣﹣﹣﹣}
						<?php if($cal->locale) { ?>
			﹣﹣﹣﹣﹣﹣﹣﹣, locale: '<?php echo $cal->locale; ?>'
						<?php } ?>
			﹣﹣﹣﹣﹣﹣});
			﹣﹣﹣﹣﹣﹣Cal._fullCal.render();

			﹣﹣﹣﹣﹣﹣Cal.fullCalendarBootstrapize('#' + calId);
			﹣﹣﹣﹣﹣﹣Cal.Translate.ready(function() {
			﹣﹣﹣﹣﹣﹣﹣﹣Cal.newEventButtons.create([
						<?php foreach($cal->events as $evId) { ?>
							<?php $ev = $pl->event($evId); ?>
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣{
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣type: '<?php echo $evId; ?>',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣color: '<?php echo $ev->color; ?>',
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣title: AppGini.Calendar.Translate.word('new_x', { event: '<?php echo str_replace('-', ' ', $evId); ?>' }),
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣table: '<?php echo $ev->table; ?>'
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣},
						<?php } ?>
			﹣﹣﹣﹣﹣﹣﹣﹣]);
			﹣﹣﹣﹣﹣﹣});

			﹣﹣﹣﹣﹣﹣$j('#' + calId + '-events-loading-error').click(function() {
			﹣﹣﹣﹣﹣﹣﹣﹣Cal._fullCal.refetchEvents();
			﹣﹣﹣﹣﹣﹣});

			﹣﹣﹣﹣﹣﹣if($j('div.hidden-print').length == 3) $j('div.hidden-print').eq(1).remove();
			﹣﹣﹣﹣﹣﹣AppGini.Calendar.Translate.live();
			﹣﹣﹣﹣})
			﹣﹣</script>

			﹣﹣<div class="page-header"><h1>
			﹣﹣﹣﹣<img src="[?php echo PREPEND_PATH; ?]resources/table_icons/calendar.png">
			﹣﹣﹣﹣<?php echo $cal->title; ?>
			﹣﹣</h1></div>

			﹣﹣<div 
			﹣﹣﹣﹣id="{calId}-loading" 
			﹣﹣﹣﹣class="hidden alert alert-info text-center" 
			﹣﹣﹣﹣style="width: 88%; height: 70vh; position: fixed; z-index: 500; top: 26vh;"
			﹣﹣>
			﹣﹣﹣﹣<img src="[?php echo PREPEND_PATH; ?]loading.gif"> <span class="language" data-key="please_wait"></span>
			﹣﹣</div>
			﹣﹣<div id="{calId}-events-loading-error" class="hidden alert alert-warning text-center">
			﹣﹣﹣﹣[?php echo $Translation['Connection error']; ?]
			﹣﹣﹣﹣<button type="button" class="btn btn-warning reload-calendar"><i class="glyphicon glyphicon-refresh"></i></button>
			﹣﹣</div>
			﹣﹣<div id="{calId}"></div>

			﹣﹣[?php
			﹣﹣include_once("{$hooks_dir}/../footer.php");
		<?php

		$code = ob_get_clean();
		$code = $pl->format_indents(
			str_replace(
				array_keys($replace), 
				array_values($replace), 
				$code
			)
		);

		/* Generating calendar file */
		if(!@file_put_contents($cal_file, $code)) {
			$pl->progress_log->failed();
			return;
		}

		$pl->progress_log->ok();
	}

	function event_file($pl, $path, $ev) {
		$evId = $ev->type;
		$ev_file = "{$path}/calendar-events-{$evId}.json.php";

		$pl->progress_log->add("Generating {$ev_file}  ", 'text-info');

		$replace = array(
			'[?php' => '<' . '?php',
			'?]' => '?>',
		);

		ob_start();

		?>
			[?php
			﹣﹣/*
			﹣﹣ Returns an array of events according to the 
			﹣﹣ format specified here: https://fullcalendar.io/docs/event-object
			﹣﹣ */

			﹣﹣define('PREPEND_PATH', '../');
			﹣﹣@header('Content-type: application/json');

			﹣﹣$hooks_dir = dirname(__FILE__);
			﹣﹣include("{$hooks_dir}/../defaultLang.php");
			﹣﹣include("{$hooks_dir}/../language.php");
			﹣﹣include("{$hooks_dir}/../lib.php");

			﹣﹣// event config
			﹣﹣$type = '<?php echo $evId; ?>';
			﹣﹣$color = '<?php echo $ev->color; ?>';
			﹣﹣$defaultClasses = "text-{$color} bg-{$color}";
			﹣﹣$table = '<?php echo $ev->table; ?>';
			﹣﹣$customWhere = '<?php echo trim($ev->customWhere) ? addcslashes($ev->customWhere, "'\\") : '1 = 1'; ?>';
			﹣﹣$title = '<?php echo addcslashes($ev->title, '"'); ?>';
			﹣﹣$allDay = <?php echo $ev->allDay ? 'true' : 'false'; ?>;
			﹣﹣$startDateField = '<?php echo $ev->startDateField; ?>';
			﹣﹣$startTimeField = '<?php echo $ev->startTimeField; ?>';
			﹣﹣$endDateField = '<?php echo $ev->endDateField; ?>';
			﹣﹣$endTimeField = '<?php echo $ev->endTimeField; ?>';
			﹣﹣$pk = getPKFieldName($table);
			﹣﹣// end of event config
			﹣﹣
			﹣﹣/* return this on error */
			﹣﹣$nothing = json_encode(array());

			﹣﹣/* check access */
			﹣﹣$from = get_sql_from($table);
			﹣﹣if(!$from) { // no permission to access that table
			﹣﹣﹣﹣@header('HTTP/1.0 403 Forbidden');
			﹣﹣﹣﹣exit($nothing);
			﹣﹣}

			﹣﹣$date_handler = function($dt) {
			﹣﹣﹣﹣$dto = DateTime::createFromFormat(DateTime::ISO8601, $dt);
			﹣﹣﹣﹣if($dto === false) return false;

			﹣﹣﹣﹣return date('Y-m-d H:i:s', $dto->format('U'));
			﹣﹣};

			﹣﹣$start = $date_handler($_REQUEST['start']);
			﹣﹣$end = $date_handler($_REQUEST['end']);
			﹣﹣if(!$start || !$end) exit($nothing);

			﹣﹣$events = array();
			﹣﹣$fields = get_sql_fields($table);

			﹣﹣/* 
			﹣﹣ * Build event start/end conditions:
			﹣﹣ * if event is configured with both a startDateField and endDateField,
			﹣﹣ *    get events where startDateField < end and endDateField > start and startDateField <= endDateField
			﹣﹣ * if event is configured with only a startDateField (default),
			﹣﹣ *    get events where startDateField < end and startDateField >= start

			﹣﹣ * Here, we apply date conditions only and ignore time.
			﹣﹣ * The reason is that the minimum interval for fullcalendar is 1 day.
			﹣﹣ * So, there is no need to build time filters using time fields.
			﹣﹣ */
			﹣﹣$eventDatesWhere = "`{$table}`.`{$startDateField}` >= '{$start}' AND 
			﹣﹣                    `{$table}`.`{$startDateField}` < '{$end}'";
			﹣﹣
			﹣﹣if($endDateField) $eventDatesWhere = "`{$table}`.`{$endDateField}` >= '{$start}' AND
			﹣﹣                                      `{$table}`.`{$startDateField}` < '{$end}' AND
			﹣﹣                                      `{$table}`.`{$startDateField}` <= `{$table}`.`{$endDateField}`";

			﹣﹣$eo = array('silentErrors' => true);
			﹣﹣$res = sql(
			﹣﹣﹣﹣"SELECT {$fields} FROM {$from} AND 
			﹣﹣﹣﹣﹣﹣`{$table}`.`{$startDateField}` >= '{$start}' AND 
			﹣﹣﹣﹣﹣﹣({$eventDatesWhere}) AND
			﹣﹣﹣﹣﹣﹣({$customWhere})", $eo
			﹣﹣﹣﹣);

			﹣﹣while($row = db_fetch_array($res)) {
			﹣﹣﹣﹣// preparing event title variables
			﹣﹣﹣﹣$replace = array();
			﹣﹣﹣﹣foreach($row as $key => $value)
			﹣﹣﹣﹣﹣﹣if(is_numeric($key)) $replace['{' . ($key + 1) . '}'] = $value;
			﹣﹣﹣﹣$currentTitle = str_replace(array_keys($replace), array_values($replace), $title);

			﹣﹣﹣﹣$events[] = array(
			﹣﹣﹣﹣﹣﹣'id' => $row[$pk],
			﹣﹣﹣﹣﹣﹣'url' => PREPEND_PATH . $table . '_view.php?Embedded=1&SelectedID=' . urlencode($row[$pk]),

			﹣﹣﹣﹣﹣﹣/*
			﹣﹣﹣﹣﹣﹣﹣﹣if a function named 'calendar_event_title' is defined
			﹣﹣﹣﹣﹣﹣﹣﹣(in hooks/__global.php for example), it will be called instead of using the title
			﹣﹣﹣﹣﹣﹣﹣﹣defined through the plugin. This is useful if you want to modify/append the
			﹣﹣﹣﹣﹣﹣﹣﹣default title defined for this event type based on some criteria in the data. For
			﹣﹣﹣﹣﹣﹣﹣﹣example to add some icon or extra info if specific criteria are met

			﹣﹣﹣﹣﹣﹣﹣﹣The calendar_event_title() function should:
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣1. Accept the following parameters:
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣(string) event_type (set to current event type)
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣(string) title (set to the default event title)
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣(associative array) event_data (contains the event data as retrieved from this event's table)

			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣2. Return a string containing the new/modified title to apply to the event, HTML is allowed
			﹣﹣﹣﹣﹣﹣*/
			﹣﹣﹣﹣﹣﹣'title' => (
			﹣﹣﹣﹣﹣﹣﹣﹣function_exists('calendar_event_title') ? 
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣call_user_func_array('calendar_event_title', array($type, $currentTitle, $row)) :
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣$currentTitle
			﹣﹣﹣﹣﹣﹣),

			﹣﹣﹣﹣﹣﹣/*
			﹣﹣﹣﹣﹣﹣﹣﹣if a function named 'calendar_event_classes' is defined
			﹣﹣﹣﹣﹣﹣﹣﹣(in hooks/__global.php for example), it will be called instead of using the color classes
			﹣﹣﹣﹣﹣﹣﹣﹣defined through the plugin. This is useful if you want to apply CSS classes other than the
			﹣﹣﹣﹣﹣﹣﹣﹣default ones defined for this event type based on some criteria in the data.

			﹣﹣﹣﹣﹣﹣﹣﹣The calendar_event_classes() function should:
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣1. Accept the following parameters:
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣(string) event_type (set to current event type)
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣(string) classes (set to the default classes)
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣(associative array) event_data (contains the event data as retrieved from this event's table)

			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣2. Return a string containing CSS class names (space-separated) to apply to the event.
			﹣﹣﹣﹣﹣﹣*/
			﹣﹣﹣﹣﹣﹣'classNames' => (
			﹣﹣﹣﹣﹣﹣﹣﹣function_exists('calendar_event_classes') ? 
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣call_user_func_array('calendar_event_classes', array($type, $defaultClasses, $row)) :
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣$defaultClasses
			﹣﹣﹣﹣﹣﹣),
			﹣﹣﹣﹣);

			﹣﹣﹣﹣$lastEvent = &$events[count($events) - 1];

			﹣﹣﹣﹣// convert formatted start and end dates to ISO
			﹣﹣﹣﹣$lastEvent['start'] = iso_datetime($row[$startDateField]);
			﹣﹣﹣﹣$lastEvent['end'  ] = $endDateField ? iso_datetime($row[$endDateField]) : $lastEvent['start'];

			﹣﹣﹣﹣if($allDay) {
			﹣﹣﹣﹣﹣﹣// no start/end time
			﹣﹣﹣﹣﹣﹣$lastEvent['start'] = date_only($lastEvent['start']); 
			﹣﹣﹣﹣﹣﹣$lastEvent['end'  ] = date_only($lastEvent['end'  ]);
			﹣﹣﹣﹣﹣﹣continue;
			﹣﹣﹣﹣}

			﹣﹣﹣﹣if($startTimeField)
			﹣﹣﹣﹣﹣﹣$lastEvent['start'] = iso_datetime(
			﹣﹣﹣﹣﹣﹣﹣﹣// take only the app-formatted date part of startDateField (in case it's a datetime)
			﹣﹣﹣﹣﹣﹣﹣﹣date_only($row[$startDateField]) . 
			﹣﹣﹣﹣﹣﹣﹣﹣// append a space then fomratted startTimeField
			﹣﹣﹣﹣﹣﹣﹣﹣' ' . $row[$startTimeField]
			﹣﹣﹣﹣﹣﹣);

			﹣﹣﹣﹣if($endTimeField)
			﹣﹣﹣﹣﹣﹣$lastEvent['end'] = iso_datetime(
			﹣﹣﹣﹣﹣﹣﹣﹣// take only the app-formatted date part of endDateField (in case it's a datetime)
			﹣﹣﹣﹣﹣﹣﹣﹣date_only($endDateField ? $row[$endDateField] : $row[$startDateField]) . 
			﹣﹣﹣﹣﹣﹣﹣﹣// and append a space then fomratted endTimeField
			﹣﹣﹣﹣﹣﹣﹣﹣' ' . $row[$endTimeField]
			﹣﹣﹣﹣﹣﹣);
			﹣﹣}

			﹣﹣echo json_encode($events);

			﹣﹣function date_only($dt) { return substr($dt, 0, 10); }

			﹣﹣function iso_datetime($dt) {
			﹣﹣﹣﹣// if date already in the format yyyy-mm-dd? do nothing
			﹣﹣﹣﹣if(preg_match('/^[0-9]{4}-/', $dt)) return $dt;

			﹣﹣﹣﹣// convert app-formatted date to iso (mysql)
			﹣﹣﹣﹣return mysql_datetime($dt);
			﹣﹣}
		<?php

		$code = ob_get_clean();
		$code = $pl->format_indents(
			str_replace(
				array_keys($replace), 
				array_values($replace), 
				$code
			)
		);

		/* Generating calendar file */
		if(!@file_put_contents($ev_file, $code)) {
			$pl->progress_log->failed();
			return;
		}

		$pl->progress_log->ok();
	}

	function table_dvhook($pl, $path, $table, $events) {
		$dvhook_file = "{$path}/{$table}-dv.js";
		$pl->progress_log->add("Updating {$dvhook_file}  ", 'text-info');
	
		// create hook file if not already there
		if(!@touch($dvhook_file)) {
			$pl->progress_log->failed();
			return;
		}

		ob_start();
		?>

			/* Inserted by Calendar plugin on <?php echo date('Y-m-d H:i:s'); ?> */
			(function($j) {
			﹣﹣var urlParam = function(param) {
			﹣﹣﹣﹣var url = new URL(window.location.href);
			﹣﹣﹣﹣return url.searchParams.get(param);
			﹣﹣};

			﹣﹣var setDate = function(dateField, date, time) {
			﹣﹣﹣﹣var dateEl = $j('#' + dateField);
			﹣﹣﹣﹣if(!dateEl.length) return; // no date field present

			﹣﹣﹣﹣var d = date.split('-').map(parseFloat).map(Math.floor); // year-month-day
			﹣﹣﹣﹣
			﹣﹣﹣﹣// if we have a date field with day and month components
			﹣﹣﹣﹣if($j('#' + dateField + '-mm').length && $j('#' + dateField + '-dd').length) {
			﹣﹣﹣﹣﹣﹣dateEl.val(d[0]);
			﹣﹣﹣﹣﹣﹣$j('#' + dateField + '-mm').val(d[1]);
			﹣﹣﹣﹣﹣﹣$j('#' + dateField + '-dd').val(d[2]);
			﹣﹣﹣﹣﹣﹣return;
			﹣﹣﹣﹣}

			﹣﹣﹣﹣// for datetime fields that have datetime picker, populate with formatted date and time
			﹣﹣﹣﹣if(dateEl.parents('.datetimepicker').length == 1) {
			﹣﹣﹣﹣﹣﹣dateEl.val(
			﹣﹣﹣﹣﹣﹣﹣﹣moment(date + ' ' + time).format(AppGini.datetimeFormat('dt'))
			﹣﹣﹣﹣﹣﹣);
			﹣﹣﹣﹣﹣﹣return;
			﹣﹣﹣﹣}

			﹣﹣﹣﹣// otherwise, try to populate date and time as-is
			﹣﹣﹣﹣dateEl.val(date + ' ' + time);
			﹣﹣};

			﹣﹣$j(function() {
			﹣﹣﹣﹣// continue only if this a new record form
			﹣﹣﹣﹣if($j('[name=SelectedID]').val()) return;

			﹣﹣﹣﹣var params = ['newEventType', 'startDate', 'startTime', 'endDate', 'endTime', 'allDay'], v = {};
			﹣﹣﹣﹣for(var i = 0; i < params.length; i++)
			﹣﹣﹣﹣﹣﹣v[params[i]] = urlParam('calendar.' + params[i]);

			﹣﹣﹣﹣// continue only if we have a newEventType param
			﹣﹣﹣﹣if(v.newEventType === null) return;

			﹣﹣﹣﹣// continue only if event start and end specified
			﹣﹣﹣﹣if(v.startDate === null || v.endDate === null) return;

			﹣﹣﹣﹣// adapt event data types
			﹣﹣﹣﹣v.allDay = JSON.parse(v.allDay);
			﹣﹣﹣﹣v.start = new Date(v.startDate + ' ' + v.startTime);
			﹣﹣﹣﹣v.end = new Date(v.endDate + ' ' + v.endTime);

			﹣﹣﹣﹣// now handle various event types, populating the relevent fields
			﹣﹣﹣﹣switch(v.newEventType) {
					<?php foreach($events as $ev) { ?>
			﹣﹣﹣﹣﹣﹣case '<?php echo $ev->type; ?>':
						<?php if($ev->startDateField) { ?>
			﹣﹣﹣﹣﹣﹣﹣﹣setDate('<?php echo $ev->startDateField; ?>', v.startDate, v.startTime);
						<?php } ?>
						<?php if($ev->startTimeField) { ?>
			﹣﹣﹣﹣﹣﹣﹣﹣if(!v.allDay) $j('#<?php echo $ev->startTimeField; ?>').val(
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣moment(v.startDate + ' ' + v.startTime)
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣.format(AppGini.datetimeFormat('t'))
			﹣﹣﹣﹣﹣﹣﹣﹣);
						<?php } ?>
						<?php if($ev->endDateField && $ev->endDateField != $ev->startDateField) { ?>
			﹣﹣﹣﹣﹣﹣﹣﹣setDate('<?php echo $ev->endDateField; ?>', v.endDate, v.endTime);
						<?php } ?>
						<?php if($ev->endTimeField) { ?>
			﹣﹣﹣﹣﹣﹣﹣﹣if(!v.allDay) $j('#<?php echo $ev->endTimeField; ?>').val(
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣moment(v.endDate + ' ' + v.endTime)
			﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣﹣.format(AppGini.datetimeFormat('t'))
			﹣﹣﹣﹣﹣﹣﹣﹣);
						<?php } ?>
			﹣﹣﹣﹣﹣﹣﹣﹣break;
					<?php } ?>
			﹣﹣﹣﹣}

			﹣﹣﹣﹣// finally, trigger user-defined event handlers
			﹣﹣﹣﹣$j(function() { 
			﹣﹣﹣﹣﹣﹣$j(document).trigger('newCalendarEvent', [v]); 
			﹣﹣﹣﹣})
			﹣﹣});
			})(jQuery);
			/* End of Calendar plugin code */

		<?php

		$code = ob_get_clean();
		$code = $pl->format_indents("\n\n" . trim($code) . "\n\n");

		// remove existing calendar code if found
		$old_code = preg_replace(
			/* regex to match dv code from first line to last */
			"/\s*\/\* Inserted by Calendar plugin on (\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2}) \*\/" .
			"(.*)" .
			"\/\* End of Calendar plugin code \*\/\s*" .
			"/s", 
			
			/* and remove it */
			'', 
			
			/* from existing code */
			file_get_contents($dvhook_file)
		);

		// finally append new code to clean old one
		if(!@file_put_contents($dvhook_file, $old_code . $code)) {
			$pl->progress_log->failed();
			return;
		}

		$pl->progress_log->ok();
	}

	function copy_resources($pl, $hooks_dir) {
		$dest_resources_dir = realpath("{$hooks_dir}/../resources");
		$plugin_resources_dir = dirname(__FILE__) . '/app-resources';

		$pl->progress_log->add("<b>Copying resources</b>", 'text-info');

		// copy fullcalendar
		$pl->recurse_copy("{$plugin_resources_dir}/fullcalendar", "{$dest_resources_dir}/fullcalendar", true, 1);
		
		// copy plugin-calendar folder
		$pl->recurse_copy("{$plugin_resources_dir}/plugin-calendar", "{$dest_resources_dir}/plugin-calendar", true, 1);

		// copy calendar icon for use in links
		$pl->copy_file(
			realpath("{$plugin_resources_dir}/../../plugins-resources/table_icons/calendar.png"), 
			"{$dest_resources_dir}/table_icons/calendar.png", 
			true
		);

		$pl->progress_log->add("<b>Finished copying resources</b>", 'text-success');
	}

	function create_links($pl, $calendars) {
		// retrieve table groups as a 0-based numeric array
		$tableGroups = array_keys(get_table_groups());

		$linksHome = array();
		$linksNavmenu = array();
		foreach($calendars as $calId => $cal) {
			if(!empty($cal->{'links-home'})) 
				$linksHome[] = array(
					'url' => "hooks/calendar-{$calId}.php",
					'icon' => 'resources/table_icons/calendar.png', 
					'title' => $cal->title,
					'description' => '',
					'groups' => $cal->groups,
					'grid_column_classes' => 'col-sm-6 col-md-4 col-lg-3',
					'panel_classes' => 'panel-info',
					'link_classes' => 'btn-info',
					// for links-home, pass the table group title
					'table_group'=> $tableGroups[$cal->{'links-home'} - 1],
				);

			if(!empty($cal->{'links-navmenu'})) 
				$linksNavmenu[] = array(
					'url' => "hooks/calendar-{$calId}.php",
					'icon' => 'resources/table_icons/calendar.png', 
					'title' => $cal->title,
					'groups' => $cal->groups,
					// for links-navmenu, pass the table group 0-based index
					'table_group'=> $cal->{'links-navmenu'} - 1,
				);
		}

		$pl->add_links('links-home', $linksHome);
		$pl->add_links('links-navmenu', $linksNavmenu);
	}
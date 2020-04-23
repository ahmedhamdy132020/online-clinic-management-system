<?php
	$rdata = array_map('to_utf8', array_map('nl2br', array_map('html_attr_tags_ok', $rdata)));
	$jdata = array_map('to_utf8', array_map('nl2br', array_map('html_attr_tags_ok', $jdata)));
?>
<script>
	$j(function() {
		var tn = 'events';

		/* data for selected record, or defaults if none is selected */
		var data = {
			name_patient: <?php echo json_encode(array('id' => $rdata['name_patient'], 'value' => $rdata['name_patient'], 'text' => $jdata['name_patient'])); ?>
		};

		/* initialize or continue using AppGini.cache for the current table */
		AppGini.cache = AppGini.cache || {};
		AppGini.cache[tn] = AppGini.cache[tn] || AppGini.ajaxCache();
		var cache = AppGini.cache[tn];

		/* saved value for name_patient */
		cache.addCheck(function(u, d) {
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'name_patient' && d.id == data.name_patient.id)
				return { results: [ data.name_patient ], more: false, elapsed: 0.01 };
			return false;
		});

		cache.start();
	});
</script>


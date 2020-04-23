<?php
	include(dirname(__FILE__).'/../plugins-resources/loader.php');

	class calendar extends AppGiniPlugin{
		/* add any plugin-specific properties here */
		
		public function __construct($config = array()){
			parent::__construct($config);
			
			/* add any further plugin-specific initialization here */
		}

		public function data() {
			if(!isset($this->project_xml->plugins)) return false;
			if(!isset($this->project_xml->plugins->calendar)) return false;
			if(!isset($this->project_xml->plugins->calendar->data)) return false;

			return @json_decode($this->project_xml->plugins->calendar->data);
		}
		
		public function event($id) {
			if(!$events = $this->events()) return false;
			if(!isset($events->$id)) return false;

			return $events->$id;
		}

		public function calendars() {
			if(!$data = $this->data()) return false;
			if(!isset($data->calendars)) return false;

			return $data->calendars;
		}
		
		public function events() {
			if(!$data = $this->data()) return false;
			if(!isset($data->events)) return false;

			return $data->events;
		}
	}

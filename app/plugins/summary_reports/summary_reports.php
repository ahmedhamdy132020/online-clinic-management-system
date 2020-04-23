<?php
	include(dirname(__FILE__) . '/../plugins-resources/loader.php');

	class summary_reports extends AppGiniPlugin {
		/* add any plugin-specific properties here */
		
		public function __construct($config = array()) {
			parent::__construct($config);
			
			/* add any further plugin-specific initialization here */
		}
		
		/**
			@param $table1 string, name of table
			@param $table2 string, name of table
			@return string of join statement of table1 and table2
		*/	 
		function get_join_statment($table1, $table2) {	
			$join_statment = '';
			$lookup_fields = $this->get_fk_fields();
			$table2_lookups = $lookup_fields[$table2];
			$joined_table = $table2;
			$table2_children = array();
			
			foreach($table2_lookups as $key => $value){
				$table2_children[] = $value;
			}
			
			/* if table2 is parent of table1, swap tables */
			if(in_array($table1, $table2_children)) {
				$temp = $table1;
				$table1 = $table2;
				$table2 = $temp;
			}
			
			$table1_lookups = $lookup_fields[$table1];
			
			foreach($table1_lookups as $key1 => $value1) {
				if($value1 == $table2) {
					$table2_pk = $this->get_pk_field_name($table2);
					$join_statment .= "`{$joined_table}` ON `{$table1}`.`{$key1}` = `{$table2}`.`{$table2_pk}`";
				}
			}

			return $join_statment;
		}

		/**
			@param $tn string, name of table
			@param $fn string, name of field
			@return integer 0-based index (acording to project order) of given field in given table if the field is a lookup, -1 otherwise
		*/
		function lookup_field_index($tn, $fn) {
			$field = $this->field($tn, $fn);
			if($field === false) return -1; // table/field name not found

			$pt = (string) $field->parentTable;
			if(!$pt) return -1; // not a lookup field

			return $this->field_index($tn, $fn);
		}
	}

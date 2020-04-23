<?php
	// check this file's MD5 to make sure it wasn't called before
	$prevMD5=@implode('', @file(dirname(__FILE__).'/setup.md5'));
	$thisMD5=md5(@implode('', @file("./updateDB.php")));
	if($thisMD5==$prevMD5) {
		$setupAlreadyRun=true;
	}else{
		// set up tables
		if(!isset($silent)) {
			$silent=true;
		}

		// set up tables
		setupTable('patients', "create table if not exists `patients` (   `id` INT unsigned not null auto_increment , primary key (`id`), `last_name` VARCHAR(40) not null , `first_name` VARCHAR(40) not null , `gender` VARCHAR(10) not null default 'Unknown' , `sexual_orientation` TEXT not null , `birth_date` DATE not null , `age` INT null , `image` VARCHAR(40) null , `address` TEXT null , `city` VARCHAR(40) null , `state` VARCHAR(15) null , `zip` CHAR(8) null , `home_phone` VARCHAR(40) null , `work_phone` VARCHAR(40) null , `mobile` VARCHAR(40) null , `tobacco_usage` VARCHAR(40) not null default 'Unknown' , `alcohol_intake` VARCHAR(40) not null default 'Unknown' , `history` VARCHAR(100) not null default 'Unknown' , `surgical_history` TEXT null , `obstetric_history` TEXT null , `genetic_diseases` TEXT null , `contact_person` VARCHAR(100) null , `other_details` TEXT null , `comments` TEXT null , `filed` DATETIME null , `last_modified` DATETIME null ) CHARSET utf8", $silent);
		setupTable('disease_symptoms', "create table if not exists `disease_symptoms` (   `id` INT unsigned not null auto_increment , primary key (`id`), `disease` VARCHAR(200) not null , unique `disease_unique` (`disease`), `symptoms` TEXT not null , `reference` TEXT null ) CHARSET utf8", $silent);
		setupTable('events', "create table if not exists `events` (   `id` INT unsigned not null auto_increment , primary key (`id`), `title` VARCHAR(40) null , `date` DATE not null , `status` VARCHAR(40) not null , `name_patient` INT unsigned null , `time` TIME not null default '12:00' , `prescription` VARCHAR(40) null , `diagnosis` VARCHAR(40) null , `comments` TEXT null ) CHARSET utf8", $silent);
		setupIndexes('events', array('name_patient'));
		setupTable('medical_img', "create table if not exists `medical_img` (   `id` INT unsigned not null auto_increment , primary key (`id`), `patient` INT unsigned null , `image` VARCHAR(40) not null , `description` TEXT null ) CHARSET utf8", $silent);
		setupIndexes('medical_img', array('patient'));
		setupTable('medical_docs', "create table if not exists `medical_docs` (   `id` INT unsigned not null auto_increment , primary key (`id`), `patient` INT unsigned null , `doc` VARCHAR(40) not null , `description` TEXT null ) CHARSET utf8", $silent);
		setupIndexes('medical_docs', array('patient'));


		// save MD5
		if($fp=@fopen(dirname(__FILE__).'/setup.md5', 'w')) {
			fwrite($fp, $thisMD5);
			fclose($fp);
		}
	}


	function setupIndexes($tableName, $arrFields) {
		if(!is_array($arrFields)) {
			return false;
		}

		foreach($arrFields as $fieldName) {
			if(!$res=@db_query("SHOW COLUMNS FROM `$tableName` like '$fieldName'")) {
				continue;
			}
			if(!$row=@db_fetch_assoc($res)) {
				continue;
			}
			if($row['Key']=='') {
				@db_query("ALTER TABLE `$tableName` ADD INDEX `$fieldName` (`$fieldName`)");
			}
		}
	}


	function setupTable($tableName, $createSQL='', $silent=true, $arrAlter='') {
		global $Translation;
		ob_start();

		echo '<div style="padding: 5px; border-bottom:solid 1px silver; font-family: verdana, arial; font-size: 10px;">';

		// is there a table rename query?
		if(is_array($arrAlter)) {
			$matches=array();
			if(preg_match("/ALTER TABLE `(.*)` RENAME `$tableName`/", $arrAlter[0], $matches)) {
				$oldTableName=$matches[1];
			}
		}

		if($res=@db_query("select count(1) from `$tableName`")) { // table already exists
			if($row = @db_fetch_array($res)) {
				echo str_replace("<TableName>", $tableName, str_replace("<NumRecords>", $row[0],$Translation["table exists"]));
				if(is_array($arrAlter)) {
					echo '<br>';
					foreach($arrAlter as $alter) {
						if($alter!='') {
							echo "$alter ... ";
							if(!@db_query($alter)) {
								echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
								echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
							}else{
								echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
							}
						}
					}
				}else{
					echo $Translation["table uptodate"];
				}
			}else{
				echo str_replace("<TableName>", $tableName, $Translation["couldnt count"]);
			}
		}else{ // given tableName doesn't exist

			if($oldTableName!='') { // if we have a table rename query
				if($ro=@db_query("select count(1) from `$oldTableName`")) { // if old table exists, rename it.
					$renameQuery=array_shift($arrAlter); // get and remove rename query

					echo "$renameQuery ... ";
					if(!@db_query($renameQuery)) {
						echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
						echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
					}else{
						echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
					}

					if(is_array($arrAlter)) setupTable($tableName, $createSQL, false, $arrAlter); // execute Alter queries on renamed table ...
				}else{ // if old tableName doesn't exist (nor the new one since we're here), then just create the table.
					setupTable($tableName, $createSQL, false); // no Alter queries passed ...
				}
			}else{ // tableName doesn't exist and no rename, so just create the table
				echo str_replace("<TableName>", $tableName, $Translation["creating table"]);
				if(!@db_query($createSQL)) {
					echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
					echo '<div class="text-danger">' . $Translation['mysql said'] . db_error(db_link()) . '</div>';
				}else{
					echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
				}
			}
		}

		echo "</div>";

		$out=ob_get_contents();
		ob_end_clean();
		if(!$silent) {
			echo $out;
		}
	}
?>
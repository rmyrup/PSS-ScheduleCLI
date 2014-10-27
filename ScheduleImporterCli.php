<?php 
define('_JEXEC', 1);

// setup base path
(strpos(__DIR__, 'REDACTED-DOMAIN')) ? define('JPATH_BASE', 'REDACTED-PATH') : define('JPATH_BASE', 'REDACTED-PATH');

// location of joomla files
require_once ( JPATH_BASE . '/includes/defines.php' );
require_once ( JPATH_BASE . '/includes/framework.php' );

class SchgeduleImportCli extends JApplicationCli
{
	
	private $_data = array();
	private $_warns = array();
	private $_errors = array();

	private static function getDbo($isDev = false)
	{
		static $dbo = NULL;

		if($dbo === NULL)
		{
			if($isDev)
			{
				$db_host = 'REDACTED';
				$db_user = 'REDACTED';
				$db_password = 'REDACTED';
				$db_db = 'REDACTED';
			}
			else
			{
				$db_host = 'REDACTED';
				$db_user = 'REDACTED';
				$db_password = 'REDACTED';
				$db_db = 'REDACTED';
			}
				
			$option = array();
			$option['driver']   = 'mysql';
			$option['host']     = $db_host;
			$option['user']     = $db_user;
			$option['password'] = $db_password;
			$option['database'] = $db_db;
			$option['prefix']   = '';
			$dbo = JDatabase::getInstance( $option );
		}

		return $dbo;
	}

	public function execute()
	{
		// one time
		$this->loadVars();
		
		$this->cliIntro();
		
		// prompt to select a file
		$file = $this->getFile();
		
		// reprompt if a file isn't selected
		while(!$file) 
		{
			$this->cliClear();
			$this->cliIntro();
			$file = $this->getFile();
		}

		// do not proceed if a file is not selected
		if(!$file) return false;
	
		$this->out();
		$this->out("Loading data in $file....");
		$isLoaded = $this->loadFile($file);

		if(!$isLoaded)
		{
			$this->out('Unable to load data from the file.  Check file format and contents.');
			return;
		}
		else
		{
			$this->out("\t....Data loaded from selected file.");
		}
		
		$this->out();
		$this->out("Parsing data loaded from file....");
		$isParsed = $this->dataParse();
		
		// report any errors or warnings
		if(count($this->_errors) || count($this->_warns))
		{
			$this->cliReportErrors();
		}
		else
		{
			$this->out("\t....Data parsed without errors or warnings.");
		}
		
		// do not proceed if not parsed
		if(!$isParsed) 
		{
			$this->out('Unable to continue until data errors are resolved.');
			return;
		}
		
		$this->out();
		$this->cliDivider();
		$this->out('Ready to start putting schedule data into the database.  Press ENTER to continue, or Ctrl+C to quit.');
		$this->cliDivider();
		$this->in();
		
		$this->out();
		$this->out('Importing schedule data....');
		
		if($this->input->get('test')) ob_start();
		$numImported = $this->dataImport();
		if($this->input->get('test')) file_put_contents('test_results.txt', ob_get_clean());
		
		if(!$numImported)
		{
			$this->out("\t....There was a problem importing schedule data.");
		}
		else
		{
			$this->out("\t....Imported $numImported schedules into the database.");
		}
		
	}

	private function cliDivider($thick = false)
	{
		if($thick)
		{
			$this->out('##########################################');
		}
		else
		{
			$this->out('------------------------------------------');
		}
	}
	
	private function cliClear()
	{
		system('clear');
	}
	
	private function cliIntro()
	{
		$this->cliDivider(true);
		$this->out('Schedule Importer');
		$this->cliDivider(true);
		$this->out();
	}
	private function cliReportErrors()
	{
		$this->out();
		$this->cliDivider(true);
		$this->out('There are ' . count($this->_errors) . ' errors and ' . count($this->_warns) . ' warnings in the schedule data.  Press ENTER to view the messages.');
		$this->cliDivider(true);
		$this->in();

		if(count($this->_warns))
		{
			$this->out();		
			$this->cliDivider();
			$this->out('Warnings');
			$this->cliDivider();
			$this->out();
			foreach($this->_warns as $warn) $this->out($warn);
		}

		if(count($this->_errors))
		{
			$this->out();		
			$this->cliDivider();
			$this->out('Errors');
			$this->cliDivider();
			$this->out();
			foreach($this->_errors as $err) $this->out($err);
		}

		$this->out();
	}
	
	private function getFile() 
	{
		$files = scandir(__DIR__);
		
		foreach($files as $i => $file) {
			if(strtolower(substr($file, -4)) != '.csv') unset($files[$i]);
		}

		if(!count($files))
		{
			$this->out('No CSV files found in this directory.  Only properly formatted CSV files can be parsed for import.');
			$this->out();
			return false;
		}
		
		sort($files);
		
		$this->cliDivider();
		$this->out('Specify A Schedule File');
		$this->cliDivider();
		$this->out("  #\tFile Name");
		$this->cliDivider();
		
		foreach($files as $i => $file) $this->out("  $i\t$file");

		$this->out();
		$this->out("What schedule file do you want to import?  ");
		$idx = $this->in("Enter number:  ");
		
		if(!isset($files[$idx]))
		{
			$this->out();
			$this->out('Invalid number entered.  Enter the index number for a listed file.');
			$this->out();
			return false;
		}
		else
		{
			return __DIR__ . '/' . $files[$idx];
		}
	}
	private function loadVars()
	{
		$this->cols = array(
				'code_type'=>0, //Type Code
				'code_date'=>1, //Date Code
				'code_site'=>2, //Site Code
				'notes_pub'=>3, //Public Notes
				'notes_inst'=>4, //Instructor Notes
		
				's1d'=>5,
				's1s'=>6,
				's1e'=>7,
				's1l'=>8,
		
				's2d'=>9,
				's2s'=>10,
				's2e'=>11,
				's2l'=>12,
		
				's3d'=>13,
				's3s'=>14,
				's3e'=>15,
				's3l'=>16,
		
				's4d'=>17,
				's4s'=>18,
				's4e'=>19,
				's4l'=>20,
		
				's5d'=>21,
				's5s'=>22,
				's5e'=>23,
				's5l'=>24,
		
				's6d'=>25,
				's6s'=>26,
				's6e'=>27,
				's6l'=>28,
		
				's7d'=>29,
				's7s'=>30,
				's7e'=>31,
				's7l'=>32,
		
				's8d'=>33,
				's8s'=>34,
				's8e'=>35,
				's8l'=>36,
		
				's9d'=>37,
				's9s'=>38,
				's9e'=>39,
				's9l'=>40,
		
				's10d'=>41,
				's10s'=>42,
				's10e'=>43,
				's10l'=>44,
		
				's11d'=>45,
				's11s'=>46,
				's11e'=>47,
				's11l'=>48,
		
				's12d'=>49,
				's12s'=>50,
				's12e'=>51,
				's12l'=>52,
		
				's13d'=>53,
				's13s'=>54,
				's13e'=>55,
				's13l'=>56,
		
				's14d'=>57,
				's14s'=>58,
				's14e'=>59,
				's14l'=>60,
		
				's15d'=>61,
				's15s'=>62,
				's15e'=>63,
				's15l'=>64
		);
		
		$db = $this->getDbo($this->input->get('dev'));
		
		// load currently available sites
		$querySites = $db->getQuery(true);
		$querySites->select('*')
		->from('pss_sites');
		$db->setQuery($querySites);
		$this->sites = $db->loadAssocList('site_code');
		
		// load currently available locations
		$queryLocations = $db->getQuery(true);
		$queryLocations->select('*')
		->from('pss_sites_locations AS l')
		->where('l.location_state = \'1\'');
		$db->setQuery($queryLocations);
		$this->locations = $db->loadAssocList('location_code');
		
		// load currently available course types
		$queryTypes = $db->getQuery(true);
		$queryTypes->select('*')
		->from('pss_course_types AS ct')
		->where('ct.ct_state = \'1\'');
		$db->setQuery($queryTypes);
		$this->course_types = $db->loadAssocList('ct_code');
		
		// currently supported sub types
		$this->course_sub_types = array(
				"2-3 Wheel Written"=>"311,321",
				"2 Wheel Riding"=>"312",
				"2 Wheel Both"=>"313",
				"3 Wheel Both"=>"323",
		
				"Women Only"=>2,
				"Solo"=>3
		);
		
	}
	private function loadFile($file = false)
	{
		// check that the file is readable
		if(!is_readable($file))
		{
			$this->out();
			$this->out('The selected file is not readable.  Unable to continue.');
			$this->out();
			return false;
		}
		
		// read the csv file with schedule data
		$lines = explode("\r\n",file_get_contents($file));
		
		// skip the column headers
		unset($lines[0]);
		
		// loop each line and check the data
		foreach($lines as $i => $line) {
		
			// make sure this is a valid line
			if($line == "" || !preg_match('/[^,]/', $line) )
			{
				$this->_data[] = false;
				continue;
			}
			
			// break out the comma separated data
			$data = explode(',', $line);
			
			// check that the line was not blank
			if(!count($data)) continue;

			$item = array();
			
			// loop each column and match keys with values
			foreach($this->cols as $col => $i) {
				// clean the data a bit (especially for the notes column)
				if(isset($data[$i])) $item[$col] = stripslashes($data[$i]);
			}
				
			$this->_data[] = array('raw' => $line, 'exploded' => $data, 'parsed' => $item);
		}
		
		return (count($this->_data));
	}
	private function dataParse()
	{
		foreach($this->_data as $i => &$item)
		{
			if(!$item) continue;
			
			$data = $item['exploded'];
				
			// make sure the site is valid
			if(!isset($this->sites[ $data[ $this->cols['code_site'] ] ]))
			{
				$this->_errors[] = "INVALID SITE (" . $data[ $this->cols['code_site'] ] . ") at line " . ($i+2);
			}
		
			// make sure the course type is valid
			if(!isset($this->course_types[ $data[ $this->cols['code_type'] ] ]))
			{
				$this->_errors[] = "INVALID COURSE TYPE (" . $data[ $this->cols['code_type'] ] . ") at line " . ($i+2);
			}
		
			for($si = 1; $si <= 15; $si++)
			{
				$loc = 's' . $si . 'l';
				$date = 's' . $si . 'd';
				$t_start = 's' . $si . 's';
				$t_end = 's' . $si . 'e';
				
				// must have session data
				if(!isset($data[ $this->cols[ $loc ] ]) || $data[ $this->cols[ $loc ] ] == "") continue;
			
				// check the value for the location
				if(!isset($this->locations[ $data[ $this->cols[ $loc ] ] ]))
				{
					$this->_errors[] = "INVALID SESSION LOCATION (SESSION $si -- " . $data[ $this->cols[ $loc ] ] . ") at line " . ($i+2);
				}
				
				//check the values for the date and time
				$str_start = $data[ $this->cols[ $date ] ] . ' ' . $data[ $this->cols[ $t_start ] ];
				$str_end = $data[ $this->cols[ $date] ] . ' ' . $data[ $this->cols[ $t_end] ];

				$ts_start = strtotime($str_start);
				$ts_end = strtotime($str_end);
				
				$ds = date('Y-m-d H:i:s', $ts_start);
				$de = date('Y-m-d H:i:s', $ts_end);
				
				if($ts_start === false || (substr($ds,0,4) == '1969'))
				{
					$this->_errors[] = "INVALID SESSION START TIME (SESSION $si -- \"$str_start\") at line " . ($i+2);
				}
				if($ts_end === false || (substr($de,0,4) == '1969'))
				{
					$this->_errors[] = "INVALID SESSION END TIME (SESSION $si -- \"$str_end\") at line " . ($i+2);
				}
				
				$item['parsed']['s' . $si . 'ts_start'] = $ts_start;
				$item['parsed']['s' . $si . 'ts_end'] = $ts_end;
				
				$item['parsed']['s' . $si . 'date_start'] = $ds;
				$item['parsed']['s' . $si . 'date_end'] = $de;
			}
		
			// create a code date from the first session
			$s1dt = strtotime( $data[ $this->cols['s1d'] ] );
			$s1dc = date('ymd', $s1dt);
		
			// make sure the created code date is formatted as expected with the first session
			if($data[ $this->cols['code_date'] ] != $s1dc)
			{
				$this->_warns[] = "INVALID CODE DATE (" . $data[ $this->cols['code_date'] ] . " != " . $s1dc . "]) at line " . ($i+2);
			}
		
			$item['parsed']['date_code'] = $data[ $this->cols['code_type'] ] . "-" . $data[ $this->cols['code_date'] ] . "-" . $data[ $this->cols['code_site'] ];
		}

		return (count($this->_errors)) ? false : true;
		
	}
	private function dataImport()
	{
		$inserts = array();
		$numCourses = 0;
		$db = $this->getDbo($this->input->get('dev'));		
		
		foreach($this->_data as $item) {
			if(!$item) continue;
			
			$row = $item['parsed'];
			
			//the course
			$course_cols = array();
			$course_vals = array();
			
			// the code date with year
			$course_cols[] = "code_date";
			$course_vals[] = $row['code_date'];
			
			// the site id
			$course_cols[] = "site_id";
			$course_vals[] = $this->sites[ $row['code_site'] ]['site_id'];
			
			// the course type id
			$course_cols[] = "ct_id";
			$course_vals[] = $this->course_types[ $row['code_type'] ]['ct_id'];
			
			if($this->input->get('blockInstructors'))
			{
				// no instructor positions
				$course_cols[] = "instructors_needed";
				$course_vals[] = "0";
			}
			
			// call to register
			$course_cols[] = "status";
			$course_vals[] = "13";

			if( $row['notes_pub'] != '' )
			{
				$course_cols[] = "notes_pub";
				$course_vals[] = addslashes( $row['notes_pub'] );
			}
				
			if( $row['notes_inst'] != '' ) 
			{
				$course_cols[] = "notes_inst";
				$course_vals[] = addslashes( $row['notes_inst'] );
			}
		
			if( in_array( $this->course_types[ $row['code_type'] ], array(31,32,34) ) )
			{
				$course_cols[] = "cst_id_str";
				$course_vals[] = $this->course_sub_types[ $row['code_type'] ];
				
				$course_cols[] = "students_needed";
				$course_vals[] = "20";
			}

			// quote the values for database insertion
			foreach($course_vals as &$val) $val = $db->quote($val);
			
			// the course query
			$course = $db->getQuery(true);
			$course->insert('classes')
					->columns($db->quoteName($course_cols))
					->values(implode(',', $course_vals));

			if($this->input->get('test'))
			{
				$result_course_id = rand(0,100000) . "TEST";
			}
			else
			{
				$db->setQuery($course);
				$db->query();
				$result_course_id = $db->insertid();
			}
			
			$numCourses++;
			
			$inserts[$result_course_id . "_"] = (string) $course;
		
			//the session(s)
			for($i=1; $i <= 15; $i++) {
				//make sure there is a session value set
				if(!isset($row['s'.$i.'d']) || $row['s'.$i.'d'] == "") continue;
		
				
				$session_cols = array();
				$session_vals = array();
				
				$session_cols[] = "session_course_id";
				$session_vals[] = $result_course_id;
				
				$session_cols[] = "session_location_id";
				$session_vals[] = $this->locations[ $row['s'.$i.'l'] ]['location_id'];
				
				$session_cols[] = "session_datetime_start";
				$session_vals[] = $row['s'.$i.'date_start'];
				
				$session_cols[] = "session_datetime_end";
				$session_vals[] = $row['s'.$i.'date_end'];
				
				$session_cols[] = "session_set_by_user_id";
				$session_vals[] = "0";
		
				// quote the values for database insertion
				foreach($session_vals as &$val) $val = $db->quote($val);
					
				// the course query
				$session = $db->getQuery(true);
				$session->insert('pss_sessions')
					->columns($db->quoteName($session_cols))
					->values(implode(',', $session_vals));
								
				if($this->input->get('test'))
				{
					$result_session_id = rand(0,100000) . "TEST";
				}
				else
				{
					$db->setQuery($session);
					$db->query();
					$result_session_id = $db->insertid();
				}
									
				$inserts[$result_session_id . "_"] = (string) $session;
			}
		}

		if($this->input->get('test')) print_r($inserts);
		
		return $numCourses;
	}
}

JApplicationCli::getInstance('SchgeduleImportCli')->execute();

<?php
/******************************************************************************
 Pepper
 
 Developer		: Andrew Sutherland
 Plug-in Name	: Outclicks
 
 http://code.jalenack.com/
 
******************************************************************************/

$installPepper = "AS_Outclicks";

class AS_Outclicks extends Pepper { 

	var $version	= 114; 
	var $info		= array
	(
		'pepperName'	=> 'Outclicks',
		'pepperUrl'		=> 'http://code.jalenack.com/archives/outclicks-pepper/',
		'pepperDesc'	=> 'Where do all those people go when they leave? This clever pepper tracks outgoing clicks from your site. It also plays friendly with the <a href="http://beaucollins.com/laboratory/mint-session-tracker/">Session Tracker</a> Pepper.',
		'developerName'	=> 'Andrew Sutherland',
		'developerUrl'	=> 'http://code.jalenack.com/'
	);
	var $panes		= array
	(
		'Outclicks'	=> array
		(
			'Newest Unique',
			'Most Common',
			'Most Recent'
		)
	);
	var $prefs		= array
	(
	);
	var $manifest	= array
	(
		'outclicks'	=> array
		(
			'id' => "int(11) unsigned NOT NULL auto_increment",
			'dt' => "int(10) unsigned NOT NULL default '0'",
			'ip' => "varchar(15) NOT NULL default ''",
			'to' => "varchar(255) NOT NULL default ''",
			'from' => "varchar(255) NOT NULL default ''",
			'from_title' => "varchar(255) NOT NULL default ''"
		)
	);

	/**************************************************************************
	 isCompatible()
	 **************************************************************************/
	function isCompatible()
	{
	
		if($this->Mint->version >= 124)
			$key['isCompatible'] = true;
		
		else {
			$key['isCompatible'] = false;
			$key['explanation'] = '<p>Outclicks requires Mint 1.24 or higher.</p>';
		}
		return $key;
	}
		
	/**************************************************************************
	 onRecord()
	 Operates on existing $_GET values, values generated as a result of the 
	 JavaScript output below or existing $_SERVER variables and returns an 
	 associative array with a column name as the index and the value to be 
	 stored in that column as the value.
	 **************************************************************************/
	function onRecord() { 
		return array();
	}
			
	/**************************************************************************
	 onJavaScript()
	 Returns a JavaScript string responsible for extracting the necessary values
	 (if any) necessary for this plug-in.
	 
	 Should follow format of the new SI Object()
	 **************************************************************************/
     function onJavaScript() {
            
		$js = "pepper/andrewsutherland/outclicks/script.php";
		if (file_exists($js))
            include_once ($js);

    }
    

	/**************************************************************************
	 onTidy()
	 
	 Any Pepper that adds a table to the users Mint database is responsible for
	 maintaining the size of the table. Any table starting with the Mint table 
	 prefix counts towards the total Mint database size. This method is called
	 after expired visit data is removed from the database but before Mint trims
	 it's own visit table to the size (optionally) specified by the user. This
	 method will be called once an hour.
	 
	 See Mint->_tidySave() for sample code.
	 **************************************************************************/
	
	// not meticulously checked over... suggestions invited
	
	function onTidy() 
	{
		// Safe-guard weeks against bad Mint updates, default back to 5
		$weeks = (isset($this->Mint->cfg['preferences']['expiry']))?(0+$this->Mint->cfg['preferences']['expiry']):5;
		$expiration = time() - (60 * 60 * 24 * 7 * $weeks);
		$this->query("DELETE FROM `{$this->Mint->db['tblPrefix']}outclicks` WHERE `dt` < $expiration");

		$doOptimize	= ($this->Mint->cfg['lastOptimized'] < time() - (60 * 60 * 24 * 7));
		$doCheckup	= ($this->Mint->cfg['lastChecked'] < time() - (60 * 60));
		
		if ($doOptimize)
			$this->Mint->query("OPTIMIZE TABLE `{$this->db['tblPrefix']}outclicks`");
		
		// Hourly check-up
		if ($doCheckup) 
		{	
			// Reduced optimized statements should prevent crashes but just to be sure
			$query = "CHECK TABLE {$this->db['tblPrefix']}outclicks FAST";
			if ($result = $this->query($query)) 
			{
				mysql_data_seek($result,mysql_num_rows($result)-1);
				$status = mysql_fetch_assoc($result);
				
				if ($status['Msg_type']=='error') { $this->query("REPAIR TABLE {$this->db['tblPrefix']}outclicks"); }
			}
		}
	}

	
	/**************************************************************************
	 onDisplay()
	 Produces what the user sees when they are browsing their Mint install
	 
	 Returns an associative array of associative arrays that contain an HTML 
	 string for each display unit this plug-in is responsible for, plus a formal 
	 display name and the containing element's id (for ordering in preferences 
	 and anchor linking)
	 
	 **************************************************************************/
	function onDisplay($pane,$tab,$column='',$sort='') {
		$html = '';
		
		switch($pane) {
		/* Visitors ***********************************************************/
			case 'Outclicks': 
				switch($tab) {
				
				/* Newest Unique ************************************************/
					case 'Newest Unique':
						$html .= $this->getHTML_OutclicksNewUnique();
						break;
						
				/* Most Recent ************************************************/
					case 'Most Recent':
						$html .= $this->getHTML_OutclicksRecent();
						break;

				/* Most Common ************************************************/
					case 'Most Common':
						$html .= $this->getHTML_OutclicksCommon();
						break;
					}
				break;
			}
		return $html;
		}
	
	/**************************************************************************
	 onWidget()
	 
	 **************************************************************************/
	function onWidget() { }
	
	/**************************************************************************
	 onDisplayPreferences()
	 
	 Should return an assoicative array (indexed by pane name) that contains the
	 HTML contents of that pane's preference. Preferences used by all panes in 
	 this plug-in should be indexed as 'global'. Any pane that isn't represeneted
	 by an index in the return array will simply display the string "This pane
	 does not have any preferences" (or similar).
	 
	 **************************************************************************/
	function onDisplayPreferences() { }
	
	/**************************************************************************
	 onSavePreferences()
	 
	 **************************************************************************/
	function onSavePreferences() { }
	
	/**************************************************************************
	 onCustom()

	 **************************************************************************/
	function onCustom() {
		return;	
	}
	
	
	/**************************************************************************
	 
	 **************************************************************************/
	function getHTML_OutclicksRecent() {
	
		header("Content-type: text/html; charset=UTF-8"); 

		$html = '';
		
		$tableData['table'] = array('id'=>'','class'=>'');
		$tableData['thead'] = array(
			// display name, CSS class(es) for each column
			array('value'=>'Destination','class'=>'stacked-rows'),

			array('value'=>'When','class'=>'stacked-rows')

			);
			
		$query = "SELECT *
					FROM `{$this->Mint->db['tblPrefix']}outclicks` 
					ORDER BY `dt` DESC 
					LIMIT 0,{$this->Mint->cfg['preferences']['rows']}";
		if ($result = $this->query($query)) {
			while ($r = mysql_fetch_array($result)) {
				
				$dt = $this->Mint->formatDateTimeRelative($r['dt']);
				
				$to = $this->Mint->abbr(stripslashes($r['to']));
				$title = $this->Mint->abbr(stripslashes($r['from_title']));
				
				$tableData['tbody'][] = array(
					"<a href=\"{$r['to']}\">$to</a>".(($this->Mint->cfg['preferences']['secondary'])?"
						<br /><span>From <a href=\"{$r['from']}\">$title</a></span>":''),
					$dt
					);
				}
			}
			
		$html = $this->Mint->generateTable($tableData);
		return $html;
		}


	/**************************************************************************

	 **************************************************************************/
	function getHTML_OutclicksNewUnique() {

		header("Content-type: text/html; charset=UTF-8"); 

		$html = '';

		$tableData['table'] = array('id'=>'','class'=>'');
		$tableData['thead'] = array(
			// display name, CSS class(es) for each column
			array('value'=>'Destination','class'=>'stacked-rows'),

			array('value'=>'When','class'=>'stacked-rows')

			);

		$query = "SELECT *
					FROM `{$this->Mint->db['tblPrefix']}outclicks`
					GROUP BY `to` 
					ORDER BY `dt` DESC 
					LIMIT 0,{$this->Mint->cfg['preferences']['rows']}";
		if ($result = mysql_query($query)) {
			while ($r = mysql_fetch_array($result)) {

				$dt = $this->Mint->formatDateTimeRelative($r['dt']);

				$to = $this->Mint->abbr(stripslashes($r['to']));
				$title = $this->Mint->abbr(stripslashes($r['from_title']));

				$tableData['tbody'][] = array(
					"<a href=\"{$r['to']}\">$to</a>".(($this->Mint->cfg['preferences']['secondary'])?"
						<br /><span>From <a href=\"{$r['from']}\">$title</a></span>":''),
					$dt
					);
				}
			}

		$html = $this->Mint->generateTable($tableData);
		return $html;
		}


	/**************************************************************************
	 
	 **************************************************************************/
	function getHTML_OutclicksCommon() {
		
		header("Content-type: text/html; charset=UTF-8"); 
		
		$html = '';

		$tableData['table'] = array('id'=>'','class'=>'');
		$tableData['thead'] = array(
			// display name, CSS class(es) for each column
			array('value'=>'Destination','class'=>'stacked-rows'),

			array('value'=>'Clicks','class'=>'')

			);

		$query = "SELECT `id`, `dt`, `ip`, `to`, `from`, COUNT(`to`) as `total`, `from_title`
					FROM `{$this->Mint->db['tblPrefix']}outclicks` 
					GROUP BY `to`
					ORDER BY `total` DESC, `dt` DESC
					LIMIT 0,{$this->Mint->cfg['preferences']['rows']}";
		if ($result = mysql_query($query)) {
			while ($r = mysql_fetch_array($result)) {

				$total = $r['total'];

				$to = $this->Mint->abbr(stripslashes($r['to']));
				$title = $this->Mint->abbr(stripslashes($r['from_title']));

				$tableData['tbody'][] = array(
					"<a href=\"{$r['to']}\">$to</a>".(($this->Mint->cfg['preferences']['secondary'])?"
						<br /><span>From <a href=\"{$r['from']}\">$title</a></span>":''),
					$total
					);
				}
			}

		$html = $this->Mint->generateTable($tableData);
		return $html;
		}
		
	}
?>
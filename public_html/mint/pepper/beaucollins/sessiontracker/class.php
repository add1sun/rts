<?php
/******************************************************************************
 Pepper

 Developer      : Beau Collins
 Plug-in Name   : Session Tracker

 http://beaucollins.com/
 
 Copyright 2005 Beau Collins. This package cannot be redistributed without
 permission from http://beaucollins.com/

 You may contact the author at beaucollins@gmail.com
******************************************************************************/
$installPepper = "RHC3_SessionTracker";
 
class RHC3_SessionTracker extends Pepper{
	var $version    = 96;
	var $info       = array(
		'pepperName'    => 'Session Tracker',
		'pepperUrl'     => 'http://beaucollins.com/laboratory/mint-session-tracker/',
		'pepperDesc'    => 'Watch your visitors as they navigate your site.',
		'developerName' => 'Beau Collins',
		'developerUrl'  => 'http://beaucollins.com/'
	);
	var $panes = array (
   		'Session Tracker' => array (
        	'Active',
        	'New',
        	)
        );
	var $prefs = array(
		//user defined session timeout in minutes
		'sessiontimeout' => '5',
		'usesessiontimeout' => true,
		'sessionlabel' => 'SI_Default',
		'sessionicon' => 'red'
	);
	var $manifest = array(
		'visit' => array(
			'rhc3_sessiontracker_key' => 'VARCHAR(32)'
		)
	);
	var $data = array(
		'sessionkeytoken' => 'RHC3_MintSessionTrackerKey'
	);
	
	/********************************************************************
	update()
	- I'm an idiot so I need to add my token to the table field name as
	  well as the cookie, this should prevent us from losing all of our
	  session data.
	- Looks like it works! Kind of, err!
	- need to add update to set usetimeout preference to true
	********************************************************************/
	
	function update(){
		//change the manifest to reflect changes to db doesn't work
		$pepperID = $this->Mint->cfg['pepperLookUp']['RHC3_SessionTracker'];
		if($this->Mint->cfg['pepperShaker'][$pepperID]['version'] <= 3){
			$this->manifest = array(
				'visit' => array(
					'rhc3_sessiontracker_key' => 'VARCHAR (32)'
				)
			);
			//update the data property
			$this->data['sessionkeytoken'] = 'RHC3_MintSessionTrackerKey';


			//check if we have the old db field
			$describe = "DESCRIBE {$this->Mint->db['tblPrefix']}visit `sessiontracker_key`";
			$test = $this->query($describe);
			if(mysql_num_rows($test) > 0){
				$alter = "ALTER TABLE {$this->Mint->db['tblPrefix']}visit CHANGE `sessiontracker_key` `rhc3_sessiontracker_key` VARCHAR(32)";
				$this->query($alter);
			}

			foreach($this->Mint->cfg['manifest']['visit'] as $field => $pepper){
				if($field != 'sessiontracker_key'){
					$update[$field] = $pepper; 
				}else{
					$update['rhc3_sessiontracker_key'] = $pepper;
				}
			}
			$this->Mint->cfg['manifest']['visit'] = $update;				
			$this->Mint->cfg['pepperShaker'][$pepperID]['version'] = 4;
		}
		
		//make the necessary fixes for those who are for some reason on an old Beta
		
		if($this->Mint->cfg['pepperShaker'][$pepperID]['version'] < 90){
			//anything past version 3 just make sure session_tracker is not in the manifest
			foreach($this->Mint->cfg['manifest']['visit'] as $field => $pepper){
				if($field != 'sessiontracker_key'){
					$update[$field] = $pepper; 
				}
			}
			$this->Mint->cfg['manifest']['visit'] = $update;			
			$this->Mint->cfg['pepperShaker'][$pepperID]['version'] = 90;
		}
		
		//set default preferences for those who are upgrading from 90
		
		if($this->Mint->cfg['pepperShaker'][$pepperID]['version'] < 91){
			$this->prefs['usesessiontimeout'] = true;
			if(!$this->prefs['sessionlabel']) $this->prefs['sessionlabel'] = 'SI_Default';
			if(!$this->prefs['sessionicon']) $this->prefs['sessionicon'] = 'red';
			$this->Mint->cfg['pepperShaker'][$pepperID]['version'] = $this->version;
		}
		
		if($this->Mint->cfg['pepperShaker'][$pepperID]['version'] >= 91){
			$this->Mint->cfg['pepperShaker'][$pepperID]['version'] = $this->version;
		}		

		return true;
	}
	
	/********************************************************************
	onDisplayPreferences()
	-currently only one preference, sessiontimeout
	********************************************************************/
	
	function onDisplayPreferences(){
		if($this->prefs['usesessiontimeout']) $checked = ' checked="checked" ';
		$preferences['Session Timeout'] = <<<HERE
<!--<script type="text/javascript">
	function RHC3_toggle() {
		the_cb = document.getElementById('usesessiontimeout');
		the_tr = document.getElementById('rhc3togglerow');
		if(the_cb.checked){
			the_tr.style.display = 'table-row';
		}else{
			the_tr.style.display = 'none';
		}
	}
</script>-->
<table class="snug">
	<!--<tr>
		<td><input type="checkbox" id="usesessiontimeout" name="usesessiontimeout" {$checked} value="use" /> <label for="usesessiontimeout">Enable Session Timeout</label></td>
	</tr>-->
	<tr id="rhc3togglerow">
		<th scope="row"><label for="sessiontimeout">Session Timeout in Minutes</label></th>
		<td><span><input type="text" id="sessiontimeout" name="sessiontimeout" value="{$this->prefs['sessiontimeout']}" size="3" /></span></td>
	</tr>
</table>
<!--<script type="text/javascript">RHC3_toggle();</script>-->
HERE;

	$label_options = $this->sessionLabelOptions();
	foreach($label_options as $option=>$props){
		$selected = ($props['selected']) ? ' checked="checked" ' : '' ;
		$disabled = (!$props['active']) ?  ' disabled="disabled" ' : '' ;
		$example = ($props['example']) ? "<br/>Example: {$props['example']}" : '';
		$secondary = (!$props['active']) ? "<br/><strong>Required:</strong> {$props['pepperName']} must be installed" : $example;
		$display .= "<tr>
					<th scope='row' style='vertical-align: top;'><input type='radio' name='sessionlabel' id='RHC3_label$option' value='$option' $selected $disabled /></th>
					<td><label for='RHC3_label$option'><strong>{$props['description']}</strong> ({$props['pepperName']})$secondary</label></td>
					</tr>\n";
	}
	$preferences['Session Label'] = "<table>\n$display\n</table>";
	
	$icons = array('red','green','blue','yellow','orange','purple','black','none');
	$imgpath = $this->imgPath();
	foreach($icons as $icon){
		$selected = ($this->prefs['sessionicon'] == $icon) ? ' checked="checked" ' : '' ;
		$img = "$imgpath$icon.png";
		$back = ($icon == 'none') ? 'noneback.png' : 'iconback.png';
		$iconoptions .= "\n\t<td><input type='image' style='background-image: url(\"{$imgpath}{$back}\"); padding: 4px; margin: 3px; background-repeat: no-repeat;' name='rhc3_st_icon$icon' value='$icon' src='$img' alt='$icon' onclick='rhc3_icon(\"$icon\"); this.blur(); return false;' /></td>";
	}
	$preferences['Active Session Icon'] = "
		<input type='hidden' name='sessionicon' id='rhc3sessionicon' value='{$this->prefs['sessionicon']}'/>
		<table class='snug'>
		<tr id='rhc3sessionicons'>
			$iconoptions
		</tr>
		</table>
	";
	$preferences['Active Session Icon'] .= <<<HERE
	<script>
		function rhc3_icon(setcolor){
			var tr = document.getElementById('rhc3sessionicons');
			var btns = tr.getElementsByTagName('input');
			var ico = document.getElementById('rhc3sessionicon');
			ico.value = setcolor;
			for(var i=0;i<btns.length;i++){
				if(btns[i].value == ico.value){
					btns[i].style.backgroundPosition = 'center center';
					if(btns[i].value == 'none') btns[i].style.backgroundImage = 'url({$imgpath}noneback.png)';
				}else{
					btns[i].style.backgroundPosition = '-1000px -1000px';
					if(btns[i].value == 'none') btns[i].style.backgroundImage = 'none';
				}
			}
			return false;
		}
		rhc3_icon('{$this->prefs['sessionicon']}');
	</script>
HERE;
	return $preferences;
	}
	
	
	function onJavaScript(){
		$js = 'pepper/beaucollins/sessiontracker/script.js';
		if(file_exists($js)){
			include($js);
		}
		return;
	}
	
	/********************************************************************
	onSavePreferences()
	- Saves the sessiontieout preference
	*********************************************************************/
	function onSavePreferences() 
	{
		$this->prefs['sessiontimeout']	= $this->escapeSQL($_POST['sessiontimeout']);
		$this->prefs['sessionlabel']		= $this->escapeSQL($_POST['sessionlabel']);
		$this->prefs['sessionicon'] 		= $this->escapeSQL($_POST['sessionicon']);
		//if($usetimeout = $this->escapeSQL($_POST['usesessiontimeout'])){
			$this->prefs['usesessiontimeout'] = true;
		//}else{
		//	$this->prefs['usesessiontimeout'] = false;
		//}
	}

	/********************************************************************
	isCompatible()
	- One requirement currently: Mint >= 1.2
	*********************************************************************/

	function isCompatible(){
		if($this->Mint->version >= 120){
			$key['isCompatible'] = true;
			$key['explanation'] = '<p>Session Tracker plays nicely with <a href="http://orderedlist.com/download/" title="Go to Orderedlist.com to get Download Counter">Download Counter</a> and <a href="http://code.jalenack.com/archives/outclicks-pepper/">Outclicks</a>.</p>';
		}else{
			$key['isCompatible'] = false;
			$key['explanation'] = '<p>Session Tracker requires Mint 1.2 or higher.</p>';
		}
		return $key;
	}
	
	/********************************************************************
	onRecord()
	- Checks for sessionkey, if no session key, set new session key
	- Plan for javascript to set session key to true so we can tell wether or
	  not the browser accepts cookies
	*********************************************************************/
	
	function onRecord(){
		$sessionkey = $_COOKIE[$this->data['sessionkeytoken']];
		if($this->escapeSQL($_GET['eatscookies']) == 'yes' || $sessionkey){
			if($this->checkTimedOut(false, $sessionkey)){
				$domain = '.'.$this->Mint->cfg['siteDomains'];
				$sessionkey = $this->randomKeyGenerator();
				setcookie($this->data['sessionkeytoken'],$sessionkey,-1,'/', $domain);
			}
			if($sessionkey) return array('rhc3_sessiontracker_key'=>$sessionkey);
		}
		return array();
	}
	
	/********************************************************************
	onDisplay()
	- New: display sessions as they are created
	- Active display sessions by idle time
	********************************************************************/

	function onDisplay($pane, $tab, $column = '', $sort = ''){
		$html = '';
		if($pane == 'Session Tracker'){
			switch($tab){
				case "New":
					$html .= $this->getHTML_NewSessions();
				break;
				case 'Active':
					$html .= $this->getHTML_ActiveSessions();
				break;
			}
		}
		return $html;
	}
	
	/********************************************************************
	onCustom()
	- Only one custom, getting table rows of sessions
	*********************************************************************/
	
	function onCustom(){
		if(
			isset($_POST['action'])	&&
			$_POST['action'] == 'showsession' &&
			isset($_POST['sessionkey'])
		){
			echo $this->getHTML_Session($_POST['sessionkey']);
		}

	}
	
	/********************************************************************
	onOutlick()
	- For sending the Session Tracker key to the outclicks plug-in
	for recording into the outclicks table
	*********************************************************************/
	function onOutclick(){
		if($this->checkOutclicksKey()){
			$key_pair = $this->onRecord();//does what it needs to do
			$q['fields'] = ', `rhc3_sessiontracker_key`';
			$q['values'] = ', \''.$key_pair['rhc3_sessiontracker_key'].'\'';
			return $q;
		}else{
			return false;
		}
	}
		
	/********************************************************************
	getHTML_ActiveSession()
	- Called for Active view, returns a table of data with sessions ordered
	  by idle time
	*********************************************************************/

	function getHTML_ActiveSessions(){
		$html = '';
		$activecount = $this->getActiveSessionCount();
		$tableData['hasFolders'] = true;
		$tableData['table'] = array('id'=>'','class'=>'folder');
		$tableData['thead'] = array
		(
			array('value'=>"Sessions ({$activecount} active)",'class'=>''),
			array('value'=>'Length','class'=>'')
		);
		
		$sessions = $this->getSessionsData('active');
		$outclick_lookup = $this->getSessionsOutclicksData($sessions);
		if(is_array($sessions)){
			foreach($sessions as $s){
				$outclicks = $outclick_lookup[$s['rhc3_sessiontracker_key']];
				$sessionlabel = $this->formatSessionLabel($s);	
				if($s['views'] > 1 || $outclicks){
					$end = ($outclicks['last_clicktime'] < $s['end']) ? $s['end'] : $outclicks['last_clicktime'];
					$duration = $this->formatTimeDuration($end-$s['start']);
				}elseif($this->checkTimedOut($s['end'])){
					$duration = '<em>timed out</em>';
				}else{
					$duration = '<em>viewing</em>';
				}
				$tableData['tbody'][] = array(
					$sessionlabel,
					$duration,
					'folderargs' => array(
						'action'	=> 'showsession',
						'sessionkey'	=> $s['rhc3_sessiontracker_key']
					)
				);
			}
		}
		
		$html .= $this->Mint->generateTable($tableData);
		return $html;
	}
	
	/********************************************************************
	getHTML_NewSession()
	- Called for New view, shows sessions as they are created
	*********************************************************************/
	function getHTML_NewSessions(){
		$html = '';
		$activecount = $this->getActiveSessionCount();
		$tableData['hasFolders'] = true;
		$tableData['table'] = array('id'=>'','class'=>'folder');
		$tableData['thead'] = array
		(
			array('value'=>"Sessions ({$activecount} active)",'class'=>''),
			array('value'=>'Began','class'=>'')
		);
		
		$sessions = $this->getSessionsData('new');
		$outclick_lookup = $this->getSessionsOutclicksData($sessions);
		if(is_array($sessions)){
			foreach($sessions as $s){
				$outclicks = $outclick_lookup[$s['rhc3_sessiontracker_key']];
				$sessionlabel = $this->formatSessionLabel($s);	
				$timepast = $this->Mint->formatDateTimeRelative($s['start']);
				$tableData['tbody'][] = array(
					$sessionlabel,
					$timepast,
					'folderargs' => array(
						'action'	=> 'showsession',
						'sessionkey'	=> $s['rhc3_sessiontracker_key']
					)
				);
			}
		}
		$html .= $this->Mint->generateTable($tableData);
		return $html;
	}
	
	/********************************************************************
	getHTML_Session()
	- Accepts one argument, the session key
	- Returns table rows of session page views
	*********************************************************************/
	function getHTML_Session($session_key){
		$records = $this->mergeOutclicks($this->getSingleSessionData($session_key), $this->getSingleSessionOutclicksData($session_key));
		//$records = $this->getSingleSessionData($session_key);
		$recnum = 0;
		$imgpath = $this->imgPath();
		for($i=0;$i<count($records);$i++){
			if(!$this->isDownload($records[$i]) && !$this->isOutclick($records[$i])){
				$recnum ++;
				$lbl = $recnum.' - ';
				$recordtype = 'visit';
			}elseif($this->isDownload($records[$i])){
				$recnum++;
				$lbl = $recnum.' - ';
				$recordtype = 'download';
			}elseif($this->isOutclick($records[$i])){
				$recordtype = 'outclick';
			}
			$lbl .= $this->formatPageLabel($records[$i]);
			if($recordtype == 'visit' && $records[$i+1]){//if it's a visit and it's not the last record
				$offset = $this->endRecordOffset($records, $i);
				$start = $records[$i]['dt'];
				$end = $records[$i+$offset]['dt'];
				$duration = $this->formatTimeDuration($end-$start);
			}elseif($recordtype == 'visit'){//it's the last visit of the session
				$duration = ($this->checkTimedOut($records[$i]['dt'])) ? '<em>timed out</em>' : '<em>viewing</em>' ;
			}elseif($recordtype == 'download'){
				$duration = "<img style='float: right; margin-top: -2px;' src='{$imgpath}download.png' alt='download'/>";
			}elseif($recordtype == 'outclick'){
				$duration = "<img style='float: right;' src='{$imgpath}outclick.png' alt='outclick' />";
			}
			$tableData['tbody'][] = array(
				$lbl,
				$duration
			);
			unset($lbl);
			unset($duration);
			unset($recordtype);
		}
		$html = $this->Mint->generateTableRows($tableData);
		return $html;
	}
	
	/*******************************************************************
	getActiveSessionCount()
	- gets the data for the session view
	*******************************************************************/
	function getActiveSessionCount(){
		$tko = time();
		$threshold = $this->prefs['sessiontimeout'] * 60;
		$data = $this->query("SELECT COUNT(DISTINCT `rhc3_sessiontracker_key`)
				FROM {$this->Mint->db['tblPrefix']}visit
				WHERE `rhc3_sessiontracker_key` IS NOT NULL
				GROUP BY `rhc3_sessiontracker_key`
				HAVING ($tko - MAX(`dt`)) < $threshold");
		return mysql_num_rows($data);
	}
		
	/*******************************************************************
	getSessionsData()
	- gets the data for the session view
	*******************************************************************/
	
	function getSessionsData($view = 'active'){
		$orderfield = ($view == 'active') ? 'end' : 'start' ;//end = last page view, start = created
		
		$query = "SELECT *, COUNT(`id`) AS `views`, MIN(`dt`) AS `start`, MAX(`dt`) AS `end`
			FROM {$this->Mint->db['tblPrefix']}visit
			WHERE `rhc3_sessiontracker_key` IS NOT NULL
			GROUP BY `rhc3_sessiontracker_key`
			ORDER BY `$orderfield` DESC
			LIMIT 0, {$this->Mint->cfg['preferences']['rows']}";
			
		if($data = $this->query($query)){
			$sessions = array();
			while($r = mysql_fetch_assoc($data)){
				$sessions[] = $r;
			}
			return $sessions;			
		}else{
			return false;
		}
	}
				
	/********************************************************************
	getSingleSessionData()
	- Accepts session key
	- Queries for all of the page views for that session
	- used by getHTML_Session() method
	*********************************************************************/
	function getSingleSessionData($session_key){
		$query = "SELECT * FROM {$this->Mint->db['tblPrefix']}visit WHERE `rhc3_sessiontracker_key` = '$session_key' ORDER BY `dt` ASC";
		$data = $this->query($query);
		$results = array();
		while($r = mysql_fetch_assoc($data)){
			$results[] = $r;
		}
		return $results;
	}
	
	/*******************************************************************
	getSessionsOutclicksData()
	- Grab the relevant outlicks data based on getSessionData query
	*******************************************************************/
	function getSessionsOutclicksData($sessiondata){
		$keys = array();
		if($sessiondata && $this->checkOutclicksKey()){
			foreach($sessiondata as $s){
				$keys[] = $s['rhc3_sessiontracker_key'];
			}
			$where = implode("' OR `rhc3_sessiontracker_key` = '",$keys);
			$data = $this->query("SELECT `rhc3_sessiontracker_key`, MAX(`dt`) AS `last_clicktime`, COUNT(`id`) AS `total` 
				FROM {$this->Mint->db['tblPrefix']}outclicks
				WHERE `rhc3_sessiontracker_key` = '$where'
				GROUP BY `rhc3_sessiontracker_key`");
			$outclicks = array();
			while($c = mysql_fetch_assoc($data)){
				$outclicks[$c['rhc3_sessiontracker_key']]['total'] = $c['total'];
				$outclicks[$c['rhc3_sessiontracker_key']]['last_clicktime'] = $c['last_clicktime'];
			}
			return $outclicks;
		}
	}
	/*******************************************************************
	getSingleSessionOutclicksData()
	- Try to find outlick data related to session key
	- Requires Outclicks 1.11 or higher
	*******************************************************************/
	function getSingleSessionOutclicksData($session_key){
		$results = array();
		if($this->checkOutclicksKey()){
			$query = "SELECT * FROM {$this->Mint->db['tblPrefix']}outclicks
					WHERE `rhc3_sessiontracker_key` = '$session_key'
					ORDER BY `dt` ASC";
			$data = $this->query($query);
			while($r = mysql_fetch_assoc($data)){
				$results[] = $r;
			}
		}
		return $results;
	}

	/********************************************************************
	formatTimeDuration()
	- Receives number of seconds
	- Returns length of time in readable format "1 min, 24 secs"
	- Pretty good for now
	*********************************************************************/
	function formatTimeDuration($seconds){
		$idle_secs = $seconds;
		$hours_idle = floor($seconds/3600);
		$idle_secs = $idle_secs - ($hours_idle * 3600);
		$minutes_idle = floor($idle_secs/60);
		$seconds_idle = $idle_secs - ($minutes_idle * 60);
		
		if($hours_idle > 0){
			$value = $hours_idle;
			$label = ($value == 1) ? "$value hour" : "$value hours";
			if($minutes_idle !=0) $label .= ($minutes_idle > 1) ? ", $minutes_idle mins" : ", 1 min";
		}elseif($minutes_idle > 0){
			$value = $minutes_idle;
			$label = ($value == 1) ? "$value min" : "$value mins";
			if($seconds_idle > 0) $label .= ($seconds_idle == 1) ? ', 1 sec' : ", $seconds_idle secs";
		}elseif($seconds_idle >= 1){
			$value = $seconds_idle;
			$label = ($value == 1) ? "$value sec" : "$value secs";
		}else{
			$label = "&lt; 1 sec";
		}
		//$label = "$hours_idle";
		return $label;
	}
	
	/********************************************************************
	formatSessionLabel()
	- Receives array of session data depending on if user agent is installed
	- Detects downloads, thank you Steve Smith!
	- detect outlicks, working on that with Andrew Sutherland
	*********************************************************************/

	function formatSessionLabel($s){
		$lbl = '<strong>'.$s['views'].(($s['views'] == 1) ? ' hit ' : ' hits ').'</strong>';
		
		if($s['xxx_hostname'] && $this->prefs['sessionlabel'] == 'NK_XXXStrongMint'){
			$lbl .= ' from <strong>'.$this->Mint->abbr($s['xxx_hostname'], 25, true).'</strong>';
		}elseif($s['browser_family'] && $this->prefs['sessionlabel'] == 'SI_UserAgent'){
			$lbl .= ' with <strong>'.$s['browser_family'].'</strong> '.$s['browser_version'];//use browser family and version
		}else{
			$lbl .= ' from <strong>'.long2ip($s['ip_long']).'</strong>';
		}
		if(!$this->checkTimedOut($s['end']) && $this->prefs['sessionicon'] != 'none'){
			$activeimg = $this->imgPath().$this->prefs['sessionicon'].'.png';
			$lbl.= " <img src='$activeimg' alt='active' style='margin: 3px 0 -3px 1px;'/>"; 
		}
		if($this->Mint->cfg['preferences']['secondary'] && $s['referer'] && $s['referer_is_local'] != 1){
			if(!$s['search_terms']){
				$ref = $this->Mint->abbr($s['referer'], 35);
				$lbl .= "<br/><span>From <a href='$s[referer]'>$ref</a></span>";
			}else{
				$ref = parse_url($s['referer']);
				$ref = $ref['host'];
				$lbl .= "<br/><span>Search <a href='$s[referer]'>$ref</a> ($s[search_terms])</span>";
			}
		}
		
		return $lbl;
	}
	
	/********************************************************************
	formatPageLabel()
	- Kind of the same as formatSessionLabel, put for individual page
	  views
	*********************************************************************/
	function formatPageLabel($p){

		if(!$this->isDownload($p) && !$this->isOutclick($p)){		
			$page_title = $this->Mint->abbr(($p['resource_title']) ? $p['resource_title'] : $p['resource'], 40);
			$lbl = "<a href=\"$p[resource]\">$page_title</a>";		
			if($this->Mint->cfg['preferences']['secondary'] && $p['referer_is_local'] != 1 && $p['referer']){
				if(!$p['search_terms']){
					$ref = $this->Mint->abbr($p['referer'], 35);
					$lbl .= "<br/><span>From <a href='$p[referer]'>$ref</a></span>";
				}else{
					$ref = parse_url($s['referer']);
					$ref = $ref['host'];
					$lbl .= "<br/><span>Search <a href='$p[referer]'>$ref</a> ($p[search_terms])</span>";
				}
			}
		}elseif($this->isDownload($p)){
			$lbl = '<strong>Download:</strong> <abbr title="'.$p['resource'].'">'.basename($p['resource']).'</abbr>';
		}else{
			//outclick
			$lbl = '<strong>Outclick: </strong> <a href="'.$p['to'].'">'.$this->Mint->abbr($p['to'], 30).'</a>';
		}
		return $lbl;
	}
	
	/********************************************************************
	endRecordOffset()
	- Give it a record array and returns offset for
	*********************************************************************/
	
	function endRecordOffset($records, $i){
		$offset = 1;
		$i++;//start at the next record
		while($records[$i]){//while we still have records
			if(($this->isDownload($records[$i]) || $this->isOutclick($records[$i])) && $records[$i+1]){
				$offset ++;//if it's a download or outclick, increase the offset
			}else{//if it's a regular record, stop the while loop
				break;
			}
			$i++;
		}
		return $offset;
	}
	
	/********************************************************************
	checkTimedOut()
	- Accepts dt and checks agains sessiontimeout
	- returns true if idle time exceeds sessiontimeout preference or false
	  if not
	*********************************************************************/
	function checkTimedOut($lastviewtime, $sessionkey=null){
		if(($lastviewtime || $sessionkey) && !$this->prefs['usesessiontimeout']){//if we're not using timeout
			return false;		
		}elseif($sessionkey && !$lastviewtime){
			$query = "SELECT `dt` FROM {$this->Mint->db['tblPrefix']}visit WHERE `rhc3_sessiontracker_key` = '$sessionkey' ORDER BY `dt` DESC LIMIT 1";
			if($data = $this->query($query)){
				$r = mysql_fetch_assoc($data);
				$lastviewtime = $r['dt'];
			}
		}elseif(!$sessionkey && !$lastviewtime){
			return true;
		}
		
		if($lastviewtime){
			if(time() - $lastviewtime < $this->prefs['sessiontimeout']*60){
				return false;
			}else{
				return true;
			}
		}else{
			return true;
		}
	}

	/********************************************************************
	randomKeyGenerator()
	- Uses remote ip, time, and a random number to generate the session key
	*********************************************************************/
	function randomKeyGenerator(){
		$ip = $_SERVER['REMOTE_ADDR'];
		$time = time();
		$random = rand();
		$key = $ip.$time.$random;
		return md5($key);
	}
		
	/********************************************************************
	isDownload()
	- Checks if page record is actually a download record
	- Hopefully we can update this to use `is_download` field
	*********************************************************************/

	function isDownload($p){
		if(strpos($p['resource_title'], 'Download: ') === 0) return true;
		return false;
	}
	
	/********************************************************************
	isOutclick()
	- like isDownload() except for Outclicks
	*********************************************************************/

	function isOutclick($p){
		if($p['to']) return true;
		return false;
	}
	
	/********************************************************************
	imgPath()
	- returns url path to images
	*********************************************************************/

	function imgPath(){
		$pepperID = $this->Mint->cfg['pepperLookUp']['RHC3_SessionTracker'];
		$pepperDat = $this->Mint->cfg['pepperShaker'][$pepperID];
		$path = $this->Mint->cfg['installFull'].'/'.dirname($pepperDat['src']).'/images/';
		return $path;
	}
	
	/********************************************************************
	mergeOutclicks()
	- try to get outclicks and pages in one array
	*********************************************************************/
	
	function mergeOutclicks($hits, $outclicks){
		//we're going to loop through all of them
		$total = count($hits) + count($outclicks);
		$merged = array();
		while($hits || $outclicks){
			//we're looping through each array every unless we shift the array? because they're already sorted!
			if($hits && $outclicks){
				//need to figure out which one has the smallest datetime
				if($hits[0]['dt'] <= $outclicks[0]['dt']){
					//shift the hits
					$merged[] = array_shift($hits);
				}else{
					$merged[] = array_shift($outclicks);//shift the outclicks
				}
				
			}elseif($hits){
				//just grab the hit and shift it
				$merged[] = array_shift($hits);
			}elseif($outclicks){
				//just grab the outlicks and shift it
				$merged[] = array_shift($outclicks);
			}
		}
		
		return $merged;
		
	}
	
	/********************************************************************
	checkOutclicksKey()
	- called by onOutclick to make sure the key is in the table and that
	they have the right version of outclicks installed
	*********************************************************************/

	function checkOutclicksKey(){
		//first query if we have the key stored in the AS_Outclicks Manifest
		if($outclicks = $this->Mint->getPepperByClassName('AS_Outclicks')){
			if($outclicks->version >= 111){
				$st_key = $this->query("DESCRIBE {$this->Mint->db['tblPrefix']}outclicks `rhc3_sessiontracker_key`");
				if(mysql_num_rows($st_key) == 0){
					if($this->query("ALTER TABLE {$this->Mint->db['tblPrefix']}outclicks ADD `rhc3_sessiontracker_key` VARCHAR(32)")){
						return true;
					}
				}else{
					return true;
				}
			}
		}
		return false;
	}

	/********************************************************************
	sessionLabelOptions()
	- Right now three that I can think of, default (IP), UserAgent, and
	  XXX Strong Mint.  Maybe Geo Mint?
	*********************************************************************/
	
	function sessionLabelOptions(){
		$pref = $this->prefs['sessionlabel'];
		$labels['SI_Default'] = array('description'=>'Remote IP', 'example' => '<strong>192.168.10.1</strong>', 'pepperName' => 'Default');
		$labels['SI_UserAgent'] = array('description'=>'Browser Family','example'=>'<strong>Firefox</strong> 1.5', 'pepperName' => 'UserAgent 007');
		$labels['NK_XXXStrongMint'] = array('description'=>'Remote Host Name','example'=>'<strong>'.$this->Mint->abbr('bln1.verwalt-berlin.de', 20, true).'</strong>', 'pepperName' => 'XXX Strong Mint');
		foreach($labels as $pepperclass => $props){
			if($pepper = $this->Mint->getPepperByClassName($pepperclass)){
				$labels[$pepperclass]['active'] = true;
				$labels[$pepperclass]['pepperName'] = $pepper->info['pepperName'];
				if($pref == $pepperclass) $labels[$pepperclass]['selected'] = true;
			}else{
				$labels[$pepperclass]['active'] = false;
				$labels[$pepperclass]['selected'] = false;
				if($pref == $pepperclass) $labels['SI_Default']['selected'] = true;
			}
		}
		return $labels;
	}
	
}
 
 ?>
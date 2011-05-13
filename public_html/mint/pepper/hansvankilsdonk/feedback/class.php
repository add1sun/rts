<?

/****************************************************

Feedback v0.1 - (c) Hans van Kilsdonk
Website: http://mint.ufx.nl
E-mail: mail@mint.ufx.nl
Licensed under the GPL

How to install:
copy the directory 'feedback' to:
'mint/pepper/hansvankilsdonk'

Login into Mint and go to 'preferences' => 'install'.
Select Feedback to install it.

Next, read the manual in the file 'tracker.php' or
'README.txt'.

Feel free to change the script but please let me know
if you do :)

*****************************************************/

if (!defined('MINT')) { header('Location:/'); }; // Prevent viewing this file directly

$installPepper = "uFx_Feedback";

class uFx_Feedback extends Pepper {
	var $version = 10; 
	var $info = array(
		'pepperName' => 'Feedback',
		'pepperUrl' => 'http://mint.ufx.nl',
		'pepperDesc' => 'Feedback for Mint 1.2x & Mint 2.0 is a Pepper which keeps track of your (RSS/Atom) feeds. By using a seperate "tracker" you can see how many hits, subscribers and views your feed has. Check the README.txt for usage and installation instructions.',
		'developerName' => 'Hans van Kilsdonk',
		'developerUrl' => 'http://mint.ufx.nl'
	);
	var $panes = array(
		'Feedback' => array(
			'Daily',
			'Monthly',
			'Subscribers',
			'Hot items',
			'Sparks'
		)	
	);

	var $manifest = array(
		'uFx_Feedback' => array(
			'id' => "INT(11) auto_increment",
			'dt' => "INT(10)",
			'ip_long' => "INT(10)",
			'hostinfo' => "VARCHAR(255) NOT NULL",
			'reader' => "VARCHAR(200) NOT NULL",
			'resource' => "VARCHAR(255) NOT NULL",
			'resource_title' => "VARCHAR(255) NOT NULL",
			'feed_name' => "VARCHAR(255) NOT NULL",
			'click' => "CHAR( 1 ) NOT NULL"
		),
		'uFx_Feedback_archive' => array(
			'id' => 'INT(11) auto_increment',
			'month' => 'VARCHAR(25) NOT NULL',
			'views' => 'INT(11) NOT NULL',
			'subscribers' => 'INT(11) NOT NULL',
			'clicks' => 'INT(11) NOT NULL',
			'feed_name' => 'VARCHAR(100) NOT NULL'
		)
	);

	var $prefs = array (
		'uFx_subscribers' => '25',
		'uFx_hotitems' => '25',
		'uFx_onlyclicks' => '0',
		'uFx_tracknoclicks' => '',
		'uFx_noofdays' => '7',
		'uFx_sparkstype' => 'b',
		'uFx_FB_debug' => ''
        );

	function isCompatible()
	{
		if ($this->Mint->version >= 120) {
			return array (
				'isCompatible'  => true
			);
		}
		else {
			return array (
				'isCompatible'  => false,
				'explanation'   => '<p>This Pepper is only compatible with Mint 1.2 and higher.</p>'
			);
		}
	}

	function update() {
		/******************
		update 0.01 => 0.05
		******************/
		$sql = $this->query("SHOW COLUMNS FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` LIKE 'reader'");
		$i = mysql_fetch_array($sql);
		if (!$i['Field']) {
			$sql = $this->query("ALTER TABLE `{$this->Mint->db['tblPrefix']}uFx_Feedback` ADD `reader` VARCHAR(200) NOT NULL AFTER `ip_long`") ;
		}

		/******************
		update 0.06 => 0.07
		******************/
		$sql = $this->query("SHOW COLUMNS FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` LIKE 'click'");
		$i = mysql_fetch_array($sql);
		if ($i[Type] != "smallint(1)") {
			$sql = $this->query("ALTER TABLE `{$this->Mint->db['tblPrefix']}uFx_Feedback` CHANGE `click` `click` SMALLINT( 1 ) NOT NULL");
		}
		if (!$i[Key]) {
			$sql = $this->query("ALTER TABLE `mint_uFx_Feedback` ADD INDEX ( `click` )");
		}
		/******************
		update 0.07 => 0.08
		******************/
		$sql = $this->query("SHOW COLUMNS FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` LIKE 'hostinfo'");
		$i = mysql_fetch_array($sql);
		if (!$i['Field']) {
			$sql = $this->query("ALTER TABLE `{$this->Mint->db['tblPrefix']}uFx_Feedback` ADD `hostinfo` VARCHAR(255) NOT NULL AFTER `ip_long`") ;
		}
		foreach($this->Mint->cfg['panes'] as $paneId => $paneData){
			if($paneData['pepperId'] == $this->pepperId){
				$this->Mint->cfg['panes'][$paneId]['tabs'] = array('Daily', 'Monthly', 'Subscribers', 'Hot items', 'Sparks');
			}
		}
	}
	function onDisplay($pane, $tab, $column = '', $sort = '') {
		$html = '';
		switch($pane) {
			case 'Feedback': 
				switch ($tab) {
					case 'Subscribers':
						$this->archive_stats();
						$html .= $this->getHTML_Subscribers();
						break;
					case 'Daily':
						$this->archive_stats();
						$html .= $this->getHTML_Daily_Stats();
						break;
					case 'Monthly':
						$this->archive_stats();
						$html .= $this->getHTML_Monthly_Stats();
						break;
					case 'Hot items':
						$this->archive_stats();
						$html .= $this->getHTML_Hotitems();
						break;
					case 'Sparks':
						$this->archive_stats();
						$html .= $this->getHTML_Sparks();
						break;
				}
		}
		return $html;
	}

	function archive_stats() {
		$sqlf = $this->query("SELECT feed_name, MIN(`dt`) as `dt` FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` GROUP by feed_name");
		while ($if = mysql_fetch_array($sqlf)) {
			$processmonth = date("Ym",$if['dt']);
			$processdate = $if['dt'];
			$nom = 0;
			while ($processmonth <= date('Ym')) {
				$starttime = mktime(0,0,0,date('m',$processdate),1,date('Y',$processdate));
				$endtime = mktime(0,0,0,date('m',$processdate)+1,1,date('Y',$processdate));
				$stats = $this->uFx_getFeedstats($starttime,$endtime,$if[feed_name]);
				$sql = $this->query("SELECT `id` FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback_archive` WHERE feed_name = '$if[feed_name]' AND month = '$starttime'");
				$i = mysql_fetch_array($sql);
				if ($i[id]) {
					$sql = $this->query("UPDATE `{$this->Mint->db['tblPrefix']}uFx_Feedback_archive` set views = '$stats[0]', subscribers = '$stats[1]', clicks = '$stats[2]' WHERE id = '$i[id]'");
				} else {
					$sql = $this->query("INSERT INTO `{$this->Mint->db['tblPrefix']}uFx_Feedback_archive` (`id`, `month`, `views`, `subscribers`, `clicks`, `feed_name`) VALUES ('', '$starttime', '$stats[0]', '$stats[1]', '$stats[2]', '$if[feed_name]')");
				} 
				$nom++;
				$processdate = mktime(0,0,0,date('m',$if['dt'])+$nom,1,date('Y',$if['dt']));
				$processmonth = date('Ym',$processdate);
			}
		}
		// clean up
		$deldate = time()-(60*60*24*45);
		$sql = $this->query("DELETE FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `dt`  < '$deldate'");
	}

	function getHTML_Subscribers() {
		$html = '';
		$tableData['hasFolders'] = true;
		if ($this->prefs['uFx_onlyclicks']) {
			$tablehead = 'Last click';
		} else {
			$tablehead = 'Last hit';
		}
		$tableData['table'] = array('id'=>'','class'=>'folder stacked-rows');
		$tableData['thead'] = array (
			array('value'=>'Subscriber','class'=>'focus'),
			array('value'=>'Reader','class'=>'sort'),
			array('value'=>'Clicks','class'=>'sort'),
			array('value'=>"$tablehead",'class'=>'sort')
		);
		foreach ($this->Mint->cfg['panes'] as $paneinfo) {
			if ($paneinfo['name'] == 'Nametags') {
				$nametags_installed = 1;
				$nametags_id = $paneinfo['pepperId']; 
				$aliases = $this->Mint->cfg[preferences][pepper][$nametags_id][uFx_aliases];
				break;
			}
		}
		if ($nametags_installed) {
			$aliases = eregi_replace("\r\n","\n",$aliases);
			$aliases_array = split("\n",$aliases);
			foreach ($aliases_array as $alias) {
				$values = split(", ",$alias);
				$aliasip = $values[0]; $aliasname = $values[1];
				$aliasname_array["$aliasip"] = $aliasname;
			}
		}

		// get iconfile
		if (file_exists('pepper/hansvankilsdonk/feedback/icons.txt')) {
			$iconfile = file('pepper/hansvankilsdonk/feedback/icons.txt');
		}
		$subsdone = array();
		if ($this->prefs['uFx_subscribers'] < 1) {
			$this->prefs['uFx_subscribers'] = '25';
		}
		if ($this->prefs['uFx_subscribers'] > 50) {
			$this->prefs['uFx_subscribers'] = '50';
		}	
		if ($this->prefs['uFx_onlyclicks']) {
			$sql = $this->query("SELECT `id`, COUNT(`id`) as aantal, `ip_long`, MAX(`dt`) as `dt`, MAX(`hostinfo`) as `hostinfo` FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `click` = '1' GROUP by `ip_long` ORDER by `dt` DESC");
		} else { 
			$sql = $this->query("(SELECT `id`, COUNT(id) as aantal, `ip_long`,MAX(`dt`) as `dt`, MAX(`hostinfo`) as `hostinfo` FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE click = '' GROUP by ip_long HAVING aantal > 4) UNION (SELECT `id`, COUNT(id) as aantal, `ip_long`, MAX(`dt`) as `dt`, MAX(`hostinfo`) as `hostinfo` FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE click = '1' GROUP by ip_long) ORDER by `dt` DESC");
		}
		while ($i = mysql_fetch_array($sql)) {
			$ip = long2ip($i[ip_long]);
			if (!in_array($ip,$subsdone)) {
				array_push($subsdone,$ip);
				$hostname = $this->Mint->abbr(gethostbyaddr($ip),25);
				$trackid = $i[ip_long];
				if (!$this->prefs['uFx_onlyclicks']) {
					$sql_c = $this->query("SELECT COUNT(id) as aantal FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE ip_long = '$i[ip_long]' AND click = '1'");
					$i_c = mysql_fetch_array($sql_c);
					$aantal = $i_c[aantal];
				} else {
					$aantal = $i[aantal];
				}
				$sql_r = $this->query("SELECT `reader` FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE ip_long = '$i[ip_long]' AND reader <> '' AND click = '' ORDER by `dt` DESC LIMIT 0,1");
				$i_r = mysql_fetch_array($sql_r);
				if (!$i_r[reader]) {
					$sql_r = $this->query("SELECT `reader`  FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE ip_long = '$i[ip_long]' AND reader <> '' AND click = '1' ORDER by `dt` DESC LIMIT 0,1");
					$i_r = mysql_fetch_array($sql_r);
				}
				if (!$aantal) { $aantal = "0"; }
				if ($nametags_installed) {
					$tag = '';
					$sql_ct = $this->query("SELECT uFx_tagcookie FROM `{$this->Mint->db['tblPrefix']}visit` WHERE ip_long = '$i[ip_long]' AND uFx_tagcookie <> '' GROUP by `uFx_tagcookie`",$db);
					while ($i_ct = mysql_fetch_array($sql_ct)) {
						$tag .= '<span style="color: #6B8DA6">'.$i_ct['uFx_tagcookie'].'</span>, ';
					}
					if ($tag) {
						$tag = substr($tag,0,strlen($tag)-2);
						$tag = "( $tag )";
					}
					if ($aliasname_array["$ip"]) {
						$iptag = '( <span style="color: red">'.$aliasname_array["$ip"].'</span> )';
					} else {
						$iptag = '';
					}
				}
				$icon = $this->uFx_geticon($i_r[reader],$iconfile);
				if ($this->prefs[uFx_FB_hostinfo]) {
					if (!$i[hostinfo]) {
						require_once('pepper/hansvankilsdonk/feedback/magpierss/extlib/Snoopy.class.inc');
						$snoopy = new Snoopy();
						@$snoopy->fetch("http://api.hostip.info/get_html.php?ip=$ip");
						$hostinfo = $snoopy->results;
						$hostinfo = ereg_replace("Country: ","",$hostinfo);
						$hostinfo = ereg_replace("City: ","",$hostinfo);	
						$hostinfo = eregi_replace("(\([a-zA-Z ?]+\))","",$hostinfo);
						$hostinfo_array = split("\n",$hostinfo);
						if ($hostinfo_array[1]) {
							$hostinfo = $hostinfo_array[0].", ".$hostinfo_array[1];
						} else {
							$hostinfo = $hostinfo_array[0];
						}
						$hostinfo = ucwords(strtolower($hostinfo));
						if (!eregi("[a-zA-Z]",$hostinfo)) { $hostinfo = "Unknown"; }
						$sqlu = $this->query("UPDATE `{$this->Mint->db['tblPrefix']}uFx_Feedback` set `hostinfo` = '$hostinfo' WHERE id = '$i[id]'"); 
						$ip = $hostinfo;
					} else {
						$ip = $i[hostinfo];
					}
			
				}
				$tableData['tbody'][] = array (
					"$hostname<br /><span style=\"color: #aaa\">$ip</span> $tag $iptag",
					$icon,
					$aantal,
					$this->Mint->formatDateTimeRelative($i['dt']),
					'folderargs'=>array(
						'action'=>'Subscriber_clicks',
						'trackid'=>$trackid
					)
				);
				$subscriber_found = 1;
				$no_of_subs++;
				if ($no_of_subs > $this->prefs['uFx_subscribers']) { break; }
			}
		}
		if (!$subscriber_found) {
			$tableData['tbody'][] = array (
				"no subscribers yet...",
				"",
				""
			);
			$tableData['hasFolders'] = false; 
		}
		$html .= $this->Mint->generateTable($tableData);
		return $html;
	}

	function getHTML_Subscriber_clicks($trackid) {
		$html = '';
		$results_found = '';
		$sql = $this->query("SELECT * FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE ip_long = '$trackid' AND reader <> '' AND click = '' ORDER by `dt` DESC LIMIT 0,1");
		$i = mysql_fetch_array($sql);
		if (!$i[reader]) {
			$sql = $this->query("SELECT * FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE ip_long = '$trackid' AND reader <> '' AND click = '1' ORDER by `dt` DESC LIMIT 0,1");
			$i = mysql_fetch_array($sql);
		}
		if ($i[reader]) {
			$tableData['tbody'][] = array(
				"$i[reader]",
				"",
				"",
				""
			);
		}
		$sql = $this->query("SELECT * FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE ip_long = '$trackid' AND click = '1' ORDER by `dt` DESC");
		while ($i = mysql_fetch_array($sql)) {
			$title = $i[resource_title]; 
			if (!$title) {
				$title = $i[resource]; 
			}
			$title = htmlentities($title);
			$title = $this->Mint->abbr($title, 25);
			if (!$title) { $title = "(no title)"; }
			$tableData['tbody'][] = array(
				"<a href=\"{$i['resource']}\">" . $title . "</a><br /><span style=\"color: #000\">$i[feed_name]</span>",
				"",
				"",
				$this->Mint->formatDateTimeRelative($i['dt'])
			);
			$results_found = 1;
		}
		if (!$results_found) {
			$tableData['tbody'][] = array(
				"No clicks found",
				"",
				"",
				""
			);
		}
		$html = $this->Mint->generateTableRows($tableData);
		return $html; 

	}

	function uFx_geticon($reader,$iconfile) {
		if ($iconfile) {
			foreach ($iconfile as $iconinfo) {
				if (!ereg("^#",$iconinfo)) {
					$iconinfo = chop(eregi_replace("[\t]+","\t",$iconinfo));
					list($regcode,$iconfilename) = split("\t",$iconinfo);
					if (eregi($regcode,$reader)) {
						$icon = $iconfilename;
					}
				}
			}
		}
		if ($icon) {
			$title = ereg_replace("\"","\\\"",$reader); 
			$iconimg = "<img src=\"pepper/hansvankilsdonk/feedback/icons/$icon\" width=\"16\" height=\"16\" align=\"left\" title=\"$title\" alt=\"$title\" />";
		}
		return $iconimg;
	}

	function getHTML_Monthly_Stats() {
		$html = '';
		// folder stacked-rows
		$tableData['table'] = array('id'=>'','class'=>'folder stacked-rows');
		$tableData['hasFolders'] = true;
		$tableData['thead'] = array (
			array('value'=>'Month','class'=>'focus'),
			array('value'=>'Subs','class'=>'sort'),
			array('value'=>'Hits','class'=>'sort'),
			array('value'=>'Clicks','class'=>'sort')
		);
		$sql = $this->query("SELECT month, SUM(views) AS views, SUM(subscribers) as subscribers, SUM(clicks) as clicks FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback_archive` GROUP by month ORDER by month DESC");
		while ($i = mysql_fetch_array($sql)) {
			$monthname = date("F 'y",$i[month]);
			$trackid = $i[month];
			$tableData['tbody'][] = array (
				"$monthname",
				$i[subscribers],
				$i[views],
				$i[clicks],
				'folderargs'=>array(
					'action'=>'uFx_specmonth',
					'trackid'=>$trackid
				)	
			);
		}
		return $this->Mint->generateTable($tableData);
	}

	function getHTML_Daily_Stats() {
		// days
		$tableData['table'] = array('id'=>'','class'=>'folder stacked-rows');
		$tableData['hasFolders'] = true;
		$tableData['thead'] = array (
			array('value'=>'Day','class'=>'focus'),
			array('value'=>'Subs','class'=>'sort'),
			array('value'=>'Hits','class'=>'sort'),
			array('value'=>'Clicks','class'=>'sort')
		);
		$no_of_days = $this->prefs['uFx_noofdays'];
		if ($no_of_days < 1) { $no_of_days = '7'; }
		if ($no_of_days > 14) { $no_of_days = '14'; }
		$dayno = 0;
		while ($dayno != $no_of_days) {
			$starttime =  mktime(0,0,0,date('m'),date('d')-$dayno,date('Y'));
			$endtime = mktime(23,59,59,date('m'),date('d')-$dayno,date('Y'));
			$totalsubs = ''; $totalhits = ''; $totalclicks = '';
			$sql = $this->query("SELECT * FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `dt` > '$starttime' AND `dt` < '$endtime' GROUP by `feed_name`");
			while ($i = mysql_fetch_array($sql)) {
				$stats = $this->uFx_getFeedstats($starttime,$endtime,$i[feed_name]);
				$totalsubs = $totalsubs+$stats[1];
				$totalhits = $totalhits+$stats[0];
				$totalclicks = $totalclicks+$stats[2];
			}
			if ($totalsubs < 1) { $totalsubs = '0'; }
			if ($totalhits < 1) { $totalhits = '0'; }
			if ($totalclicks < 1) { $totalclicks = '0'; }
			$dayname = date("l dS \of F Y",$starttime);
			if (date('Ymd',$starttime) == date('Ymd')) { $dayname = "Today"; }
			
			$trackid = $starttime;
			$tableData['tbody'][] = array (
				"$dayname",
				$totalsubs,
				$totalhits,
				$totalclicks,
				'folderargs'=>array(
					'action'=>'uFx_specday',
					'trackid'=>$trackid
				)
			);
			$dayno++;
		}
		return $this->Mint->generateTable($tableData);
	}

	function getHTML_Hotitems() {
		$html = '';
		$tableData['thead'] = array (
			array('value'=>'Item','class'=>'focus'),
			array('value'=>'Clicks','class'=>'sort')
		);
		$prefs = $this->prefs;
		if ($prefs['uFx_hotitems'] < 1) { 
			$prefs['uFx_hotitems'] = '25';
		}
		if ($prefs['uFx_hotitems'] > 50) {
			$prefs['uFx_hotitems'] = '50';
		}
		$limit = $prefs['uFx_hotitems'];
		$sql = $this->query("SELECT COUNT(click) as aantal, resource, MAX(`resource_title`) as resource_title, feed_name FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE click = '1' GROUP by 'resource' HAVING aantal > 0 ORDER by aantal DESC LIMIT 0,$limit");
		while ($i = mysql_fetch_array($sql)) {
			$title = $i[resource_title];
			if (!$title) {
				$title = $i[resource];
			}
			$title = htmlentities($title);
			$title = $this->Mint->abbr($title, 40);
			if (!$title) { $title = "(no title)"; }
			$tableData['tbody'][] = array (
				"<a href=\"$i[resource]\">$title</a><br /><span style=\"color: #aaa\">$i[feed_name]</span>",
				$i[aantal]
			);
			$item_found = 1;
		}
		if (!$item_found) {
			$tableData['tbody'][] = array (
				"no clicks yet...",
				""
			);
		}
		$html = $this->Mint->generateTable($tableData);
		return $html;
	}

	function uFx_getFeedstats($starttime,$endtime,$feed_name) {
		$subscribers = array();
		if (!$feed_name) {
			$feed_query = "feed_name LIKE \"%\"";
		} else {
			$feed_query = "feed_name = '$feed_name'";
		}
		$sql = $this->query("SELECT COUNT(id) as aantal, ip_long, reader FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `dt` > '$starttime' AND `dt` < '$endtime' AND $feed_query GROUP by ip_long HAVING aantal > 4");
		while ($i = mysql_fetch_array($sql)) {
			if (!in_array($i[ip_long],$subscribers)) {
				if (preg_match("/([0-9]+) subscriber/",$i[reader],$matches)) {
					$reader_subs = $matches[1];
				} else if (preg_match("/subscriber[s \:]+([0-9]+)/",$i[reader],$matches)) {
					$reader_subs = $matches[1];
				} else {
					$reader_subs = 1;
				}
				$count_subs = 1;
				while ($count_subs <= $reader_subs) { 
					array_push($subscribers,$i[ip_long]);
					$count_subs++;
				}
			}
		}
		$sql = $this->query("SELECT ip_long FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `dt` > '$starttime' AND `dt` < '$endtime' AND click = '1' AND $feed_query GROUP by ip_long");
		while ($i = mysql_fetch_array($sql)) {
			if (!in_array($i[ip_long],$subscribers)) {
				if (preg_match("/([0-9]+) subscriber/",$i[reader],$matches)) {
					$reader_subs = $matches[1];
				} else if (preg_match("/subscriber[s \:]+([0-9]+)/",$i[reader],$matches)) {
					$reader_subs = $matches[1];
				} else {
					$reader_subs = 1;
				}
				$count_subs = 1;
				while ($count_subs <= $reader_subs) {
					array_push($subscribers,$i[ip_long]);
					$count_subs++;
				}
			}
		}
		$no_subscribers = count($subscribers);
		$sql = $this->query("SELECT COUNT(id) as aantal FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `dt` > '$starttime' AND `dt` < '$endtime' AND click = '' AND $feed_query");
		$i = mysql_fetch_array($sql);
		$no_views = $i[aantal];

		$sql = $this->query("SELECT COUNT(id) as aantal FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `dt` > '$starttime' AND `dt` < '$endtime' AND click = '1' AND $feed_query");
		$i = mysql_fetch_array($sql);
		$no_clicks = $i[aantal];
		return array($no_views,$no_subscribers,$no_clicks);
	}

	function onCustom() {
		if ($_POST['action'] == 'Subscriber_clicks' && $_POST['trackid']) {
			$trackid = $this->Mint->escapeSQL($_POST['trackid']);
			echo $this->getHTML_Subscriber_clicks($trackid);
		}
		if ($_POST['action'] == 'uFx_specmonth' && $_POST['trackid']) {
			$trackid = $this->Mint->escapeSQL($_POST['trackid']);
			echo $this->getHTML_Month_details($trackid);
		}
		if ($_POST['action'] == 'uFx_specday' && $_POST['trackid']) {
			$trackid = $this->Mint->escapeSQL($_POST['trackid']);
			echo $this->getHTML_Day_details($trackid);
		}
		if ($_POST['action'] == 'uFx_addFeed' || $_POST['action'] == 'uFx_delFeed') {
			$this->onSavePreferences();
			echo $this->getHTML_uFx_feedlist();
		}
	}

	function getHTML_Month_details($trackid) {
		$html = '';
		$sql = $this->query("SELECT * FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback_archive` WHERE month = '$trackid' ORDER by subscribers DESC");
		$tableData['classes'] = array ( 'focus', 'sort', 'sort', 'sort' );
		while ($i = mysql_fetch_array($sql)) {
			$i[feed_name] = htmlentities($i[feed_name]);
			$title = $this->Mint->abbr($i[feed_name], 20);
			$tableData['tbody'][] = array(
			$i[feed_name],
			$i[subscribers],
			$i[views],
			$i[clicks]
			);	
	
		}
		$html = $this->Mint->generateTableRows($tableData);
		return $html;
	}

	function getHTML_Day_details($trackid) {
		$html = '';
		$datem = date('m',$trackid);
		$dated = date('d',$trackid);
		$datey = date('Y',$trackid);
		$endtime = mktime(23,59,59,$datem,$dated,$datey);
		$tableData['classes'] = array ( 'focus', 'sort', 'sort', 'sort' );
		$sql = $this->query("SELECT * FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback` WHERE `dt` > '$trackid' AND `dt` < '$endtime' GROUP by `feed_name`");
		while ($i = mysql_fetch_array($sql)) {
			$stats = $this->uFx_getFeedstats($trackid,$endtime,$i[feed_name]);
			$i[feed_name] = htmlentities($i[feed_name]);
			$title = $this->Mint->abbr($i[feed_name], 20);
			$tableData['tbody'][] = array(
				$i[feed_name],
				$stats[1],
				$stats[0],
				$stats[2]
			);
			$results_found = 1;
		}
		if (!$results_found) {
			$tableData['tbody'][] = array(
				"no statistics",
				"",
				"",
				""
			);
		}
		$html = $this->Mint->generateTableRows($tableData);
		return $html;
	}

	function getHTML_Sparks() {
		$html = '';
		$tableData['table'] = array('id'=>'','class'=>'');
		$tableData['hasFolders'] = false;
		$tableData['thead'] = array(
			array('value'=>'Period','class'=>'focus'),
			array('value'=>'Views','class'=>'sort'),
			array('value'=>'Subscribers','class'=>'sort'),
			array('value'=>'Clicks','class'=>'sort')
		);		
		$prefs = $this->prefs;
		$type = $prefs['uFx_sparkstype'];

		// daily
		$start = mktime(0,0,0,date('m'),date('d')-14,date('Y'));
		while ($start < time()) {
			$nodays++;
			if ($nodays > 15) { break; }
			$end = mktime(23,59,59,date('m',$start),date('d',$start),date('Y',$start));
			$stats = $this->uFx_getFeedstats($start,$end,'');
			$viewdata .= "$stats[0],";
			$subsdata .= "$stats[1],";
			$clicksdata .= "$stats[2],";
			$start = $start+86400;
		}
		$random = rand(0,9999999);
		$tableData['tbody'][] = array (
			'Past 14 days<br />&nbsp;',
			'<img src="pepper/hansvankilsdonk/feedback/sparks/sparks.php?d='.$viewdata.'&r='.$random.'&t='.$type.'" alt="views" title="views" />',
			'<img src="pepper/hansvankilsdonk/feedback/sparks/sparks.php?d='.$subsdata.'&r='.$random.'&t='.$type.'" alt="subscribers" title="subscribers" />',
			'<img src="pepper/hansvankilsdonk/feedback/sparks/sparks.php?d='.$clicksdata.'&r='.$random.'&t='.$type.'" alt="clicks" title="clicks" />'
		);	
		
		// monthly
		$viewdata = ''; $subsdata = ''; $clicksdata = '';
		$start = mktime(0,0,0,date('m')-12,date('d'),date('Y'));
		$sql = mysql_query("SELECT month, SUM(views) AS views, SUM(subscribers) as subscribers, SUM(clicks) as clicks FROM `{$this->Mint->db['tblPrefix']}uFx_Feedback_archive` WHERE `month` > '$start' GROUP by month");
		while ($i = mysql_fetch_array($sql)) {
			if (!$startmonth) {
				$startmonth = $i[month];
			}
			$viewdata .= "$i[views],";
			$subsdata .= "$i[subscribers],";
			$clicksdata .= "$i[clicks],";
		}
		if (!$startmonth) { $startmonth = time(); }
		$tableData['tbody'][] = array (
			'Past 12 months<br />&nbsp;',
			'<img src="pepper/hansvankilsdonk/feedback/sparks/sparks.php?d='.$viewdata.'&r='.$random.'&t='.$type.'" alt="views" title="views" />',
			'<img src="pepper/hansvankilsdonk/feedback/sparks/sparks.php?d='.$subsdata.'&r='.$random.'&t='.$type.'" alt="subscribers" title="subscribers" />',
			'<img src="pepper/hansvankilsdonk/feedback/sparks/sparks.php?d='.$clicksdata.'&r='.$random.'&t='.$type.'" alt="clicks" title="clicks" />'
		);
		return $this->Mint->generateTable($tableData);
	}

	function onDisplayPreferences() {
		$prefs = $this->prefs;
		if ($prefs[uFx_onlyclicks]) {
			$onlyclicks_checked = 'checked';
		}
		if ($prefs[uFx_tracknoclicks] != '1') {
			$trackclicks_checked = 'checked';
		}
		if ($prefs[uFx_FB_debug]) {
			$debug_checked = 'checked';
		}
		if ($prefs[uFx_FB_hostinfo]) {
			$hostinfo_checked = 'checked';
		}
		if ($prefs[uFx_sparkstype] == 'l') {
			$lines_select = 'selected';
		}	
		$preferences['Global'] = <<<EOT
<table>
        <tr>
        <td>Number of subscribers to show:</td>
	<td><input type="text" name="uFx_subscribers" id="uFx_subscribers" value="$prefs[uFx_subscribers]" style="width: 30px"></td>
        </tr>
	<tr>
	<td>Number of hot items to show:</td>
	<td><input type="text" name="uFx_hotitems" id="uFx_hotitems" value="$prefs[uFx_hotitems]" style="width: 30px"></td>
	</tr>
        <tr>
        <td>Number of days to show:</td>
        <td><input type="text" name="uFx_noofdays" id="uFx_noofdays" value="$prefs[uFx_noofdays]" style="width: 30px"></td>
        </tr>
        <tr>
        <td>Show only subscribers with clicks:</td>
        <td><input type="checkbox" name="uFx_onlyclicks" id="uFx_onlyclicks" $onlyclicks_checked value="1"></td>
        </tr>
	<tr>
	<td>Track clicks in the feeds:</td>
	<td><input type="checkbox" name="uFx_trackclicks" id="uFx_trackclicks" $trackclicks_checked value="1"></td>
	</tr>
	<tr>
	<td>Which type of sparks to use:</td>
	<td><select name="uFx_sparkstype" size="1" style="width: 60px">
		<option value="b">bars</option>
		<option value="l" $lines_select >lines</option>
	</select></td>
	</tr>
	<tr>
	<td>Use hostip.info for country/city resolve:</td>
	<td><input type="checkbox" name="uFx_FB_hostinfo" id="uFx_FB_hostinfo" $hostinfo_checked value="1"></td>
	</tr>
	<tr>
	<td>Show debug information on error:</td>
	<td><input type="checkbox" name="uFx_FB_debug" id="uFx_FB_debug" $debug_checked value="1"></td>
	</tr>
</table>

EOT;
		$uFx_feedlist = $this->getHTML_uFx_feedlist();
		$preferences['Feeds'] = <<<EOT
<div id="uFx_feedlist">
$uFx_feedlist
</div>

<script type="text/javascript" language="JavaScript">

// <![CDATA[
SI.uFxFeeds =
{
	updateFeeds : function(feedid)
	{
		var content = document.getElementById('uFx_feedlist');
		var no = 0;
		var uFx_values = '';
		while (no < 25) {
			no = no+1;
			var uFx_feed = 'uFx_feed_'+no;
			if (document.getElementById(uFx_feed)) {
				var value = document.getElementById(uFx_feed).value;
				var value_escaped = escape(value); 
				var uFx_values = uFx_values + '&uFx_feed_' + no + '=' + value_escaped;
			}
		}
		SI.Request.post('{$this->Mint->cfg['installDir']}/?MintPath=Custom&action=uFx_addFeed' + uFx_values + '&uFx_subscribers=' + document.getElementById('uFx_subscribers').value + '&uFx_hotitems=' + document.getElementById('uFx_hotitems').value + '&uFx_noofdays=' + document.getElementById('uFx_noofdays').value + '&uFx_onlyclicks=' + document.getElementById('uFx_onlyclicks').value + '&uFx_trackclicks=' + document.getElementById('uFx_trackclicks').value + '&uFx_FB_debug=' + document.getElementById('uFx_FB_debug').value + '&uFx_FB_hostinfo=' + document.getElementById('uFx_FB_hostinfo').value + '&uFx_Feedid=' + feedid, content);
	}
};
// ]]>
</script>
EOT;

		return $preferences;
	}

	function getHTML_uFx_feedlist() {
		$html = <<<EOT
Please enter all the feed URLS you would like to track:<br />
<table>
	<tr>
	<td><strong>Feed URL:</strong:</td>
	<td>&nbsp;</td>
	</tr>

EOT;
		$prefs = $this->prefs;
		$feeds = $prefs['uFx_feedlist'];
		$feeds = eregi_replace("\r\n","\n",$feeds);
		$feeds_array = split("\n",$feeds);
		$secretcode = substr(md5(uniqid(rand(), true)),0,6);
		$mint_path = $this->Mint->cfg['installDir'];
		$modrewrite = "# Start Feedback rules\n<IfModule mod_rewrite.c>\n";
		$no = 0;
		foreach ($feeds_array as $feed) {
			$no++;
			if ($feed) {
				$domarr = split("\/",$feed);
				array_shift($domarr); array_shift($domarr); array_shift($domarr);
				$feed_relative = implode("/",$domarr);
				if (substr($feed_relative,strlen($feed_relative)-1,1) == '/') {
					$feed_relative = substr($feed_relative,0,strlen($feed_relative)-1);
				}
				$modrewrite .= "RewriteEngine On\nRewriteBase /\nRewriteCond %{QUERY_STRING} !FB_secret=$secretcode".'$'."\n";
				$modrewrite .= "RewriteRule ^$feed_relative"."/?".'$'." $mint_path/pepper/hansvankilsdonk/feedback/tracker.php?FB_feed=$feed&FB_secret=$secretcode&".'%{QUERY_STRING} [L]'."\n";
				$feed_found = 1;
				$html .= <<<EOT
<tr>
	<td><input type="text" name="uFx_feed_$no" id="uFx_feed_$no" value="$feed" style="width: 220px"></td>
	<td><a href="#" onclick="SI.uFxFeeds.updateFeeds($no); return false;"><img src="pepper/hansvankilsdonk/feedback/icons/btn-del-s.png" width="16" height="16" alt="Delete feed" title="Delete feed" border="0" /></a></td>
</tr>

EOT;
			}
		}
                $modrewrite .= "</IfModule>\n# End Feedback rules\n";
		$no++;
		$html .= <<<EOT
<tr>
	<td><input type="text" name="uFx_feed_$no" id="uFx_feed_$no" value="$feed" style="width: 220px"></td>
	<td>&nbsp;</td>
</tr>
</table>
<a href="#Feeds" onclick="SI.uFxFeeds.updateFeeds(); return false;"><img src="pepper/hansvankilsdonk/feedback/icons/btn-add-s.png" width="16" height="16" alt="Add feed" title="Add feed" border="0" /></a>
<br /><br />

EOT;
		if ($feed_found) {
			$html .= <<<EOT
<div id="modrewriteblock">
Place the following text in the .htaccess file for your root webdirectory. <br /><br /><strong>You need to update this every time you add a new feed!</strong><br />
<span>
<textarea name="uFx_modrewritetxt" id="uFx_modrewritetxt" style="width: 250px; height: 120px" wrap="off">$modrewrite</textarea>
</span>
</div>
EOT;
		}
		return $html;
	}	
	function onSavePreferences() {
		if ($_POST['uFx_subscribers'] > 50) {
			$_POST['uFx_subscribers'] = '50';
		}
		if ($_POST['uFx_noofdays'] > 14) {
			$_POST['uFx_noofdays'] = '14';
		}
		if ($_POST['uFx_sparkstype'] != 'l' && $_POST['uFx_sparkstype'] != 'b') {
			$_POST['uFx_sparkstype'] = 'b';
		}
		$prefs['uFx_subscribers'] = (isset($_POST['uFx_subscribers']))?$_POST['uFx_subscribers']:'';
                $prefs['uFx_noofdays'] = (isset($_POST['uFx_noofdays']))?$_POST['uFx_noofdays']:'';
                $prefs['uFx_onlyclicks'] = (isset($_POST['uFx_onlyclicks']))?$_POST['uFx_onlyclicks']:'';
		$prefs['uFx_hotitems'] = (isset($_POST['uFx_hotitems']))?$_POST['uFx_hotitems']:'';
		$prefs['uFx_FB_debug'] = (isset($_POST['uFx_FB_debug']))?$_POST['uFx_FB_debug']:'';
		$prefs['uFx_FB_hostinfo'] = (isset($_POST['uFx_FB_debug']))?$_POST['uFx_FB_hostinfo']:'';
		$prefs['uFx_sparkstype'] = (isset($_POST['uFx_sparkstype']))?$_POST['uFx_sparkstype']:'';
		if (!isset($_POST['uFx_trackclicks'])) {
			$prefs['uFx_tracknoclicks'] = '1';
		}
		$no = 0;
		$uFx_feedlist = '';
		$feedid = $_POST['uFx_Feedid'];
		while ($no < 25) {
			$feedfield = 'uFx_feed_'.$no;
			$feed = $_POST["$feedfield"];
			if (eregi("http:\/\/",$feed) && $no != $feedid) {
				$uFx_feedlist .= $feed."\n";
			}
			$no++;
		}
		$prefs['uFx_feedlist'] = (isset($uFx_feedlist))?$uFx_feedlist:'';
                $this->prefs = $prefs;
        }
}

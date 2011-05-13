<?
/****************************************************************
Feedback v0.1 - (c) Hans van Kilsdonk
Website: http://mint.ufx.nl
E-mail: mail@mint.ufx.nl

This is the tracker file which will track your feeds.

****************************************************************/

// if the tracker.php file cannot find your mint installation,
// set your absolute path to mint right here:

$your_mint_path = '/path/to/mint/';

// --------------------------------------------------------------
// do not edit after this line =)
// --------------------------------------------------------------
$mint_path = ereg_replace("\/pepper\/hansvankilsdonk\/feedback\/tracker.php","",$_SERVER["SCRIPT_FILENAME"]); 

define('MINT',true);

if (!file_exists("$mint_path/app/lib/mint.php")) {
	$mint_path = ereg_replace("\/pepper\/hansvankilsdonk\/feedback\/tracker.php",$_SERVER["PATH_TRANSLATED"]);
}

if (!file_exists("$mint_path/app/lib/mint.php")) {
	$mint_path = $your_mint_path;
}

if (!file_exists("$mint_path/app/lib/mint.php")) {
	FB_debug("The tracker file cannot find your mint installation. Please enter it manually in tracker.php");
}

include("$mint_path/app/lib/mint.php");
include("$mint_path/config/db.php");

// get the Feedback preferences
foreach ($Mint->cfg['panes'] as $paneinfo) {
	if ($paneinfo['name'] == 'Feedback') {
		$feedback_id = $paneinfo['pepperId'];
		$noclicks = $Mint->cfg[preferences][pepper][$feedback_id][uFx_tracknoclicks];
		$show_debug = $Mint->cfg[preferences][pepper][$feedback_id][uFx_FB_debug];
		break;
	}
}

if (!$show_debug) {
	error_reporting(0);
}

if (!file_exists("$mint_path/pepper/hansvankilsdonk/feedback/magpierss")) {
        FB_debug("Cannot find MagpieRSS? Check the absolute path.");
}

if ($_GET['FB_request'] && !$_GET['FB_go']) {
	FB_debug("The script is looping :(");
}

// get the variables
$feed_url = strip_tags($_GET['FB_feed']);
$FB_go = $_GET['FB_go'];
$tablename = $Mint->db['tblPrefix']."uFx_Feedback";
$ip_long = ip2long($_SERVER['REMOTE_ADDR']);
$dt = time();
$reader = addslashes($_SERVER["HTTP_USER_AGENT"]);
$installdomain = $Mint->cfg['installDomain'];
$FB_secret = $_GET['FB_secret'];

if (!$FB_secret) {
	FB_debug("Missing secret code");
}

if (eregi("^www\.",$installdomain)) {
	$installdomain = eregi_replace("^www\.","",$installdomain);
}

if (!eregi("http://(www\.)?$installdomain",$feed_url)) {
	FB_debug("Feed not allowed. Feed URL: $feed_url, Install domain: $installdomain");
}

// load magpie & snoopy
define('MAGPIE_DIR', "$mint_path/pepper/hansvankilsdonk/feedback/magpierss/");
define('MAGPIE_FETCH_TIME_OUT','30');
require_once(MAGPIE_DIR.'rss_fetch.inc');
require_once(MAGPIE_DIR.'extlib/Snoopy.class.inc');

// remove the FB_ GET variables from the QUERY STRING so only custom additions remain
$_SERVER['QUERY_STRING'] = eregi_replace("\?FB_feed=$FB_feed","",$_SERVER['QUERY_STRING']);
$_SERVER['QUERY_STRING'] = eregi_replace("\&FB_secret=$FB_secret","",$_SERVER['QUERY_STRING']);

if (!ereg("\?",$feed_url)) {
	$feed_url = $feed_url."?FB_request=1&".$_SERVER['QUERY_STRING']."&FB_secret=".$FB_secret;
} else { 
	$feed_url = $feed_url."&FB_request=1&".$_SERVER['QUERY_STRING']."&FB_secret=".$FB_secret;
}

$rss = fetch_rss($feed_url);
$feed_name = $rss->channel['title'];

if (!$feed_name) {
	FB_debug("Feed has no name?");
}

if (!$FB_go) {
	$sql = $Mint->query("INSERT INTO $tablename (`id`, `dt`, `ip_long`, `reader`, `feed_name`) VALUES ('', '$dt', '$ip_long', '$reader', '$feed_name')");
	$snoopy = new Snoopy();
	@$snoopy->fetch($feed_url);
	$data = $snoopy->results;
	$title_code = '/\<link\>(.*)\<\/link\>/';
	if (ereg("\?",$_SERVER['REQUEST_URI'])) {
		$xtra = "&amp;";
	} else {
		$xtra = "?";
	}

	// check if we need to rewrite the links
	foreach ($Mint->cfg['panes'] as $paneinfo) {
		if ($paneinfo['name'] == 'Feedback') {
			$feedback_id = $paneinfo['pepperId'];
			$noclicks = $Mint->cfg[preferences][pepper][$feedback_id][uFx_tracknoclicks];
			break;
		}
	}
	header("Content-Type: text/xml");
	if (!$noclicks) {
		$data_array = split("\n",$data);
		foreach ($data_array as $data) {
			if ($rss->feed_type == 'RSS') {
				if (eregi("\<item",$data)) {
					$in_item = 1;
				}
				if (eregi("\<\/item",$data)) {
					$in_item = '';
				}
			} else if ($rss->feed_type == 'Atom') {
				if (eregi("\<entry",$data)) {
					$in_item = 1;
				}
				if (eregi("\<\/entry",$data)) {
					$in_item = '';
				}
			}
			if ($in_item) {
				if ($rss->feed_type == 'RSS') {
					$title_code = '/\<link\>(.*)\<\/link\>/';
					$replace_code = '<link>http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].$xtra.'FB_go=1&amp;FB_url=${1}</link>';
					$data = preg_replace($title_code,$replace_code,$data);
				}
				else if ($rss->feed_type == 'Atom') {
					$title_code = '/\<link(.*)href=\"(.*)\"(.*)>/';
					$replace_code = '<link${1}href="http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].$xtra.'FB_go=1&amp;FB_url=${2}"${3}>';
					$data = preg_replace($title_code,$replace_code,$data);
				}
			}
			echo $data."\n";
		}
	} else {
		echo $data;
	}
} else {
	foreach ($rss->items as $item) {
		$link = $item['link'];
		$titles["$link"] = $item['title'];
	}
	$resource = addslashes($_GET['FB_url']);
	$resource_title = addslashes($titles["$resource"]);
	$sql = $Mint->query("INSERT INTO $tablename (`id`, `dt`, `ip_long`, `reader`, `resource`, `resource_title`, `feed_name`, `click`) VALUES ('', '$dt', '$ip_long', '$reader', '$resource', '$resource_title', '$feed_name', '1')");
	header("location: $resource");
}

function FB_debug($error) {
	global $show_debug, $feed_name, $feed_url, $FB_go, $tablename, $installdomain, $_SERVER;
	if ($show_debug) {
		echo "<pre>\n";
		echo 'Error: '.$error."\n";
		echo 'Feed name: '.$feed_name."\n";	
		echo 'Feed URL: '.$feed_url."\n";
		echo 'FB_go: '.$FB_go."\n";
		echo 'FB_secret: '.$FB_secret."\n";
		echo 'Query string: '.$_SERVER['QUERY_STRING']."\n";
		echo 'Tablename: '.$tablename."\n";
		echo 'Installdomain: '.$installdomain."\n\n";
		echo "Send this information - including the contents of your .htaccess file - to:\n\n";
		echo "mail [ AT ] mint.ufx.nl\n\n";
		echo "</pre>\n";
	}
	exit;
}

?>

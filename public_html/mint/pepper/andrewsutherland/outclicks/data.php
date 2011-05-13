<?php 

define('MINT',true);
ini_set("include_path", ini_get("include_path") . ":../../../");

include_once('app/lib/mint.php');
include_once('app/lib/pepper.php');
include_once('config/db.php');

	
$dt   = time();
$ip   = $Mint->escapeSQL($_SERVER['REMOTE_ADDR']);
$to   = $Mint->escapeSQL($_GET['outclick']);
$from = $Mint->escapeSQL($_GET['from']);
$from_title = $Mint->escapeSQL($_GET['from_title']);

$Mint->loadPepper();

$st_queryparts = array('values' => '', 'fields' => '');

if($sessiontracker = $Mint->getPepperByClassName('RHC3_SessionTracker')) {
    if($sessiontracker->version >= 91){
        $st_queryparts = $sessiontracker->onOutclick();//a special method just for sessiontracker
    }
}

$sql = "INSERT INTO `{$Mint->db['tblPrefix']}outclicks` (`dt`, `ip`, `to`, `from`, `from_title` {$st_queryparts['fields']}) VALUES 
('{$dt}', '{$ip}', '{$to}', '{$from}', '{$from_title}' {$st_queryparts['values']})
 ";
 
$Mint->query($sql);

/* 
Send javascript to the browser that will send them to their destination.
This runs for js environments with no ajax.
*/
echo "self.location = '$to';";

?>
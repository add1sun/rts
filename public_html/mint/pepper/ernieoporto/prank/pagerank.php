<?php
/*
         Written and contributed by
         Alex Stapleton,
         Andy Doctorow,
         Tarakan,
         Bill Zeller,
         Vijay "Cyberax" Bhatter
         traB
    This code is released into the public domain


    Modified by Ernie Oporto to add caching so as to not anger 
    our Google masters.
*/
#header("Content-Type: text/html; charset=utf-8");
define('GOOGLE_MAGIC', 0xE6359A60);

//unsigned shift right
function zeroFill($a, $b)
{
    $z = hexdec(80000000);
        if ($z & $a)
        {
            $a = ($a>>1);
            $a &= (~$z);
            $a |= 0x40000000;
            $a = ($a>>($b-1));
        }
        else
        {
            $a = ($a>>$b);
        }
        return $a;
} 


function mix($a,$b,$c) {
  $a -= $b; $a -= $c; $a ^= (zeroFill($c,13)); 
  $b -= $c; $b -= $a; $b ^= ($a<<8); 
  $c -= $a; $c -= $b; $c ^= (zeroFill($b,13));
  $a -= $b; $a -= $c; $a ^= (zeroFill($c,12));
  $b -= $c; $b -= $a; $b ^= ($a<<16);
  $c -= $a; $c -= $b; $c ^= (zeroFill($b,5)); 
  $a -= $b; $a -= $c; $a ^= (zeroFill($c,3));  
  $b -= $c; $b -= $a; $b ^= ($a<<10); 
  $c -= $a; $c -= $b; $c ^= (zeroFill($b,15));
  
  return array($a,$b,$c);
}

function GoogleCH($url, $length=null, $init=GOOGLE_MAGIC) {
    if(is_null($length)) {
        $length = sizeof($url);
    }
    $a = $b = 0x9E3779B9;
    $c = $init;
    $k = 0;
    $len = $length;
    while($len >= 12) {
        $a += ($url[$k+0] +($url[$k+1]<<8) +($url[$k+2]<<16) +($url[$k+3]<<24));
        $b += ($url[$k+4] +($url[$k+5]<<8) +($url[$k+6]<<16) +($url[$k+7]<<24));
        $c += ($url[$k+8] +($url[$k+9]<<8) +($url[$k+10]<<16)+($url[$k+11]<<24));
        $mix = mix($a,$b,$c);
        $a = $mix[0]; $b = $mix[1]; $c = $mix[2];
        $k += 12; 
        $len -= 12;
    }

    $c += $length;
    switch($len)              /* all the case statements fall through */
    {
        case 11: $c+=($url[$k+10]<<24);
        case 10: $c+=($url[$k+9]<<16);
        case 9 : $c+=($url[$k+8]<<8);
          /* the first byte of c is reserved for the length */
        case 8 : $b+=($url[$k+7]<<24);
        case 7 : $b+=($url[$k+6]<<16);
        case 6 : $b+=($url[$k+5]<<8);
        case 5 : $b+=($url[$k+4]);
        case 4 : $a+=($url[$k+3]<<24);
        case 3 : $a+=($url[$k+2]<<16);
        case 2 : $a+=($url[$k+1]<<8);
        case 1 : $a+=($url[$k+0]);
         /* case 0: nothing left to add */
    }
    $mix = mix($a,$b,$c);
    /*-------------------------------------------- report the result */
    return $mix[2];
}

//converts a string into an array of integers containing the numeric value of the char
function strord($string) {
    for($i=0;$i<strlen($string);$i++) {
        $result[$i] = ord($string{$i});
    }
    return $result;
}


// converts an array of 32 bit integers into an array with 8 bit values. Equivalent to (BYTE *)arr32

function c32to8bit($arr32) {
    for($i=0;$i<count($arr32);$i++) {
        for ($bitOrder=$i*4;$bitOrder<=$i*4+3;$bitOrder++) {
            $arr8[$bitOrder]=$arr32[$i]&255;
            $arr32[$i]=zeroFill($arr32[$i], 8);
        }    
    }
    return $arr8;
}

function getrank($url, $cache_path,$cache_limit,$prefix="info:", $datacenter="www.google.com") {
	//This is the function used to get the PageRank value.
	//If $prefix is "info:", then the Toolbar pagerank will be returned.
	//$datacenter sets the datacenter to get the results from. 
        //e.g., "www.google.com", "216.239.53.99", "66.102.11.99".
	$url = $prefix.$url;
        $md5name = md5($url);
        $cache_file = $cache_path . $md5name;
	$ch = GoogleCH(strord($url));
	//Get the Google checksum for $url using the GoogleCH function.

// Check for cache version of URL first
// use the md5 the name of the URL for filename
// if cache does not exist, grab the data and cache it for 1 week
    if (file_exists($cache_file)) 
    {
        $cache_time   = @filemtime($cache_file);
    }
    if (((time() - $cache_time) > $cache_limit) || !file_exists($cache_file)) 
    {
	$file = "http://$datacenter/search?client=navclient-auto&ch=6$ch&features=Rank&q=$url";
        //To get the Crawl Date instead of the PageRank, change 
        //"&features=Rank" to "&features=Crawldate"
        //To get detailed XML results, remove "&features=Rank"
	$oldlevel = error_reporting(0);	//Suppress error reporting temporarily.
	$data = file($file);
	error_reporting($oldlevel);	//Restart error reporting.
	if(!$data || preg_match("/(.*)\.(.*)/i", $url)==0) return "NA";
	//If the Google data is unavailable, or URL is invalid, return "NA".
	//The preg_match check is a very basic url validator that only 
        //checks if the URL has a period in it.
	$rankarray = explode (":", $data[2]);
        //There are two line breaks before the PageRank data on the Google page.
	$rank = trim($rankarray[2]);	//Trim whitespace and line breaks.
        $handle = fopen($cache_file, "w+");
        fwrite($handle, $rank);
        fclose($handle);
    }
    else
    {
        $file = $cache_file;
	$oldlevel = error_reporting(0);	//Suppress error reporting temporarily.
	$data = file($file);
	error_reporting($oldlevel);	//Restart error reporting.
        $rank = file_get_contents($cache_file);
    }

    if($rank=="") return "NA";			//Return NA if no rank.
    return $rank;
}

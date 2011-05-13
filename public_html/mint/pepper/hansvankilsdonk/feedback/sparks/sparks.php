<?

/****************************************************

Feedback v0.1 - (c) Hans van Kilsdonk
Website: http://mint.ufx.nl
E-mail: mail@mint.ufx.nl
Licensed under the GPL

ugly PHP script for the sparks in Feedback

****************************************************/

// type
$t = $_GET['t'];

// data
$d = $_GET['d'];

// bg?
$bg = $_GET['bg'];

$data_array = split(',',$d);

while (count($data_array) < 13) {
	array_unshift($data_array,'0');
}

if ($t == "l") {
	require_once('./lib/Sparkline_Line.php');
        $sparkline = new Sparkline_Line();
	$sparkline->SetYMin(0);
	$sparkline->SetPadding(0);
} else {
	require_once('./lib/Sparkline_Bar.php');
	$sparkline = new Sparkline_Bar();
	$sparkline->SetBarWidth(3);
	$sparkline->SetBarSpacing(1);
} 



//$sparkline->SetDebugLevel(DEBUG_NONE);

$sparkline->setColorHtml("bgcolor", "#ffffff");
 
$sparkline->setColorHtml("fgcolor", "#7b9f53");
$sparkline->setColorBackground('bgcolor');

foreach ($data_array as $x) {
	$y++;
	if ($x != '') {
		if ($t == 'l') {
			$sparkline->SetData($y, $x);
		} else {
			$sparkline->SetData($y, $x, 'fgcolor');
		}
	}
}

if ($t == 'l') {
	$sparkline->Render(50,20);
} else {
	$sparkline->Render(25);
}
$sparkline->Output();

?>


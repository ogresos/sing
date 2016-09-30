<?php
$arguments = getopt("t:s:e:");
$type = $arguments['t'];
$start = $arguments['s'];
$end = $arguments['e'];
if (!isset($type) || !isset($start) || !isset($end)) {
	echo 'php genall.php -t <type> -s <start num> -e <end num>', "\n";
	exit;
}
$logFile = '/Users/Eric/work/hymnal.net/code/html/lib/setup/results.log';
if (file_exists($logFile)) {
	unlink($logFile);
}
touch($logFile);
$errorFile = '/Users/Eric/work/hymnal.net/code/html/lib/setup/error.log';
if (file_exists($errorFile)) {
	unlink($errorFile);
}
touch($errorFile);
switch ($type) {
	case 'c':
		$path = '/Users/Eric/work/hymnal.net/code/resources/Children';
		$typestr = 'Children Songs';
		break;
	case 'h':
		$path = '/Users/Eric/work/hymnal.net/code/resources/Hymnal/English';
		$typestr = 'Hymns';
		break;
	case 'lb':
		$path = '/Users/Eric/work/hymnal.net/code/resources/LongBeach';
		$typestr = 'Long Beach Songs';
		break;
	case 'ns':
		$path = '/Users/Eric/work/hymnal.net/code/resources/NewSongs';
		$typestr = 'New Songs';
		break;
	case 'nt':
		$path = '/Users/Eric/work/hymnal.net/code/resources/NewTunes';
		$typestr = 'New Tunes';
		break;
}
if ($handle = opendir($path)) {
	$numlist = array();
	echo 'Processing Range: ', $start, '-', $end, "\n";
	while (false !== ($file = readdir($handle))) {
		if (preg_match('/^[^0-9]+([0-9]+)([a-f]?)\.xml$/', $file, $regs)) {
			$num = ltrim($regs[1], '0');
			if ($num < $start || $num > $end) {
				continue;
			}
			if ($regs[2] != '') {
				$num .= $regs[2];
			}
			array_push($numlist, $num);
			echo 'Processing ', $typestr, ', #', $num, ' (', $regs[1], ')', "\n";
		}
	}
	foreach ($numlist as $num) {
		exec("/Users/Eric/work/hymnal.net/code/resources/scripts/lilypondgenpianoandguitar.sh $type $num $num 2>&1 | tee -a $logfile");
	}
}
if (file_exists($errorFile)) {
	$fp = fopen($errorFile, 'r');
	echo '=== ERRORS =====================================================', "\n";
	$errorcount = 0;
	while (!feof($fp)) {
		$line = trim(fgets($fp, 4096));
		if ($line != '') {
			$errorcount++;
		}
		echo $line, "\n";
	}
	if ($errorcount == 0) {
		echo 'None.', "\n";
	}
	echo '================================================================', "\n";
	unlink($errorFile);
}
echo 'Log File: ', $logFile, "\n";
?>

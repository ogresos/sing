<?php
	require('../global-lib.php');
	require('oldrcv-lib.php');

	$bookname = $_REQUEST['b'];

	$comparefile = '../../../resources/rcv/' . $_REQUEST['c'];
	$fp = fopen($comparefile, "r") or die ("Cannot open " . $comparefile);
	$comparetextmap = array();
	while (!feof($fp)) {
		$line = trim(fgets($fp, 4096));
		if (strlen($line) == 0) {
			continue;
		}
		if (preg_match('/^([0-9]+):([0-9]+) ([^\n]+)$/', $line, $regs)) {
			$comparetextmap[$regs[1] . ':' . $regs[2]] = trim($regs[3]);
		}
	}
	fclose($fp);

	$rcvparser = new RcvParser();
	$oldbook = $rcvparser->getBook($bookname);
	foreach ($oldbook->chapters as $oldchapter) {
		foreach ($oldchapter->verses as $oldverse) {
			$versenum++;
			$text = trim(str_replace(array('&#151;', '[note]', '[/note]', '[xref]', '[/xref]', '[i]', '[/i]'), array('—', '', '', '', '', '', ''), $oldverse->text));
			$compareverse = $comparetextmap[$oldchapter->num . ':' . $oldverse->num];
			if (strcmp($compareverse, $text) != 0) {
				$xmllen = strlen($text);
				$cmplen = strlen($compareverse);
				$len = $xmllen;
				if ($xmllen > $cmplen) {
					$len = $cmplen;
				}
				$diffstartindex = 0;
				$diffendindex = $len;
				for ($i = 0; $i < $len; $i++) {
					if ($diffstartindex > 0 && ($text[$i] == ' ' || $compareverse[$i] == ' ')) {
						$diffendindex = $i;
						break;
					}
					if ($text[$i] != $compareverse[$i]) {
						$diffstartindex = $i;
					}
				}
				if ($diffstartindex == 0) {
					echo '<p>XML: ', $oldchapter->num, ':', $oldverse->num, ' ', $text, ' (', $xmllen, ')<br/>';
					echo 'CMP: ', $oldchapter->num, ':', $oldverse->num, ' ', $compareverse, ' (', $cmplen, ')</p>';
				} else {
					echo '<p>XML: ', $oldchapter->num, ':', $oldverse->num, ' ', substr($text, 0, $diffstartindex), '<b>', substr($text, $diffstartindex, $xmllen - $diffstartindex + 1), '</b> (', $xmllen, ')<br/>';
					echo 'CMP: ', $oldchapter->num, ':', $oldverse->num, ' ', substr($compareverse, 0, $diffstartindex), '<b>', substr($compareverse, $diffstartindex, $cmplen - $diffstartindex + 1), '</b> (', $cmplen, ')</p>';
				}
			}
		}
	}
?>

<?php

use HymnalNet\Bootstrap\Constants;

$ROOT = '/Users/eric/Work/hymnal.net';
require_once('/Users/eric/Work/hymnal.net/code/html/lib/hymn.lib.php');

foreach (Constants::$YPSB as $ypnum => $typenum) {
	if (preg_match('/^([^0-9]+)([0-9ab]+)$/', $typenum, $regs)) {
		$type = $regs[1];
		$num = $regs[2];
	} else {
		echo 'Invalid typenum: ' . $typenum . "\n";
		exit;
	}

	$paddedypnum = str_pad($ypnum, 3, '0', STR_PAD_LEFT);

	$mp3file = null;
	$pianofile = null;
	$guitarfile = null;
	$xmlfile = null;
	$hassuffix = preg_match("/[a-z]$/", $num);
	$numOfChars = $hassuffix ? 5 : 4;
	switch ($type) {
		case 'c':
			$paddednum = str_pad($num, $numOfChars, '0', STR_PAD_LEFT);
			$mp3file = $ROOT . '/assets/Hymns/Children/mp3/c' . $paddednum . '.mp3';
			$pianofile = $ROOT . '/assets/Hymns/Children/pdfs/child' . $paddednum . '_p.pdf';
			$guitarfile = $ROOT . '/assets/Hymns/Children/pdfs/child' . $paddednum . '_g.pdf';
			$xmlfile = $ROOT . '/resources/Children/c' . $paddednum . '.xml';
			break;
		case 'h':
			$paddednum = str_pad($num, $numOfChars, '0', STR_PAD_LEFT);
			$mp3file = $ROOT . '/assets/Hymns/Hymnal/mp3/e' . $paddednum . '_i.mp3';
			$pianofile = $ROOT . '/assets/Hymns/Hymnal/pdfs/e' . $paddednum . '_p.pdf';
			$guitarfile = $ROOT . '/assets/Hymns/Hymnal/pdfs/e' . $paddednum . '_g.pdf';
			$xmlfile = $ROOT . '/resources/Hymnal/English/h' . $paddednum . '.xml';
			break;
		case 'lb':
			$numOfChars = $hassuffix ? 3 : 2;
			$paddednum = str_pad($num, $numOfChars, '0', STR_PAD_LEFT);
			$mp3file = $ROOT . '/assets/Hymns/LongBeach/mp3/lb' . $paddednum . '.mp3';
			$pianofile = trim($ROOT . '/assets/Hymns/LongBeach/pdfs/lb' . $paddednum . '_p.pdf');
			$guitarfile = trim($ROOT . '/assets/Hymns/LongBeach/pdfs/lb' . $paddednum . '_g.pdf');
			$xmlfile = $ROOT . '/resources/LongBeach/l' . $paddednum . '.xml';
			break;
		case 'ns':
			$paddednum = str_pad($num, $numOfChars, '0', STR_PAD_LEFT);
			$mp3file = $ROOT . '/assets/Hymns/NewSongs/mp3/newsong' . $paddednum . '.mp3';
			$pianofile = $ROOT . '/assets/Hymns/NewSongs/pdfs/ns' . $paddednum . '_p.pdf';
			$guitarfile = $ROOT . '/assets/Hymns/NewSongs/pdfs/ns' . $paddednum . '_g.pdf';
			$xmlfile = $ROOT . '/resources/NewSongs/ns' . $paddednum . '.xml';
			break;
		case 'nt':
			$paddednum = str_pad($num, $numOfChars, '0', STR_PAD_LEFT);
			$mp3file = $ROOT . '/assets/Hymns/NewTunes/mp3/e' . $paddednum . '_new.mp3';
			$pianofile = $ROOT . '/assets/Hymns/NewTunes/pdfs/e' . $paddednum . '_new_p.pdf';
			$guitarfile = $ROOT . '/assets/Hymns/NewTunes/pdfs/e' . $paddednum . '_new_g.pdf';
			$xmlfile = $ROOT . '/resources/NewTunes/nt' . $paddednum . '.xml';
			break;
		default:
			echo 'Invalid type: ' . $type . "\n";
			exit;
	}

	$targetMediaDir = $ROOT . '/assets/Hymns/VSB';
	if (!file_exists($targetMediaDir)) {
		mkdir($targetMediaDir);
		mkdir($targetMediaDir . '/pdfs');
	}
	$targetMediaMp3Dir = $targetMediaDir . '/mp3';
	if (!file_exists($targetMediaMp3Dir)) {
		mkdir($targetMediaMp3Dir);
	}
	$targetMediaPdfDir = $targetMediaDir . '/pdfs';
	if (!file_exists($targetMediaPdfDir)) {
		mkdir($targetMediaPdfDir);
	}
	$targetXMLDir = $ROOT . '/resources/VSB';
	if (!file_exists($targetXMLDir)) {
		mkdir($targetXMLDir);
	}
	if (file_exists($mp3file)) {
		copy($mp3file, $targetMediaMp3Dir . '/vsb' . $paddedypnum . '.mp3');
	} else {
		echo 'Not found: ' . $mp3file . "\n";
	}
	if (file_exists($pianofile)) {
		copy($pianofile, $targetMediaPdfDir . '/vsb' . $paddedypnum . '_p.pdf');
	} else {
		echo 'Not found: ' . $pianofile . "\n";
	}
	if (file_exists($guitarfile)) {
		copy($guitarfile, $targetMediaPdfDir . '/vsb' . $paddedypnum . '_g.pdf');
	} else {
		echo 'Not found: ' . $guitarfile . "\n";
	}
	if (file_exists($xmlfile)) {
		copy($xmlfile, $targetXMLDir . '/vsb' . $paddedypnum . '.xml');
	} else {
		echo 'Not found: ' . $xmlfile . "\n";
	}
}
?>

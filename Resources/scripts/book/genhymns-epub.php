<?php
define('BOOK_PARSER_DIR', '/Users/Eric/Work/bookparser');
define('ROOT', '/Users/Eric/Work/hymnal.net/code');
//define('ROOT', '/home/cluster1/data/f/j/a324692');

/*
 * IMPORTANT: Remember to change env.php to production
 */
require(ROOT . '/html/lib/global.lib.php');
require(ROOT . '/html/lib/hymn.lib.php');
require(BOOK_PARSER_DIR . '/Utils.php');
require_once(BOOK_PARSER_DIR . '/genhymns-epub.lib.php');
require_once(BOOK_PARSER_DIR . '/genhymns.lib.php');

define('OUTPUT_DIR', BOOK_PARSER_DIR . '/generated/Hymns.epub');
define('OUTPUT_CONTENT_DIR', OUTPUT_DIR . '/OEBPS');

date_default_timezone_set('America/Los_Angeles');

$INCLUDE_MUSIC = false;
$INCLUDE_PIANO_SCORE = true;
$INCLUDE_GUITAR_SCORE = true;
$REMOVE_DIR = true;

$VERSION = '1.4';

$HEADER = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head>
<meta charset="utf-8" />
<title>Hymns</title>
<link rel="stylesheet" type="text/css" href="hymns-epub.css" />
<script type="text/javascript" src="jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="hymns.js"></script>
</head>
<body>
';

$FOOTER = '</body>
</html>';

$songparser = new SongParser();
$commentparser = new CommentParser();
$cacheManager = new CacheManager();

/*
$song = $songparser->getSong('ns', '6');
getSongNumbersInfo($song);
exit;
*/

$songtypes = array('h', 'nt', 'vsb', 'ch', 'ts', 'cb', 'hd', 'hf', 'hp', 'hr', 'hs', 'ht');

$tocList = array();
$manifestList = array();
$spineList = array();

initEpub(OUTPUT_DIR, OUTPUT_CONTENT_DIR);

// Copy resources
copyArtwork('Hymns', BOOK_PARSER_DIR . '/template/ArtWorks', OUTPUT_DIR, OUTPUT_CONTENT_DIR);
$resourceFiles = array('/template/jquery-1.7.2.min.js', '/template/hymns/hymns.js', '/template/hymns/images/icon_down.png', '/template/hymns/images/icon_guitar.png', '/template/hymns/images/icon_list.png', '/template/hymns/images/icon_piano.png', '/template/hymns/images/icon_up.png', '/template/hymns/css/hymns-epub.css');
foreach ($resourceFiles as $resourceFile) {
	$filename = basename($resourceFile);
	copy(BOOK_PARSER_DIR . $resourceFile, OUTPUT_CONTENT_DIR . DIRECTORY_SEPARATOR . $filename);
	$manifestList[] = $filename;
}

/*
saveCategories('ch');
$categoryManager = new CategoryManager();
$category = '事奉與福音';
		$map = $categoryManager->getSongInfoMap($category, 'ch');
echo '#### countmap='.count($map).PHP_EOL;
exit;
*/

saveHome();
foreach ($songtypes as $type) {
	$numlist = getFileList($type, true);

	// Generate Each Song
	$count = 0;
	foreach ($numlist as $num) {
		if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
			$songtype = $regs[1];
			$songnum = $regs[2];
		} else {
			$songtype = $type;
			$songnum = $num;
		}
		$song = $songparser->getSong($songtype, $songnum);
		saveSong($type, $song, $count, $numlist);
		$count++;
	}
	// Generate Contents
	saveContent($type);
}
generateEPubFile();
zipEpub(OUTPUT_DIR, $REMOVE_DIR);
/* saveITunesMetadataPlist(OUTPUT_DIR, 'Hymns', 'hymnal.net', 'Hymns', 'Hymns'); */

?>

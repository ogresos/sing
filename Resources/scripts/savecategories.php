<?php

require_once(__DIR__ . '/../../src/bootstrap.php');

use HymnalNet\Domain\CategoryManager;
use HymnalNet\Services\SongService;

$env = getenv('HYMNALNET_ENV');
if (strlen($env) == 0) {
    $env = 'home';
}

$files = glob(__DIR__ . '/../generated/Categories/*.txt'); // get all file names
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}

$songService = new SongService();
$categoryMgr = new CategoryManager();
$catMap = array();
$count = 0;
$songTypes = array('h', 'nt', 'ns', 'lb', 'c', 'ch', 'ts', 'hd', 'hf', 'hr', 'hs', 'ht', 'cb');
foreach ($songTypes as $type) {
    $numList = $songService->getFileList($type, true);
    foreach ($numList as $num) {
        $song = $songService->getSong($type, $num);
        if ($song) {
            echo '  Saving Song Info for ', $type, $num, ': ', $song->title, "\n";
            $categoryMgr->saveSongInfo($song);
        }
    }
}
?>

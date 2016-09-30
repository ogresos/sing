<?php

require_once(__DIR__ . '/../../../src/bootstrap.php');

use HymnalNet\Bootstrap\Constants;
use HymnalNet\Services\SongService;

$songService = new SongService();
$count = 0;
foreach (Constants::$REVERSE_YPSB as $typeNum => $vsbNum) {
    $count++;
    $type = null;
    $num = null;
    if (preg_match('/^([^0-9]+)([0-9]+)$/', $typeNum, $regs)) {
        $type = $regs[1];
        $num = $regs[2];
    } else {
        echo 'ERROR: Invalid typenum - ', $typeNum, "\n";
        exit;
    }
    echo 'Processing ', $count, '/', count(Constants::$REVERSE_YPSB), ' - ', $type, ':', $num, ' -> ', $vsbNum, "\n";
    $song = $songService->getSong($type, $num);
    $song->numbers['vsb'] = $vsbNum;
    $song->saveFile();
}

?>

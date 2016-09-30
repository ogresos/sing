<?php

define('SCRIPT_MODE', true);

require_once(__DIR__ . '/../../src/bootstrap.php');

use HymnalNet\Bootstrap\Util;
use HymnalNet\Services\SongService;

$songService = new SongService();

$type = 'de';
$fileType = 'g';
$dir = $config->HYMN_TYPE_TO_DIR[$type];
$files = Util::getFilesInDirectory($dir);
foreach ($files as $file) {
    $path = $dir . $file;
    if (!preg_match('/^' . $fileType . '([^\.]+)\.xml$/', $file, $regs)) {
        continue;
    }
    $num = $regs[1];
    $song = $songService->getSong($type, $num);
    $singingStanza = '';
    foreach ($song->stanzas as $stanza) {
        if (!($stanza->type == 'verse' || $stanza->type == 'chorus')) {
            continue;
        }
        if ($stanza->num == '2') {
            break;
        }
        if (strlen($singingStanza) > 0) {
            $singingStanza .= ' ';
        }
        $singingStanza .= str_replace(array(' –', '&nbsp;', '<br/>'), array('–', '', ' ', ' '), $stanza->text);
    }
    if ($song->singingStanza != $singingStanza) {
        $song->singingStanza = $singingStanza;
        $song->saveFile();
    }
}

function convertPunctuationMarks($str)
{
    return str_replace(array(',', ';', '?', '!', '.', ':', 'p；', 'h；'), array('，', '；', '？', '！', '。', '：', 'p;', 'h;'), $str);
}

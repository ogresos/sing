<?php

use HymnalNet\Services\CacheService;
use HymnalNet\Services\SongService;

define('SCRIPT_MODE', true);
require_once(__DIR__ . '/../../src/bootstrap.php');

$songService = new SongService();
$cacheService = new CacheService();

$cacheDir = $config->DIRS['Cache'];
if (file_exists($cacheDir)) {
    if ($handle = opendir($cacheDir)) {
        while (false !== ($file = readdir($handle))) {
            if (!preg_match('/svn/', $dir, $regs) && !preg_match('/^\./', $file, $regs)) {
                deleteDir($cacheDir . $file);
            }
        }
    }
}
mkdir($cacheDir);

$songTypes = array('h', 'nt', 'ns', 'lb', 'c', 'ch', 'ts', 'hd', 'hf', 'de', 'hr', 'hs', 'ht', 'cb');
//$songtypes = array('h');
$authors = array();
$composers = array();
$titles = array();
$meters = array();
$hymnCodes = array();
$keys = array();
$times = array();
$categories = array();
$subcategories = array();
foreach ($songTypes as $type) {
    $numList = $songService->getFileList($type, true);
    foreach ($numList as $num) {
        echo 'Processing ', $type, ':', $num, "\n";
        $song = $songService->getSong($type, $num);
        if ($song->authors) {
            foreach ($song->authors as $author) {
                $name = ($author->fullname) ? $songService->parseWriterForLink($author->fullname) : $songService->parseWriterForLink($author->name);
                processList($type, $num, $name, $authors);
            }
        }
        if ($song->composers) {
            foreach ($song->composers as $composer) {
                $name = ($composer->fullname) ? $songService->parseWriterForLink($composer->fullname) : $songService->parseWriterForLink($composer->name);
                processList($type, $num, $name, $composers);
            }
        }
        processList($type, $num, $song->title, $titles);
        processList($type, $num, $song->meter, $meters);
        processList($type, $num, $song->hymnCode, $hymnCodes);
        processList($type, $num, $song->key, $keys);
        processList($type, $num, $song->time, $times);

        $subcategory = null;
        if (preg_match('/.+&mdash;.+/', $song->category, $regs)) {
            list($category, $subcategory) = explode('&mdash;', $song->category);
            $category = mb_convert_encoding($category, 'html', 'utf-8');
            $subcategory = mb_convert_encoding($subcategory, 'html', 'utf-8');
        } else if (preg_match('/.+-.+/', $song->category, $regs)) {
            list($category, $subcategory) = explode('-', $song->category);
            $category = mb_convert_encoding($category, 'html', 'utf-8');
            $subcategory = mb_convert_encoding($subcategory, 'html', 'utf-8');
        } else {
            $category = $song->category;
            $category = mb_convert_encoding($category, 'html', 'utf-8');
            unset($subcategory);
        }
        processList($type, $num, $category, $categories);
        if (isset($subcategory)) {
            processList($type, $num, $category . '_' . $subcategory, $subcategories);
        }
    }
}

function processList($type, $num, $value, &$cacheList)
{
    if ($value) {
        $list = null;
        if (array_key_exists($value, $cacheList)) {
            $list = $cacheList[$value];
        } else {
            $list = array();
        }
        $list[$type . ':' . $num] = 1;
        $cacheList[$value] = $list;
    }
}

function deleteDir($dir)
{
    if (file_exists($dir)) {
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if (!preg_match('/^\./', $file, $regs)) {
                    unlink($dir . '/' . $file);
                }
            }
        }
    }
}

$cacheLists = array('Authors' => $authors, 'Composers' => $composers, 'Meters' => $meters, 'HymnCodes' => $hymnCodes, 'Keys' => $keys, 'Times' => $times, 'Categories' => $categories, 'Subcategories' => $subcategories);
foreach ($cacheLists as $dirName => $cacheList) {
    $dir = $cacheDir . $dirName . '/';
    if (!file_exists($dir)) {
        mkdir($dir);
    }
    foreach ($cacheList as $name => $list) {
        $filename = $cacheService->getFile($dirName, $name);
        $fp = fopen($filename, 'a');
        foreach ($list as $typeNum => $ignore) {
            fwrite($fp, $typeNum . "\n");
        }
        fclose($fp);
    }
}
?>

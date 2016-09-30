<?php

define('SCRIPT_MODE', true);
require_once(__DIR__ . '/../../../src/bootstrap.php');

use HymnalNet\Book\BookConstants;
use HymnalNet\Book\BookGenerator;
use HymnalNet\Bootstrap\Constants;
use HymnalNet\Domain\CategoryManager;
use HymnalNet\Domain\Song;
use HymnalNet\ThirdParty\CConvert;

$bookGenerator = new BookGenerator();
$bookGenerator->build();

//foreach ($this->songTypes as $type => $typeDir) {
//    generateIsiloFile($type, $typeDir, true);
//}
//exit;


function getNumListValue($type, $numList, $index)
{
    switch ($type) {
        case 'vsb':
            return $index + 1;
        default:
            if (array_key_exists($index, $numList)) {
                return $numList[$index];
            }
    }
    return null;
}

function savePreface($type, $typeDir)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'savePreface(type=' . $type . ', typeDir=' . $typeDir . ')' . PHP_EOL;

    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $prefaceStr = '';
    switch ($type) {
        case 'h':
            $prefaceStr = file_get_contents($config->DIRS['Classic Hymns'] . '/English/preface.txt');
            $prefaceStr = str_replace(array("\n"), array('<br/>'), $prefaceStr);
            break;
        case 'ch':
            $prefaceStr = file_get_contents($config->DIRS['Classic Hymns'] . '/Chinese/preface.txt');
            $prefaceStr = str_replace("\n", '<br/>', $prefaceStr);
            break;
        case 'cb':
            $prefaceStr = file_get_contents($config->DIRS['Classic Hymns'] . '/Cebuano/preface.txt');
            $prefaceStr = str_replace("\n", '<br/>', $prefaceStr);
            break;
        case 'ht':
            $prefaceStr = file_get_contents($config->DIRS['Classic Hymns'] . '/Tagalog/preface.txt');
            $prefaceStr = str_replace("\n", '<br/>', $prefaceStr);
            break;
    }

    $content = $HEADER;
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
    $content .= '<span class="category">Preface</span></td></tr>';
    $topNavBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, null, 'index') . '">Contents</a></td></tr>';
    $content .= $topNavBar;
    $content .= '</table>';
    $content .= '<div id="verses">';
    $content .= $prefaceStr;
    $content .= '</div>
<table border="0" cellpadding="3" cellspacing="0">';
    $content .= $topNavBar;
    $content .= '</table>';
    $content .= $FOOTER;
    $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, null, 'preface'), 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function saveCategories($type, $typeDir)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'saveCategories(type=' . $type . ', typeDir=' . $typeDir . ')' . PHP_EOL;

    $categoryManager = new CategoryManager();
    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $numList = $this->songService->getFileList($type, true);
    $typeList = array();
    foreach ($numList as $num) {
        if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
            $songType = $regs[1];
            $songNum = $regs[2];
        } else {
            $songType = $type;
            $songNum = $num;
        }
        $song = $this->songService->getSong($songType, $songNum);
        $songCategory = trim($song->category);
        if ($songCategory == '') {
            continue;
        }
        list($cat, $subcat) = $categoryManager->splitCategoryAndSubcategory($songCategory, $songType);
        $typeList[$cat] = 1;
    }
    ksort($typeList);

    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $content = $HEADER;
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
    $content .= '<span class="category">Categories</span></td></tr>';
    $navBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, null, 'index') . '">Contents</a></td></tr>';
    $content .= $navBar;
    $content .= '</table>';
    $content .= '<div id="verses">';
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    foreach ($typeList as $category => $ignore) {
        $map = $categoryManager->getSongInfoMap($category, $type);
        if (count($map) == 0) {
            echo 'ERROR: Cannot get category info for ', $category, ' - type=', $type, "\n";
            continue;
        }
        $canSaveCategory = true;
        if (count($map) == 1) {
            $keys = array_keys($map);
            $subcategory = $keys[0];
            $typeNumList = $map[$subcategory];
            $catSubCat = $category;
            $typeNumCount = count($typeNumList);
            if ($subcategory == 'NULL') {
                $canSaveCategory = false;
            } else {
                $catSubCat .= '&mdash;' . $subcategory;
            }
            if ($typeNumCount == 1) {
                $typeNumKeys = array_keys($typeNumList);
                list($songType, $songNum) = explode(':', $typeNumKeys[0]);
                $file = getFile($type, $songType, $typeDir, $songNum, 'song');
                switch ($type) {
                    case 'vsb':
                        $displayValue = 'YP' . Constants::$REVERSE_YPSB[$songType . $songNum];
                        break;
                    default:
                        $displayValue = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                        break;
                }
            } else {
                $file = getFile($type, $type, $typeDir, null, $subcategory == 'NULL' ? 'category' : 'subcategory', $catSubCat);
                $displayValue = $typeNumCount . ' songs';
            }
            $content .= '<tr><td><a href="' . $file . '">' . $catSubCat . '</a></td><td align="right">';
            $content .= $displayValue;
            $content .= '</td></tr>';
            saveSubcategory($type, $typeDir, $category, ($subcategory != 'NULL' ? $subcategory : null), $typeNumList);
        } else {
            $content .= '<tr><td><a href="' . getFile($type, $type, $typeDir, null, 'category', $category) . '">' . $category . '</a></td><td align="right">';
            $content .= count($map);
            $content .= '</td></tr>';
        }
        if ($canSaveCategory) {
            saveCategory($type, $typeDir, $category, $map);
        }
    }
    $content .= '</table>
</div>
<table border="0" cellpadding="3" cellspacing="0">';
    $content .= $navBar;
    $content .= '</table>';
    $content .= $FOOTER;
    $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, 0, 'categories'), 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function saveCategory($type, $typeDir, $category, $map)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'saveCategory(type=' . $type . ', typeDir=' . $typeDir . ', category=' . $category . ')' . PHP_EOL;

    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $content = $HEADER;
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
    $content .= '<span class="category">Category: ' . $category . '</span></td></tr>';
    $navBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, null, 'categories') . '">Categories</a></td></tr>';
    $content .= $navBar;
    $content .= '</table>';
    $content .= '<div id="verses">';
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    foreach ($map as $subcategory => $typeNumList) {
        $catSubCat = $category;
        if ($subcategory != 'NULL') {
            $catSubCat .= '&mdash;' . $subcategory;
        }
        $typeNumCount = count($typeNumList);
        if ($typeNumCount == 1) {
            $typeNumKeys = array_keys($typeNumList);
            list($songType, $songNum) = explode(':', $typeNumKeys[0]);
            $file = getFile($type, $songType, $typeDir, $songNum, 'song');
            switch ($type) {
                case 'vsb':
                    $displayValue = 'YP' . Constants::$REVERSE_YPSB[$songType . $songNum];
                    break;
                default:
                    $displayValue = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                    break;
            }
        } else {
            $file = getFile($type, $type, $typeDir, null, 'subcategory', $catSubCat);
            $displayValue = $typeNumCount . ' songs';
        }
        $content .= '<tr><td><a href="' . $file . '">' . $subcategory . '</a></td><td align="right">';
        $content .= $displayValue;
        $content .= '</td></tr>';
        saveSubcategory($type, $typeDir, $category, ($subcategory != 'NULL' ? $subcategory : null), $typeNumList);
    }
    $content .= '</table>
</div>
<table border="0" cellpadding="3" cellspacing="0">';
    $content .= $navBar;
    $content .= '</table>';
    $content .= $FOOTER;
    $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, null, 'category', $category), 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function saveSubcategory($type, $typeDir, $category, $subcategory, $typenumlist)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'saveSubcategory(type=' . $type . ', typeDir=' . $typeDir . ', category=' . $category . ', subcategory=' . $subcategory . ')' . PHP_EOL;

    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $content = $HEADER;
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
    $content .= '<span class="category">Category: ';
    if ($subcategory) {
        $content .= '<a href="' . getFile($type, $type, $typeDir, null, 'category', $category) . '">' . $category . '</a>&mdash;' . $subcategory;
    } else {
        $content .= $category;
    }
    $content .= '</span></td></tr>';
    $navBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, null, 'categories') . '">Categories</a></td></tr>';
    $content .= $navBar;
    $content .= '</table>';
    $content .= '<div id="verses">';
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $titles = getSortedTitlesFromTypeNumList($typenumlist);
    foreach ($titles as $lowerTitle => $titleTypeNum) {
        list($title, $songType, $songNum) = explode('|', $titleTypeNum);
        switch ($type) {
            case 'vsb':
                $displayNum = 'YP' . Constants::$REVERSE_YPSB[$songType . $songNum];
                break;
            default:
                $displayNum = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                break;
        }
        $file = getFile($type, $songType, $typeDir, $songNum, 'song');
        $content .= '<tr><td><a href="' . $file . '">' . $title . '</a></td><td align="right">' . $displayNum . '</td></tr>';
    }
    $content .= '</table>
</div>
<table border="0" cellpadding="3" cellspacing="0">';
    $content .= $navBar;
    $content .= '</table>';
    $content .= $FOOTER;
    $catTitle = $category;
    if ($subcategory) {
        $catTitle .= '&mdash;' . $subcategory;
    }
    $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, null, $subcategory == null ? 'category' : 'subcategory', $catTitle), 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function getSongFirstLines($song)
{
    $list = array('', '', '', '');
    $firstVerse = true;
    $firstChorus = true;
    $firstChar = '';
    foreach ($song->stanzas as $stanza) {
        if (($firstVerse && $stanza->type == 'verse') ||
            ($firstChorus && $stanza->type == 'chorus')
        ) {
            $textArray = explode("<br/>", $stanza->text);
            $textArrayIndex = 0;
            if (preg_match('/^\(/', $textArray[0], $regs)) {
                $textArrayIndex++;
            }
            $title = trim(str_replace(array('«', '&nbsp;'), array('', ''), $textArray[$textArrayIndex]));
            $title = trim(preg_replace("/^([^\(]+).*$/", '${1}', $title));
            if (preg_match("/^([\"]*)(.+)([&<\"\*\/\(\),;:\.\?!\-])$/", $title, $regs)) {
                $title = $regs[2];
                if ($regs[3] == '"' && $regs[1] != '"') {
                    $title .= '"';
                }
            }
            $upperTitle = strtoupper($title);
            if ($song->type == 'hr') {
                $charIndex = 0;
                if ($title[0] == '"' || $title[0] == '\'') {
                    $charIndex++;
                }
                setlocale(LC_CTYPE, 'en_CA.UTF8');
                $firstChar = mb_strtoupper(substr($title, $charIndex, 2), "UTF-8");
                $upperTitle = mb_strtoupper($title, "UTF-8");
            } else if ($song->type == 'hp' || $song->type == 'hs') {
                $charIndex = 0;
                if ($title[0] == '"' || $title[0] == '\'') {
                    $charIndex++;
                }
                if (preg_match('/[A-Za-z]/', $title[$charIndex], $regs)) {
                    $firstChar = strtoupper($title[$charIndex]);
                } else {
                    $firstChar = strtoupper(substr($title, $charIndex, 2));
                }
            } else {
                $firstChar = strtoupper($title[0]);
                if ($firstChar == '"' || $firstChar == '\'') {
                    $firstChar = strtoupper($title[1]);
                }
            }
            if ($stanza->type == 'verse') {
                $list[0] = $title;
                $list[1] = $firstChar;
                $firstVerse = false;
            } else {
                $list[2] = $upperTitle;
                $list[3] = $firstChar;
                $firstChorus = false;
            }
        }
    }
    if (!$list[0] || $list[0] == '') {
        echo 'WARNING: type=', $song->type, ', num=', $song->num, ', firstChar=', $firstChar, ', textArray[0]=', $textArray[0], "\n";
    }
    return $list;
}

function saveChineseFirstLines($type, $typeDir)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'saveChineseFirstLines(type=' . $type . ', typeDir=' . $typeDir . ')' . PHP_EOL;

    if ($type == 'ts') {
        $contentTitle = '補充本詩歌首句筆畫索引';
        $indices = array('一畫', '二畫', '三畫', '四畫', '五畫', '六畫', '七畫', '八畫', '九畫', '十畫', '十一畫', '十二畫', '十三畫', '十四畫', '十五畫', '十六畫', '十七畫', '十八畫', '十九畫', '二十四畫', '二十六畫');
        $file = 'TaiwanSupplementFirstLines.txt';
    } else {
        $contentTitle = '詩歌首句筆畫索引';
        $indices = array('一畫', '二畫', '三畫', '四畫', '五畫', '六畫', '七畫', '八畫', '九畫', '十畫', '十一畫', '十二畫', '十三畫', '十四畫', '十五畫', '十六畫', '十七畫', '十八畫', '十九畫', '二十畫', '二十二畫', '二十三畫', '二十四畫', '二十六畫');
        $file = 'ChineseFirstLines.txt';
    }

    $inputLines = file(__DIR__ . $file);
    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $count = 0;
    foreach ($indices as $index) {
        $content = $HEADER;
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
        $content .= '<span class="category">' . $contentTitle . '</span></td></tr>';
        $navBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, null, 'index') . '">Contents</a></td></tr>';
        $content .= $navBar;
        $letterBar = '<tr><td id="topversenav" colspan="4">';
        $first = true;
        $letterCount = 0;
        foreach ($indices as $letter) {
            if ($first) {
                $first = false;
            } else {
                $letterBar .= '&nbsp; ';
            }
            if ($letter == $index) {
                $letterBar .= '<b>' . $letter . '</b>';
            } else {
                $letterBar .= '<a href="' . getFile($type, $type, $typeDir, $letterCount, 'first-lines') . '">' . $letter . '</a>';
            }
            $letterCount++;
        }
        $letterBar .= '</td></tr>';
        $content .= $letterBar;
        $content .= '</table>';
        $content .= '<div id="verses">';
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        $list = array();
        $found = false;
        foreach ($inputLines as $line) {
            $line = trim($line);
            if ($line == $index) {
                $found = true;
                continue;
            }
            if ($found) {
                if ($line == '') {
                    break;
                }
                array_push($list, $line);
            }
        }
        if (!$found) {
            echo 'ERROR: no match for letter: ', $index, "\n";
            exit;
        }
        foreach ($list as $line) {
            list($firstLineTitle, $typeNum) = explode(' ', $line);
            unset($songType);
            unset($songNum);
            if ($type == 'ts') {
                if (preg_match('/^\(([^:]+):([^:]+)\)$/', $typeNum, $regs)) {
                    $songType = $regs[1];
                    $songNum = $regs[2];
                } else {
                    echo 'ERROR: Invalid line: ', $line, "\n";
                    exit;
                }
            } else {
                if (preg_match('/^\(([^\)]+)\)$/', $typeNum, $regs)) {
                    $songType = 'ch';
                    $songNum = $regs[1];
                } else {
                    echo 'ERROR: Invalid line: ', $line, "\n";
                    exit;
                }
            }
            switch ($type) {
                case 'vsb':
                    $displayNum = 'YP' . Constants::$REVERSE_YPSB[$songType . $songNum];
                    break;
                default:
                    $displayNum = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                    break;
            }
            $file = getFile($type, $songType, $typeDir, $songNum, 'song');
            $content .= '<tr><td><a href="' . $file . '">' . $firstLineTitle . '</a></td><td align="right">' . $displayNum . '</td></tr>';
        }
        $content .= '</table>
</div>
<table border="0" cellpadding="3" cellspacing="0">';
        $content .= $letterBar;
        $content .= $navBar;
        $content .= '</table>';
        $content .= $FOOTER;
        $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, $count, 'first-lines'), 'w');
        fwrite($fp, $content);
        fclose($fp);
        $count++;
    }
}

function saveFirstLines($type, $typeDir)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'saveFirstLines(type=' . $type . ', typeDir=' . $typeDir . ')' . PHP_EOL;

    if ($type == 'ch' || $type == 'ts') {
        saveChineseFirstLines($type, $typeDir);
        return;
    }

    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $numList = $this->songService->getFileList($type, true);
    $typeList = array();
    foreach ($numList as $num) {
        if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
            $songType = $regs[1];
            $songNum = $regs[2];
        } else {
            $songType = $type;
            $songNum = $num;
        }
        $song = $this->songService->getSong($songType, $songNum);
        list($verseTitle, $verseChar, $chorusTitle, $chorusChar) = getSongFirstLines($song);
        if (!$typeList[$verseChar]) {
            $typeList[$verseChar] = array();
        }
        array_push($typeList[$verseChar], convertToKey($verseTitle) . '|' . $verseTitle . '|' . $songType . '|' . $songNum);
        if ($chorusTitle) {
            if (!$typeList[$chorusChar]) {
                $typeList[$chorusChar] = array();
            }
            array_push($typeList[$chorusChar], convertToKey($chorusTitle) . '|' . $chorusTitle . '|' . $songType . '|' . $songNum);
        }
    }
    ksort($typeList);

    $count = 0;
    $letters = array();
    foreach ($typeList as $firstChar => $list) {
        array_push($letters, $firstChar);
    }
    foreach ($typeList as $firstChar => $list) {
        $content = $HEADER;
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
        $content .= '<span class="category">First Lines</span></td></tr>';
        $navBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, null, 'index') . '">Contents</a></td></tr>';
        $content .= $navBar;
        $letterBar = '<tr><td id="topversenav" colspan="4">';
        $first = true;
        $letterCount = 0;
        foreach ($letters as $letter) {
            if ($first) {
                $first = false;
            } else {
                $letterBar .= '&nbsp; ';
            }
            if ($letter == $firstChar) {
                $letterBar .= '<b>' . $letter . '</b>';
            } else {
                $letterBar .= '<a href="' . getFile($type, $type, $typeDir, $letterCount, 'first-lines') . '">' . $letter . '</a>';
            }
            $letterCount++;
        }
        $letterBar .= '</td></tr>';
        $content .= $letterBar;
        $content .= '</table>';
        $content .= '<div id="verses">';
        sort($list);
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        foreach ($list as $lowerTitleTitleTypeNum) {
            list($lowerTitle, $songTitle, $songType, $songNum) = explode('|', $lowerTitleTitleTypeNum);
            switch ($type) {
                case 'vsb':
                    $displayNum = 'YP' . Constants::$REVERSE_YPSB[$songType . $songNum];
                    break;
                default:
                    $displayNum = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                    break;
            }
            $file = getFile($type, $songType, $typeDir, $songNum, 'song');
            $content .= '<tr><td><a href="' . $file . '">' . $songTitle . '</a></td><td align="right">' . $displayNum . '</td></tr>';
        }
        $content .= '</table>
</div>
<table border="0" cellpadding="3" cellspacing="0">';
        $content .= $letterBar;
        $content .= $navBar;
        $content .= '</table>';
        $content .= $FOOTER;
        $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, $count, 'first-lines'), 'w');
        fwrite($fp, $content);
        fclose($fp);
        $count++;
    }
}

function convertToKey($str)
{
    return strtolower(str_replace(array('"', '\''), array('', ''), $str));
}

function saveSongCategories($type, $typeDir, $songCategoryType, $indexLetters = true)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'saveSongCategories(type=' . $type . ', typeDir=' . $typeDir . ', songCategoryType=' . $songCategoryType . ')' . PHP_EOL;

    $numList = $this->songService->getFileList($type, true);
    $typeList = array();
    foreach ($numList as $num) {
        if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
            $songType = $regs[1];
            $songNum = $regs[2];
        } else {
            $songType = $type;
            $songNum = $num;
        }
        $song = $this->songService->getSong($songType, $songNum);
        $songCategoryList = null;
        switch ($songCategoryType) {
            case 'authors':
                $songCategoryList = $song->authors ? $song->authors : null;
                break;
            case 'composers':
                $songCategoryList = $song->composers ? $song->composers : null;
                break;
            case 'meters':
                $songCategoryList = $song->meter ? array($song->meter) : null;
                break;
            case 'hymncodes':
                $songCategoryList = $song->hymnCode ? array($song->hymnCode) : null;
                break;
            case 'times':
                $songCategoryList = $song->time ? array($song->time) : null;
                break;
            case 'keys':
                $songCategoryList = $song->key ? array($song->key) : null;
                break;
        }
        if ($songCategoryList) {
            foreach ($songCategoryList as $obj) {
                switch ($songCategoryType) {
                    case 'authors':
                    case 'composers':
                        $realName = ucwords($this->songService->parseWriterForLink($obj->name));
                        $key = convertToKey($realName);
                        $additionalInfo = $obj->biodate;
                        break;
                    default:
                        $key = $obj;
                        $realName = $obj;
                        $additionalInfo = '';
                        break;
                }
                $firstChar = strtoupper($key[0]);
                if (array_key_exists($firstChar, $typeList)) {
                    $list = $typeList[$firstChar];
                } else {
                    $list = array();
                }
                array_push($list, $key . '|' . $realName . '|' . $additionalInfo . '|' . $songType . '|' . $songNum);
                $typeList[$firstChar] = $list;
            }
        }
    }
    ksort($typeList);

    $padKeys = false;
    switch ($songCategoryType) {
        case 'authors':
            $categoryName = 'Authors';
            $singleType = 'author';
            break;
        case 'composers':
            $categoryName = 'Composers';
            $singleType = 'composer';
            break;
        case 'meters':
            $categoryName = 'Meters';
            $singleType = 'meter';
            $padKeys = true;
            break;
        case 'hymncodes':
            $categoryName = 'Hymn Codes';
            $singleType = 'hymncode';
            $padKeys = true;
            break;
        case 'times':
            $categoryName = 'Time Signatures';
            $singleType = 'time';
            break;
        case 'keys':
            $categoryName = 'Key Signatures';
            $singleType = 'key';
            break;
        default:
            $categoryName = '';
            $singleType = '';
            break;
    }

    if (!$indexLetters) {
        $newTypeList = array();
        foreach ($typeList as $letter => $list) {
            $newTypeList = array_merge($newTypeList, $list);
        }
        $typeList = array();
        $typeList['All'] = $newTypeList;
    }

    $count = 0;
    foreach ($typeList as $letter => $list) {
        sort($list);
        $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
        $content = $HEADER;
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
        $content .= '<span class="category">' . $categoryName . '</span></td></tr>';
        $navBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, null, 'index') . '">Contents</a></td></tr>';
        $content .= $navBar;
        if ($indexLetters) {
            $letterBar = '<tr><td id="topversenav" colspan="4">';
            $first = true;
            $letterCount = 0;
            foreach ($typeList as $myLetter => $myList) {
                if ($first) {
                    $first = false;
                } else {
                    $letterBar .= '&nbsp; ';
                }
                if ($myLetter == $letter) {
                    $letterBar .= '<b>' . $letter . '</b>';
                } else {
                    $letterBar .= '<a href="' . getFile($type, $type, $typeDir, $letterCount, $songCategoryType) . '">' . $myLetter . '</a>';
                }
                $letterCount++;
            }
            $letterBar .= '</td></tr>';
        } else {
            $letterBar = '';
        }
        $content .= $letterBar;
        $content .= '</table>';
        $content .= '<div id="verses">';
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';

        $map = array();
        $convertList = array();
        foreach ($list as $item) {
            list($key, $objName, $objAdditionalInfo, $songType, $songNum) = explode('|', $item);
            if ($padKeys) {
                $key = str_pad($key, 30, '_', STR_PAD_RIGHT);
            }
            if (array_key_exists($key, $map)) {
                $itemList = $map[$key];
            } else {
                $itemList = array();
            }
            array_push($itemList, $item);
            $map[$key] = $itemList;
            $convertList[$key] = $objName . '|' . $objAdditionalInfo;
        }
        ksort($map);

        foreach ($map as $key => $itemList) {
            if ($padKeys) {
                $key = ltrim($key, '_');
            }
            list($objName, $objAdditionalInfo) = explode('|', $convertList[$key]);
            $songCount = count($itemList);
            if ($songCount == 1) {
                list($thisKey, $thisObjName, $thisObjAdditionalInfo, $songType, $songNum) = explode('|', $itemList[0]);
                switch ($type) {
                    case 'vsb':
                        $rightColValue = 'YP' . Constants::$REVERSE_YPSB[$songType . $songNum];
                        break;
                    default:
                        $rightColValue = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                        break;
                }
                $file = getFile($type, $songType, $typeDir, $songNum, 'song');
            } else {
                $file = getFile($type, $type, $typeDir, null, $singleType, $objName);
                $rightColValue = $songCount . ' songs';
            }
            $content .= '<tr><td><a href="' . $file . '">' . $objName . '</a>';
            if ($objAdditionalInfo != '') {
                $content .= ' (' . $objAdditionalInfo . ')';
            }
            $content .= '</td><td align="right">';
            $content .= $rightColValue;
            $content .= '</td></tr>';
            saveSongCategory($type, $typeDir, $songCategoryType, $objName, $itemList, $count);
        }
        $content .= '</table>
	</div>
	<table border="0" cellpadding="3" cellspacing="0">';
        $content .= $letterBar;
        $content .= $navBar;
        $content .= '</table>';
        $content .= $FOOTER;
        $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, $count, $songCategoryType), 'w');
        fwrite($fp, $content);
        fclose($fp);
        $count++;
    }
}

function saveSongCategory($type, $typeDir, $songCategoryType, $name, $list, $fileindex)
{
    global $HEADER;
    global $FOOTER;
    global $config;

    echo 'saveSongCategory(type=' . $type . ', typeDir=' . $typeDir . ', songCategoryType=' . $songCategoryType . ')' . PHP_EOL;

    switch ($songCategoryType) {
        case 'authors':
            $mainCategoryName = 'Authors';
            $categoryName = 'Author';
            $singleType = 'author';
            break;
        case 'composers':
            $mainCategoryName = 'Composers';
            $categoryName = 'Composer';
            $singleType = 'composer';
            break;
        case 'meters':
            $mainCategoryName = 'Meters';
            $categoryName = 'Meter';
            $singleType = 'meter';
            break;
        case 'hymncodes':
            $mainCategoryName = 'Hymn Codes';
            $categoryName = 'Hymn Code';
            $singleType = 'hymncode';
            break;
        case 'times':
            $mainCategoryName = 'Time Signatures';
            $categoryName = 'Time Signature';
            $singleType = 'time';
            break;
        case 'keys':
            $mainCategoryName = 'Key Signatures';
            $categoryName = 'Key Signature';
            $singleType = 'key';
            break;
        default:
            $mainCategoryName = '';
            $categoryName = '';
            $singleType = '';
            break;
    }

    $title = BookConstants::$TYPE_TO_PAGE_TITLE[$type];
    $content = $HEADER;
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
    $content .= '<span class="category">' . $categoryName . ': ' . $name . '</span></td></tr>';
    $navBar = '<tr><td id="nav" align="center" colspan="4"><a href="' . getFile($type, $type, $typeDir, $fileindex, $songCategoryType) . '">' . $mainCategoryName . '</a></td></tr>';
    $content .= $navBar;
    $content .= '</table>';
    $content .= '<div id="verses">';
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $typeNumList = array();
    foreach ($list as $item) {
        list($key, $objName, $objAdditionalInfo, $songType, $songNum) = explode('|', $item);
        if ($objName == $name) {
            $typeNumList[$songType . ':' . $songNum] = 1;
        }
    }

    $titles = getSortedTitlesFromTypeNumList($typeNumList);
    foreach ($titles as $lowerTitle => $titleTypeNum) {
        list($title, $songType, $songNum) = explode('|', $titleTypeNum);
        switch ($type) {
            case 'vsb':
                $displayNum = 'YP' . Constants::$REVERSE_YPSB[$songType . $songNum];
                break;
            default:
                $displayNum = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                break;
        }
        $file = getFile($type, $songType, $typeDir, $songNum, 'song');
        $content .= '<tr><td><a href="' . $file . '">' . $title . '</a></td><td align="right">' . $displayNum . '</td></tr>';
    }
    $content .= '</table>
</div>
<table border="0" cellpadding="3" cellspacing="0">';
    $content .= $navBar;
    $content .= '</table>';
    $content .= $FOOTER;
    $fp = fopen($config->DIRS['iSilo'] . $typeDir . getFile($type, $type, $typeDir, null, $singleType, $name), 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function getSortedTitlesFromTypeNumList($typeNumList)
{

    $titles = array();
    foreach ($typeNumList as $typeNum => $ignore) {
        list($songType, $songNum) = explode(':', $typeNum);
        $song = $this->songService->getSong($songType, $songNum);
        $title = $song->title;
        $titles[convertToKey($title)] = $title . '|' . $songType . '|' . $songNum;
    }
    ksort($titles);
    return $titles;
}

$INVALID_LANGS = array();
function saveSong($type, $typeDir, Song $song, $songIndex, $numList, $saveType = 'song')
{
    global $HEADER;
    global $FOOTER;
    global $INVALID_LANGS;
    global $config;

    echo 'saveSong(type=' . $type . ', typeDir=' . $typeDir . ', num=' . $song->num . ')' . PHP_EOL;

    $num = ($type == 'vsb') ? $songIndex + 1 : $song->num;

    $realNum = preg_replace('/^([0-9]+)[^0-9]*$/', '${1}', $num);
    $isAlternateTune = ($realNum != $num);

    switch ($type) {
        case 'h':
            $title = 'Hymns, #' . $num;
            if ($isAlternateTune) {
                $title .= ' (Alternate Tune)';
            }
            break;
        case 'nt':
            $title = 'Hymns, #' . $num . ' (New Tune)';
            break;
        case 'ch':
            $title = '詩歌' . $num;
            break;
        case 'ts':
            $title = '補充本詩歌' . $num;
            break;
        case 'cb':
            $title = 'Cebuano Hymns, #' . $num;
            break;
        case 'hf':
            $title = 'French Hymns, #' . $num;
            break;
        case 'de':
            $title = 'German Hymns, #' . $num;
            break;
        case 'hp':
            $title = 'Portuguese Hymns, #' . $num;
            break;
        case 'hr':
            $title = 'Russian Hymns, #' . $num;
            break;
        case 'hs':
            $title = 'Spanish Hymns, #' . $num;
            break;
        case 'ht':
            $title = 'Tagalog Hymns, #' . $num;
            break;
        default:
            $title = $song->title;
            break;
    }

    $titleSuffix = null;
    $authorSize = $song->authors ? count($song->authors) : 0;
    $authorContent = '';
    if ($authorSize > 0) {
        $authorContent = getWriterContent($type, $typeDir, $song, 'author', $titleSuffix);
    }
    if ($titleSuffix) {
        $title .= $titleSuffix;
    }
    $fav = $this->commentService->getFavorite($song->type, $song->num);
    $comments = $fav->excerpts;

    $content = $HEADER;
    $content .= '<table border="0" cellpadding="3" cellspacing="0">';
    $content .= '<tr><td id="heading" colspan="4"><span class="title">' . $title . '<a name="top">&nbsp;</a></span><br/>';
    if ($song->category) {
        $content .= '<span class="category">';
        if (preg_match('/.+&mdash;.+/', $song->category, $regs)) {
            list($category, $subcategory) = split('&mdash;', $song->category);
            $category = mb_convert_encoding($category, 'html', 'utf-8');
            $subcategory = mb_convert_encoding($subcategory, 'html', 'utf-8');
        } else if (preg_match('/.+－.+/', $song->category, $regs)) {
            list($category, $subcategory) = split('－', $song->category);
            $category = mb_convert_encoding($category, 'html', 'utf-8');
            $subcategory = mb_convert_encoding($subcategory, 'html', 'utf-8');
        } else {
            $category = $song->category;
            $category = mb_convert_encoding($category, 'html', 'utf-8');
            $subcategory = null;
        }
        $rowCount = $this->cacheService->getRowCount('Categories', $category, $type);
        if ($rowCount > 1) {
            $content .= '<a href="' . getFile($type, $song->type, $typeDir, $song->num, 'category', $category) . '">' . $category . '</a>';
            if ($subcategory) {
                $content .= '&mdash;';
                $rowCount = $this->cacheService->getRowCount('Subcategories', $category . '_' . $subcategory, $type);
                if ($rowCount > 1) {
                    $content .= '<a href="' . getFile($type, $song->type, $typeDir, $song->num, 'subcategory', $category . '&mdash;' . $subcategory) . '">' . $subcategory . '</a>';
                } else {
                    $content .= $subcategory;
                }
            }
        } else {
            $content .= $category;
        }
        $content .= '</span></td></tr>';
    }

    $primaryInfoContent = '';
    if ($song->numbers) {
        $numberStr = '';
        $processedNumbers = array();
        foreach ($song->numbers as $langType => $langNum) {
            if ($langType == 'lsmyp') {
                continue;
            }
            $songType = BookConstants::$LANG_TYPE_TO_TYPE[$langType];
            $songNum = $langNum;
            if (preg_match('/^([^:]+):([^:]+)$/', $langNum, $regs)) {
                $songType = $regs[1];
                $songNum = $regs[2];
            }
            if (strlen($songType) == 0) {
                continue;
            }
            $displayCode = BookConstants::$TYPE_TO_NUMBER_CODE[$songType];
            if (preg_match('/^([c]*[0-9]+[b]?)(\*)$/', $songNum, $regs)) {
                $songNum = $regs[1];
                $langNumSuffix = $regs[2];
            } else {
                $langNumSuffix = null;
            }
            $thisNumberStr = '';
            $thisNumberStrValue = '';
            switch ($songType) {
                case 'hk':
                    $thisNumberStr = 'K' . $songNum;
                    break;
                case 'c':
                case 'ns':
                case 'lb':
                    $ypNum = Constants::$REVERSE_YPSB[$songType . $songNum];
                    if ($ypNum) {
                        $thisNumberStrValue = 'YP' . $ypNum;
                        if (!preg_match('/^([0-9]+)([a-z])$/', $songNum, $regs)) {
                            $songType = 'vsb';
                            $songNum = $ypNum;
                        }
                        if ($processedNumbers[$thisNumberStrValue]) {
                            break;
                        }
                        $processedNumbers[$thisNumberStrValue] = 1;

                        $thisFilename = getFile($songType, $songType, $typeDir, $songNum, 'song');
                        $isCurrentFile = ($thisFilename == getFile($type, $song->type, $typeDir, $song->num, 'song'));
                        if (!$isCurrentFile) {
                            $thisNumberStr .= '<a href="' . $thisFilename . '">';
                        }
                        $thisNumberStr .= $thisNumberStrValue;
                        if (!$isCurrentFile) {
                            $thisNumberStr .= '</a>';
                        }
                        $thisNumberStr .= PHP_EOL;
                        break;
                    }
                    if (preg_match('/^([0-9]+)([a-z])$/', $songNum, $regs)) {
                        $thisNumberStrValue = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                        switch ($regs[2]) {
                            case 'c':
                                $thisNumberStrValue = 'Chinese';
                                break;
                            case 'd':
                                $thisNumberStrValue = 'Dutch';
                                break;
                            case 'f':
                                $thisNumberStrValue = 'French';
                                break;
                            case 'de':
                                $thisNumberStrValue = 'German';
                                break;
                            case 'i':
                                $thisNumberStrValue = 'Indonesian';
                                break;
                                break;
                        }
                    } else {
                        if ($langType == 'english') {
                            $thisNumberStrValue = 'English';
                        } else {
                            echo 'ERROR: Cannot find YP number for song ', $songType, ':', $songNum, ': langtype=', $langType, ', langnum=', $langNum, "\n";
                        }
                    }
                    if (array_key_exists($thisNumberStrValue, $processedNumbers)) {
                        break;
                    }
                    $processedNumbers[$thisNumberStrValue] = 1;
                    $thisFilename = getFile($songType, $songType, $typeDir, $songNum, 'song');
                    $currentFilename = getFile($type, $song->type, $typeDir, $song->num, 'song');
                    if (!file_exists($config->DIRS['iSilo'] . $typeDir . $thisFilename)) {
                        $thisSong = $this->songService->getSong($songType, $songNum);
                        if (!file_exists($config->DIRS['iSilo'] . $typeDir . $currentFilename)) {
                            touch($config->DIRS['iSilo'] . $typeDir . $currentFilename);
                        }
                        saveSong($songType, $typeDir, $thisSong, 0, array(), 'song');
                    }
                    $isCurrentFile = ($thisFilename == $currentFilename);
                    if (!$isCurrentFile) {
                        $thisNumberStr .= '<a href="' . $thisFilename . '">';
                    }
                    $thisNumberStr .= $thisNumberStrValue;
                    if (!$isCurrentFile) {
                        $thisNumberStr .= '</a>';
                    }
                    $thisNumberStr .= PHP_EOL;
                    break;
                default:
                    if (!$displayCode) {
                        if (!$INVALID_LANGS[$langType]) {
                            $INVALID_LANGS[$langType] = 1;
                            echo 'WARNING: Invalid number code: TYPE_TO_NUMBERCODE[', $songType, ']=', $displayCode, ', langtype=', $langType, ', langnum=', $langNum, ', songtype=', $songType, "\n";
                            break;
                        }
                    }
                    $thisNumberStrValue = BookConstants::$TYPE_TO_NUMBER_CODE[$songType] . $songNum;
                    if ($processedNumbers[$thisNumberStrValue]) {
                        break;
                    }
                    $processedNumbers[$thisNumberStrValue] = 1;
                    $thisFilename = getFile($songType, $songType, $typeDir, $songNum, 'song');
                    $isCurrentFile = ($thisFilename == getFile($type, $song->type, $typeDir, $song->num, 'song'));
                    if (!$isCurrentFile) {
                        $thisNumberStr .= '<a href="' . $thisFilename . '">';
                    }
                    $thisNumberStr .= $thisNumberStrValue;
                    if (!$isCurrentFile) {
                        $thisNumberStr .= '</a>';
                    }
                    $thisNumberStr .= PHP_EOL;
                    break;
            }
            if ($thisNumberStr == '') {
                continue;
            }
            if ($numberStr != '') {
                $numberStr .= '&nbsp; ';
            }
            $numberStr .= $thisNumberStr;
            if ($langNumSuffix) {
                $thisNumberStr .= '<sup>' . $langNumSuffix . '</sup>';
            }
        }
        $primaryInfoContent .= '<tr id="primaryinfo" valign="top"><td id="numbers" colspan="4">';
        $primaryInfoContent .= $numberStr;
        $primaryInfoContent .= '</td></tr>';
    }

    $primaryInfoContent .= getPrimaryInfoContentRow($type, $typeDir, $num, $song, 'meter', $song->meter);
    $hymnCodeContent = getPrimaryInfoContentRow($type, $typeDir, $num, $song, 'hymncode', $song->hymncode);
    if ($saveType != 'melody' && !empty($song->melody)) {
        $file = getFile($type, $song->type, $typeDir, $song->num, 'melody');
        $hymnCodeContent = str_replace('</td></tr>', ' (<a href="' . $file . '">Tune</a>)</td></tr>', $hymnCodeContent);
    }
    $primaryInfoContent .= $hymnCodeContent;

    if ($primaryInfoContent != '') {
        $content .= $primaryInfoContent . "\n";
    }

    if ($saveType == 'excerpt') {
        $backBar = '<tr><td id="topversenav" align="center" colspan="4"><a href="' . getFile($type, $song->type, $typeDir, $song->num, 'song') . '">Back to Song</a></td></tr>';
        $content .= $backBar;
        $content .= '</table>';
        $content .= '<div id="verses">';
        $commentSize = count($comments);
        if ($commentSize > 0) {
            $first = true;
            foreach ($comments as $comment) {
                if ($comment->author == 'Hymnal.Net') {
                    if ($first) {
                        $first = false;
                    } else {
                        $content .= '<hr/>';
                    }
                    $content .= '<p>' . str_replace(array("Source: ", "\n"), array("<b>Source:</b> ", "<br/>"), $comment->text) . '</p>';
                }
            }
        }
        $content .= '</div>';
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        $content .= $backBar;
    } else if ($saveType == 'melody') {
        $backBar = '<tr><td id="topversenav" align="center" colspan="4"><a href="' . getFile($type, $song->type, $typeDir, $song->num, 'song') . '">Back to Song</a></td></tr>';
        $content .= $backBar;
        $content .= '</table>';
        $content .= '<div id="verses">';
        $content .= parseMelody($song->melody);
        $content .= '</div>';
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        $content .= $backBar;
    } else {
        $content .= getNavigationBar($type, $typeDir, $numList, $songIndex, true) . "\n";

        $verseNavigationContent = getVerseNavigationHTML($song, true);
        if ($verseNavigationContent) {
            $content .= '<tr><td id="topversenav" colspan="4">' . $verseNavigationContent . '</td></tr>';
        }
        $content .= '</table>';
        $content .= '<div id="verses">';
        $first = true;
        $maxStanzaNum = 0;
        $canRepeatChorus = false;
        $stanzaNumCount = 0;
        $chorusStanza = null;
        foreach ($song->stanzas as $stanza) {
            $stanzaNumCount++;
            if ($stanza->type == 'chorus') {
                $canRepeatChorus = ($stanzaNumCount == 2);
                $chorusStanza = $stanza;
            }
            if ($stanza->type == "verse") {
                $maxStanzaNum = $stanza->num;
            }
        }
        $content .= '<table border="0" cellpadding="0" cellspacing="0">';
        $stanzaNumCount = 0;
        $toSimplified = false;
        foreach ($song->stanzas as $stanza) {
            $stanzaNumCount++;
            $stanzaText = ($toSimplified) ? CConvert::t2s($stanza->text) : $stanza->text;
            $stanzaText = preg_replace('/^(.+)<br\/>[ \t]*<br\/>$/', '$1', $stanzaText);
            if ($first) {
                $first = false;
            } else {
                $content .= '<tr valign="top"><td colspan="4"><div id="verseseparator">&nbsp;</div></td></tr>';
            }
            $content .= '<tr valign="top">';
            if ($stanza->type == "chorus") {
                $content .= '<td><div id="hiddennum">' . $maxStanzaNum . '</div></td><td>&nbsp;</td><td><div id="chorusindent">&nbsp;</div></td><td style="color:#0000AA">' . $stanzaText . '</td>';
            } else if ($stanza->type == "note") {
                $content .= '<td colspan="4" align="center" style="color:gray">' . $stanzaText . '</td>';
            } else if ($stanza->type == "copyright") {
                // ignore
            } else {
                $content .= '<td><div id="stanzanum"><a href="#lversenav">' . $stanza->num . '</a></div><br/><div id="hiddennum"><a name="v' . $stanza->num . '">' . $maxStanzaNum . '</a></div></td><td>&nbsp;</td><td colspan="2">' . $stanzaText . '</td>';
                if ($canRepeatChorus && $stanzaNumCount != 1) {
                    $chorusStanzaText = ($toSimplified) ? CConvert::t2s($chorusStanza->text) : $chorusStanza->text;
                    $chorusStanzaText = preg_replace('/^(.+)<br\/>[ \t]*<br\/>$/', '$1', $chorusStanzaText);
                    $content .= '</tr>';
                    $content .= '<tr valign="top"><td colspan="4"><div id="verseseparator">&nbsp;</div></td></tr>';
                    $content .= '<tr valign="top">';
                    $content .= '<td><div id="hiddennum">' . $maxStanzaNum . '</div></td><td>&nbsp;</td><td><div id="chorusindent">&nbsp;</div></td><td style="color:#0000AA">' . $chorusStanzaText . '</td>';
                }
            }
            $content .= '</tr>';
        }
        $content .= '</table>';
        $content .= '</div>';
        $content .= '<table border="0" cellpadding="3" cellspacing="0">';
        $verseNavigationContent = getVerseNavigationHTML($song, false);
        if ($verseNavigationContent) {
            $content .= '<tr><td id="botversenav" colspan="4">' . $verseNavigationContent . '<a name="bottom">&nbsp;</a></td></tr>';
        }

        $content .= getNavigationBar($type, $typeDir, $numList, $songIndex, false) . "\n";
    }

    $secondaryInfoContent = '';
    if ($song->notes) {
        $secondaryInfoContent .= '<tr id="secondaryinfo" valign="top"><td class="infokey">Notes:&nbsp;&nbsp;</td><td class="infoval" colspan="3">';
        $secondaryInfoContent .= $song->notes;
        $secondaryInfoContent .= '</td></tr>';
    }

    $relatedSongs = $song->getRelatedSongs();
    $relatedSongList = array();
    foreach ($relatedSongs as $relatedSong) {
        $relatedSongType = null;
        switch ($relatedSong->type) {
            case 'ns':
            case 'lb':
                if (Constants::$REVERSE_YPSB[$relatedSong->type . $relatedSong->num]) {
                    $relatedSongType = 'vsb';
                }
                break;
            default:
                $relatedSongType = $relatedSong->type;
                break;
        }
        if (!$relatedSongType) {
            continue;
        }
        if (preg_match('/[0-9]+b$/', $relatedSong->num, $regs)) {
            array_push($relatedSongList, '<a href="' . getFile($relatedSongType, $relatedSong->type, $typeDir, $relatedSong->num, 'song') . '">Alternate Tune</a>');
            continue;
        }
        if (preg_match('/([0-9]+)b$/', $song->num, $regs) && $regs[1] == $relatedSong->num) {
            if ($relatedSong->type == 'nt') {
                array_push($relatedSongList, '<a href="' . getFile($relatedSongType, $relatedSong->type, $typeDir, $relatedSong->num, 'song') . '">Alternate New Tune</a>');
            } else {
                array_push($relatedSongList, '<a href="' . getFile($relatedSongType, $relatedSong->type, $typeDir, $relatedSong->num, 'song') . '">Original Tune</a>');
            }
            continue;
        }
        if ($song->type == "h" && $relatedSong->type == "nt") {
            array_push($relatedSongList, '<a href="' . getFile($relatedSongType, $relatedSong->type, $typeDir, $relatedSong->num, 'song') . '">New Tune</a>');
            continue;
        }
        if ($relatedSong->num == $song->num) {
            if ($song->type == "nt" && $relatedSong->type == "h") {
                array_push($relatedSongList, '<a href="' . getFile($relatedSongType, $relatedSong->type, $typeDir, $relatedSong->num, 'song') . '">Original Tune</a>');
                continue;
            }
        }
        array_push($relatedSongList, '<a href="' . getFile($relatedSongType, $relatedSong->type, $typeDir, $relatedSong->num, 'song') . '">' . $relatedSong->title . '</a>');
    }
    if (count($relatedSongList) > 0) {
        $relatedSongsStr = '';
        $first = true;
        foreach ($relatedSongList as $item) {
            if ($first) {
                $first = false;
            } else {
                $relatedSongsStr .= ', ';
            }
            $relatedSongsStr .= $item;
        }
        $secondaryInfoContent .= getSecondaryInfoContentRow($type, $typeDir, $num, $song, 'relatedsongs', $relatedSongsStr);
    }

    if ($song->authors) {
        $secondaryInfoContent .= $authorContent;
    }

    if ($song->composers) {
        $secondaryInfoContent .= getWriterContent($type, $typeDir, $song, 'composer');
    }

    $refs = $song->getReferences();
    $refStr = '';
    if (count($refs) > 0) {
        $first = true;
        foreach ($refs as $ref) {
            if ($first == true) {
                $first = false;
            } else {
                $refStr .= "; ";
            }
            $refStr .= $ref->title;
        }
    }
    $secondaryInfoContent .= getSecondaryInfoContentRow($type, $typeDir, $num, $song, 'bible', $refStr);

    if ($song->key) {
        $secondaryInfoContent .= getSecondaryInfoContentRow($type, $typeDir, $num, $song, 'key', $song->key);
    }

    if ($song->time) {
        $secondaryInfoContent .= getSecondaryInfoContentRow($type, $typeDir, $num, $song, 'time', $song->time);
    }

    $numOfExcerpts = 0;
    if ($saveType != 'excerpt' && $comments) {
        $commentSize = count($comments);
        if ($commentSize > 0) {
            foreach ($comments as $comment) {
                if ($comment->author == 'Hymnal.Net') {
                    $numOfExcerpts++;
                }
            }
            if ($numOfExcerpts > 0) {
                $excerptStr = $numOfExcerpts . ' excerpt';
                if ($numOfExcerpts > 1) {
                    $excerptStr .= 's';
                }
                $secondaryInfoContent .= getSecondaryInfoContentRow($type, $typeDir, $num, $song, 'excerpt', $excerptStr);
            }
        }
    }

    if ($secondaryInfoContent != '') {
        $content .= $secondaryInfoContent;
    }

    $content .= '<tr><td colspan="4" id="topborder">&nbsp;</td></tr>';
    $content .= '</table>';
    $content .= $FOOTER;

    $file = getFile($type, $song->type, $typeDir, $song->num, $saveType);
    $fp = fopen($config->DIRS['iSilo'] . $typeDir . $file, 'w');
    fwrite($fp, $content);
    fclose($fp);

    if ($saveType == 'song') {
        if (!empty($song->melody)) {
            saveSong($type, $typeDir, $song, $song->num, $numList, 'melody');
        }
        if ($numOfExcerpts > 0) {
            saveSong($type, $typeDir, $song, $song->num, $numList, 'excerpt');
        }
    }
}

function getNavigationBar($type, $typeDir, $numList, $songIndex, $isTop)
{
    $navigationBar = '<tr><td id="leftnav" style="width: 40%" align="left">';
    $navNum = findPrevious($type, $numList, $songIndex, 100);
    if ($navNum) {
        $navigationBar .= '<a href="' . getFile($type, $type, $typeDir, $navNum, 'song') . '">&lt;&lt;&lt;</a>';
    } else {
        $navigationBar .= '&lt;&lt;&lt;';
    }
    $navigationBar .= '&nbsp;&nbsp;&nbsp;';
    $navNum = findPrevious($type, $numList, $songIndex, 10);
    if ($navNum) {
        $navigationBar .= '<a href="' . getFile($type, $type, $typeDir, $navNum, 'song') . '">&lt;&lt;</a>';
    } else {
        $navigationBar .= '&lt;&lt;';
    }
    $navigationBar .= '&nbsp;&nbsp;&nbsp;';
    if ($songIndex > 0) {
        switch ($type) {
            case 'vsb':
                $navNum = $songIndex;
                break;
            default:
                $navNum = $numList[$songIndex - 1];
                break;
        }
        $navigationBar .= '<a href="' . getFile($type, $type, $typeDir, $navNum, 'song') . '">&lt;</a>';
    } else {
        $navigationBar .= '&lt;';
    }
    $navigationBar .= '</td><td id="nav" style="width: 10%" align="center">';
    $navigationBar .= '<a href="' . getFile($type, $type, $typeDir, null, 'index') . '">&diams;</a></td><td id="nav" style="width: 10%" align="center">';
    if ($isTop) {
        $navigationBar .= '<a href="#bottom">&darr;</a>';
    } else {
        $navigationBar .= '<a href="#top">&uarr;</a>';
    }
    $navigationBar .= '</td><td id="rightnav" style="width: 40%" align="right">';
    if (($songIndex + 1) < count($numList)) {
        switch ($type) {
            case 'vsb':
                $navNum = $songIndex + 2;
                break;
            default:
                $navNum = $numList[$songIndex + 1];
                break;
        }
        $navigationBar .= '<a href="' . getFile($type, $type, $typeDir, $navNum, 'song') . '">&gt;</a>';
    } else {
        $navigationBar .= '&gt;';
    }
    $navigationBar .= '&nbsp;&nbsp;&nbsp;';
    $navNum = findNext($type, $numList, $songIndex, 10);
    if ($navNum) {
        $navigationBar .= '<a href="' . getFile($type, $type, $typeDir, $navNum, 'song') . '">&gt;&gt;</a>';
    } else {
        $navigationBar .= '&gt;&gt;';
    }
    $navigationBar .= '&nbsp;&nbsp;&nbsp;';
    $navNum = findNext($type, $numList, $songIndex, 100);
    if ($navNum) {
        $navigationBar .= '<a href="' . getFile($type, $type, $typeDir, $navNum, 'song') . '">&gt;&gt;&gt;</a>';
    } else {
        $navigationBar .= '&gt;&gt;&gt;';
    }
    $navigationBar .= '</td></tr>';
    return $navigationBar;
}

function getVerseNavigationHTML($song, $isTop)
{
    $content = '<span class="gennumlinkhead">';
    if ($isTop) {
        $content .= '<a name="lversenav">';
    }
    $content .= '<b>Verses:</b>';
    if ($isTop) {
        $content .= '</a>';
    }
    $content .= '</span>';
    $size = 0;
    foreach ($song->stanzas as $stanza) {
        if ($stanza->num != "") {
            $size++;
        }
    }
    if ($size <= 1) {
        return null;
    }
    $first = true;
    foreach ($song->stanzas as $stanza) {
        if ($stanza->num == "") {
            continue;
        }
        if ($first) {
            $first = false;
        }
        $content .= ' <span class="gennumlink"><a href="#v' . $stanza->num . '">' . $stanza->num . '</a></span>';
    }
    return $content;
}

function findPrevious($type, $numlist, $songindex, $modValue)
{
    switch ($type) {
        case 'vsb':
            $retindex = $songindex - $modValue + 1;
            if ($retindex > 1) {
                return $retindex;
            }
            return null;
    }
    for ($i = $songindex - 5; $i >= 0; $i--) {
        $num = preg_replace('/^([0-9]+)[^0-9]*$/', '${1}', $numlist[$i]);
        if ($num % $modValue == 0) {
            return $numlist[$i + 1];
        }
    }
    if ($songindex > 1) {
        return $numlist[0];
    }
    return null;
}

function findNext($type, $numList, $songIndex, $modValue)
{
    $maxCount = count($numList);
    switch ($type) {
        case 'vsb':
            $retIndex = $songIndex + $modValue + 1;
            if ($retIndex <= $maxCount) {
                return $retIndex;
            }
            return null;
    }
    for ($i = $songIndex + 5; $i < $maxCount; $i++) {
        $num = preg_replace('/^([0-9]+)[^0-9]*$/', '${1}', $numList[$i]);
        if ($num % $modValue == 0) {
            return $num;
        }
    }
    return null;
}

function getFileLink($section, $type, $typeDir, $num, $field, $value, $displayValue)
{
    if (isHiddenType($section)) {
        return $displayValue;
    }
    $link = getFile($section, $type, $typeDir, $num, $field, $value);
    if ($link == null) {
        return $displayValue;
    }
    return '<a href="' . $link . '">' . $displayValue . '</a>';
}

function getFile($section, $type, $typeDir, $num, $field, $value = null)
{
    if ($type == 'vsb') {
        if (array_key_exists($num, Constants::$YPSB) &&
            preg_match('/^([^0-9]+)([0-9]+[b]*)$/', Constants::$YPSB[$num], $regs)
        ) {
            $type = $regs[1];
            $num = $regs[2];
        }
    }
    if ($section && !BookConstants::$TYPE_TO_FILE_PREFIX[$section]) {
        echo 'ERROR: getFile(', $section, ', ', $type, ', ', $num, ', ', $field, ', ', $value, '): invalid TYPE_TO_FILEPREFIX[', $section, ']=', BookConstants::$TYPE_TO_FILE_PREFIX[$section], "\n";
        return null;
    }
    $thisSection = getSectionForField($section, $field);
    if ($thisSection == null) {
        return null;
    }
    $filename = BookConstants::$TYPE_TO_FILE_PREFIX[$thisSection] ? BookConstants::$TYPE_TO_FILE_PREFIX[$thisSection] : '00';
    $filename .= BookConstants::$FIELD_TO_FILE_PREFIX[$field];
    switch ($section) {
        case 'vsb':
            $songNum = Constants::$REVERSE_YPSB[$type . $num];
            break;
        default:
            $songNum = $num;
            break;
    }
    if ($songNum == null) {
        $songNum = '0';
    }
    switch ($field) {
        case 'home':
        case 'index':
        case 'preface':
        case 'categories':
        case 'first-lines':
        case 'authors':
        case 'composers':
        case 'meters':
        case 'hymncodes':
        case 'keys':
        case 'times':
            $filename .= str_pad($num, 5, '0', STR_PAD_LEFT) . '.html';
            break;
        case 'song':
        case 'melody':
        case 'excerpt':
            $realNum = preg_replace('/^([c]*[0-9]+)[^0-9]*$/', '${1}', $songNum);
            if ($songNum == '0') {
                echo 'ERROR: invalid num - getFile(', $section, ', ', $type, ', ', $typeDir, ', ', $num, ', ', $field, ($value ? ', ' . $value : ''), ')', "\n";
                return null;
            }
            $isAlternateTune = ($realNum != $songNum);
            if ($isAlternateTune) {
                $filename .= str_pad($songNum, 5, '0', STR_PAD_LEFT);
            } else {
                $filename .= str_pad($songNum, 4, '0', STR_PAD_LEFT) . '0';
            }
            $filename .= '.html';
            break;
        case 'author':
        case 'composer':
        case 'category':
        case 'subcategory':
        case 'meter':
        case 'hymncode':
        case 'key':
        case 'time':
            $filename .= $value . '.html';
            break;
        default:
            echo 'ERROR: getFile(', $section, ', ', $type, ', ', $typeDir, ', ', $num, ', ', $field, ($value ? ', ' . $value : ''), ')', "\n";
            exit;
    }
    $filename = mb_convert_encoding($filename, 'html', 'utf-8');
    $filename = htmlentities($filename, ENT_QUOTES);
    $filename = str_replace(array('/', '#', '&', ';', ' ', '"', '?', 'ó', 'amp'), array('_', '', '', '', '_', '', '', '', ''), $filename);
    if (strlen($filename) > 240) {
        $filename = substr($filename, 0, 240);
    }
    if (preg_match('/^(15).+$/', $filename, $ig)) {
        echo '## filename=' . $filename . PHP_EOL;
    }
    if ($type) {
        if ($section == 'lb' || $section == 'ns' || $section == 'c') {
            $songTypeDir = $this->songTypes['vsb'];
        } else {
            $songTypeDir = $this->songTypes[$section];
        }
        return '../' . $songTypeDir . $filename;
    }
    return $filename;
}

function getTitleAndDirName($type, $field)
{
    $list = array();
    $key = '';
    $dirName = '';
    switch ($field) {
        case 'meter':
            $key = 'Meter:';
            $dirName = 'Meters';
            break;
        case 'hymncode':
            $key = 'Code:';
            $dirName = 'HymnCodes';
            break;
        case 'author':
            $key = 'Lyrics:';
            $dirName = 'Authors';
            break;
        case 'composer':
            $key = 'Music:';
            $dirName = 'Composers';
            break;
        case 'key':
            $key = 'Key:';
            $dirName = 'Keys';
            break;
        case 'time':
            $key = 'Time:';
            $dirName = 'Times';
            break;
        case 'excerpt':
            $key = 'Ministry:';
            $dirName = 'Excerpts';
            break;
        case 'bible':
            $key = 'Bible:';
            $dirName = null;
            break;
        case 'relatedsongs':
            $key = 'Related:';
            $dirName = null;
            break;
    }
    $list[0] = $key;
    if (isHiddenType($type)) {
        $list[1] = null;
    } else {
        $list[1] = $dirName;
    }
    $list[1] = $dirName;
    return $list;
}

function getPrimaryInfoContentRow($type, $typeDir, $num, $song, $field, $value)
{
    if (!$value) {
        return '';
    }
    list($key, $dirName) = getTitleAndDirName($type, $field);
    $content = '<tr id="primaryinfo" valign="top"><td style="width: 10%" class="infokey">' . $key . '</td><td class="infoval" style="width: 90%" colspan="3">&nbsp;&nbsp;';
    $rowCount = $this->cacheService->getRowCount($dirName, $value, $type);
    if ($rowCount > 1) {
        $content .= getFileLink($type, $song->type, $typeDir, $song->num, $field, $value, $value);
    } else {
        $content .= $value;
    }
    $content .= '</td></tr>';
    return $content;
}

function getSecondaryInfoContentRow($type, $typeDir, $num, $song, $field, $value)
{
    if (!$value) {
        return '';
    }
    list($key, $dirName) = getTitleAndDirName($type, $field);
    $content = '<tr id="secondaryinfo" valign="top"><td style="width: 10%" class="infokey">' . $key . '</td><td style="width: 90%" class="infoval" colspan="3">&nbsp;&nbsp;';
    if ($dirName) {
        $content .= getFileLink($type, $song->type, $typeDir, $song->num, $field, $value, $value);
    } else {
        $content .= $value;
    }
    $content .= '</td></tr>';
    return $content;
}

function getWriterContent($type, $typeDir, $song, $writerType, &$titleSuffix = null)
{
    if ($writerType == 'author') {
        $writerList = $song->authors;
        $title = 'Lyrics';
        $dirName = 'Authors';
    } else {
        $writerList = $song->composers;
        $title = 'Music';
        $dirName = 'Composers';
    }
    $content = '<tr id="secondaryinfo" valign="top"><td class="infokey">' . $title . ':&nbsp;&nbsp;</td><td class="infoval" colspan="3">';
    $first = true;
    foreach ($writerList as $person) {
        if ($first) {
            $first = false;
        } else {
            $content .= ', ';
        }
        if (!$titleSuffix) {
            switch ($person->name) {
                case 'Witness Lee':
                    $titleSuffix .= '<sup>*</sup>';
                    break;
                case 'Watchman Nee':
                    $titleSuffix .= '<sup>†</sup>';
                    break;
            }
        }
        $parsedWriterPrefix = '';
        $parsedWriter = $this->songService->parseWriterForLink($person->name);
        if ($parsedWriter == '') {
            continue;
        }
        if (preg_match("/^(.+)$parsedWriter$/", $person->name, $regs)) {
            $parsedWriterPrefix = $regs[1];
        }
        $rowCount = $this->cacheService->getRowCount($dirName, $parsedWriter, $type);
        $content .= $parsedWriterPrefix;
        if ($rowCount > 1) {
            switch ($type) {
                case 'vsb':
                    $songNum = Constants::$REVERSE_YPSB[$song->type . $song->num];
                    break;
                default:
                    $songNum = $song->num;
                    break;
            }
            $content .= getFileLink($type, $song->type, $typeDir, $songNum, $writerType, $parsedWriter, $parsedWriter);
        } else {
            $content .= $parsedWriter;
        }
        if (!empty($person->biodate)) {
            $content .= ' (' . $person->biodate . ')';
        }
    }
    $content .= '</td></tr>';
    return $content;
}

function parseMelody($melody)
{
    $melody = str_replace(array('(break) '), array(''), $melody);
    $bars = explode('|', $melody);
    $retStr = '';
    $firstBar = true;
    foreach ($bars as $bar) {
        $bar = trim($bar);
        if (empty($bar)) {
            continue;
        }
        if ($firstBar) {
            $firstBar = false;
            $retStr .= '|';
        } else {
            $retStr .= '&nbsp;|';
        }
        $notes = explode(' ', $bar);
        $firstNote = true;
        foreach ($notes as $note) {
            $ulType = null;
            if (preg_match('/\/\//', $note, $regs)) {
                $ulType = 'dl';
            } else if (preg_match('/\//', $note, $regs)) {
                $ulType = 'ul';
            }
            $note = str_replace(array('(', ')', '/', 'b', '#', 'Chorus'), array('', '', '', '<sup>b</sup>', '<sup>#</sup>', '<b>(Chorus)</b>'), $note);
            if ($firstNote) {
                $firstNote = false;
                $retStr .= ' ';
            } else {
                $retStr .= '&nbsp;';
            }
            if (preg_match('/^\[([0-9][^\]]+)\]$/', $note, $regs)) {
                $retStr .= '<b>' . $regs[1] . '</b>';
                continue;
            }
            if (preg_match('/^\[([A-Z].*)$/', $note, $regs)) {
                $retStr .= '<b>' . $regs[1];
                continue;
            }
            if (preg_match('/^(Major)\]$/', $note, $regs) || preg_match('/^(Minor)\]$/', $note, $regs)) {
                $retStr .= $regs[1] . '</b>';
                continue;
            }
            if (preg_match('/^\([A-Za-z:]+\)$/', $note, $regs) || preg_match('/^\(vv\.[A-Za-z0-9:,_]+\)$/', $note, $regs) || preg_match('/^\(Verse[A-Za-z0-9:,_]+\)$/', $note, $regs)) {
                $retStr .= '<b><i>' . str_replace('_', ' ', $note) . '</i></b>';
                continue;
            }
            $note = str_replace(array('[', ']'), array('', ''), $note);
            if (preg_match('/^\._(.+)$/', $note, $regs)) {
                $retStr .= '<sup>*</sup>';
                if ($ulType != null) {
                    if ($ulType == 'ul') {
                        $retStr .= $regs[1] . '/';
                    } else {
                        $retStr .= $regs[1] . '//';
                    }
                } else {
                    $retStr .= $regs[1];
                }
                continue;
            }
            if (preg_match('/^(.+)_\.(.*)$/', $note, $regs)) {
                $retStr .= $regs[1] . $regs[2];
                if ($ulType != null) {
                    if ($ulType == 'ul') {
                        $retStr .= '/';
                    } else {
                        $retStr .= '//';
                    }
                }
                $retStr .= '<sub>*</sub>';
                continue;
            }
            if ($ulType != null) {
                if ($ulType == 'ul') {
                    $note .= '/';
                } else {
                    $note .= '//';
                }
            }
            $retStr .= $note;
        }
    }
    $retStr .= '&nbsp;|';
    return $retStr;
}

?>

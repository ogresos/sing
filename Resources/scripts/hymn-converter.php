<?php

use HymnalNet\Bootstrap\Constants;
use HymnalNet\Domain\Song;
use HymnalNet\Domain\Stanza;
use HymnalNet\Services\SongService;

define('SCRIPT_MODE', true);
require_once(__DIR__ . '/../../src/bootstrap.php');

$songService = new SongService();

//convertFrenchTextToHymns();
//convertHymnNumbersForAllLanguages();
//convertGermanTextToHymns();
convertGermanTitles();

function convertHymnNumbersForAllLanguages()
{
    global $config;
	$dirTypes = array('h', 'ns', 'lb', 'nt', 'c', 'ch', 'ts', 'cb', 'hd', 'hf', 'de', 'hr', 'hp', 'hs', 'ht');
    foreach ($dirTypes as $type) {
        $dir = $config->HYMN_TYPE_TO_DIR[$type];
        echo 'Processing hymn type ' . $type . ' in dir: ' . $dir . PHP_EOL;
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/^([a-z]+)([0-9]+)\.xml$/', $file, $regs)) {
                    $num = ltrim($regs[2], '0');
                    if ($type == 'ht' && $regs[1] == 'tc') { // Tagalog from Chinese
                        $num = 'c' . $num;
                    }
                    convertSong($type, $num);
                    checkSongWithNoMusic($type, $num);
                }
            }
        }
    }
}

function convertFrenchTextToHymns()
{
    $filePath = '/Users/eric/Music/Hymns/french.txt';
    echo 'Converting French hymns in ' . $filePath . PHP_EOL;
    $lines = explode("\n", file_get_contents($filePath));
    /* @var $hymn Song */
    $hymn = null;
    $max = count($lines);
    for ($i = 0; $i < $max; $i++) {
        $line = trim($lines[$i]);
        if (strlen($line) == 0) {
            continue;
        }
        if (preg_match('/^([0-9]+)$/', $line, $regs)) {
            echo ' - French Hymns, #' . $regs[1] . PHP_EOL;
            if ($hymn != null) {
                $hymn->saveFile();
            }
            $hymn = new Song();
            $hymn->type = 'hf';
            $hymn->num = $regs[1];
            setCategoryAndSubcategory($hymn, $lines[++$i]);
            setHymnNumbersForFrench($hymn, $lines[++$i]);
            $i = setLyrics($hymn, ++$i, $lines) - 1;
        }
    }
    if ($hymn != null) {
        $hymn->saveFile();
    }
}

function convertGermanTextToHymns()
{
    $filePath = '/Users/eric/Music/Hymns/German-Hymns.txt';
    echo 'Converting German hymns in ' . $filePath . PHP_EOL;
    $lines = explode("\n", file_get_contents($filePath));
    /* @var $hymn Song */
    $hymn = null;
    $max = count($lines);
    for ($i = 0; $i < $max; $i++) {
        // Find end index
        $previousLineIsEmpty = false;
        $endIndex = $max;
        for ($j = $i; $j < $max; $j++) {
            $jLine = trim($lines[$j]);
            if (strlen($jLine) == 0) {
                if ($previousLineIsEmpty) { // previous line is empty
                    $endIndex = $j;
                    break;
                }
                $previousLineIsEmpty = true;
            } else {
                $previousLineIsEmpty = false;
            }
        }

        convertGermanHymn($lines, $i, $endIndex);
        $i = $endIndex;
    }
    if ($hymn != null) {
        $hymn->saveFile();
    }
}

function convertGermanTitles()
{
    global $config;
    global $songService;
	$dir = $config->HYMN_TYPE_TO_DIR['de'];
	echo 'Processing German hymns in dir: ' . $dir . PHP_EOL;
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			if (preg_match('/^g([0-9]+)\.xml$/', $file, $regs)) {
				$num = ltrim($regs[1], '0');
				$song = $songService->getSong('de', $num);
				$stanza = $song->stanzas[0];
				$title = $songService->getFirstLineTitle($stanza->text);
				if ($song->title != $title) {
					echo 'G' . $num . ': original=' . $song->title . ', new=' . $title . PHP_EOL;
                    $song->title = $title;
                    $song->saveFile();
				}
			}
		}
	}
}

function convertGermanHymn(array $lines, $startIndex, $endIndex)
{
    global $songService;
    $hymn = new Song();
    $hymn->type = 'de';
    $hymn->num = (int)$lines[$startIndex + 2];
    echo ' - German Hymns, #' . $hymn->num . ' (Lines ' . $startIndex . '-' . $endIndex . ')' . PHP_EOL;
    $category = trim($lines[$startIndex]);
    if (preg_match('/^(.+) – (.+)$/', $category, $regs)) {
        $hymn->category = strtoupper($regs[1]) . '&mdash;' . $regs[2];
    } else {
        $hymn->category = $category;
    }
    /* @var $stanza Stanza */
    $stanza = new Stanza();
    $hasVerseNumber = false;
    for ($i = $startIndex + 4; $i < $endIndex; $i++) {
        $line = $lines[$i];
        if (strlen(trim($line)) == 0) { // next stanza
            if ($stanza->text != null) {
                if (strpos($stanza->text, PHP_EOL) === FALSE) {
                    $stanza->type = 'note';
                }
                array_push($hymn->stanzas, $stanza);
            }
            $stanza = new Stanza();
            continue;
        }
        if (preg_match('/^\t(.+)$/', $line, $regs)) {
            $line = $regs[1];
            if ($stanza->type == 'chorus') {
                if (preg_match('/^\t(.+)$/', $line, $regs)) {
                    $line = $regs[1];
                }
            }
        }
        $line = str_replace("\t", "    ", $line);
        if ($stanza->text == null) { // First line
            if (preg_match('/^([0-9]+)[\. \t]+(.+)$/', $line, $regs)) {
                $hasVerseNumber = true;
                $stanza->type = 'verse';
                $stanza->num = $regs[1];
                $stanza->text = $regs[2];
                $hymn->title = $songService->getFirstLineTitle($regs[2]);
            } else {
                if ($hasVerseNumber) {
                    $stanza->type = 'chorus';
                    if (preg_match('/^    (.+)$/', $line, $regs)) {
                        $line = $regs[1];
                    }
                } else {
                    $stanza->type = 'verse';
                }
                $stanza->num = '';
                $stanza->text = $line;
                $hymn->title = $songService->getFirstLineTitle($line);
            }
        } else {
            $stanza->text .= PHP_EOL . $line;
        }
    }
    if ($stanza->text != null) {
        if (strpos($stanza->text, PHP_EOL) === FALSE) {
            $stanza->type = 'note';
        }
        array_push($hymn->stanzas, $stanza);
    }

    // Set hymn numbers
    $englishSong = $songService->getSong('h', $hymn->num);
    $englishSong->numbers['german'] = $hymn->num;
    $englishSong->saveFile();
    $hymn->numbers = $englishSong->numbers;
    foreach ($englishSong->numbers as $key => $langNum) {
        if ($key == 'english' || $key == 'korean' || $key == 'lsmyp' || $key == 'vsb') {
            continue;
        }
        $langType = Constants::$LANGUAGE_TO_TYPE[$key];
        list($realType, $realNum, $displayableNum) = $songService->parseNumber($langType, $langNum);
        $langSong = $songService->getSong($realType, $realNum, true);
        $langSong->numbers['german'] = $hymn->num;
        $langSong->saveFile();
    }
    if (count($englishSong->authors) > 0) {
        foreach ($englishSong->authors as $author) {
            $hasPrefix = false;
            foreach (Constants::$AUTHOR_PREFIXES as $prefix) {
                if (preg_match("/^$prefix/", $author->name, $regs)) {
                    $hasPrefix = true;
                    break;
                }
            }
            if (!$hasPrefix) {
                $author->name = 'Translated from ' . $author->name;
            }
            array_push($hymn->authors, $author);
        }
    }
    if (isset($englishSong->midiRef)) {
        $hymn->midiRef = $englishSong->midiRef;
    } else {
        $hymn->midiRef = new Song();
        $hymn->midiRef->type = $englishSong->type;
        $hymn->midiRef->num = $englishSong->num;
    }
    if (count($englishSong->links) > 0) {
        foreach ($englishSong->links as $link) {
            if ($link->type == 'ref') {
                array_push($hymn->links, $link);
            }
        }
    }

    $hymn->saveFile();
}

function setCategoryAndSubcategory($hymn, $text)
{
    if (preg_match('/^(.+) – (.+)$/', $text, $regs)) {
        $hymn->category = $regs[1] . '&mdash;' . $regs[2];
    } else {
        $hymn->category = $text;
        echo 'Info: ' . $hymn->type . ':' . $hymn->num . ' - No subcategory: ' . $text . PHP_EOL;
    }
}

function setHymnNumbersForFrench($hymn, $text)
{
    global $songService;
    if (preg_match('/^\(A ([^,\) ]+).*\)$/', $text, $regs)) {
        $englishSong = null;
        if (preg_match('/^([0-9]+)$/', $regs[1], $subRegs)) {
            $englishSong = $songService->getSong('h', $subRegs[1]);
            $englishSong->numbers['english'] = $englishSong->num;
        } else if (preg_match('/^(ns):([0-9]+)$/', $regs[1], $subRegs)) {
            $englishSong = $songService->getSong('ns', $subRegs[2]);
            $englishSong->numbers['english'] = 'ns:' . $englishSong->num;
        } else {
            echo 'Warning: ' . $hymn->type . ':' . $hymn->num . ' - Invalid number: ' . $text . PHP_EOL;
        }
        if ($englishSong != null) {
            $englishSong->numbers['french'] = $hymn->num;
            $englishSong->saveFile();
            $hymn->numbers = $englishSong->numbers;
            if (count($englishSong->authors) > 0) {
                foreach ($englishSong->authors as $author) {
                    if (preg_match('/^Translated/', $author->name, $regs)) {
                        continue;
                    }
                    array_push($hymn->authors, $author);
                }
            }
            if (isset($englishSong->midiRef)) {
                $hymn->midiRef = $englishSong->midiRef;
            } else {
                $hymn->midiRef = new Song();
                $hymn->midiRef->type = $englishSong->type;
                $hymn->midiRef->num = $englishSong->num;
            }
            if (count($englishSong->links) > 0) {
                foreach ($englishSong->links as $link) {
                    if ($link->type == 'ref') {
                        array_push($hymn->links, $link);
                    }
                }
            }
        }
    } else {
        echo 'Warning: ' . $hymn->type . ':' . $hymn->num . ' - Invalid number: ' . $text . PHP_EOL;
    }
}

function setLyrics($hymn, $i, $lines)
{
    global $songService;
    $max = count($lines);
    $stanza = null;
    for (; $i < $max; $i++) {
        $line = trim($lines[$i]);
        if (strlen($line) == 0) {
            if ($stanza != null) {
                array_push($hymn->stanzas, $stanza);
            }
            $stanza = new Stanza();
            continue;
        }
        if (preg_match('/^([0-9]+)$/', $line, $regs)) {
            return $i;
        }
        if (preg_match('/^Refrain:/', $line, $regs)) {
            $line = trim($lines[++$i]);
            $stanza->type = 'chorus';
            $stanza->num = '';
            $stanza->text = $line;
            $hymn->title = $songService->getFirstLineTitle($regs[2]);
            continue;
        }
        if ($stanza->text == null) { // First line
            if (preg_match('/^([0-9]+)\. (.+)$/', $line, $regs)) {
                $stanza->type = 'verse';
                $stanza->num = $regs[1];
                $stanza->text = $regs[2];
                $hymn->title = $songService->getFirstLineTitle($regs[2]);
            } else if (preg_match('/^(.+)$/', $line, $regs)) {
                $stanza->type = 'verse';
                $stanza->num = '';
                $stanza->text = $regs[1];
                $hymn->title = $songService->getFirstLineTitle($regs[1]);
            } else {
                echo 'Warning: ' . $hymn->type . ':' . $hymn->num . ' - Invalid first line: ' . $line . PHP_EOL;
            }
        } else {
            $stanza->text .= PHP_EOL . $line;
        }
    }
    if ($stanza != null) {
        array_push($hymn->stanzas, $stanza);
    }
    return $i;
}

function convertSong($type, $num)
{
    global $songService;
    $song = $songService->getSong($type, $num);
    /* @var $mainRefSong Song */
    $mainRefSong = null;
    if (count($song->numbers) > 0) {
        if ($song->type == 'h' || $song->type == 'ns' || $song->type == 'nt' || $song->type == 'lb' || $song->type == 'c') {
            if ($song->type == 'h') {
                $song->numbers['english'] = $song->num;
            } else {
                $song->numbers['english'] = $song->type . ':' . $song->num;
            }
        } else {
            $mainRefLangType = 'english';
            $mainRefSongType = '';
            list($langType, $langNum) = $songService->parseLanguageNumber($mainRefLangType, $song->numbers['english']);
            if (isset($langType)) {
                $mainRefSong = $songService->getSong($langType, $langNum);
                if (count($mainRefSong->numbers) < count($song->numbers)) {
                    echo 'WARNING: ' . $type . ':' . $num . ' - Number discrepancy: ' . $langType . '=' . count($mainRefSong->numbers) . ', ' . $type . '=' . count($song->numbers) . PHP_EOL;
                    print_r($mainRefSong->numbers);
                    print_r($song->numbers);
                    return;
                }
                $mainRefSong->numbers[$mainRefLangType] = $song->numbers[$mainRefLangType];
                $song->numbers = $mainRefSong->numbers;
                $song->authors = $mainRefSong->authors;
                if ($song->authors) {
                    $patterns = array('Adapted by ', 'Adapted from ', 'Altered by ', 'Arranged from ', 'Arranged by ', 'From ', 'Translated by ', 'Translated from ', 'Harmony by ', 'Modified by ');
                    foreach ($song->authors as $author) {
                        $addTranslatedFrom = true;
                        foreach ($patterns as $pattern) {
                            if (preg_match('/^' . $pattern . '.+$/', $author->name, $regs)) {
                                $addTranslatedFrom = false;
                                break;
                            }
                        }
                        if ($addTranslatedFrom) {
                            $author->name = 'Translated from ' . $author->name;
                        }
                    }
                }
            } else {
                switch ($type) {
                    case 'ht':
                        $mainRefLangType = 'tagalog';
                        break;
                    case 'ch':
                        $mainRefLangType = 'chinese';
                        break;
                    case 'ts':
                        $mainRefLangType = 'chinese';
                        $mainRefSongType = 'ts:';
                        break;
                    case 'cb':
                        $mainRefLangType = 'cebuano';
                        break;
                    case 'hd':
                        $mainRefLangType = 'dutch';
                        break;
                    case 'he':
                        $mainRefLangType = 'estonian';
                        break;
                    case 'hf':
                        $mainRefLangType = 'french';
                        break;
                    case 'de':
                        $mainRefLangType = 'german';
                        break;
                    case 'hr':
                        $mainRefLangType = 'russian';
                        break;
                    case 'hp':
                        $mainRefLangType = 'portuguese';
                        break;
                    case 'hs':
                        $mainRefLangType = 'spanish';
                        break;
                    default:
                        $mainRefLangType = null;
                        break;
                }
                if (isset($mainRefLangType)) {
                    $song->numbers[$mainRefLangType] = $mainRefSongType . $song->num;
                }
                echo 'WARNING: ' . $type . ':' . $num . ' - No main ref numbers: ' . $mainRefLangType . '=' . count($mainRefSong->numbers) . ', ' . $type . '=' . count($song->numbers) . PHP_EOL;
                print_r($song->numbers);
            }
        }
    }

    // Clear melody (only for certain hymns)
    if ($song->type != 'h' && $song->type != 'ns' && $song->type != 'lb' && $song->type != 'nt' && $song->type != 'c' && $song->type != 'hr') {
        if ($song->midiRef) {
            $song->melody = null;
        } else if (!$song->midi) {
            $song->melody = null;
            if ($mainRefSong->midiRef) {
                $song->midiRef = $mainRefSong->midiRef;
            } else if ($mainRefSong->midi) {
                $song->midiRef = new Song();
                $song->midiRef->type = $mainRefSong->type;
                $song->midiRef->num = $mainRefSong->num;
            }
        }
    }
    $song->saveFile();
}

function checkSongWithNoMusic($type, $num)
{
    global $songService;
    $song = $songService->getSong($type, $num);
    if (!$song->midiRef && !$song->midi) {
        if (count($song->numbers) > 0) {
            list($langType, $langNum) = $songService->parseLanguageNumber('english', $song->numbers['english']);
            if (isset($langType)) {
                echo 'No music for ' . $type . ':' . $num . ' - English: ' . $langType . ':' . $langNum . PHP_EOL;
            }
        }
    }
}

?>

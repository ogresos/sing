<?php

define('SCRIPT_MODE', true);
define('DEBUG', $_SERVER['debug'] == 'true');

$genType = $_SERVER['gentype'];
$type = $_SERVER['hymntype'];
if ($type == 'test') {
    $num = 'test';
} else {
    $num = ltrim($_SERVER['hymnnum'], '0');
}
define('TYPE', $type);
define('NUM', $num);

$isSimplifiedChinese = array_key_exists('isSimplifiedChinese', $_SERVER) && ($_SERVER['isSimplifiedChinese'] == 'true');
switch ($type) {
    case 'ch':
    case 'ts':
        if ($isSimplifiedChinese) {
            $GLOBALS['LANGUAGE'] = 'cn';
        } else {
            $GLOBALS['LANGUAGE'] = 'zh';
        }
        break;
    case 'de':
        $GLOBALS['LANGUAGE'] = 'de';
        break;
    case 'ns':
        if (preg_match('/^[0-9]+de$/', $num, $ig)) {
            $GLOBALS['LANGUAGE'] = 'de';
        } elseif (preg_match('/^[0-9]+c$/', $num, $ig)) {
            if ($isSimplifiedChinese) {
                $GLOBALS['LANGUAGE'] = 'cn';
            } else {
                $GLOBALS['LANGUAGE'] = 'zh';
            }
        } else {
            $GLOBALS['LANGUAGE'] = 'en';
        }
        break;
    default:
        $GLOBALS['LANGUAGE'] = 'en';
        break;
}

require_once(__DIR__ . '/../../src/bootstrap.php');

use HymnalNet\Domain\MusicParser;
use HymnalNet\Domain\Song;
use HymnalNet\Services\SongService;
use HymnalNet\Util\LilypondBuilder;

$songService = new SongService();
$songFile = $songService->getSongFile($type, $num);

if (!file_exists($songFile)) {
    error_log(' --> FILE NOT FOUND: ' . $songFile);
    return;
}
$song = $songService->getSong($type, $num);

generateLilypondFile($song);
//if ($isSimplifiedChinese) {
//    $GLOBALS['LANGUAGE'] = 'en';
//}

function generateLilypondFile(Song $song)
{
    $isSimplifiedChinese = ($GLOBALS['LANGUAGE'] == 'cn');
    $isChineseSong = ($song->type == 'ch' || $song->type == 'ts' || preg_match('/^[0-9]+c$/', $song->num, $regs));
    $fileSuffix = $isSimplifiedChinese ? '_cn' : '';
    $musicParser = new MusicParser();
    $builder = new LilypondBuilder();

    $genMidi = array_key_exists('genmidi', $_SERVER) && ($_SERVER['genmidi'] == 'true');
    $showAuthor = array_key_exists('showauthor', $_SERVER) && ($_SERVER['showauthor'] == 'true');
    $showComposer = array_key_exists('showcomposer', $_SERVER) && ($_SERVER['showcomposer'] == 'true');
    $showHymnNumber = array_key_exists('showhymnnumber', $_SERVER) && ($_SERVER['showhymnnumber'] == 'true');
    $showMeter = array_key_exists('showmeter', $_SERVER) && ($_SERVER['showmeter'] == 'true');
    $doIndent = array_key_exists('doindent', $_SERVER) && ($_SERVER['doindent'] == 'true');
    $genType = $_SERVER['gentype'];

    $dir = __DIR__ . '/';
    $file = '';

    switch ($song->type) {
        case 'h':
            if (preg_match('/b$/', $song->num, $regs)) {
                $file = "e" . str_pad($song->num, 5, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            } else {
                $file = "e" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            }
            break;
        case 'ch':
            if (preg_match('/b$/', $song->num, $regs)) {
                $file = "c" . str_pad($song->num, 5, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            } else {
                $file = "c" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            }
            break;
        case 'ts':
            if (preg_match('/b$/', $song->num, $regs)) {
                $file = "ts" . str_pad($song->num, 5, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            } else {
                $file = "ts" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            }
            break;
        case 'de':
            if (preg_match('/b$/', $song->num, $regs)) {
                $file = "g" . str_pad($song->num, 5, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            } else {
                $file = "g" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            }
            break;
        case 'hr':
            if (preg_match('/b$/', $song->num, $regs)) {
                $file = "r" . str_pad($song->num, 5, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            } else {
                $file = "r" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            }
            break;
        case 'ns':
            if (preg_match('/([a-z]+)$/', $song->num, $regs)) {
                $file = "ns" . str_pad($song->num, 4 + strlen($regs[1]), '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            } else {
                $file = "ns" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            }
            break;
        case 'nt':
            if (preg_match('/b$/', $song->num, $regs)) {
                $file = "e" . str_pad($song->num, 5, '0', STR_PAD_LEFT) . $fileSuffix . "_new_lilypond.ly";
            } else {
                $file = "e" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_new_lilypond.ly";
            }
            break;
        case 'lb':
            $file = "lb" . str_pad($song->num, 2, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            break;
        case 'c':
            $file = "child" . str_pad($song->num, 4, '0', STR_PAD_LEFT) . $fileSuffix . "_lilypond.ly";
            break;
        case 'm':
            $file = $song->num . $fileSuffix . "_lilypond.ly";
            break;
        case 'test':
            $file = "test_lilypond.ly";
            break;
    }

    echo 'Song: type=', $song->type, ', num=', $song->num, ', file=', $file, PHP_EOL;
    if (strlen(trim($song->melody)) == 0 || strlen(trim($song->singingStanza)) == 0) {
        error_log(' --> SKIPPED!');
        return;
    }

    $pianoTransposeKey = null;
    $guitarTransposeKey = null;
    $guitarCapo = null;
    $octaveCase = '';
    if ($song->key) {
        switch ($song->key) {
            case 'C Major':
                $pianoTransposeKey = 'c';
                $guitarTransposeKey = null;
                $guitarCapo = null;
                $octaveCase = '\'';
                break;
            case 'B Major':
                $pianoTransposeKey = 'b';
                $guitarTransposeKey = 'g';
                $guitarCapo = 4;
                break;
            case 'Bb Major':
                $pianoTransposeKey = 'bes';
                $guitarTransposeKey = 'g';
                $guitarCapo = 3;
                break;
            case 'Db Major':
                $pianoTransposeKey = 'des';
                $guitarTransposeKey = 'c';
                $guitarCapo = 1;
                $octaveCase = '\'';
                break;
            case 'D Major':
                $pianoTransposeKey = 'd';
                $guitarTransposeKey = 'd';
                $guitarCapo = null;
                $octaveCase = '\'';
                break;
            case 'Eb Major':
                $pianoTransposeKey = 'ees';
                $guitarTransposeKey = 'd';
                $guitarCapo = 1;
                $octaveCase = '\'';
                break;
            case 'E Major':
                $pianoTransposeKey = 'e';
                $guitarTransposeKey = 'd';
                $guitarCapo = 2;
                $octaveCase = '\'';
                break;
            case 'F Major':
                $pianoTransposeKey = 'f';
                $guitarTransposeKey = 'd';
                $guitarCapo = 3;
                $octaveCase = '\'';
                break;
            case 'F# Major':
                $pianoTransposeKey = 'fis';
                $guitarTransposeKey = 'dis';
                $guitarCapo = 4;
                $octaveCase = '\'';
                break;
            case 'F Minor':
                $pianoTransposeKey = 'aes';
                $guitarTransposeKey = 'f';
                $guitarCapo = 3;
                $octaveCase = '\'';
                break;
            case 'G Major':
                $pianoTransposeKey = 'g';
                $guitarTransposeKey = 'g';
                $guitarCapo = null;
                $octaveCase = '\'';
                break;
            case 'Ab Major':
                $pianoTransposeKey = 'aes';
                $guitarTransposeKey = 'g';
                $guitarCapo = 1;
                break;
            case 'A Major':
                $pianoTransposeKey = 'a';
                $guitarTransposeKey = 'a';
                $guitarCapo = null;
                break;
            case 'G Minor':
                $pianoTransposeKey = 'bes';
                $guitarTransposeKey = 'bes';
                $guitarCapo = null;
                break;
            case 'C Minor':
                $pianoTransposeKey = 'ees';
                $guitarTransposeKey = 'ees';
                $guitarCapo = null;
                $octaveCase = '\'';
                break;
            case 'B Minor':
                $pianoTransposeKey = 'bes';
                $guitarTransposeKey = 'bes';
                $guitarCapo = null;
                break;
            case 'D Minor':
                $pianoTransposeKey = 'f';
                $guitarTransposeKey = 'f';
                $guitarCapo = null;
                $octaveCase = '\'';
                break;
            case 'E Minor':
                $pianoTransposeKey = 'g';
                $guitarTransposeKey = 'g';
                $guitarCapo = null;
                $octaveCase = '\'';
                break;
        }
    }

    if ($genType == 'html') {
        $file = str_replace('.ly', '.html', $file);
        $fp = fopen($dir . $file, 'w');
        $bars = $musicParser->parseMelody($song->melody, $song->time, $octaveCase, $isChineseSong);
        fwrite($fp, '<lilypond fragment>' . PHP_EOL);
        if ($song->time) {
            fwrite($fp, '\\time ' . $song->time . PHP_EOL);
        }
        if (preg_match('/^([^ ]+) ([^ ]+)$/', $song->key, $regs)) {
            fwrite($fp, '\\key ' . $pianoTransposeKey . '\\' . strtolower($regs[2]) . PHP_EOL);
        }
        fwrite($fp, '\\transpose c ' . $pianoTransposeKey . ' { ' . PHP_EOL);
        fwrite($fp, '\\once \\override Score.MetronomeMark #\'transparent = ##t' . PHP_EOL);
        fwrite($fp, $musicParser->barsToMelody($bars) . PHP_EOL);
        fwrite($fp, '\\bar "|."' . PHP_EOL);
        fwrite($fp, ' } ' . PHP_EOL);
        fwrite($fp, '</lilypond>' . PHP_EOL);
        exit;
    }

    $fp = fopen($dir . $file, 'w');
    fwrite($fp, '\\version "2.12.2"  % necessary for upgrading to future LilyPond versions.' . PHP_EOL);
    $indentRight = $doIndent ? ($song->type == 'h' && ($song->num % 2 == 0)) : false;
    $globalStaffSize = isset($song->globalStaffSize) ? $song->globalStaffSize : 16;
    $topMargin = isset($song->topMargin) ? $song->topMargin : '0.35';
    $bottomMargin = isset($song->bottomMargin) ? $song->bottomMargin : '0.35';
    if ($indentRight) {
        $leftMargin = '0.5';
        $rightMargin = '0.65';
    } else {
        if ($doIndent) {
            $leftMargin = '0.65';
            $rightMargin = '0.5';
        } else {
            $leftMargin = '0.5';
            $rightMargin = '0.35';
        }
    }
    fwrite($fp, '#(set-global-staff-size ' . $globalStaffSize . ')' . PHP_EOL);
    fwrite($fp, '\\paper  {' . PHP_EOL);
    fwrite($fp, '    top-margin = ' . $topMargin . '\\in' . PHP_EOL);
    fwrite($fp, '    bottom-margin = ' . $bottomMargin . '\\in' . PHP_EOL);
    fwrite($fp, '    left-margin = ' . $leftMargin . '\\in' . PHP_EOL);
    fwrite($fp, '    right-margin = ' . $rightMargin . '\\in' . PHP_EOL);
    fwrite($fp, '    paper-height = 11\\in' . PHP_EOL);
    fwrite($fp, '    paper-width = 8.5\\in' . PHP_EOL);
    fwrite($fp, '    ragged-bottom = ##t' . PHP_EOL);
    fwrite($fp, '    before-title-space = 0\\in' . PHP_EOL);
    fwrite($fp, '    after-title-space = 0\\in' . PHP_EOL);
    fwrite($fp, '    indent = #(* mm 0)' . PHP_EOL);
//     fwrite($fp, '    #(define fonts (make-pango-font-tree "TeX Gyre Schola" "LMSans10" "LMTypewriter10 Regular" (/ 14 20)))' . PHP_EOL);
    /*
        if ($isChineseSong) {
            fwrite($fp, '    #(define fonts' . PHP_EOL);
            fwrite($fp, '      (set-global-fonts' . PHP_EOL);
            fwrite($fp, '      #:music "emmentaler"' . PHP_EOL);
            fwrite($fp, '      #:brace "emmentaler"' . PHP_EOL);
            fwrite($fp, '      #:roman "Times New Roman"' . PHP_EOL);
            fwrite($fp, '      #:sans "Lucida Grande"' . PHP_EOL);
            fwrite($fp, '      #:typewriter "monospace"' . PHP_EOL);
            fwrite($fp, '      #:factor (/ staff-height pt 20)' . PHP_EOL);
            fwrite($fp, '    ))' . PHP_EOL);
        } else {
            fwrite($fp, '    #(define fonts' . PHP_EOL);
            fwrite($fp, '      (set-global-fonts' . PHP_EOL);
            fwrite($fp, '      #:music "emmentaler"' . PHP_EOL);
            fwrite($fp, '      #:brace "emmentaler"' . PHP_EOL);
            fwrite($fp, '      #:roman "Times New Roman"' . PHP_EOL);
            fwrite($fp, '      #:sans "Lucida Grande"' . PHP_EOL);
            fwrite($fp, '      #:typewriter "monospace"' . PHP_EOL);
            fwrite($fp, '      #:factor (/ staff-height pt 20)' . PHP_EOL);
            fwrite($fp, '    ))' . PHP_EOL);
        }
        */
    fwrite($fp, '}' . PHP_EOL);
    fwrite($fp, '\\header {' . PHP_EOL);
    $songTitle = $song->title;
    if ($song->type == 'nt') {
        $songTitle .= ' (New Tune)';
    }
    fwrite($fp, '    title = \\markup \\bold \\fontsize #-1"' . $builder->replaceStr($songTitle) . '"' . PHP_EOL);
    $category = $builder->replaceStr($song->category);
    $category = str_replace('—', ' — ', $category);
    fwrite($fp, '    subtitle = \\markup \\fontsize #-1 "' . $category . '"' . PHP_EOL);

    // Show Hymn Numbers
    if (false && $song->type == 'h') {
        $numbers = '';
        if ($song->numbers['chinese']) {
            if (preg_match('/^([^:]+):([^:]+)$/', $song->numbers['chinese'], $regs)) {
                if ($regs[1] == 'ts') {
                    $numbers .= 'Cs' . $regs[2];
                } else if ($regs[1] == 'ch') {
                    $numbers .= 'C' . $regs[2];
                } else {
                    error_log('Unknown langtype for chinese: ' . $song->numbers['chinese']);
                }
            } else {
                $numbers .= 'C' . $song->numbers['chinese'];
            }
        }
        if ($song->numbers['cebuano']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'Cb' . $song->numbers['cebuano'];
        }
        if ($song->numbers['dutch']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'D' . $song->numbers['dutch'];
        }
        if ($song->numbers['french']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'F' . $song->numbers['french'];
        }
        if ($song->numbers['german']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'G' . $song->numbers['german'];
        }
        if ($song->numbers['indonesian']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'I' . $song->numbers['indonesian'];
        }
        if ($song->numbers['korean']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'K' . $song->numbers['korean'] . ' ';
        }
        if ($song->numbers['portuguese']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'P' . $song->numbers['portuguese'] . ' ';
        }
        if ($song->numbers['russian']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'R' . $song->numbers['russian'];
        }
        if ($song->numbers['spanish']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'S' . $song->numbers['spanish'];
        }
        if ($song->numbers['tagalog']) {
            if ($numbers != '') {
                $numbers .= '   ';
            }
            $numbers .= 'Tg' . $song->numbers['tagalog'];
        }
        fwrite($fp, '    subsubtitle = \\markup \\fontsize #-1 "' . $numbers . '"' . PHP_EOL);
    }
    if ($showHymnNumber && $song->type != 'm' && $song->type != 'c' && $song->type != 'ns' && $song->type != 'lb') {
        $hymnNumPrefix = '';
        switch ($song->type) {
            case 'ch':
                $hymnNumPrefix = 'C';
                break;
            case 'ts':
                $hymnNumPrefix = 'Cs';
                break;
            case 'de':
                $hymnNumPrefix = 'G';
                break;
            case 'hr':
                $hymnNumPrefix = 'R';
                break;
        }
        $numberType = ($indentRight && $song->type == 'h') ? 'poet' : 'arranger';
        fwrite($fp, '    ' . $numberType . ' = \\markup { \\fontsize #8.5 "' . $hymnNumPrefix . $song->num . '" }' . PHP_EOL);
    }
    if ($showMeter) {
        fwrite($fp, '    meter = "' . $song->meter . '"' . PHP_EOL);
    }
    if ($showAuthor && $song->authors) {
        $authors = '';
        foreach ($song->authors as $person) {
            if ($authors != '') {
                $authors .= ', ';
            }
            $authors .= $person->name;
        }
        fwrite($fp, '    poet = "' . $builder->replaceStr($authors) . '"' . PHP_EOL);
    }
    if ($showComposer && $song->composers) {
        $composers = '';
        foreach ($song->composers as $person) {
            if ($composers != '') {
                $composers .= ', ';
            }
            $composers .= $person->name;
        }
        fwrite($fp, '    composer = "' . $builder->replaceStr($composers) . '"' . PHP_EOL);
    }
    if ($genType == 'g' || $genType == 'gt') {
        if ($guitarCapo) {
            fwrite($fp, '    piece = \\markup \\bold \\italic "(' . i18n('lilypond.guitar') . ': ' . i18n('lilypond.capo') . ' ' . $guitarCapo . ')"' . PHP_EOL);
        } else {
            fwrite($fp, '    piece = \\markup \\bold \\italic "(' . i18n('lilypond.guitar') . ')"' . PHP_EOL);
        }
    }
    if (strlen($song->sheetCopyright) > 0) {
        fwrite($fp, '    copyright = \\markup \\override #\'(font-name . "Trebuchet MS") { "' . $builder->replaceStr($song->sheetCopyright) . '" }' . PHP_EOL);
    } else {
        fwrite($fp, '    copyright = \\markup \\override #\'(font-name . "Trebuchet MS") "www.hymnal.net"' . PHP_EOL);
    }
    fwrite($fp, '    tagline = ""' . PHP_EOL);
    fwrite($fp, '}' . PHP_EOL);
    fwrite($fp, '#(define (parenthesis-ignatzek-chord-names in-pitches bass inversion context)' . PHP_EOL);
    fwrite($fp, '  (markup #:line (#:bold (ignatzek-chord-names in-pitches bass inversion context) )))' . PHP_EOL);
    fwrite($fp, 'italic = {' . PHP_EOL);
    fwrite($fp, '    \override Lyrics.LyricText #\'font-shape = #\'italic' . PHP_EOL);
    fwrite($fp, '}' . PHP_EOL);
    fwrite($fp, 'normal = {' . PHP_EOL);
    fwrite($fp, '    \revert Lyrics.LyricText #\'font-shape' . PHP_EOL);
    fwrite($fp, '    \revert Lyrics.LyricText #\'font-series' . PHP_EOL);
    fwrite($fp, '}' . PHP_EOL);
    $bars = $musicParser->parseMelody($song->melody, $song->time, $octaveCase, $isChineseSong);
    $partialMeasureDuration = $musicParser->getPartialMeasureDuration($bars, $song->time);
    $chords = $song->chords;
    if (($genType == 'g' || $genType == 'gt') && isset($song->guitarChords)) {
        $chords = $song->guitarChords;
    }
    if (isset($chords)) {
        fwrite($fp, 'chordsPart = \chordmode {' . PHP_EOL);
        fwrite($fp, "\t" . '\\set majorSevenSymbol = \\markup { maj7 }' . PHP_EOL);
        if ($song->type == 'de') {
            fwrite($fp, "\t" . '\\set chordRootNamer = #(chord-name->german-markup #t)' . PHP_EOL);
            fwrite($fp, "\t" . '\\set chordNoteNamer = #note-name->german-markup' . PHP_EOL);
        }
        fwrite($fp, "\t" . $musicParser->parseChords($chords, $genType, $partialMeasureDuration) . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    fwrite($fp, 'melody = {' . PHP_EOL);
    fwrite($fp, '    \\clef treble' . PHP_EOL);
    if ($song->time) {
        fwrite($fp, '    \\time ' . $song->time . PHP_EOL);
    }
    fwrite($fp, '    \\key c \\major' . PHP_EOL);
    if ($song->tempo) {
        fwrite($fp, '    \\tempo 4 = ' . $song->tempo . PHP_EOL);
    }
    fwrite($fp, '    \\once \\override Score.MetronomeMark #\'transparent = ##t' . PHP_EOL);
    if ($genType == 'gt') {
        fwrite($fp, '    \override Score.BarNumber #\'transparent = ##t' . PHP_EOL);
    }
    fwrite($fp, $musicParser->barsToMelody($bars) . PHP_EOL);
    fwrite($fp, '\\bar "|."' . PHP_EOL);
    fwrite($fp, '}' . PHP_EOL);
    fwrite($fp, 'text = \\lyricmode {' . PHP_EOL);
    fwrite($fp, '    \\set ignoreMelismata = ##t' . PHP_EOL);
    $stanza = $song->stanzas[0];
    if ($stanza->num == '1') {
        fwrite($fp, '    \\set stanza = "1."' . PHP_EOL);
    } else if (count($song->stanzas) >= 2 && $stanza->type == 'chorus') {
        fwrite($fp, '    \\set stanza = "(C)"' . PHP_EOL);
    }
    fwrite($fp, $musicParser->mergeLyricsWithMelody($builder, $bars, $song->singingStanza, false) . PHP_EOL);
    fwrite($fp, '    \\unset ignoreMelismata' . PHP_EOL);
    fwrite($fp, '}' . PHP_EOL);
    if (isset($song->singingStanza2)) {
        $singingStanza2 = $song->singingStanza2;
        fwrite($fp, 'textb = \\lyricmode {' . PHP_EOL);
        fwrite($fp, '    \\set ignoreMelismata = ##t' . PHP_EOL);
        if (preg_match('/^([0-9]+)\. (.+)$/', $singingStanza2, $regs)) {
            $singingStanza2 = $regs[2];
            fwrite($fp, '    \\set stanza = "' . $regs[1] . '."' . PHP_EOL);
        }
        fwrite($fp, $musicParser->mergeLyricsWithMelody($builder, $bars, $singingStanza2, true) . PHP_EOL);
        fwrite($fp, '    \\unset ignoreMelismata' . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    if (isset($song->singingStanza3)) {
        $singingStanza3 = $song->singingStanza3;
        fwrite($fp, 'textc = \\lyricmode {' . PHP_EOL);
        fwrite($fp, '    \\set ignoreMelismata = ##t' . PHP_EOL);
        if (preg_match('/^([0-9]+)\. (.+)$/', $singingStanza3, $regs)) {
            $singingStanza3 = $regs[2];
            fwrite($fp, '    \\set stanza = "' . $regs[1] . '."' . PHP_EOL);
        }
        fwrite($fp, $musicParser->mergeLyricsWithMelody($builder, $bars, $singingStanza3, true) . PHP_EOL);
        fwrite($fp, '    \\unset ignoreMelismata' . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    if (isset($song->singingStanza4)) {
        $singingStanza4 = $song->singingStanza4;
        fwrite($fp, 'textd = \\lyricmode {' . PHP_EOL);
        fwrite($fp, '    \\set ignoreMelismata = ##t' . PHP_EOL);
        if (preg_match('/^([0-9]+)\. (.+)$/', $singingStanza4, $regs)) {
            $singingStanza4 = $regs[2];
            fwrite($fp, '    \\set stanza = "' . $regs[1] . '."' . PHP_EOL);
        }
        fwrite($fp, $musicParser->mergeLyricsWithMelody($builder, $bars, $singingStanza4, true) . PHP_EOL);
        fwrite($fp, '    \\unset ignoreMelismata' . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    if (isset($song->singingStanza5)) {
        $singingStanza5 = $song->singingStanza5;
        fwrite($fp, 'texte = \\lyricmode {' . PHP_EOL);
        fwrite($fp, '    \\set ignoreMelismata = ##t' . PHP_EOL);
        if (preg_match('/^([0-9]+)\. (.+)$/', $singingStanza5, $regs)) {
            $singingStanza5 = $regs[2];
            fwrite($fp, '    \\set stanza = "' . $regs[1] . '."' . PHP_EOL);
        }
        fwrite($fp, $musicParser->mergeLyricsWithMelody($builder, $bars, $singingStanza5, true) . PHP_EOL);
        fwrite($fp, '    \\unset ignoreMelismata' . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    if (isset($song->singingStanza6)) {
        $singingStanza6 = $song->singingStanza6;
        fwrite($fp, 'textf = \\lyricmode {' . PHP_EOL);
        fwrite($fp, '    \\set ignoreMelismata = ##t' . PHP_EOL);
        if (preg_match('/^([0-9]+)\. (.+)$/', $singingStanza6, $regs)) {
            $singingStanza6 = $regs[2];
            fwrite($fp, '    \\set stanza = "' . $regs[1] . '."' . PHP_EOL);
        }
        fwrite($fp, $musicParser->mergeLyricsWithMelody($builder, $bars, $singingStanza6, true) . PHP_EOL);
        fwrite($fp, '    \\unset ignoreMelismata' . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    if (isset($song->singingStanza7)) {
        $singingStanza7 = $song->singingStanza7;
        fwrite($fp, 'textg = \\lyricmode {' . PHP_EOL);
        fwrite($fp, '    \\set ignoreMelismata = ##t' . PHP_EOL);
        if (preg_match('/^([0-9]+)\. (.+)$/', $singingStanza7, $regs)) {
            $singingStanza7 = $regs[2];
            fwrite($fp, '    \\set stanza = "' . $regs[1] . '."' . PHP_EOL);
        }
        fwrite($fp, $musicParser->mergeLyricsWithMelody($builder, $bars, $singingStanza7, true) . PHP_EOL);
        fwrite($fp, '    \\unset ignoreMelismata' . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    fwrite($fp, '\\score {' . PHP_EOL);
    fwrite($fp, '    <<' . PHP_EOL);

// ChordNames
    fwrite($fp, "\t" . '\\new ChordNames {' . PHP_EOL);
//			fwrite($fp, "\t" . '    \\set chordNameFunction = #parenthesis-ignatzek-chord-names' . PHP_EOL);
    fwrite($fp, "\t" . '    \\set chordChanges = ##t' . PHP_EOL);
    fwrite($fp, "\t" . '    \\override ChordName #\'font-size = #-1' . PHP_EOL);
    if ($genType == 'gt') {
        fwrite($fp, "\t" . '    \\override ChordName #\'font-series = #\'bold' . PHP_EOL);
    }
    if ($genType == 'g' || $genType == 'gt') {
        if ($guitarTransposeKey) {
            fwrite($fp, "\t" . '    \\transpose c ' . $guitarTransposeKey . ' \\chordsPart' . PHP_EOL);
        } else {
            fwrite($fp, "\t" . '    \\chordsPart' . PHP_EOL);
        }
    } else {
        if ($pianoTransposeKey) {
            fwrite($fp, "\t" . '    \\transpose c ' . $pianoTransposeKey . ' \\chordsPart' . PHP_EOL);
        } else {
            fwrite($fp, "\t" . '    \\chordsPart' . PHP_EOL);
        }
    }
    fwrite($fp, "\t" . '}' . PHP_EOL);

    if ($genType != 'gt') {
        fwrite($fp, "\t" . '\\new Voice = "one" {' . PHP_EOL);
        fwrite($fp, "\t" . '    \\autoBeamOff' . PHP_EOL);
        if ($genType != 'gt') {
            if ($pianoTransposeKey) {
                fwrite($fp, "\t" . '    \\transpose c ' . $pianoTransposeKey . ' \\melody' . PHP_EOL);
            } else {
                fwrite($fp, "\t" . '    \\melody' . PHP_EOL);
            }
        }
        fwrite($fp, "\t" . '}' . PHP_EOL);
        fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "one" \\text' . PHP_EOL);
        if (isset($song->singingStanza2)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "one" \\textb' . PHP_EOL);
        }
        if (isset($song->singingStanza3)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "one" \\textc' . PHP_EOL);
        }
        if (isset($song->singingStanza4)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "one" \\textd' . PHP_EOL);
        }
        if (isset($song->singingStanza5)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "one" \\texte' . PHP_EOL);
        }
        if (isset($song->singingStanza6)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "one" \\textf' . PHP_EOL);
        }
        if (isset($song->singingStanza7)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "one" \\textg' . PHP_EOL);
        }
    } else {
        fwrite($fp, "\t" . '\\new Devnull="nowhere" \\melody' . PHP_EOL);
        fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "nowhere" \\text' . PHP_EOL);
        if (isset($song->singingStanza2)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "nowhere" \\textb' . PHP_EOL);
        }
        if (isset($song->singingStanza3)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "nowhere" \\textc' . PHP_EOL);
        }
        if (isset($song->singingStanza4)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "nowhere" \\textd' . PHP_EOL);
        }
        if (isset($song->singingStanza5)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "nowhere" \\texte' . PHP_EOL);
        }
        if (isset($song->singingStanza6)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "nowhere" \\textf' . PHP_EOL);
        }
        if (isset($song->singingStanza7)) {
            fwrite($fp, "\t" . '\\new Lyrics \\lyricsto "nowhere" \\textg' . PHP_EOL);
        }
    }

    fwrite($fp, '    >>' . PHP_EOL);
    fwrite($fp, '    \\layout {' . PHP_EOL);
    fwrite($fp, "\t" . 'indent = 0.0\mm' . PHP_EOL);
    fwrite($fp, '    }' . PHP_EOL);
    fwrite($fp, '}' . PHP_EOL);
    if ($genMidi) {
        fwrite($fp, '\\score {' . PHP_EOL);
        fwrite($fp, '    <<' . PHP_EOL);
        fwrite($fp, "\t" . '\\new Staff = "one" {' . PHP_EOL);
        fwrite($fp, "\t" . '    \\autoBeamOff' . PHP_EOL);
        if ($pianoTransposeKey) {
            fwrite($fp, "\t" . '    \\transpose c ' . $pianoTransposeKey . ' \\melody' . PHP_EOL);
        } else {
            fwrite($fp, "\t" . '    \\melody' . PHP_EOL);
        }
        fwrite($fp, "\t" . '}' . PHP_EOL);
        fwrite($fp, '    >>' . PHP_EOL);
        fwrite($fp, '    \\midi {' . PHP_EOL);
        fwrite($fp, "\t" . '\\context {' . PHP_EOL);
        fwrite($fp, "\t" . '    \\Voice' . PHP_EOL);
        fwrite($fp, "\t" . '}' . PHP_EOL);
        fwrite($fp, '    }' . PHP_EOL);
        fwrite($fp, '}' . PHP_EOL);
    }
    $countRemainingStanzas = true;
    if (empty($song->singingStanzaEndNum)) {
        $stanzaIndex = 0;
        $foundVerse = false;
        $stanzaCount = count($song->stanzas);
        for ($i = 0; $i < $stanzaCount; $i++) {
            $stanza = (object)$song->stanzas[$i];
            if ($stanza->type != 'verse' && $stanza->type != 'chorus' && $stanza->type != 'note') {
                continue;
            }
            if ($stanza->type == 'verse') {
                if ($foundVerse) {
                    break;
                }
                $foundVerse = true;
            }
            $stanzaIndex++;
        }
    } else {
        if ($song->singingStanzaEndNum == '-1') {
            $stanzaIndex = 0;
            $countRemainingStanzas = false;
        } else {
            $stanzaIndex = (int)$song->singingStanzaEndNum;
        }
    }
    $remainingStanzaCount = 0;
    $hasNotes = false;
    for ($i = (int)$stanzaIndex; $i < count($song->stanzas); $i++) {
        $stanza = $song->stanzas[$i];
//        if ($stanza->type == 'note') {
//            $hasNotes = true;
//            continue;
//        }
        if ($stanza->type == 'verse' || $stanza->type == 'note') {
            if ($countRemainingStanzas) {
                $remainingStanzaCount++;
            }
        }
    }
    if (!$countRemainingStanzas) {
        $stanzaIndex = 99999;
    }
    if (isset($song->columns)) {
        $numberOfColumns = $song->columns;
    } else {
        $numberOfColumns = 1;
    }

    fwrite($fp, '\\markup {' . PHP_EOL);
    fwrite($fp, '    \\fill-line {' . PHP_EOL);
    if ($remainingStanzaCount % 2 == 1) {
        $numOfStanzasPerColumn = round(($remainingStanzaCount + 1) / $numberOfColumns);
    } else {
        $numOfStanzasPerColumn = round($remainingStanzaCount / $numberOfColumns);
    }
    $stanzaCount = 0;
    $realStanzaCount = 0;
    for ($col = 1; $col <= $numberOfColumns; $col++) {
        $colStanzaCount = 0;
        fwrite($fp, "\t" . '    \\column {' . PHP_EOL);
        for (; $stanzaIndex < count($song->stanzas); $stanzaIndex++) {
            $stanza = (object)$song->stanzas[(int)$stanzaIndex];
            if ($stanza->type != 'verse' && $stanza->type != 'chorus' && $stanza->type != 'note') {
                continue;
            }
            $stanzaCount++;
            if ($stanza->type != 'chorus') {
                $colStanzaCount++;
            }
            if ($colStanzaCount > $numOfStanzasPerColumn) {
                break;
            }
            if ($stanza->type == 'verse') {
                $realStanzaCount++;
            }
            fwrite($fp, $builder->buildStanza($stanza));
        }
        fwrite($fp, "\t" . '    }' . PHP_EOL);
    }
    fwrite($fp, '    }' . PHP_EOL);
    fwrite($fp, '}' . PHP_EOL);

//    if ($hasNotes) {
//        fwrite($fp, '\\markup {' . PHP_EOL);
//        fwrite($fp, '    \\fill-line {' . PHP_EOL);
//        fwrite($fp, "\t" . '    \\column {' . PHP_EOL);
//        for ($stanzaIndex = 0; $stanzaIndex < count($song->stanzas); $stanzaIndex++) {
//            $stanza = (object)$song->stanzas[(int)$stanzaIndex];
//            if ($stanza->type != 'note') {
//                continue;
//            }
//            fwrite($fp, $builder->buildStanza($stanza));
//        }
//        fwrite($fp, "\t" . '    }' . PHP_EOL);
//        fwrite($fp, '    }' . PHP_EOL);
//        fwrite($fp, '}' . PHP_EOL);
//    }

    fclose($fp);

    if ($realStanzaCount != $remainingStanzaCount) {
        error_log('ERROR: remainingStanzaCount=' . $remainingStanzaCount . ', realStanzaCount=' . $realStanzaCount);
    }
}

?>

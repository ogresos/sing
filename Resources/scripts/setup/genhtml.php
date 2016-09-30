<?php
use HymnalNet\Services\CommentService;
use HymnalNet\Services\SongService;
use HymnalNet\ThirdParty\CConvert;

require_once(__DIR__ . '/../../../src/bootstrap.php');

$MAX_ENGLISH_NUM = 1348;
$MAX_CHINESE_NUM = 1006;
$MAX_CHINESE_SUPPLEMENT_NUM = 1005;
$MAX_SPANISH_NUM = 500;
$MAX_TAGALOG_NUM = 757;

$songService = new SongService();
$commentService = new CommentService();

function getHomeLink($type)
{
    return '';
}

function getSongLink($type, $num)
{
    return '';
}

function getGoToLink($type)
{
    return '';
}

function getPrevLink($type, $num)
{
    return '';
}

function getNextLink($type, $num)
{
    return '';
}

function getAuthorLink($str)
{
    return '';
}

function getComposerLink($str)
{
    return '';
}

function getMeterLink($str)
{
    return '';
}

function getHymnCodeLink($str)
{
    return '';
}

function getCategoryLink($str)
{
    return '';
}

function getSubcategoryLink($category, $subcategory)
{
    return '';
}

function getExcerptLink($type, $num, $comments)
{
    return '';
}

function getNavigationHTML($type, $num, $istop)
{
    $content = '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
    $content .= '<tr><td align="left">';
    $prevlink = getPrevLink($type, $num);
    if ($prevlink) {
        $content .= '<a href="' . $prevlink . '">Prev</a>';
    } else {
        $content .= 'Prev';
    }
    $content .= '</td><td align="center">';
    $content .= '<a href="' . getHomeLink($type) . '">Home</a>';
    $content .= '</td><td align="center">';
    $content .= '<a href="' . getGoToLink($type) . '">Go To</a>';
    $content .= '</td><td align="center">';
    if ($istop) {
        $content .= '<a href="#bottom">Bottom</a>';
        $content .= '</td><td align="right">';
    } else {
        $content .= '<a href="#top">Top</a>';
        $content .= '</td><td align="right">';
    }
    $nextlink = getNextLink($type, $num);
    if ($nextlink) {
        $content .= '<a href="' . $nextlink . '">Next</a>';
    } else {
        $content .= 'Next';
    }
    $content .= '</td></tr></table>';
    return $content;
}

function getVerseNavigationHTML($song)
{
    $size = count($song->stanzas);
    if ($size <= 1) {
        return null;
    }
    $content = '<b>Verses:</b> ';
    $first = true;
    foreach ($song->stanzas as $stanza) {
        if ($first) {
            $first = false;
        } else {
            $content .= '&nbsp;';
        }
        $nextnum = $stanza->num + 1;
        if ($nextnum > $size) {
            $nextnum = 1;
        }
        $content .= '<span class="numlink"><a href="#v' . $nextnum . '">' . $stanza->num . '</a></span>';
    }
    return $content;
}

function generateHTML($type, $num, $toSimplified)
{
    global $songService;
    global $commentService;
    $song = $songService->getSong($type, $num);
    if (!$song) {
        echo 'ERROR: cannot find song with type ', $type, ' and num ', $num, '<br/>';
        exit;
    }
    switch ($type) {
        case 'h':
            $title = 'Hymns, #' . $num;
            break;
        case 'ch':
            $title = '詩歌' . $num;
            break;
        case 'ts':
            $title = '補充本詩歌' . $num;
            break;
        case 'ht':
            $title = 'Tagalog Hymns, #' . $num;
            break;
        case 'hs':
            $title = 'Spanish Hymns, #' . $num;
            break;
        default:
            $title = $song->title;
            break;
    }

    $fav = $commentService->getFavorite($type, $num);

    $content = '<html>';
    $content .= '<head>';
    $content .= '<title>' . $title . '</title>';
    $content .= '<link rel="stylesheet" type="text/css" href="http://www.hymnal.net/css/hymn-isilo.css" />';
    $content .= '</head>';
    $content .= '<body>';
    $content .= '<table border="0" cellpadding="3" cellspacing="3">';
    $content .= '<tr><td id="heading"><span class="title"><a href="' . getHomeLink($type) . '">' . $title . '</a></span><a name="top"> </a><br/>';
    if ($song->category) {
        $content .= '<span class="category">';
        if (preg_match('/.+&mdash;.+/', $song->category, $regs)) {
            list($category, $subcategory) = explode('&mdash;', $song->category);
            $content .= '<a href="' . getCategoryLink($category) . '">' . $category . '</a>&mdash;';
            $content .= '<a href="' . getSubcategoryLink($category, $subcategory) . '">' . $subcategory . '</a></span></td></tr>';
        } else {
            $content .= '<a href="' . getCategoryLink($song->category) . '">' . $song->category . '</a>';
        }
        $content .= '</span></td></tr>';
    }
    $content .= '<tr><td id="primaryinfo">';

    $content .= '<table border="0" cellpadding="0" cellspacing="0">';
    $content .= '<tr valign="top"><td id="numbers" colspan="2">';
    if ($song->numbers) {
        $numberStr = '';
        foreach ($song->numbers as $langType => $langNum) {
            if ($numberStr != '') {
                $numberStr .= '&nbsp;&nbsp;';
            }
            if (preg_match('/^([^:]+):([^:]+)$/', $langNum, $regs)) {
                $thisLangType = $regs[1];
                $langNum = $regs[2];
            } else {
                $thisLangType = null;
            }
            if (preg_match("/^([s]*[0-9]+)([^0-9]*)$/", $langNum, $regs)) {
                $langNum = $regs[1];
                $langNumSuffix = $regs[2];
            } else {
                $langNumSuffix = null;
            }
            $langNumPrefix = '';
            switch ($langType) {
                case 'chinese':
                    if (preg_match('/^s([0-9]+)$/', $langNum, $regs)) {
                        $thisLangType = 'ts';
                        $langNum = $regs[1];
                        $langNumPrefix = 'Cs';
                    } else {
                        $thisLangType = 'ch';
                        $langNumPrefix = 'C';
                    }
                    break;
                case 'english':
                    if ($thisLangType == null) {
                        $thisLangType = 'h';
                    }
                    $langNumPrefix = 'E';
                    break;
                case 'korean':
                    if ($thisLangType == null) {
                        $thisLangType = 'hk';
                    }
                    $langNumPrefix = 'K';
                    break;
                case 'portuguese':
                    if ($thisLangType == null) {
                        $thisLangType = 'hp';
                    }
                    $langNumPrefix = 'P';
                    break;
                case 'russian':
                    if ($thisLangType == null) {
                        $thisLangType = 'hr';
                    }
                    $langNumPrefix = 'R';
                    break;
                case 'spanish':
                    if ($thisLangType == null) {
                        $thisLangType = 'hs';
                    }
                    $langNumPrefix = 'S';
                    break;
                case 'tagalog':
                    if ($thisLangType == null) {
                        $thisLangType = 'ht';
                    }
                    $langNumPrefix = 'T';
                    break;
                case 'vsb':
                    if ($thisLangType == null) {
                        $thisLangType = 'vsb';
                    }
                    $langNumPrefix = 'YP';
                    break;
            }
            $songLink = getSongLink($thisLangType, $langNum);
            if ($songLink) {
                $numberStr .= '<a href="' . $songLink . '">';
            }
            $numberStr .= $langNumPrefix . $langNum;
            if ($langNumSuffix != null) {
                $numberStr .= $langNumSuffix;
            }
            if ($songLink) {
                $numberStr .= '</a>';
            }
        }
        $content .= $numberStr;
        $content .= '</td></tr>';
    }

    if ($song->meter) {
        $content .= '<tr valign="top"><td class="infokey">Meter:&nbsp;&nbsp;</td><td class="infoval">';
        $content .= '<a href="' . getMeterLink($song->meter) . '">' . $song->meter . '</a>';
        $content .= '</td></tr>';
    }

    if ($song->hymnCode) {
        $content .= '<tr valign="top"><td class="infokey">Code:&nbsp;&nbsp;</td><td class="infoval">';
        $content .= '<a href="' . getHymnCodeLink($song->hymnCode) . '">' . $song->hymnCode . '</a>';
        $content .= '</td></tr>';
    }
    $content .= '</table>';
    $content .= '</td></tr>';

    $content .= '<tr><td align="center" id="nav">';
    $navigationContent = getNavigationHTML($type, $num, true);
    $content .= $navigationContent;
    $content .= '</td></tr>';

    $verseNavigationContent = getVerseNavigationHTML($song);
    if ($verseNavigationContent) {
        $content .= '<tr><td id="versenav">';
        $content .= $verseNavigationContent;
        $content .= '</td></tr>';
    }

    $content .= '<tr><td id="verses">';
    $content .= '<table border="0" cellpadding="5" cellspacing="0">';
    foreach ($song->stanzas as $stanza) {
        $stanzaText = ($toSimplified) ? CConvert::t2s($stanza->text) : $stanza->text;
        $stanzaText = preg_replace('/^(.+)<br\/>[ \t]*<br\/>$/', '$1', $stanzaText);
        $content .= '<tr valign="top">';
        if ($stanza->type == "chorus") {
            $content .= '<td>&nbsp;</td><td>' . $stanzaText . '</td>';
        } else if ($stanza->type == "note") {
            $content .= '<td colspan="2" align="center">' . $stanzaText . '</td>';
        } else {
            $content .= '<td><span class="numlink">' . $stanza->num . '</a></span><a name="v' . $stanza->num . '"> </a></td><td>' . $stanzaText . '</td>';
        }
    }
    $content .= '</table>';
    $content .= '</td></tr>';

    if ($verseNavigationContent) {
        $content .= '<tr><td id="versenav">';
        $content .= $verseNavigationContent;
        $content .= '</td></tr>';
    }

    $content .= '<tr><td align="center" id="nav">';
    $navigationContent = getNavigationHTML($type, $num, false);
    $content .= $navigationContent;
    $content .= '</td></tr>';

    $content .= '<tr><td id="secondaryinfo">';
    if ($song->authors) {
        $content .= '<table border="0" cellpadding="0" cellspacing="0">';
        $authorSize = count($song->authors);
        if ($authorSize > 0) {
            $content .= '<tr valign="top"><td class="infokey">';
            if ($authorSize > 1) {
                $content .= 'Authors:';
            } else {
                $content .= 'Author:';
            }
            $content .= '&nbsp;&nbsp;</td><td class="infoval">';
            $first = true;
            foreach ($song->authors as $person) {
                if ($first) {
                    $first = false;
                } else {
                    $content .= ', ';
                }
                $content .= '<a href="' . getAuthorLink($person->name) . '">' . $person->name . '</a>';
                if (!empty($person->biodate)) {
                    $content .= ' (' . $person->biodate . ')';
                }
            }
            $content .= '</td></tr>';
        }
    }

    if ($song->composers) {
        $composersize = count($song->composers);
        if ($composersize > 0) {
            $content .= '<tr valign="top"><td class="infokey">';
            if ($composersize > 1) {
                $content .= 'Composers:';
            } else {
                $content .= 'Composer:';
            }
            $content .= '&nbsp;&nbsp;</td><td class="infoval">';
            $first = true;
            foreach ($song->composers as $person) {
                if ($first) {
                    $first = false;
                } else {
                    $content .= ', ';
                }
                $content .= '<a href="' . getAuthorLink($person->name) . '">' . $person->name . '</a>';
                if (!empty($person->biodate)) {
                    $content .= ' (' . $person->biodate . ')';
                }
            }
            $content .= '</td></tr>';
        }
    }

    if ($fav) {
        $commentsize = count($fav->comments);
        $numofexcerpts = 0;
        if ($commentsize > 0) {
            foreach ($fav->comments as $comment) {
                if ($comment->author == 'Hymnal.Net') {
                    $numofexcerpts++;
                }
            }
            if ($numofexcerpts > 0) {
                $content .= '<tr valign="top"><td class="infokey">';
                $content .= 'Ministry:&nbsp;&nbsp;</td><td class="infoval"><a href="' . getExcerptLink($type, $num, $fav->comments) . '">' . $numofexcerpts . ' excerpt';
                if ($numofexcerpts > 1) {
                    $content .= 's';
                }
                $content .= '</a></td></tr>';
            }
        }
    }
    $content .= '</td></tr></table>';

    $content .= '</table>';
    $content .= '<a name="bottom"> </a>';
    $content .= '</body>';
    $content .= '</html>';
    return $content;
}

echo generateHTML('h', '1', false);
?>

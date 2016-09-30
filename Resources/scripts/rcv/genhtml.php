<?php
$BIBLE_BOOKS = array("Genesis" => "01", "Exodus" => "02", "Leviticus" => "03", "Numbers" => "04", "Deuteronomy" => "05", "Joshua" => "06", "Judges" => "07", "Ruth" => "08", "1 Samuel" => "09", "2 Samuel" => "10", "1 Kings" => "11", "2 Kings" => "12", "1 Chronicles" => "13", "2 Chronicles" => "14", "Ezra" => "15", "Nehemiah" => "16", "Esther" => "17", "Job" => "18", "Psalms" => "19", "Proverbs" => "20", "Ecclesiastes" => "21", "Song of Songs" => "22", "Isaiah" => "23", "Jeremiah" => "24", "Lamentations" => "25", "Ezekiel" => "26", "Daniel" => "27", "Hosea" => "28", "Joel" => "29", "Amos" => "30", "Obadiah" => "31", "Jonah" => "32", "Micah" => "33", "Nahum" => "34", "Habakkuk" => "35", "Zephaniah" => "36", "Haggai" => "37", "Zechariah" => "38", "Malachi" => "39", "Matthew" => "40", "Mark" => "41", "Luke" => "42", "John" => "43", "Acts" => "44", "Romans" => "45", "1 Corinthians" => "46", "2 Corinthians" => "47", "Galatians" => "48", "Ephesians" => "49", "Philippians" => "50", "Colossians" => "51", "1 Thessalonians" => "52", "2 Thessalonians" => "53", "1 Timothy" => "54", "2 Timothy" => "55", "Titus" => "56", "Philemon" => "57", "Hebrews" => "58", "James" => "59", "1 Peter" => "60", "2 Peter" => "61", "1 John" => "62", "2 John" => "63", "3 John" => "64", "Jude" => "65", "Revelation" => "66");

function generateIsiloFile()
{
    $content = '<?xml version="1.0"?>
<iSiloXDocumentList>
	<iSiloXDocument>
		<ColorOptions>
			<BackgroundColors value="keep"/>
			<TextColors value="keep"/>
		</ColorOptions>
		<SecurityOptions>
			<Convert value="disallow"/>
			<CopyBeam value="disallow"/>
			<CopyAndPaste value="disallow"/>
			<Modify value="disallow"/>
			<Print value="disallow"/>
		</SecurityOptions>
	<Source>
		<Sources>
';
    $fileCount = 0;
    $handle = opendir('.');
    $fileList = array();
    while (false !== ($file = readdir($handle))) {
        if (!preg_match("/^[0-9][0-9][0-9][0-9][0-9].html$/", $file)) {
            continue;
        }
        $fileCount++;
        array_push($fileList, $file);
    }
    sort($fileList);
    foreach ($fileList as $file) {
        $content .= '<Path>' . $file . '</Path>' . "\n";
    }
    closedir($handle);
    $handle = opendir('.');
    $fileList = array();
    while (false !== ($file = readdir($handle))) {
        if (!preg_match("/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9].html$/", $file)) {
            continue;
        }
        $fileCount++;
        array_push($fileList, $file);
    }
    sort($fileList);
    foreach ($fileList as $file) {
        $content .= '<Path>' . $file . '</Path>' . "\n";
    }
    closedir($handle);
    $handle = opendir('.');
    $fileList = array();
    while (false !== ($file = readdir($handle))) {
        if (!preg_match("/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]_v.html$/", $file)) {
            continue;
        }
        $fileCount++;
        array_push($fileList, $file);
    }
    sort($fileList);
    foreach ($fileList as $file) {
        $content .= '<Path>' . $file . '</Path>' . "\n";
    }
    closedir($handle);
    $content .= '</Sources>
		<CharSet>utf-8</CharSet>
	</Source>';
    $content .= '	<Destination>
		<Title>RcV</Title>
			<Files>
				<Path>RcV.pdb</Path>
			</Files>
	</Destination>
	</iSiloXDocument>
</iSiloXDocumentList>';
    $file = 'isilo.ixl';
    $fp = fopen($file, 'w');
    fwrite($fp, $content);
    fclose($fp);
    echo 'iSilo File: ', $file, ' (', $fileCount, ' files)', "\n";;
}

function getHomeLink()
{
    return '00000.html';
}

function getOutlineLink($bookname)
{
    global $BIBLE_BOOKS;
    $bookname = parseBook($bookname);
    $link = $BIBLE_BOOKS[$bookname];
    $link .= '000.html';
    return $link;
}

function getLink($bookname, $chapter, $verse, $noteorxref)
{
    global $BIBLE_BOOKS;
    $bookname = parseBook($bookname);
    $link = $BIBLE_BOOKS[$bookname];
    $link .= str_pad($chapter, 3, '0', STR_PAD_LEFT);
    if ($verse != null) {
        if ($noteorxref != null) {
            $link .= str_pad($verse, 3, '0', STR_PAD_LEFT);
            $link .= '.html';
            if ($noteorxref != 9999) {
                $link .= '#nx' . $noteorxref;
            }
        } else {
            $link .= '.html';
            if ($verse == 1) {
                $link .= '#top';
            } else {
                $link .= '#V' . $verse;
            }
        }
    } else {
        $link .= '.html';
    }
    return $link;
}

function getOtherVersionsLink($bookname, $chapter, $verse)
{
    global $BIBLE_BOOKS;
    $bookname = parseBook($bookname);
    $link = $BIBLE_BOOKS[$bookname];
    $link .= str_pad($chapter, 3, '0', STR_PAD_LEFT);
    $link .= str_pad($verse, 3, '0', STR_PAD_LEFT);
    $link .= '_v.html';
    return $link;
}

function printVerse($book, $chapter, $verse, $versesection, $numofnotes, $numofxrefs)
{
    $html = '';
    if ($verse->num != 0 && $verse->num < 10000) {
        $html .= '<p class="verse">';
        $html .= '<span class="verseref">';
        if ($versesection != null) {
            $versesuffix = ($versesection == 1) ? 'a' : 'b';
        } else {
            $versesuffix = '';
        }

        if ($numofnotes > 0 || $numofxrefs > 0) {
            $html .= '<a href="' . getLink($book->name, $chapter, $verse->num, 9999) . '">' . $chapter . ':' . $verse->num . $versesuffix . '</a>';
        } else {
            $html .= $chapter . ':' . $verse->num . $versesuffix;
        }
        $html .= ' <a name="V' . $verse->num . '" href="' . getOtherVersionsLink($book->name, $chapter, $verse->num) . '"><img src="http://www.hymnal.net/rcv/images/otherversions.gif" width="8" height="9" /></a>';
        $html .= '</span> ';
    } else {
        $html .= '<p class="superscription">';
    }
    if ($versesection != null) {
        $subverses = explode('[separator]', $verse->text);
        $versetext = $subverses[$versesection - 1];
    } else {
        $subverses = explode(' / ', $verse->text);
        if (count($subverses) == 1) {
            $versetext = $verse->text;
        } else {
            $versetext = '';
            $count = 0;
            foreach ($subverses as $subverse) {
                $count++;
                if ($count > 1) {
                    $versetext .= '<br/>';
                }
                if ($count % 2 == 0) {
                    $versetext .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                }
                $versetext .= trim($subverse);
            }
        }
    }

    $ACTIVE_REF->fromBook = $book->name;
    $ACTIVE_REF->fromChapter = $chapter;
    $html .= '</span> ';
    $html .= getGeneratedVerseToHTML($book->name, $chapter, $verse->num, $versetext) . '</p>';
    if (preg_match('/^(.+)Selah$/', $html, $regs)) {
        $html = '<div class="selahleft">' . $regs[1] . '</div><div class="selah">Selah</div>';
    }
    return $html;
}

function getGeneratedVerseToHTML($bookname, $chapter, $verse, $text)
{
    $html = str_replace(array('[br]', '[br/]', '[i]', '[/i]'), array('<br/>', '<br/>', '<i>', '</i>'), $text);
    $link = getLink($bookname, $chapter, $verse, 9999);
    $html = preg_replace("/\[note\]([0-9]+)\[\/note\]/", '<a href="' . $link . '#nx\\1"><sup>\\1</sup></a>', $html);
    $html = preg_replace("/\[xref\]([a-z]+)\[\/xref\]/", '<a href="' . $link . '#nx\\1"><sup>\\1</sup></a>', $html);
    return $html;
}

function parseRefLink($html)
{
    $strlist = explode('<a ', $html);
    $retstr = '';
    foreach ($strlist as $str) {
        if (preg_match('/^href="home\.php\?k=([^"]+)">([^<]+)<\/a>(.*)$/', $str, $regs)) {
            if (preg_match('/^(Song of Songs) ([0-9]+):([0-9]+)(.*)$/', $regs[1], $subregs)) {
                $retstr .= '<a href="' . getLink($subregs[1], $subregs[2], $subregs[3], null) . '">' . $regs[2] . '</a>';
                $retstr .= $regs[3];
            } else if (preg_match('/^([1-3 ]*[A-Za-z\.]+) ([0-9]+):([0-9]+)(.*)$/', $regs[1], $subregs)) {
                $retstr .= '<a href="' . getLink($subregs[1], $subregs[2], $subregs[3], null) . '">' . $regs[2] . '</a>';
                $retstr .= $regs[3];
            } else if (preg_match('/^([1-3 ]*[A-Za-z\.]+) ([0-9]+)-([0-9]+)([^"]*)$/', $regs[1], $subregs)) {
                $retstr .= '<a href="' . getLink($subregs[1], $subregs[2], 1, null) . '">' . $regs[2] . '</a>';
                $retstr .= $regs[3];
            } else if (preg_match('/^([1-3 ]*[A-Za-z\.]+) ([0-9]+)([^"]*)$/', $regs[1], $subregs)) {
                $retstr .= '<a href="' . getLink($subregs[1], 1, $subregs[2], null) . '">' . $regs[2] . '</a>';
                $retstr .= $regs[3];
            } else {
                echo '<b>WARNING in parseRefLink:</b> ', $str, ' - regs[1]=', $regs[1], "\n";
                $retstr .= $str;
            }
        } else {
            $retstr .= $str;
        }
    }
    return $retstr;
}

function parseNoteXref($text)
{
    global $ACTIVE_REF;
    $html = str_replace(array('br]', 'br/]', '[i]', '[/i]', '[separator]'), array('<br/>', '<br/>', '<i>', '</i>', '<br/>'), $text);
    $html = preg_replace("/\[sup\]([^\[]+)\[/sup\]/", "<sup>\\1</sup>", $html);
    $html = preg_replace("/\n/", "<br/>", $html);
    $tokens = explode('[', $html);
    $rettext = '';
    foreach ($tokens as $token) {
        if (preg_match('/^\/link\](.*)$/', $token, $tokenregs)) {
            $rettext .= $tokenregs[1];
        } else if (preg_match('/^link(.*)\](.*)$/', $token, $tokenregs)) {
            if (preg_match('/^ book="([^"]+)" chapter="([^"]+)"$/', $tokenregs[1], $linkregs)) {
                $ACTIVE_REF->fromBook = $linkregs[1];
                $ACTIVE_REF->fromChapter = $linkregs[2];
            } else if (preg_match('/^ book="([^"]+)"$/', $tokenregs[1], $linkregs)) {
                $ACTIVE_REF->fromBook = $linkregs[1];
            }
            $ref = $tokenregs[2];
            if ($ref == '') {
                continue;
            }
            $sup = '';
            if (preg_match('/^([^<]+)(<sup>[^<]+<\/sup>)$/', $ref, $regs)) {
                $ref = $regs[1];
                $sup = $regs[2];
            }
            if (preg_match('/^([0-9]+)$/', $ref, $regs)) {
                $rettext .= '<a href="' . getLink($ACTIVE_REF->fromBook, $ACTIVE_REF->fromChapter, $regs[1], null) . '">' . $ref . '</a>';
            } else if (preg_match('/^(v[v]*\. )([^\/]+)$/', $ref, $regs)) {
                $rettext .= '<a href="' . getLink($ACTIVE_REF->fromBook, $ACTIVE_REF->fromChapter, $regs[2], null) . '">' . $ref . '</a>';
            } else if (preg_match('/^([0-9]+):([^\/]+)$/', $ref, $regs)) {
                $ACTIVE_REF->fromChapter = $regs[1];
                $rettext .= '<a href="' . getLink($ACTIVE_REF->fromBook, $regs[1], $regs[2], null) . '">' . $ref . '</a>';
            } else if (preg_match('/^([1-3 ]*[A-Z][A-Za-z\. ]+) ([0-9]+):([0-9]+).*$/', $ref, $regs)) {
                $ACTIVE_REF->fromBook = $regs[1];
                $ACTIVE_REF->fromChapter = $regs[2];
                $rettext .= '<a href="' . getLink($regs[1], $regs[2], $regs[3], null) . '">' . $ref . '</a>';
            } else if (preg_match('/^([1-3 ]*[A-Z][A-Za-z\. ]+) ([0-9]+).*$/', $ref, $regs)) {
                $ACTIVE_REF->fromBook = $regs[1];
                $ACTIVE_REF->fromChapter = 1;
                $rettext .= '<a href="' . getLink($regs[1], 1, $regs[2], null) . '">' . $ref . '</a>';
            } else if (preg_match('/^([0-9]+)<sup>([0-9]+)<\/sup> in ([1-3 ]*[A-Z][A-Za-z\. ]+) ([0-9]+)$/', $ref, $regs)) {
                $ACTIVE_REF->fromBook = $regs[3];
                $ACTIVE_REF->fromChapter = $regs[4];
                $rettext .= '<a href="' . getLink($ACTIVE_REF->fromBook, $ACTIVE_REF->fromChapter, $regs[1], null) . '">' . $ref . '</a>';
            } else if (preg_match('/^([0-9]+)\-([0-9]+)$/', $ref, $regs)) {
                if ($ACTIVE_REF->fromChapter > 0) {
                    $rettext .= '<a href="' . getLink($ACTIVE_REF->fromBook, $ACTIVE_REF->fromChapter, $ref, null) . '">' . $ref . '</a>';
                } else {
                    $ACTIVE_REF->fromChapter = $regs[1];
                    $ACTIVE_REF->toChapter = $regs[2];
                    $rettext .= '<a href="' . getLink($ACTIVE_REF->fromBook, $regs[1], 1, null) . '">' . $ref . '</a>';
                }
            } else {
                $rettext .= $tokenregs[2];
            }
            if ($sup != '') {
                $rettext .= $sup;
            }
        } else {
            if (preg_match("/\]/", $token)) {
                $rettext .= '[';
            }
            $rettext .= $token;
        }
    }
    return $rettext;
}

function getNavigation($book, $chapter)
{
    $content .= '<div id="navigation">';
    if ($chapter != null && $chapter == 0) {
        $content .= '<b>Outline</b>';
    } else {
        $content .= '<a href="' . getLink($book->name, 0, null, null) . '">Outline</a>';
    }
    for ($chp = 1; $chp <= $book->numOfChapters; $chp++) {
        $content .= ', ';
        if ($chapter == $chp) {
            $content .= '<b>' . $chp . '</b>';
        } else {
            $content .= '<a href="' . getLink($book->name, $chp, null, null) . '">' . $chp . '</a>';
        }
    }
    $content .= '</div>';
    return $content;
}

function generateOutline($book, $outlines)
{
    global $UPPER_ROMAN_MAP;
    $content = file_get_contents('header.php');
    $content .= '<h1><a href="' . getHomeLink() . '">' . $book->name . '</a></h1>';
    $content .= getNavigation($book, 0);
    $info = $book->getInfo();
    if (count($info) > 0) {
        foreach ($info as $key => $value) {
            $content .= '<p><span class="bookinfo">' . $key . ':</span> <span class="bookval">' . parseRefLink($value) . '</span></p>';
        }
    }
    $maxoutlinelevels = 8;
    $lastupperromanindex = 0;
    $firstoutline = true;
    $content .= '<h2>Outline</h2>';
    $content .= '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
    $prevoutline = null;
    foreach ($outlines as $outline) {
        $outlinenum = $outline->getNum();
        $outlinetext = $outline->text . '&mdash;' . parseRefLink($outline->ref);
        if (strcmp($prevoutline, $outlinetext) == 0) {
            continue;
        }
        $prevoutline = $outlinetext;
        if (preg_match('/^[IVX]+\.$/', $outlinenum, $outregs) && $UPPER_ROMAN_MAP[$outlinenum] >= $lastupperromanindex) {
            if (!$firstoutline) {
                $content .= '<tr><td colspan="' . $maxoutlinelevels . '"> </td></tr>';
            }
            $content .= '<tr valign="top" class="bold"><td width="10px" class="outlinenum">' . $outlinenum . '</td><td colspan="' . ($maxoutlinelevels - 1) . '">' . $outlinetext . '</td></tr>';
            $lastupperromanindex++;
        } else if (preg_match('/^[A-Z]\.$/', $outlinenum, $outregs)) {
            $content .= '<tr valign="top"><td> </td><td width="10px" class="outlinenum">' . $outlinenum . '</td><td colspan="' . ($maxoutlinelevels - 2) . '">' . $outlinetext . '</td></tr>';
        } else if (preg_match('/^[0-9]+\.$/', $outlinenum, $outregs)) {
            $content .= '<tr valign="top"><td colspan="2"> </td><td width="10px" class="outlinenum">' . $outlinenum . '</td><td colspan="' . ($maxoutlinelevels - 3) . '">' . $outlinetext . '</td></tr>';
        } else if (preg_match('/^[a-z]\.$/', $outlinenum, $outregs)) {
            $content .= '<tr valign="top"><td colspan="3"> </td><td width="10px" class="outlinenum">' . $outlinenum . '</td><td colspan="' . ($maxoutlinelevels - 4) . '">' . $outlinetext . '</td></tr>';
        } else if (preg_match('/^\([0-9]+\)[ ]*$/', $outlinenum, $outregs)) {
            $content .= '<tr valign="top"><td colspan="4"> </td><td width="10px" class="outlinenum">' . $outlinenum . '</td><td colspan="' . ($maxoutlinelevels - 5) . '">' . $outlinetext . '</td></tr>';
        } else if (preg_match('/^\([a-z]+\)[ ]*$/', $outlinenum, $outregs)) {
            $content .= '<tr valign="top"><td colspan="5"> </td><td width="10px" class="outlinenum">' . $outlinenum . '</td><td colspan="' . ($maxoutlinelevels - 6) . '">' . $outlinetext . '</td></tr>';
        } else {
            $content .= '<tr valign="top"><td width="10px" class="outlinenum">' . $outlinenum . '</td><td colspan="' . ($maxoutlinelevels - 1) . '">' . $outlinetext . '</td></tr>';
        }
        if ($firstoutline) {
            $firstoutline = false;
        }
    }
    $content .= '</table><br/>';
    $content .= getNavigation($book, 0);
    $content .= file_get_contents('footer.php');
    $file = getOutlineLink($book->name);
    $fp = fopen($file, 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function generateChapter($book, $oldbook, $outlines, $chapter)
{
    global $ACTIVE_REF;
    $numofverses = $book->getNumberOfVerses($chapter);
    $content = file_get_contents('header.php');
    $content .= '<h1><a href="' . getHomeLink() . '" name="top">' . $book->name . '</a></h1>';
    $content .= getNavigation($book, $chapter);

    $oldchapter = $oldbook->chapters[$chapter];
    $verses = $oldchapter->verses;
    $versenum = 0;
    $outlineindex = 0;
    $outlinesize = count($outlines);
    foreach ($verses as $verse) {
        $numofnotes = count($verse->footnotes);
        $numofxrefs = count($verse->xrefs);
        $subverses = explode('[separator]', $verse->text);
        $itnum = count($subverses);
        for ($j = 0; $j < $itnum; $j++) {
            $subverse = $subverses[$j];
            if ($outlinesize > 0) {
                for (; $outlineindex < $outlinesize;) {
                    $outline = $outlines[$outlineindex];
                    if ($itnum == 2) {
                        if (preg_match('/([0-9]+)([a-z])/', $outline->ref, $regs)) {
                            $outlinerefsection = $regs[2];
                        }
                    } else {
                        $outlinerefsection = null;
                    }
                    if ($chapter == $outline->fromChapter && $verse->num >= $outline->fromVerse) {
                        if ($itnum == 2) {
                            if ($j == 0 && $outlinerefsection == 'b') {
                                break;
                            }
                        }
                        if ($chapter < $outline->toChapter) {
                            $content .= '<p class="outline">' . $outline->getNum() . '&nbsp;' . $outline->text . '&mdash;' . parseRefLink($outline->ref) . '</p>';
                        } else if ($chapter == $outline->toChapter && $verse->num <= $outline->toVerse) {
                            $content .= '<p class="outline">' . $outline->getNum() . '&nbsp;' . $outline->text . '&mdash;' . parseRefLink($outline->ref) . '</p>';
                        }
                        $outlineindex++;
                    } else if ($chapter > $outline->fromChapter) {
                        if ($chapter < $outline->toChapter) {
                            $content .= '<p class="outline">' . $outline->getNum() . '&nbsp;' . $outline->text . '&mdash;' . parseRefLink($outline->ref) . '</p>';
                        } else if ($chapter == $outline->toChapter) {
                            if ($verse->num <= $outline->toVerse) {
                                $content .= '<p class="outline">' . $outline->getNum() . '&nbsp;' . $outline->text . '&mdash;' . parseRefLink($outline->ref) . '</p>';
                            }
                        }
                        $outlineindex++;
                    } else {
                        break;
                    }
                }
            }
            $divnum++;
            $booknametodisplay = null;
            if ($numofrefs > 1 && $ref->fromBook != $currentbook) {
                $currentbook = $ref->fromBook;
                $booknametodisplay = $ref->fromBook;
            }
            if ($itnum == 2) {
                $versesection = $j + 1;
            } else {
                $versesection = null;
            }
            $content .= printVerse($book, $chapter, $verse, $versesection, $numofnotes, $numofxrefs);
        }

        $hasnotesorxrefs = ($numofnotes > 0 || $numofxrefs > 0);
        if ($hasnotesorxrefs) {
            generateNotesAndXrefs($book, $chapter, $verse);
        }
        generateOtherVersions($book, $chapter, $verse->num, $verse->text, $hasnotesorxrefs);
    }

    $content .= getNavigation($book, $chapter);
    $content .= file_get_contents('footer.php');
    $file = getLink($book->name, $chapter, null, null);
    $fp = fopen($file, 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function generateNotesAndXrefs($book, $chapter, $verse)
{
    $notes = $verse->footnotes;
    $xrefs = $verse->xrefs;
    $content = file_get_contents('header.php');
    $content .= '<h1><a href="' . getHomeLink() . '">' . $book->name . '</a></h1>';
    $content .= getNavigation($book, null);
    $content .= '<p class="verse">';
    $content .= '<span class="verseref">';
    if ($verse->num == 0 || $verse->num > 10000) {
        $content .= '<a href="' . getLink($book->name, $chapter, $verse->num, null) . '">' . $chapter . ':Title</a>';
    } else {
        $content .= '<a href="' . getLink($book->name, $chapter, $verse->num, null) . '">' . $chapter . ':' . $verse->num . '</a>';
        $content .= ' <a href="' . getOtherVersionsLink($book->name, $chapter, $verse->num) . '"><img src="http://www.hymnal.net/rcv/images/otherversions.gif" width="8" height="9" /></a>';
    }
    $content .= '</span> ';
    $content .= getGeneratedVerseToHTML($book->name, $chapter, $verse->num, $verse->text) . '</p>';
    if ($notes) {
        foreach ($notes as $note) {
            $content .= '<div id="note"><span class="noteref"><a name="nx' . $note->num . '">' . $note->num . '</a></span> ' . parseNoteXref($note->text) . '</div>';
        }
    }
    if ($xrefs) {
        foreach ($xrefs as $xref) {
            $content .= '<div id="xref"><span class="xrefref"><a name="nx' . $xref->num . '">' . $xref->num . '</a></span> ' . parseNoteXref($xref->text) . '</div>';
        }
    }
    $content .= getNavigation($book, null);
    $content .= file_get_contents('footer.php');
    $file = getLink($book->name, $chapter, $verse->num, 9999);
    $fp = fopen($file, 'w');
    fwrite($fp, $content);
    fclose($fp);
}

function generateOtherVersions($book, $chapter, $verse, $versetext, $hasnotesorxrefs)
{
    global $VERSIONS;
    $content = file_get_contents('header.php');
    $content .= '<h1><a href="' . getHomeLink() . '">' . $book->name . '</a></h1>';
    $content .= getNavigation($book, null);
    $content .= '<p class="verse">';
    $content .= '<span class="verseref">';
    $content .= '<a href="' . getLink($book->name, $chapter, $verse, null) . '">' . $chapter . ':' . $verse . '</a>';
    $content .= '</span> ';
    $content .= getGeneratedVerseToHTML($book->name, $chapter, $verse, $versetext) . '</p>';
    $bibletexts = getAllVersionText($book->name, $chapter, $verse);
    $prevversionname = null;
    foreach ($bibletexts as $bibletext) {
        $versionname = $VERSIONS[$bibletext->versionid];
        if (strcmp($versionname, $prevversionname) == 0) {
            continue;
        }
        $prevversionname = $versionname;
        if ($versionname == 'ChineseRcV') {
            $versionname = '&#24674;&#24489;&#26412;';
            $bibletext->text = mb_convert_encoding($bibletext->text, 'utf-8', 'big5');
        }
        $thisversiontext = str_replace(array('</p>', '<p />', '<br />'), array('', '', ''), $bibletext->text);
        $content .= '<div id="vrsn"><span class="vrsnref">' . $versionname . '</span> ' . $thisversiontext . '</div>';
    }
    $content .= getNavigation($book, null);
    $content .= file_get_contents('footer.php');
    $file = getOtherVersionsLink($book->name, $chapter, $verse);
    $fp = fopen($file, 'w');
    fwrite($fp, $content);
    fclose($fp);
}

require('../global-lib.php');
require('oldrcv-lib.php');
require('../rcv-lib.php');

$hasDBConnection = $config->startDB();
if (!$hasDBConnection) {
    echo 'The database is down.';
    exit();
}

$VERSIONS = getAllVersionNames();

$content = file_get_contents('header.php');
$content .= '<h1>The Holy Bible (RcV)</h1>';
$content .= '<div id="navigation"><b>New Testament</b></div>';
$first = true;
foreach ($NEW_TESTAMENT_BOOKS as $bookname) {
    if ($first) {
        $first = false;
    } else {
        $content .= ' &middot; ';
    }
    $content .= '<a href="' . getLink($bookname, 0, null, null) . '">' . $bookname . '</a>';
}
$content .= '<br/><br/>';
$content .= '<div id="navigation"><b>Old Testament</b></div>';
$first = true;
foreach ($OLD_TESTAMENT_BOOKS as $bookname) {
    if ($first) {
        $first = false;
    } else {
        $content .= ' &middot; ';
    }
    $content .= '<a href="' . getLink($bookname, 0, null, null) . '">' . $bookname . '</a>';
}
$file = getHomeLink();
$fp = fopen($file, 'w');
fwrite($fp, $content);
fclose($fp);
echo 'Saved: ', $file, "\n";

//	$isnt = $_REQUEST['type'] == 'nt';
//	$b = isset($_REQUEST['b']) ? $_REQUEST['b'] : 0;
$isnt = ($_SERVER['BIBLETYPE'] == 'nt');
$b = $_SERVER['BIBLE'];
if (!isset($b)) {
    echo 'BIBLE not set.', "\n";
    exit;
}
$inputbookname = $isnt ? $NEW_TESTAMENT_BOOKS[$b] : $OLD_TESTAMENT_BOOKS[$b];
//	$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;
//	$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : $MAX_CHAPTERS[$inputbookname];
$start = isset($_SERVER['START']) ? $_SERVER['START'] : 0;
$end = isset($_SERVER['END']) ? $_SERVER['END'] : $MAX_CHAPTERS[$inputbookname];

if ($_SERVER['ISILO'] == '1') {
    echo "Generate iSiloX File\n";
    generateIsiloFile();
} else {
    echo "b=$b, book=$inputbookname, start=$start, end=$end\n";
    $rcvparser = new RcvParser();
    $ACTIVE_REF->fromBook = $inputbookname;
    $ACTIVE_REF->fromChapter = $start;
    foreach ($BIBLE_BOOKS as $bookname => $val) {
        if ($bookname != $inputbookname) {
            continue;
        }
        $oldbook = $rcvparser->getBook($bookname);
        $book = getBook($bookname);
        echo 'Book: ', $book->name, "\n";
        for ($i = $start; $i <= $end; $i++) {
            $outlines = $book->getOutlines();
            if ($i == 0) {
                generateOutline($book, $outlines);
                echo ' - Outline';
//break;
            } else {
                generateChapter($book, $oldbook, $outlines, $i);
                echo ', ', $i, '/', $MAX_CHAPTERS[$bookname];
            }
        }
        echo "\n";
//continue;
        if ($end + 1 <= $MAX_CHAPTERS[$bookname]) {
            echo 'NEXT: export START=', ($end + 1), '; export END=', $MAX_CHAPTERS[$bookname], "; php genhtml.php\n\n";
        } else {
            $nextb = $b + 1;
            $bookname = $isnt ? $NEW_TESTAMENT_BOOKS[$nextb] : $OLD_TESTAMENT_BOOKS[$nextb];
            if ($bookname) {
                $nextbookname = $isnt ? $NEW_TESTAMENT_BOOKS[$nextb] : $OLD_TESTAMENT_BOOKS[$nextb];
                echo 'Next Book (', $nextbookname, '): export START=0; export END=', $MAX_CHAPTERS[$nextbookname], '; export BIBLETYPE=', ($isnt ? 'nt' : 'ot'), '; export BIBLE=', $nextb, "; php genhtml.php\n\n";
            } else if (!$isnt) {
                echo 'Next Book (Matthew): export START=0; export END=', $MAX_CHAPTERS[$NEW_TESTAMENT_BOOKS[0]], '; export BIBLETYPE=nt; export BIBLE=0; php genhtml.php', "\n\n";
            }
        }
    }
}

$config->endDB();
?>

<?php
	require('../global-lib.php');
	require('oldrcv-lib.php');

	function myStripText($text) {
		$retText = preg_replace('/\[link [^\]]+\]/', '', $text);
		$retText = trim(str_replace(array('&#151;', '[link]', '[/link]', '[sup]', '[/sup]', '[note]', '[/note]', '[xref]', '[/xref]', '[i]', '[/i]'), array('�', '', '', '', '', '', '', '', '', '', ''), $retText));
		return $retText;
	}

	$bookName = $_REQUEST['b'];
	echo '<h1>', $bookName, '</h1>';

	$xmlFile = '../../../resources/rcv/' . $bookName . '.xml';
	$fp = fopen($xmlFile, "r") or die ("Cannot open " . $xmlFile);
	$xmlLines = array();
	while (!feof($fp)) {
		$line = fgets($fp, 8192);
		if (preg_match('/^[\t ]+<chapters>$/', $line, $regs)) {
			break;
		}
		array_push($xmlLines, $line);
	}
	fclose($fp);

	$textFile = '../../../resources/rcv/text.txt';
	$fp = fopen($textFile, "r") or die ("Cannot open " . $textFile);
	$textMap = array();
	$prevKey = null;
	while (!feof($fp)) {
		$line = trim(fgets($fp, 4096));
		if (strlen($line) == 0) {
			continue;
		}
		if (preg_match('/^([0-9]+):([0-9]+) ([^\n]+)$/', $line, $regs)) {
			$prevKey = $regs[1] . ':' . $regs[2];
			$textMap[$prevKey] = trim($regs[3]);
		} else {
			if ($prevKey != null) {
				$textMap[$prevKey] .= '[br/]' . $line;
			} else {
				echo 'ERROR NEW LINE: ', $line, '<br/>';
			}
		}
	}
	fclose($fp);

	$notesAndXrefsFile = '../../../resources/rcv/notesandxrefs.txt';
	$fp = fopen($notesAndXrefsFile, "r") or die ("Cannot open " . $notesAndXrefsFile);
	$lines = array();
	while (!feof($fp)) {
		$line = trim(fgets($fp, 4096));
		if (strlen($line) == 0) {
			continue;
		}
		array_push($lines, $line);
	}
	fclose($fp);

	$maxLine = count($lines);
	$notesMap = array();
	$xrefsMap = array();
	$noteCount = 0;
	$xrefCount = 0;
	$note = null;
	$xref = null;
	for ($i = 0; $i < $maxLine; $i++) {
		$line = $lines[$i];
		if (preg_match('/^([0-9]+):([0-9]+)([a-z]) ([^\n]+) - ([^\n]+)$/', $line, $regs) && $regs[2] != '00') { // note? and xref
			if ($note != null && $note->text) {
				if (!$note->note) {
					if (preg_match('/^([0-9]+)([0-9])$/', $note->verse, $subRegs)) {
						$note->verse = $subRegs[1];
						$note->note = $subRegs[2];
					}
				}
				if (!$notesMap[$note->chapter . ':' . $note->verse]) {
					$notesMap[$note->chapter . ':' . $note->verse] = array();
				}
				array_push($notesMap[$note->chapter . ':' . $note->verse], $note);
				$noteCount++;
				$note = null;
			}
			$xref = new Xref();
			if (($i+1) < $maxLine && !preg_match('/^[0-9]+:[0-9]+.+$/', $lines[$i+1], $subRegs)) { // has note
				$note = new Note();
				$note->chapter = $regs[1];
				if (preg_match('/^([0-9]+)([0-9])$/', $regs[2], $subSubRegs)) {
					$note->verse = $subSubRegs[1];
					$note->note = $subSubRegs[2];
				} else {
					echo 'ERROR NOTE NUM: line=', $line, '<br/>';
				}
				$note->keyword = $regs[4];
				$xref->chapter = $regs[1];
				$xref->verse = $note->verse;
				$xref->xref = $regs[3];
				$xref->keyword = $regs[4];
				$xref->text = $regs[5];
			} else {
				$xref->chapter = $regs[1];
				$xref->verse = $regs[2];
				$xref->xref = $regs[3];
				$xref->keyword = $regs[4];
				$xref->text = $regs[5];
			}
			if (!$xrefsMap[$xref->chapter . ':' . $xref->verse]) {
				$xrefsMap[$xref->chapter . ':' . $xref->verse] = array();
			}
			array_push($xrefsMap[$xref->chapter . ':' . $xref->verse], $xref);
			$xrefCount++;
			$xref = null;
		} else if (preg_match('/^([0-9]+):([0-9]+)([0-9]) ([^\n]+)$/', $line, $regs) && $regs[2] != '00') { // note
			if ($note != null) {
				if (!$note->note) {
					if (preg_match('/^([0-9]+)([0-9])$/', $note->verse, $subRegs)) {
						$note->verse = $subRegs[1];
						$note->note = $subRegs[2];
					}
				}
				if (!$notesMap[$note->chapter . ':' . $note->verse]) {
					$notesMap[$note->chapter . ':' . $note->verse] = array();
				}
				array_push($notesMap[$note->chapter . ':' . $note->verse], $note);
				$noteCount++;
				$note = null;
			}
			$note = new Note();
			$note->chapter = $regs[1];
			$note->verse = $regs[2];
			$note->note = $regs[3];
			$note->keyword = $regs[4];
		} else {
			if ($note != null) {
				if ($note->text) {
					$note->text .= '[br/]';
				}
				$note->text .= $line;
			}
		}
	}
	if ($note != null) {
		if (!$note->note) {
			if (preg_match('/^([0-9]+)([0-9])$/', $note->verse, $subRegs)) {
				$note->verse = $subRegs[1];
				$note->note = $subRegs[2];
			}
		}
		if (!$notesMap[$note->chapter . ':' . $note->verse]) {
			$notesMap[$note->chapter . ':' . $note->verse] = array();
		}
		array_push($notesMap[$note->chapter . ':' . $note->verse], $note);
		$noteCount++;
		$note = null;
	}

	echo '<b>Number of notes:</b> ', $noteCount, '<br/>';
	echo '<b>Number of xrefs:</b> ', $xrefCount, '<br/>';
	$rcvParser = new RcvParser();
	$oldBook = $rcvParser->getBook($bookName);
	$chapterNum = 0;
	$verseNum = 0;

	$outfile = '../../../resources/rcv/' . $bookName . '_tmp.xml';
	$outFp = fopen($outfile, 'w');
	foreach ($xmlLines as $line) {
		$line = str_replace(array('&amp;#151;', '  &amp;#151;  ', '&amp;#151; <'), array(' &amp;#151; ', ' &amp;#151; ', '&amp;#151;<'), $line);
		fwrite($outFp, $line);
	}
	fwrite($outFp, '    <chapters>' . "\n");
	foreach ($oldBook->chapters as $oldChapter) {
		$chapterNum++;
		$verseNum = 0;
		echo '<h2>Chapter ', $chapterNum, '</h2>';
		fwrite($outFp, "\t<chapter num=\"" . $chapterNum . "\">\n");
		foreach ($oldChapter->verses as $oldVerse) {
			$verseNum = $oldVerse->num;
			fwrite($outFp, "\t    <verse num=\"" . $verseNum . "\">\n");
			$rawNotes = $notesMap[$chapterNum . ':' . $verseNum];
			$rawXrefs = $xrefsMap[$chapterNum . ':' . $verseNum];
			$n = 0;
			$xrefAddCount = 0;

			$xmlVerse = trim(str_replace(array('[i]', '[/i]', '[xref]', '[/xref]', '[note]', '[/note]'), array('', '', '', '', '', ''), $oldVerse->text));
			$lsmVerse = str_replace('�', '&#151;', $textMap[$chapterNum . ':' . $verseNum]);
			if (strcmp($xmlVerse, $lsmVerse) != 0) {
				$xmlLen = strlen($xmlVerse);
				$cmpLen = strlen($lsmVerse);
				$len = $xmlLen;
				if ($xmlLen > $cmpLen) {
					$len = $cmpLen;
				}
				$diffStartIndex = 0;
				$diffEndIndex = $len;
				for ($i = 0; $i < $len; $i++) {
					if ($diffStartIndex > 0 && ($xmlVerse[$i] == ' ' || $lsmVerse[$i] == ' ')) {
						$diffEndIndex = $i;
						break;
					}
					if ($xmlVerse[$i] != $lsmVerse[$i]) {
						$diffStartIndex = $i;
					}
				}
				echo '<h3>Verse ', $verseNum, ' DIFF</h3>';
				if ($diffStartIndex == 0) {
					echo '<p><b>XML:</b> ', $xmlVerse, ' (', $xmlLen, ')<br/>';
					echo '<b>LSM:</b> ', $lsmVerse, ' (', $cmpLen, ')</p>';
				} else {
					echo '<p><b>XML:</b> ', substr($xmlVerse, 0, $diffStartIndex), '<b>', substr($xmlVerse, $diffStartIndex, $xmlLen - $diffStartIndex + 1), '</b> (', $xmlLen, ' - diffstartindex=', $diffStartIndex, ')<br/>';
					echo '<b>LSM:</b> ', substr($lsmVerse, 0, $diffStartIndex), '<b>', substr($lsmVerse, $diffStartIndex, $cmpLen - $diffStartIndex + 1), '</b> (', $cmpLen, ' - diffstartindex=', $diffStartIndex, ')</p>';
				}
			}

			$parsedVerse = getParsedVerse($textMap[$chapterNum . ':' . $verseNum], $rawNotes, $rawXrefs, $xrefAddCount);
			if (strlen($parsedVerse) == 0) {
				echo '<p><b>EMPTY VERSE:</b> ', $chapterNum, ':', $verseNum, '<br/>';
			}
			fwrite($outFp, "\t\t<text>" . $parsedVerse . "</text>\n");
			if ($xrefAddCount != count($rawXrefs)) {
				echo '<p><b>XREF MISMATCH:</b> ', $chapterNum, ':', $verseNum, ' - xrefaddcount=', $xrefAddCount, ', rawxrefcount=', count($rawXrefs), ', text=', $parsedVerse, '<br/>';
				foreach ($rawXrefs as $rawXref) {
					echo ' - rawxref=', $rawXref, '<br/>';
				}
				echo '</p>';
			}
			foreach ($oldVerse->footnotes as $oldNote) {
				$noteText = str_replace(array("\n\n", '[br][br]', '[br/][br/]'), array('[br/]', '[br/]', '[br/]'), myStripText($oldNote->text));
				$noteText = str_replace(array('&amp;#151;', '  &amp;#151;  ', '&amp;#151; <'), array(' &amp;#151; ', ' &amp;#151; ', '&amp;#151;<'), $noteText);
				$oldNoteText = str_replace(array('&#151;', "\n\n"), array('&amp;#151;', '[br/][br/]'), $oldNote->text);
				$oldNoteText = str_replace(array('&amp;#151;', '  &amp;#151;  ', '&amp;#151; <'), array(' &amp;#151; ', ' &amp;#151; ', '&amp;#151;<'), $oldNoteText);
				fwrite($outFp, "\t\t<note num=\"" . $oldNote->num . "\">" . $oldNoteText . "</note>\n");
				$rawNoteText = myStripText($rawNotes[$n++]->text);
				if (strcmp($noteText, $rawNoteText) != 0) {
					$xmlLen = strlen($noteText);
					$cmpLen = strlen($rawNoteText);
					$len = $xmlLen;
					if ($xmlLen > $cmpLen) {
						$len = $cmpLen;
					}
					$diffStartIndex = 0;
					$diffEndIndex = $len;
					for ($i = 0; $i < $len; $i++) {
						if ($diffStartIndex > 0 && ($noteText[$i] == ' ' || $rawNoteText[$i] == ' ')) {
							$diffEndIndex = $i;
							break;
						}
						if ($noteText[$i] != $rawNoteText[$i]) {
							$diffStartIndex = $i;
						}
					}
					echo '<h3>Verse ', $verseNum, ', note ', $oldNote->num, '</h3>';
					if ($diffStartIndex == 0) {
						echo '<p><b>XML:</b> ', $noteText, ' (', $xmlLen, ')<br/>';
						echo '<b>LSM:</b> ', $rawNoteText, ' (', $cmpLen, ')</p>';
					} else {
						echo '<p><b>XML:</b> ', substr($noteText, 0, $diffStartIndex), '<b>', substr($noteText, $diffStartIndex, $xmlLen - $diffStartIndex + 1), '</b> (', $xmlLen, ')<br/>';
						echo '<b>LSM:</b> ', substr($rawNoteText, 0, $diffStartIndex), '<b>', substr($rawNoteText, $diffStartIndex, $cmpLen - $diffStartIndex + 1), '</b> (', $cmpLen, ')</p>';
					}
				}
			}
			foreach ($rawXrefs as $rawXref) {
				fwrite($outFp, "\t\t<xref num=\"" . $rawXref->xref . "\">" . $rawXref->text . "</xref>\n");
			}
			fwrite($outFp, "\t    </verse>\n");
		}
		fwrite($outFp, "\t</chapter>\n");
	}
	fwrite($outFp, '    </chapters>' . "\n");
	fwrite($outFp, '</book>' . "\n");
	fclose($outFp);

	function getParsedVerse($verseText, $rawNotes, $rawXrefs, &$xrefAddCount) {
		$newText = str_replace(array('***', '**', '*', '10', '11', '12', '1', '2', '3', '4', '5', '6', '7', '8', '9', '�'), array('[note]***[/note]', '[note]**[/note]', '[note]*[/note]', '[note]10[/note]', '[note]11[/note]', '[note]12[/note]', '[note]1[/note]', '[note]2[/note]', '[note]3[/note]', '[note]4[/note]', '[note]5[/note]', '[note]6[/note]', '[note]7[/note]', '[note]8[/note]', '[note]9[/note]', '&amp;#151;'), $verseText);
		if ($rawXrefs == null) {
			return $newText;
		}
		$words = explode(' ', $newText);
		$retText = '';
		$index = 0;
		$maxWords = count($words);
		foreach ($rawXrefs as $xref) {
			$keyword = $xref->xref . $xref->keyword;
			$alternateKeyword = $xref->xref . '"' . $xref->keyword;
			for (; $index < $maxWords; $index++) {
				if (strlen($retText) > 0) {
					$retText .= ' ';
				}
				if (preg_match('/^' . $keyword . '(.*)$/', $words[$index], $regs1) || preg_match('/^' . $alternateKeyword . '(.*)$/', $words[$index], $regs1)) {
					if (preg_match('/^([a-z])(.+)$/', $words[$index], $regs)) {
						$retText .= '[xref]' . $regs[1] . '[/xref]' . $regs[2];
					} else {
						echo 'ERROR: <b>keyword=', $keyword, ', match=', '/^.+' . $keyword . '$/', ', words[', $index, ']=', $words[$index], '</b> - ', $newText, '<br/>';
					}
					$index++;
					$xrefAddCount++;
					break;
				} else if (preg_match('/^(\[note\][0-9]+\[\/note\])(' . $keyword . ')(.*)$/', $words[$index], $regs)) {
					$retText .= $regs[1] . '[xref]' . $xref->xref . '[/xref]' . $xref->keyword . $regs[3];
					$index++;
					$xrefAddCount++;
					break;
				} else if (preg_match('/^(.+\[br\/\])(' . $keyword . ')(.*)$/', $words[$index], $regs)) {
					$retText .= $regs[1] . '[xref]' . $xref->xref . '[/xref]' . $xref->keyword . $regs[3];
					$index++;
					$xrefAddCount++;
					break;
				} else if (preg_match('/^(.+\[separator\])(' . $keyword . ')(.*)$/', $words[$index], $regs)) {
					$retText .= $regs[1] . '[xref]' . $xref->xref . '[/xref]' . $xref->keyword . $regs[3];
					$index++;
					$xrefAddCount++;
					break;
				} else if (preg_match('/^(\[note\][0-9]+\[\/note\])(' . $alternateKeyword . ')(.*)$/', $words[$index], $regs)) {
					$retText .= $regs[1] . '[xref]' . $xref->xref . '[/xref]"' . $xref->keyword . $regs[3];
					$index++;
					$xrefAddCount++;
					break;
				} else if (preg_match('/^(.+\[br\/\])(' . $alternateKeyword . ')(.*)$/', $words[$index], $regs)) {
					$retText .= $regs[1] . '[xref]' . $xref->xref . '[/xref]"' . $xref->keyword . $regs[3];
					$index++;
					$xrefAddCount++;
					break;
				} else if (preg_match('/^(.+\[separator\])(' . $alternateKeyword . ')(.*)$/', $words[$index], $regs)) {
					$retText .= $regs[1] . '[xref]' . $xref->xref . '[/xref]"' . $xref->keyword . $regs[3];
					$index++;
					$xrefAddCount++;
					break;
				}
				$retText .= $words[$index];
			}
		}
		for (; $index < $maxWords; $index++) {
			if (strlen($retText) > 0) {
				$retText .= ' ';
			}
			$retText .= $words[$index];
		}
		return $retText;
	}
?>

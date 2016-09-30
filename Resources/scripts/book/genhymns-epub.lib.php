<?php

function isHiddenType($type) {
	global $songTypes;
	return !in_array($type, $songTypes);
}

function getSongInfoCategory($type, $song) {
	global $cacheService;
	$content = '';
	if ($song->category) {
/* 		$content .= '<span class="category">'; */
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
		}
		$rowcount = $cacheService->getRowCount('Categories', $category, $type);
		if ($rowcount > 1) {
			$content .= '<a href="' . getFile($type, $song->type, $song->num, 'category', $category) . '">' . $category . '</a>';
			if ($subcategory) {
				$content .= '&mdash;';
				$rowcount = $cacheService->getRowCount('Subcategories', $category . '_' . $subcategory, $type);
				if ($rowcount > 1) {
					$content .= '<a href="' . getFile($type, $song->type, $song->num, 'subcategory', $category . '&mdash;' . $subcategory) . '">' . $subcategory . '</a>';
				} else {
					$content .= $subcategory;
				}
			}
		} else {
			$content .= $category;
		}
/* 		$content .= '</span>' . PHP_EOL; */
	}
	return $content;
}

function getSongLyrics($song) {
	global $manifestList;
	global $spineList;
	$content = '';

/*
	$versenavigationcontent = getVerseNavigationHTML($song, true);
	if ($versenavigationcontent) {
		$content .= '<div class="topversenav">' . $versenavigationcontent . '</div>' . PHP_EOL;
	}
*/

	$stanzacount = count($song->stanzas);
	$first = true;
	$maxstanzanum = 0;
	$canRepeatChorus = false;
	$stanzanumcount = 0;
	$chorusstanza = null;
	foreach ($song->stanzas as $stanza) {
		$stanzanumcount++;
		if ($stanza->type == 'chorus') {
			$canRepeatChorus = ($stanzanumcount == 2);
			$chorusstanza = $stanza;
		}
		if ($stanza->type == "verse") {
			$maxstanzanum = $stanza->num;
		}
	}
	$content .= '<table>' . PHP_EOL;
	$stanzanumcount = 0;
	foreach ($song->stanzas as $stanza) {
		$stanzanumcount++;
		$stanzatext = ($tosimplified) ? str_chinese_simp($stanza->text) : $stanza->text;
		$stanzatext = processLine(preg_replace('/^(.+)<br\/>[ \t]*<br\/>$/', '$1', $stanzatext));
		if ($first) {
			$first = false;
		} else {
/* 			$content .= '<tr><td colspan="4"><div class="verseseparator">&nbsp;</div></td></tr>' . PHP_EOL; */
		}
		$content .= '<tr>' . PHP_EOL;
		if ($stanza->type == "chorus") {
			$content .= '<td><div class="hiddennum">' . $maxstanzanum . '</div></td><td>&nbsp;</td><td><div class="chorusindent">&nbsp;</div></td><td style="color:#0000AA">' . $stanzatext . '</td>';
		} else if ($stanza->type == "note") {
			$content .= '<td colspan="4" class="center" style="color:gray">' . $stanzatext . '</td>';
		} else if ($stanza->type == "copyright") {
			// ignore
		} else if ($stanza->type == 'verse') {
			$content .= '<td><div class="stanzanum"><a href="#lversenav">' . $stanza->num . '</a></div><br/><div id="v' . $stanza->num . '" class="hiddennum">' . $maxstanzanum . '</div></td><td>&nbsp;</td><td colspan="2">' . $stanzatext . '</td>';
			if ($canRepeatChorus && $stanzanumcount != 1) {
				$chorusstanzatext = ($tosimplified) ? str_chinese_simp($chorusstanza->text) : $chorusstanza->text;
				$chorusstanzatext = preg_replace('/^(.+)<br\/>[ \t]*<br\/>$/', '$1', $chorusstanzatext);
				$content .= '</tr>' . PHP_EOL;
/* 				$content .= '<tr"><td colspan="4"><div class="verseseparator">&nbsp;</div></td></tr>' . PHP_EOL; */
				$content .= '<tr>' . PHP_EOL;
				$content .= '<td><div class="hiddennum">' . $maxstanzanum . '</div></td><td>&nbsp;</td><td><div class="chorusindent">&nbsp;</div></td><td style="color:#0000AA">' . $chorusstanzatext . '</td>';
			}
		}
		$content .= '</tr>' . PHP_EOL;
	}
	$content .= '</table>' . PHP_EOL;
	$versenavigationcontent = getVerseNavigationHTML($song, false);
	if ($versenavigationcontent) {
		$content .= '<div id="bottom" class="botversenav">' . $versenavigationcontent . '</div>' . PHP_EOL;
	}
	return $content;
}

function getTitleAndDirname($type, $field) {
	$list = array();
	switch ($field) {
		case 'category':
			$key = 'Category:';
			$dirname = null;
			break;
		case 'meter':
			$key = 'Meter:';
			$dirname = 'Meters';
			break;
		case 'hymncode':
			$key = 'Code:';
			$dirname = 'HymnCodes';
			break;
		case 'author':
			$key = 'Lyrics:';
			$dirname = 'Authors';
			break;
		case 'composer':
			$key = 'Music:';
			$dirname = 'Composers';
			break;
		case 'key':
			$key = 'Key:';
			$dirname = 'Keys';
			break;
		case 'time':
			$key = 'Time:';
			$dirname = 'Times';
			break;
		case 'excerpt':
			$key = 'Ministry:';
			$dirname = 'Excerpts';
			break;
		case 'bible':
			$key = 'Bible:';
			$dirname = null;
			break;
		case 'relatedsongs':
			$key = 'Related:';
			$dirname = null;
			break;
		case 'notes':
			$key = 'Notes:';
			$dirname = null;
			break;
		case 'lyrics':
			$key = 'Lyrics:';
			$dirname = null;
			break;
		case 'music':
			$key = 'Music:';
			$dirname = null;
			break;
		case 'numbers':
			$key = 'Numbers:';
			$dirname = null;
			break;
		case 'sheet':
			$key = 'Score:';
			$dirname = null;
			break;
	}
	$list[0] = $key;
	if (isHiddenType($type)) {
		$list[1] = null;
	} else {
		$list[1] = $dirname;
	}
	return $list;
}

function getInfoContentRow($type, $num, $song, $field, $value) {
	global $cacheService;
	if (!$value) {
		return '';
	}
	list($key, $dirname) = getTitleAndDirname($type, $field);
	$content = '<tr>' . PHP_EOL;
	if ($value == null) {
		$content .= '<td class="key" colspan="2">' . $key . '</td>';
	} else {
		$content .= '<td class="key">' . $key . '</td><td class="val">';
		$rowcount = $cacheService->getRowCount($dirname, $value, $type);
		if ($rowcount > 1) {
			$content .= getFileLink($type, $song->type, $song->num, $field, $value, $value);
		} else {
			$content .= $value;
		}
		$content .= '</td>';
	}
	$content .= '</tr>' . PHP_EOL;
	return $content;
}

function getSongInfoContent($type, $num, $song, $includeMusic = true, $includePianoScore = true, $includeGuitarScore = true) {
	global $songService;
	global $manifestList;
	global $spineList;

	$infoContent = '';

	if ($includeMusic) {
		$mp3File = $song->getMp3File(true);
		if ($mp3File) {
			$mp3Filename = basename($mp3File);
			copy($mp3File, OUTPUT_CONTENT_DIR . DIRECTORY_SEPARATOR . $mp3Filename);
			$manifestList[] = $mp3Filename;
			$infoContent .= '<div class="music"><audio src="' . $mp3Filename . '" controls="controls"></audio></div>' . PHP_EOL;
		}
	}

	$infoContent .= '<table>' . PHP_EOL;

	if ($includePianoScore || $includeGuitarScore) {
		$sheetContent = '';
		if ($includePianoScore) {
			$pianoPdfFile = $song->getMediaFile('pdf', 'piano', false); // Online only
			if ($pianoPdfFile) {
/*
				$pianoPdfFilename = basename($pianoPdfFile);
				copy($pianoPdfFile, OUTPUT_CONTENT_DIR . DIRECTORY_SEPARATOR . $pianoPdfFilename);
				$manifestList[] = $pianoPdfFilename;
				$spineList[] = $pianoPdfFilename . '|no';
*/
				$pianoPdfLink = 'http://www.hymnal.net/hymn.php/' . $song->type . '/' . $song->num . '/f=ppdf';
				$sheetContent .= '<a href="' . $pianoPdfLink . '"><img src="icon_piano.png"/></a>';
			}
		}
		if ($includeGuitarScore) {
			$guitarPdfFile = $song->getMediaFile('pdf', 'guitar', false); // Online only
			if ($guitarPdfFile) {
/*
				$guitarPdfFilename = basename($guitarPdfFile);
				copy($guitarPdfFile, OUTPUT_CONTENT_DIR . DIRECTORY_SEPARATOR . $guitarPdfFilename);
				$manifestList[] = $guitarPdfFilename;
				$spineList[] = $guitarPdfFilename . '|no';
*/
				if ($includePianoScore) {
					$sheetContent .= '&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				$guitarPdfLink = 'http://www.hymnal.net/hymn.php/' . $song->type . '/' . $song->num . '/f=pdf';
				$sheetContent .= '<a href="' . $guitarPdfLink . '"><img src="icon_guitar.png"/></a>';
			}
		}
		if (strlen($sheetContent) > 0) {
		$sheetContent = '<div class="sheet">' . $sheetContent . '</div>' . PHP_EOL;
			$infoContent .= getInfoContentRow($type, $num, $song, 'sheet', $sheetContent);
		}
	}

	$infoContent .= getInfoContentRow($type, $num, $song, 'category', getSongInfoCategory($type, $song));
	$infoContent .= getSongNumbersInfo($song);
	if (count($song->authors) > 0) {
		$infoContent .= getWriterContent($type, $num, $song, 'author');
	}

	if (count($song->composers) > 0) {
		$infoContent .= getWriterContent($type, $num, $song, 'composer');
	}
	$infoContent .= getInfoContentRow($type, $num, $song, 'meter', $song->meter);
	$infoContent .= getInfoContentRow($type, $num, $song, 'key', $song->key);
	$infoContent .= getInfoContentRow($type, $num, $song, 'time', $song->time);
	$infoContent .= getInfoContentRow($type, $num, $song, 'hymncode', $song->hymncode);
	$infoContent .= getInfoContentRow($type, $num, $song, 'notes', $song->notes) . PHP_EOL;
	$infoContent .= getRelatedSongsInfo($song);

	$refs = $song->getReferences();
	if (count($refs) > 0) {
		$first = true;
		$refstr = '';
		foreach ($refs as $ref) {
			if ($first == true) {
				$first = false;
			} else {
				$refstr .= "; ";
			}
			$refstr .= $ref->title;
		}
	}
	$infoContent .= getInfoContentRow($type, $num, $song, 'bible', $refstr);
	$infoContent .= '</table>' . PHP_EOL;
	return $infoContent;
}

function getSongNumbersInfo($song) {
	global $TYPE_TO_NUMBER_CODE;
	global $LANG_TYPE_TO_TYPE;
	global $REVERSE_YPSB;
	global $INVALID_LANGS;
	global $songService;

	$content = '';
	if ($song->numbers) {
		$numberstr = '';
		$processedNumbers = array();
		foreach ($song->numbers as $langType=>$langnum) {
			if ($langType == 'lsmyp') {
				continue;
			}
			$songType = $LANG_TYPE_TO_TYPE[$langType];
			$songNum = $langnum;
			if (preg_match('/^([^:]+):([^:]+)$/', $langnum, $regs)) {
				$songType = $regs[1];
				$songNum = $regs[2];
			}
			if (strlen($songType) == 0) {
				continue;
			}
			$displayCode = $TYPE_TO_NUMBER_CODE[$songType];
			if (preg_match('/^([c]*[0-9]+[b]?)(\*)$/', $songNum, $regs)) {
				$songNum = $regs[1];
				$langnumsuffix = $regs[2];
			} else {
				$langnumsuffix = null;
			}
			$thisNumberStr = '';
			switch ($songType) {
				case 'hk':
					$thisNumberStr = 'K' . $songNum;
					break;
				case 'c':
				case 'ns':
				case 'lb':
					$ypnum = $REVERSE_YPSB[$songType . $songNum];
					if ($ypnum) {
						$thisNumberStrValue = 'YP' . $ypnum;
						if (!preg_match('/^([0-9]+)([a-z])$/', $songNum, $regs)) {
							$songType = 'vsb';
							$songNum = $ypnum;
						}
						if ($processedNumbers[$thisNumberStrValue]) {
							break;
						}
						$processedNumbers[$thisNumberStrValue] = 1;

						$thisFilename = getFile($songType, $songType, $songNum, 'song');
						$isCurrentFile = ($thisFilename == getFile($type, $song->type, $song->num, 'song'));
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
					if (preg_match('/^([0-9]+)([a-z]+)$/', $songNum, $regs)) {
						$thisNumberStrValue = $TYPE_TO_NUMBER_CODE[$songType] . $songNum;
						switch ($regs[2]) {
                            case 'ar':
                                $thisNumberStrValue = 'Arabic';
                                break;
							case 'c':
								$thisNumberStrValue = 'Chinese';
								break;
							case 'd':
								$thisNumberStrValue = 'Dutch';
								break;
							case 'f':
								$thisNumberStrValue = 'French';
								break;
							case 'i':
								$thisNumberStrValue = 'Indonesian';
								break;
						}
					} else {
						if ($langType == 'english') {
							$thisNumberStrValue = 'English';
						} else {
							echo 'ERROR: Cannot find YP number for song ', $songType, ':', $songNum, ': langtype=', $langType, ', langnum=', $langnum, "\n";
						}
					}
					if ($processedNumbers[$thisNumberStrValue]) {
						break;
					}
					$processedNumbers[$thisNumberStrValue] = 1;
					$thisFilename = getFile($songType, $songType, $songNum, 'song');
					$currentFilename = getFile($type, $song->type, $song->num, 'song');
					if (!file_exists(OUTPUT_CONTENT_DIR . $thisFilename)) {
						$thisSong = $songService->getSong($songType, $songNum);
						touch(OUTPUT_CONTENT_DIR . $thisFilename);
						saveSong($songType, $thisSong, 0, array(), 'song');
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
							echo 'WARNING: Invalid number code: TYPE_TO_NUMBERCODE[', $songType, ']=', $displayCode, ', langtype=', $langType, ', langnum=', $langnum, ', songtype=', $songType, "\n";
							break;
						}
					}
					$thisNumberStrValue = $TYPE_TO_NUMBER_CODE[$songType] . $songNum;
					if ($processedNumbers[$thisNumberStrValue]) {
						break;
					}
					$processedNumbers[$thisNumberStrValue] = 1;
					$thisFilename = getFile($songType, $songType, $songNum, 'song');
					$isCurrentFile = ($thisFilename == getFile($type, $song->type, $song->num, 'song'));
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
			if ($numberstr != '') {
				$numberstr .= ', ';
			}
			$numberstr .= $thisNumberStr;
			if ($langnumsuffix) {
				$thisNumberStr .= '<sup>' . $langnumsuffix . '</sup>';
			}
		}
/*
		$numberstr = '';
		foreach ($song->numbers as $langtype=>$langnum) {
			if ($langtype == 'lsmyp') {
				continue;
			}
			$songtype = $LANGTYPE_TO_TYPE[$langtype];
			$songnum = $langnum;
			if (preg_match('/^([^:]+):([^:]+)$/', $langnum, $regs)) {
				$songtype = $regs[1];
				$songnum = $regs[2];
			}
			$displayCode = $TYPE_TO_NUMBERCODE[$songtype];
			if (preg_match('/^([c]*[0-9]+[b]?)(\*)$/', $songnum, $regs)) {
				$songnum = $regs[1];
				$langnumsuffix = $regs[2];
			} else {
				$langnumsuffix = null;
			}
			$thisNumberStr = '';
			switch ($songtype) {
				case 'hk':
					$thisNumberStr = 'K' . $songnum;
					break;
				case 'ns':
				case 'lb':
					$ypnum = $REVERSE_YPSB[$songtype . $songnum];
					if ($ypnum) {
						$thisNumberStr = 'YP' . $ypnum;
					} else {
						echo 'ERROR: Cannot find YP number for song ', $songtype, ':', $songnum, ': langtype=', $langtype, ', langnum=', $langnum, PHP_EOL;
					}
					break;
				default:
					if (!$displayCode) {
						if (!$INVALID_LANGS[$langtype]) {
							$INVALID_LANGS[$langtype] = 1;
							echo 'WARNING: Invalid number code: TYPE_TO_NUMBERCODE[', $songtype, ']=', $displayCode, ', langtype=', $langtype, ', langnum=', $langnum, ', songtype=', $songtype, PHP_EOL;
						}
					}
					$thisNumberStr = '<a href="' . getFile($songtype, $songtype, $songnum, 'song') . '">' . $TYPE_TO_NUMBERCODE[$songtype] . $songnum . '</a>';
					break;
			}
			if ($thisNumberStr == '') {
				continue;
			}
			if ($numberstr != '') {
				$numberstr .= ', ';
			}
			$numberstr .= $thisNumberStr;
			if ($langnumsuffix) {
				$thisNumberStr .= '<sup>' . $langnumsuffix . '</sup>';
			}
		}
*/
		$content .= getInfoContentRow($type, $num, $song, 'numbers', $numberstr);
	}
	return $content;
}

function getRelatedSongsInfo($song) {
	global $REVERSE_YPSB;
	$relatedsongs = $song->getRelatedSongs();
	$relatedsonglist = array();
	foreach ($relatedsongs as $relatedsong) {
		$relatedsongtype = null;
		switch ($relatedsong->type) {
			case 'ns':
			case 'lb':
				if ($REVERSE_YPSB[$relatedsong->type . $relatedsong->num]) {
					$relatedsongtype = 'vsb';
				}
				break;
			default:
				$relatedsongtype = $relatedsong->type;
				break;
		}
		if (!$relatedsongtype) {
			continue;
		}
		if (preg_match('/[0-9]+b$/', $relatedsong->num, $regs)) {
			array_push($relatedsonglist, '<a href="' . getFile($relatedsongtype, $relatedsong->type, $relatedsong->num, 'song') . '">Alternate Tune</a>');
			continue;
		}
		if (preg_match('/([0-9]+)b$/', $song->num, $regs) && $regs[1] == $relatedsong->num) {
			if ($relatedsong->type == 'nt') {
				array_push($relatedsonglist, '<a href="' . getFile($relatedsongtype, $relatedsong->type, $relatedsong->num, 'song') . '">Alternate New Tune</a>');
			} else {
				array_push($relatedsonglist, '<a href="' . getFile($relatedsongtype, $relatedsong->type, $relatedsong->num, 'song') . '">Original Tune</a>');
			}
			continue;
		}
		if ($song->type == "h" && $relatedsong->type == "nt") {
			array_push($relatedsonglist, '<a href="' . getFile($relatedsongtype, $relatedsong->type, $relatedsong->num, 'song') . '">New Tune</a>');
			continue;
		}
		if ($relatedsong->num == $song->num) {
			if ($song->type == "nt" && $relatedsong->type == "h") {
				array_push($relatedsonglist, '<a href="' . getFile($relatedsongtype, $relatedsong->type, $relatedsong->num, 'song') . '">Original Tune</a>');
				continue;
			}
		}
		array_push($relatedsonglist, '<a href="' . getFile($relatedsongtype, $relatedsong->type, $relatedsong->num, 'song') . '">' . $relatedsong->title . '</a>');
	}

	$content = '';
	if (count($relatedsonglist) > 0) {
		$relatedsongsstr = '';
		$first = true;
		foreach ($relatedsonglist as $item) {
			if ($first) {
				$first = false;
			} else {
				$relatedsongsstr .= ', ';
			}
			$relatedsongsstr .= $item;
		}
		$content = getInfoContentRow($type, $num, $song, 'relatedsongs', $relatedsongsstr);
	}
	return $content;
}

function getTitleSuffix($song, $writerType) {
	$writerList = ($writertype == 'author') ? $song->authors : $song->composers;
	if ($writerList) {
		foreach ($writerList as $person) {
			if (!$titlesuffix) {
				switch ($person->name) {
					case 'Witness Lee':
						return '<sup>*</sup>';
					case 'Watchman Nee':
						return '<sup>†</sup>';
				}
			}
		}
	}
	return null;
}

function getWriterContent($type, $num, $song, $writertype) {
	global $REVERSE_YPSB;
	global $cacheService;
	if ($writertype == 'author') {
		$writerlist = $song->authors;
		$title = 'lyrics';
		$dirname = 'Authors';
	} else {
		$writerlist = $song->composers;
		$title = 'music';
		$dirname = 'Composers';
	}
	$content = '';
	$first = true;
	foreach ($writerlist as $person) {
		if ($first) {
			$first = false;
		} else {
			$content .= ', ';
		}
		$parsedwriterprefix = '';
		$cachedParsedWriter = ($person->fullname) ? parseWriterForLink($person->fullname) : parseWriterForLink($person->name);
		$parsedwriter = parseWriterForLink($person->name);
		if ($parsedwriter == '') {
			continue;
		}
		if (preg_match("/^(.+)$parsedwriter$/", $person->name, $regs)) {
			$parsedwriterprefix = $regs[1];
		}
		$rowcount = $cacheService->getRowCount($dirname, $cachedParsedWriter, $type);
		$content .= $parsedwriterprefix;
		if ($rowcount > 1) {
			switch($type) {
				case 'vsb':
					$songnum = $REVERSE_YPSB[$song->type . $song->num];
					break;
				default:
					$songnum = $song->num;
					break;
			}
			$content .= getFileLink($type, $song->type, $song->num, $writertype, $parsedwriter, $parsedwriter);
		} else {
			$content .= $parsedwriter;
		}
		if (!empty($person->biodate)) {
			$content .= ' (' . $person->biodate . ')';
		}
	}
	return getInfoContentRow($type, $num, $song, $title, $content);
}

function getSongExcerpts($song) {
	global $commentService;
	$fav = $commentService->getFavorite($song->type, $song->num);
	$comments = $fav->excerpts;
	$content = '';
	if (count($comments) > 0) {
		$first = true;
		foreach ($comments as $comment) {
			if ($first) {
				$first = false;
			} else {
				$content .= '<hr/>';
			}
			$paragraphs = explode(PHP_EOL, $comment->text);
			$firstParagraph = true;
			foreach ($paragraphs as $paragraph) {
				if (strlen($paragraph) == 0) {
					continue;
				}
				if (preg_match('/^(Source: )(.+)/', $paragraph, $regs)) {
					$content .= '<p class="source"><strong>Source:</strong> ' . $regs[2] . '</p>' . PHP_EOL;
				} else {
					if ($firstParagraph) {
						$content .= '<p class="first">';
						$firstParagraph = false;
					} else {
						$content .= '<p>';
					}
					$content .= $paragraph . '</p>' . PHP_EOL;
				}
			}
		}
	}
	if (strlen($content) > 0) {
		$content = '<div class="excerpts">' . $content . '</div>' . PHP_EOL;
	}
	return $content;
}

function getNavigationBar($type, $numlist, $songindex, $isTop) {
	$navigationbar = '<table id="top" class="navbar"><tr><td class="leftnav">';
	$navnum = findPrevious($type, $numlist, $songindex, 100);
	if ($navnum) {
		$navigationbar .= '<a href="' . getFile($type, $type, $navnum, 'song') . '">&lt;&lt;&lt;</a>';
	} else {
		$navigationbar .= '&lt;&lt;&lt;';
	}
	$navigationbar .= '&nbsp;&nbsp;&nbsp;';
	$navnum = findPrevious($type, $numlist, $songindex, 10);
	if ($navnum) {
		$navigationbar .= '<a href="' . getFile($type, $type, $navnum, 'song') . '">&lt;&lt;</a>';
	} else {
		$navigationbar .= '&lt;&lt;';
	}
	$navigationbar .= '&nbsp;&nbsp;&nbsp;';
	if ($songindex > 0) {
		switch ($type) {
			case 'vsb':
				$navnum = $songindex;
				break;
			default:
				$navnum = $numlist[$songindex-1];
				break;
		}
		$navigationbar .= '<a href="' . getFile($type, $type, $navnum, 'song') . '">&lt;</a>';
	} else {
		$navigationbar .= '&lt;';
	}
	$navigationbar .= '</td><td class="nav">';
	$navigationbar .= '<a href="' . getFile($type, $type, null, 'index') . '">&diams;</a></td><td class="nav">';
	if ($isTop) {
		$navigationbar .= '<a href="#bottom">&darr;</a>';
	} else {
		$navigationbar .= '<a href="#top">&uarr;</a>';
	}
	$navigationbar .= '</td><td class="rightnav">';
	if (($songindex+1) < count($numlist)) {
		switch ($type) {
			case 'vsb':
				$navnum = $songindex + 2;
				break;
			default:
				$navnum = $numlist[$songindex+1];
				break;
		}
		$navigationbar .= '<a href="' . getFile($type, $type, $navnum, 'song') . '">&gt;</a>';
	} else {
		$navigationbar .= '&gt;';
	}
	$navigationbar .= '&nbsp;&nbsp;&nbsp;';
	$navnum = findNext($type, $numlist, $songindex, 10);
	if ($navnum) {
		$navigationbar .= '<a href="' . getFile($type, $type, $navnum, 'song') . '">&gt;&gt;</a>';
	} else {
		$navigationbar .= '&gt;&gt;';
	}
	$navigationbar .= '&nbsp;&nbsp;&nbsp;';
	$navnum = findNext($type, $numlist, $songindex, 100);
	if ($navnum) {
		$navigationbar .= '<a href="' . getFile($type, $type, $navnum, 'song') . '">&gt;&gt;&gt;</a>';
	} else {
		$navigationbar .= '&gt;&gt;&gt;';
	}
	$navigationbar .= '</td></tr></table>';
	return $navigationbar;
}

function getVerseNavigationHTML($song, $isTop) {
	$content = '<span id="lversenav" class="gennumlinkhead">';
	$content .= '<b>Verses:</b>';
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

function convertToParagraphs($text) {
	$paragraphs = explode(PHP_EOL, $text);
	$content = '';
	$hasPrevBlankLine = false;
	$max = count($paragraphs);
	for ($i = 0; $i < $max; $i++) {
		$paragraph = trim($paragraphs[$i]);
		if (strlen($paragraph) == 0) {
			$hasPrevBlankLine = true;
			continue;
		}
		if ($i == 0 || $hasPrevBlankLine) {
			$content .= '<p>';
			$hasPrevBlankLine = false;
		}
		$content .= $paragraph;
		$j = $i + 1;
		if ($j == $max || strlen(trim($paragraphs[$j])) == 0) { // End of file or next line is blank
			$content .= '</p>';
		} else {
			$content .= '<br/>';
		}
	}
	return $content;
}

function saveHome() {
	echo 'saveHome()' . PHP_EOL;
	global $VERSION;
	global $HEADER;
	global $FOOTER;

	$content = $HEADER;
	$content .= '<div id="top" class="heading"><span class="title">Hymns &amp; Spiritual Songs</span><br/>' . PHP_EOL;
	$content .= '<span class="category">Version ' . $VERSION . '</span></div>' . PHP_EOL;
	$content .= '<div class="content home">
<a href="' . getFile('h', 'h', null, 'index') . '">' . getContentTitle('h') . '</a><br/>
<a href="' . getFile('ch', 'ch', null, 'index') . '">' . getContentTitle('ch') . '</a><br/>
<a href="' . getFile('ts', 'ts', null, 'index') . '">' . getContentTitle('ts') . '</a><br/>
<a href="' . getFile('cb', 'cb', null, 'index') . '">' . getContentTitle('cb') . '</a><br/>
<a href="' . getFile('hd', 'hd', null, 'index') . '">' . getContentTitle('hd') . '</a><br/>
<a href="' . getFile('hf', 'hf', null, 'index') . '">' . getContentTitle('hf') . '</a><br/>
<a href="' . getFile('hp', 'hp', null, 'index') . '">' . getContentTitle('hp') . '</a><br/>
<a href="' . getFile('hr', 'hr', null, 'index') . '">' . getContentTitle('hr') . '</a><br/>
<a href="' . getFile('hs', 'hs', null, 'index') . '">' . getContentTitle('hs') . '</a><br/>
<a href="' . getFile('ht', 'ht', null, 'index') . '">' . getContentTitle('ht') . '</a><br/>
<a href="' . getFile('vsb', 'vsb', null, 'index') . '">' . getContentTitle('vsb') . '</a><br/>
<a href="' . getFile('nt', 'nt', null, 'index') . '">' . getContentTitle('nt') . '</a><br/>
</div>';
	$content .= $FOOTER;
	saveFile(getFile(null, null, null, 'home'), $content, 0, 'Home', 'yes');
}

function getContentTitle($type) {
	switch ($type) {
		case 'h':
			return 'English Hymnal';
		case 'ch':
			return '詩歌';
		case 'ts':
			return '補充本詩歌';
		case 'cb':
			return 'Cebuano Hymnal';
        case 'hd':
            return 'Dutch Hymnal';
		case 'hf':
			return 'French Hymnal';
		case 'hp':
			return 'Portuguese Hymnal';
		case 'hr':
			return 'Russian Hymnal';
		case 'hs':
			return 'Spanish Hymnal';
		case 'ht':
			return 'Tagalog Hymnal';
		case 'vsb':
			return 'Vancouver YP Songbook';
		case 'c':
			return 'Children Songs';
		case 'nt':
			return 'New Tunes';
	}
	echo 'ERROR: getContentTitle(' . $type . ')' . PHP_EOL;
	exit;
}

function getNumListValue($type, $numlist, $index) {
	switch ($type) {
		case 'vsb':
			return $index + 1;
		default:
			return $numlist[$index];
	}
}

$savedFilenameMap = array();
function saveFile($filePath, $content, $level, $tocTitle, $linearFlag) {
/* 	echo ' - saveFile: filePath='.$filePath.', title='.$tocTitle.PHP_EOL; */
	global $savedFilenameMap;
	global $tocList;
	global $manifestList;
	global $spineList;
	$fp = fopen(OUTPUT_CONTENT_DIR . DIRECTORY_SEPARATOR . $filePath, 'w');
	fwrite($fp, $content);
	fclose($fp);
	$filename = basename($filePath);
	if ($savedFilenameMap[$filename]) {
		return;
	}
	$savedFilenameMap[$filename] = 1;
	if ($level >= 0) {
		$tocList[] = $level . '|' . $filename . '|' . $tocTitle;
	}
	$manifestList[] = $filename;
	if ($linearFlag != null) {
		$spineList[] = $filename . '|' . $linearFlag;
	}
}

function saveContent($type, $index = null) {
	echo 'saveContent(): type=' . $type . ', index=' . $index . PHP_EOL;
	global $VERSION;
	global $HEADER;
	global $FOOTER;

	$title = getContentTitle($type);
	if ($index) {
		$titleLink = getFile($type, $type, null, 'index');
	} else {
		$titleLink = getFile(null, null, null, 'home');
	}

	$content = $HEADER;
	$content .= '<div id="top" class="heading"><span class="title"><a href="' . $titleLink . '">' . $title . '</a></span><br/>' . PHP_EOL;
	$content .= '<span class="category">Contents</span></div>' . PHP_EOL;
	$content .= '<div class="content">' . PHP_EOL;
	$numlist = getFileList($type, true);
	$count = count($numlist);
	$tocTitle = $title;
	switch ($type) {
		case 'ch':
			$tocTitle = 'Chinese Hymnal';
			break;
		case 'ts':
			$tocTitle = 'Taiwan Supplement';
			break;
	}
	$tocLevel = -1;
	$content .= '<div class="numbers">' . PHP_EOL;
	if ($count > 100) {
		$itemsPerPage = 100;
		$mod = $count % $itemsPerPage;
		$numofpages = (($count - $mod) / $itemsPerPage);
		if ($mod > 0) {
			$numofpages++;
		}
		$lastmin = -1;
		for ($i = 1; $i <= $numofpages; $i++) {
			$min = ($lastmin != -1) ? $lastmin : ($i - 1) * $itemsPerPage;
			if ($min >= $count) {
				break;
			}
			$max = $min + $itemsPerPage;
			if ($max > $count) {
				$max = $count;
			}
			do {
				$maxnum = str_replace('c', '', getNumListValue($type, $numlist, $max-1));
				if ($maxnum % 100 == 0 || $max > $count) {
					$maxValue = str_replace('c', '', getNumListValue($type, $numlist, $max));
					if (preg_match('/b$/', $maxValue, $regs)) {
						$maxnum = $maxValue;
						$max++;
					}
					break;
				}
			} while ($max++ <= $count);
			if ($max > $count) {
				$max = $count;
				$maxnum = str_replace('c', '', getNumListValue($type, $numlist, $count-1));
			}
			$lastmin = $max;
			if ($index) {
				if ($index != $i) {
					continue;
				}
				for ($j = $min; $j < $max; $j++) {
					$thisnum = str_replace('c', '', getNumListValue($type, $numlist, $j));
					if (preg_match('/^([^:]+):([^:]+)$/', $numlist[$j], $regs)) {
						$file = getFile($type, $regs[1], $regs[2], 'song');
					} else {
						$file = getFile($type, $type, $numlist[$j], 'song');
					}
					$content .= '<a href="' . $file . '">' . strtoupper(getNumListValue($type, $numlist, $j)) . '</a>&nbsp;&nbsp; ';
				}
			} else {
				$tocLevel = 0;
				$indexfile = getFile($type, $type, $i, 'index');
				$content .= '<a href="' . $indexfile . '">' . strtoupper(getNumListValue($type, $numlist, $min)) . '-' . strtoupper(getNumListValue($type, $numlist, $max-1)) . '</a>&nbsp;&nbsp; ';
				saveContent($type, $i);
			}
		}
	} else {
		$tocLevel = 0;
		$numcount = 0;
		foreach ($numlist as $num) {
			$numcount++;
			if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
				$songtype = $regs[1];
				$songnum = $regs[2];
			} else {
				$songtype = $type;
				$songnum = $num;
			}
			$file = getFile($type, $songtype, $songnum, 'song');
			$content .= '<a href="' . $file . '">' . ($type == 'vsb' ? $numcount : $num) . '</a> ';
		}
	}

	$content .= '</div>' . PHP_EOL;
	$content .= '</div>' . PHP_EOL;
	$content .= $topnavbar;
	$content .= '<div class="topversenav">';
	switch ($type) {
		case 'h':
		case 'ch':
		case 'cb':
		case 'ht':
			$content .= '<a href="' . getFile($type, $type, null, 'preface') . '">Preface</a><br/>';
			if (!$index) {
				savePreface($type);
			}
			break;
	}
	switch ($type) {
		case 'hp':
			break;
		default:
			$content .= '<a href="' . getFile($type, $type, null, 'categories') . '">Categories</a><br/>';
			saveCategories($type);
			break;
	}
	$content .= '<a href="' . getFile($type, $type, null, 'first-lines') . '">First Lines</a><br/>
<a href="' . getFile($type, $type, null, 'authors') . '">Authors</a><br/>
<a href="' . getFile($type, $type, null, 'composers') . '">Composers</a><br/>
<a href="' . getFile($type, $type, null, 'meters') . '">Meters</a><br/>
<a href="' . getFile($type, $type, null, 'hymncodes') . '">Hymn Codes</a><br/>';
	$content .= '<a href="' . getFile($type, $type, null, 'keys') . '">Key Signatures</a><br/>
<a href="' . getFile($type, $type, null, 'times') . '">Time Signatures</a><br/>';
	saveSongCategories($type, 'keys', false);
	saveSongCategories($type, 'times', false);
	$content .= '</div>' . PHP_EOL;
	$content .= $FOOTER;
	saveFile(getFile($type, $type, $index, 'index'), $content, $tocLevel, $tocTitle, 'yes');
	if (!$index) {
		saveFirstLines($type);
		saveSongCategories($type, 'authors');
		saveSongCategories($type, 'composers');
		saveSongCategories($type, 'meters');
		saveSongCategories($type, 'hymncodes');
	}
}

function savePreface($type) {
	echo 'savePreface: type=' . $type . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $config;

	$title = getContentTitle($type);
	switch ($type) {
		case 'h':
			$prefacestr .= file_get_contents($config->DIRS['Classic Hymns'] . '/English/preface.txt');
			break;
		case 'ch':
			$prefacestr .= file_get_contents($config->DIRS['Classic Hymns'] . '/Chinese/preface.txt');
			break;
		case 'cb':
			$prefacestr .= file_get_contents($config->DIRS['Classic Hymns'] . '/Cebuano/preface.txt');
			break;
		case 'ht':
			$prefacestr .= file_get_contents($config->DIRS['Classic Hymns'] . '/Tagalog/preface.txt');
			break;
	}


	$content = $HEADER;
	$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'index') . '">' . $title . '</a></span><br/>';
	$content .= '<span class="category">Preface</span></div>';
	$content .= '<div class="content">';
	$content .= convertToParagraphs($prefacestr);
	$content .= '</div>';
	$content .= $FOOTER;
	saveFile(getFile($type, $type, null, 'preface'), $content, -1, 'Preface', 'yes');
}

function saveCategories($type) {
	echo 'saveCategories: type=' . $type . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $TYPE_TO_NUMBER_CODE;
	global $REVERSE_YPSB;
	global $songService;

	$categoryManager = new CategoryManager();
	$title = getContentTitle($type);
	$numlist = getFileList($type, true);
	$typelist = array();
	foreach ($numlist as $num) {
		if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
			$songtype = $regs[1];
			$songnum = $regs[2];
		} else {
			$songtype = $type;
			$songnum = $num;
		}
		$song = $songService->getSong($songtype, $songnum);
		$songcategory = trim($song->category);
		if ($songcategory == '') {
			continue;
		}
		list($cat, $subcat) = $categoryManager->splitCategoryAndSubcategory($songcategory, $songtype);
		$typelist[$cat] = 1;
	}
	ksort($typelist);

	$title = getContentTitle($type);
	$content = $HEADER;
	$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'index') . '">' . $title . '</a></span><br/>';
	$content .= '<span class="category">Categories</span></div>';
	$content .= '<div class="content">';
	$content .= '<table>';
	foreach ($typelist as $category=>$ignore) {
		$map = $categoryManager->getSongInfoMap($category, $type);
		if (count($map) == 0) {
			echo 'ERROR: Cannot get category info for ', $category, ' - type=', $type, PHP_EOL;
			continue;
		}
		$canSaveCategory = true;
		if (count($map) == 1) {
			$keys = array_keys($map);
			$subcategory = $keys[0];
			$typenumlist = $map[$subcategory];
			$catsubcat = $category;
			$typenumcount = count($typenumlist);
			if ($subcategory == 'NULL') {
				$canSaveCategory = false;
			} else {
				$catsubcat .= '&mdash;' . $subcategory;
			}
			if ($typenumcount == 1) {
				$typenumkeys = array_keys($typenumlist);
				list($songtype, $songnum) = explode(':', $typenumkeys[0]);
				$file = getFile($type, $songtype, $songnum, 'song');
				switch ($type) {
					case 'vsb':
						$displayValue = 'YP' . $REVERSE_YPSB[$songtype . $songnum];
						break;
					default:
						$displayValue = $TYPE_TO_NUMBER_CODE[$songtype] . $songnum;
						break;
				}
			} else {
				$file = getFile($type, $type, null, $subcategory == 'NULL' ? 'category' : 'subcategory', $catsubcat);
				$displayValue = $typenumcount . ' songs';
			}
			$content .= '<tr><td><a href="' . $file . '">' . $catsubcat . '</a></td><td class="right">';
			$content .= $displayValue;
			$content .= '</td></tr>';
			saveSubcategory($type, $category, ($subcategory != 'NULL' ? $subcategory : null), $typenumlist);
		} else {
			$content .= '<tr><td><a href="' . getFile($type, $type, null, 'category', $category) . '">' . $category . '</a></td><td class="right">';
			$content .= count($map);
			$content .= '</td></tr>';
		}
		if ($canSaveCategory) {
			saveCategory($type, $category, $map);
		}
	}
	$content .= '</table>
</div>';
	$content .= $FOOTER;
	saveFile(getFile($type, $type, 0, 'categories'), $content, -1, $title, 'yes');
}

function saveCategory($type, $category, $map) {
	echo 'saveCategory: type=' . $type . ', category=' . $category . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $TYPE_TO_NUMBER_CODE;
	global $REVERSE_YPSB;

	$title = getContentTitle($type);
	$content = $HEADER;
	$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'categories') . '">' . $title . '</a></span><br/>';
	$content .= '<span class="category">Category: ' . $category . '</span></div>';
	$content .= '<div class="content">';
	$content .= '<table>';
	foreach ($map as $subcategory=>$typenumlist) {
		$catsubcat = $category;
		if ($subcategory != 'NULL') {
			$catsubcat .= '&mdash;' . $subcategory;
		}
		$typenumcount = count($typenumlist);
		if ($typenumcount == 1) {
			$typenumkeys = array_keys($typenumlist);
			list($songtype, $songnum) = explode(':', $typenumkeys[0]);
			$file = getFile($type, $songtype, $songnum, 'song');
			switch ($type) {
				case 'vsb':
					$displayValue = 'YP' . $REVERSE_YPSB[$songtype . $songnum];
					break;
				default:
					$displayValue = $TYPE_TO_NUMBER_CODE[$songtype] . $songnum;
					break;
			}
		} else {
			$file = getFile($type, $type, null, 'subcategory', $catsubcat);
			$displayValue = $typenumcount . ' songs';
		}
		$content .= '<tr><td><a href="' . $file . '">' . $subcategory . '</a></td><td class="right">';
		$content .= $displayValue;
		$content .= '</td></tr>';
		saveSubcategory($type, $category, ($subcategory != 'NULL' ? $subcategory : null), $typenumlist);
	}
	$content .= '</table>
</div>';
	$content .= $FOOTER;
	saveFile(getFile($type, $type, null, 'category', $category), $content, -1, $title, 'yes');
}

function saveSubcategory($type, $category, $subcategory, $typenumlist) {
	echo 'saveSubcategory: type=' . $type . ', category=' . $category . ', subcategory=' . $subcategory . ', count(typenumlist)=' . count($typenumlist) . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $TYPE_TO_NUMBER_CODE;
	global $REVERSE_YPSB;

	$title = getContentTitle($type);
	$content = $HEADER;
	$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'categories') . '">' . $title . '</a></span><br/>';
	$content .= '<span class="category">Category: ';
	if ($subcategory) {
		$content .= '<a href="' . getFile($type, $type, null, 'category', $category) . '">' . $category . '</a>&mdash;' . $subcategory;
	} else {
		$content .= $category;
	}
	$content .= '</span></div>';
	$content .= '<div class="content">';
	$content .= '<table>';
	$titles = getSortedTitlesFromTypenumList($typenumlist);
	foreach ($titles as $lowertitle=>$titleTypeNum) {
/* 		echo ' - title=' . $lowertitle . ', titleTypeNum=' . $titleTypeNum . PHP_EOL; */
		list($title, $songtype, $songnum) = explode('|', $titleTypeNum);
		switch ($type) {
			case 'vsb':
				$displayNum = 'YP' . $REVERSE_YPSB[$songtype . $songnum];
				break;
			default:
				$displayNum = $TYPE_TO_NUMBER_CODE[$songtype] . $songnum;
				break;
		}
		$file = getFile($type, $songtype, $songnum, 'song');
		$content .= '<tr><td><a href="' . $file . '">' . $title . '</a></td><td class="right">' . $displayNum . '</td></tr>';
	}
	$content .= '</table>
</div>';
	$content .= $FOOTER;
	$cattitle = $category;
	if ($subcategory) {
		$cattitle .= '&mdash;' . $subcategory;
	}
	saveFile(getFile($type, $type, null, $subcategory == null ? 'category' : 'subcategory', $cattitle), $content, -1, $title, 'yes');
}

function getSongFirstLines($song) {
	$list = array('', '', '', '');
	$firstverse = true;
	$firstchorus = true;
	foreach ($song->stanzas as $stanza) {
		if (($firstverse && $stanza->type == 'verse') ||
			($firstchorus && $stanza->type == 'chorus')) {
			$textarray = explode("<br/>", $stanza->text);
			$textarrayindex = 0;
			if (preg_match('/^\(/', $textarray[0], $regs)) {
				$textarrayindex++;
			}
			$title = trim(str_replace(array('«', '&nbsp;', '&mdash;'), array('', '', ''), $textarray[$textarrayindex]));
			$title = trim(preg_replace("/^([^\(]+).*$/", '${1}', $title));
			if (preg_match("/^([\"]*)(.+)([&<\"\*\/\(\),;:\.\?!\-])$/", $title, $regs)) {
				$title = $regs[2];
				if ($regs[3] == '"' && $regs[1] != '"') {
					$title .= '"';
				}
			}
			$upperTitle = strtoupper($title);
			if ($song->type == 'hr') {
				$charindex = 0;
				if ($title[0] == '"' || $title[0] == '\'') {
					$charindex++;
				}
				setlocale(LC_CTYPE, 'en_CA.UTF8');
				$firstchar = mb_strtoupper(substr($title, $charindex, 2), "UTF-8");
				$upperTitle = mb_strtoupper($title, "UTF-8");
			} else if ($song->type == 'hp' || $song->type == 'hs') {
				$charindex = 0;
				if ($title[0] == '"' || $title[0] == '\'') {
					$charindex++;
				}
				if (preg_match('/[A-Za-z]/', $title[$charindex], $regs)) {
					$firstchar = strtoupper($title[$charindex]);
				} else {
					$firstchar = strtoupper(substr($title, $charindex, 2));
				}
			} else {
				$firstchar = strtoupper($title[0]);
				if ($firstchar == '"' || $firstchar == '\'') {
					$firstchar = strtoupper($title[1]);
				}
			}
			if ($stanza->type == 'verse') {
				$list[0] = $title;
				$list[1] = $firstchar;
				$firstverse = false;
			} else {
				$list[2] = $upperTitle;
				$list[3] = $firstchar;
				$firstchorus = false;
			}
		}
	}
	if (!$list[0] || $list[0] == '') {
		echo 'WARNING: type=', $song->type, ', num=', $song->num, ', firstchar=', $firstchar, ', textarray[0]=', $textarray[0], PHP_EOL;
	}
	return $list;
}

function saveChineseFirstLines($type) {
	echo 'saveChineseFirstLines: type=' . $type . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $TYPE_TO_NUMBER_CODE;
	global $REVERSE_YPSB;

	if ($type == 'ts') {
		$contenttitle = '補充本詩歌首句筆畫索引';
		$indices = array('一畫', '二畫', '三畫', '四畫', '五畫', '六畫', '七畫', '八畫', '九畫', '十畫', '十一畫', '十二畫', '十三畫', '十四畫', '十五畫', '十六畫', '十七畫', '十八畫', '十九畫', '二十四畫', '二十六畫');
		$file = 'TaiwanSupplementFirstLines.txt';
	} else {
		$contenttitle = '詩歌首句筆畫索引';
		$indices = array('一畫', '二畫', '三畫', '四畫', '五畫', '六畫', '七畫', '八畫', '九畫', '十畫', '十一畫', '十二畫', '十三畫', '十四畫', '十五畫', '十六畫', '十七畫', '十八畫', '十九畫', '二十畫', '二十二畫', '二十三畫', '二十四畫', '二十六畫');
		$file = 'ChineseFirstLines.txt';
	}

	$inputLines = file(ROOT . '/html/mobile/setup/' . $file);
	$title = getContentTitle($type);
	$count = 0;
	foreach ($indices as $index) {
		$content = $HEADER;
		$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'index') . '">' . $title . '</a></span><br/>';
		$content .= '<span class="category">' . $contenttitle . '</span></div>';
		$letterbar = '<div class="topversenav>';
		$first = true;
		$lettercount = 0;
		foreach ($indices as $letter) {
			if ($first) {
				$first = false;
			} else {
				$letterbar .= '&nbsp; ';
			}
			if ($letter == $index) {
				$letterbar .= '<b>' . $letter . '</b>';
			} else {
				$letterbar .= '<a href="' . getFile($type, $type, $lettercount, 'first-lines') . '">' . $letter . '</a>';
			}
			$lettercount++;
		}
		$letterbar .= '</div>';
		$content .= $letterbar;
		$content .= '<div class="content">';
		$content .= '<table>';
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
			echo 'ERROR: no match for letter: ', $index, PHP_EOL;
			exit;
		}
		$listcount = 0;
		foreach ($list as $line) {
			list($firstlinetitle, $typenum) = explode(' ', $line);
			unset($songtype);
			unset($songnum);
			if ($type == 'ts') {
				if (preg_match('/^\(([^:]+):([^:]+)\)$/', $typenum, $regs)) {
					$songtype = $regs[1];
					$songnum = $regs[2];
				} else {
					echo 'ERROR: Invalid line: ', $line, PHP_EOL;
					exit;
				}
			} else {
				if (preg_match('/^\(([^\)]+)\)$/', $typenum, $regs)) {
					$songtype = 'ch';
					$songnum = $regs[1];
				} else {
					echo 'ERROR: Invalid line: ', $line, PHP_EOL;
					exit;
				}
			}
			switch ($type) {
				case 'vsb':
					$displayNum = 'YP' . $REVERSE_YPSB[$songtype . $songnum];
					break;
				default:
					$displayNum = $TYPE_TO_NUMBER_CODE[$songtype] . $songnum;
					break;
			}
			$file = getFile($type, $songtype, $songnum, 'song');
			$content .= '<tr><td><a href="' . $file . '">' . $firstlinetitle . '</a></td><td class="right">' . $displayNum . '</td></tr>';
		}
		$content .= '</table>
</div>';
		$content .= $letterbar;
		$content .= $FOOTER;
		saveFile(getFile($type, $type, $count, 'first-lines'), $content, -1, $title, 'yes');
		$count++;
	}
}

function saveFirstLines($type) {
	echo 'saveFirstLines: type=' . $type . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $TYPE_TO_NUMBER_CODE;
	global $REVERSE_YPSB;
	global $songService;

	if ($type == 'ch' || $type == 'ts') {
		saveChineseFirstLines($type);
		return;
	}

	$title = getContentTitle($type);
	$numlist = getFileList($type, true);
	$typelist = array();
	foreach ($numlist as $num) {
		if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
			$songtype = $regs[1];
			$songnum = $regs[2];
		} else {
			$songtype = $type;
			$songnum = $num;
		}
		$song = $songService->getSong($songtype, $songnum);
		list($versetitle, $versechar, $chorustitle, $choruschar) = getSongFirstLines($song);
		if (!$typelist[$versechar]) {
			$typelist[$versechar] = array();
		}
		array_push($typelist[$versechar], convertToKey($versetitle) . '|' . $versetitle . '|' . $songtype . '|' . $songnum);
		if ($chorustitle) {
			if (!$typelist[$choruschar]) {
				$typelist[$choruschar] = array();
			}
			array_push($typelist[$choruschar], convertToKey($chorustitle) . '|' . $chorustitle . '|' . $songtype . '|' . $songnum);
		}
	}
	ksort($typelist);

	$count = 0;
	$letters = array();
	foreach ($typelist as $firstchar=>$list) {
		array_push($letters, $firstchar);
	}
	foreach ($typelist as $firstchar=>$list) {
		$content = $HEADER;
		$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'index') . '">' . $title . '</a></span><br/>';
		$content .= '<span class="category">First Lines</span></div>';
		$letterbar = '<div class="topversenav">';
		$first = true;
		$lettercount = 0;
		foreach ($letters as $letter) {
			if ($first) {
				$first = false;
			} else {
				$letterbar .= '&nbsp; ';
			}
			if ($letter == $firstchar) {
				$letterbar .= '<b>' . $letter . '</b>';
			} else {
				$letterbar .= '<a href="' . getFile($type, $type, $lettercount, 'first-lines') . '">' . $letter . '</a>';
			}
			$lettercount++;
		}
		$letterbar .= '</div>';
		$content .= $letterbar;
		$content .= '<div class="content">';
		sort($list);
		$content .= '<table>';
		foreach ($list as $lowertitleTitleTypeNum) {
			list($lowertitle, $songtitle, $songtype, $songnum) = explode('|', $lowertitleTitleTypeNum);
			switch ($type) {
				case 'vsb':
					$displayNum = 'YP' . $REVERSE_YPSB[$songtype . $songnum];
					break;
				default:
					$displayNum = $TYPE_TO_NUMBER_CODE[$songtype] . $songnum;
					break;
			}
			$file = getFile($type, $songtype, $songnum, 'song');
			$content .= '<tr><td><a href="' . $file . '">' . $songtitle . '</a></td><td class="right">' . $displayNum . '</td></tr>';
		}
		$content .= '</table>
</div>';
		$content .= $letterbar;
		$content .= $FOOTER;
		saveFile(getFile($type, $type, $count, 'first-lines'), $content, -1, $title, 'yes');
		$count++;
	}
}

function convertToKey($str) {
	return strtolower(str_replace(array('"', '\''), array('', ''), $str));
}

function saveSongCategories($type, $songCategoryType, $indexLetters = true) {
	echo 'saveSongCategories: type=' . $type . ', songCategoryType=' . $songCategoryType . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $TYPE_TO_NUMBER_CODE;
	global $REVERSE_YPSB;
	global $songService;

	$numlist = getFileList($type, true);
	$typelist = array();
	foreach ($numlist as $num) {
		if (preg_match('/^([^:]+):([^:]+)$/', $num, $regs)) {
			$songtype = $regs[1];
			$songnum = $regs[2];
		} else {
			$songtype = $type;
			$songnum = $num;
		}
		$song = $songService->getSong($songtype, $songnum);
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
				$songCategoryList = $song->hymncode ? array($song->hymncode) : null;
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
						$realname = ucwords(parseWriterForLink($obj->name));
						$key = convertToKey($realname);
						$additionalinfo = $obj->biodate;
						break;
					default:
						$key = $obj;
						$realname = $obj;
						$additionalinfo = '';
						break;
				}
				$firstchar = strtoupper($key[0]);
				$list = $typelist[$firstchar];
				if (!$list) {
					$list = array();
				}
				array_push($list, $key . '|' . $realname . '|' . $additionalinfo . '|' . $songtype . '|' . $songnum);
				$typelist[$firstchar] = $list;
			}
		}
	}
	ksort($typelist);

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
	}

	if (!$indexLetters) {
		$newtypelist = array();
		foreach ($typelist as $letter=>$list) {
			$newtypelist = array_merge($newtypelist, $list);
		}
		$typelist = array();
		$typelist['All'] = $newtypelist;
	}

	$count = 0;
	foreach ($typelist as $letter=>$list) {
		sort($list);
		$title = getContentTitle($type);
		$content = $HEADER;
		$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'index') . '">' . $title . '</a></span><br/>';
		$content .= '<span class="category">' . $categoryName . '</span></div>';
		if ($indexLetters) {
			$letterbar = '<div class="topversenav">';
			$first = true;
			$lettercount = 0;
			foreach ($typelist as $myletter=>$mylist) {
				if ($first) {
					$first = false;
				} else {
					$letterbar .= '&nbsp; ';
				}
				if ($myletter == $letter) {
					$letterbar .= '<b>' . $letter . '</b>';
				} else {
					$letterbar .= '<a href="' . getFile($type, $type, $lettercount, $songCategoryType) . '">' . $myletter . '</a>';
				}
				$lettercount++;
			}
			$letterbar .= '</div>';
		} else {
			$letterbar = '';
		}
		$content .= $letterbar;
		$content .= '<div class="content">';
		$content .= '<table>';

		$map = array();
		$convertlist = array();
		foreach ($list as $item) {
			list($key, $objName, $objAdditionalInfo, $songtype, $songnum) = explode('|', $item);
			if ($padKeys) {
				$key = str_pad($key, 30, '_', STR_PAD_RIGHT);
			}
			$itemlist = $map[$key];
			if (!$itemlist) {
				$itemlist = array();
			}
			array_push($itemlist, $item);
			$map[$key] = $itemlist;
			$convertlist[$key] = $objName . '|' . $objAdditionalInfo;
		}
		ksort($map);

		foreach ($map as $key=>$itemlist) {
			if ($padKeys) {
				$key = ltrim($key, '_');
			}
			list($objName, $objAdditionalInfo) = explode('|', $convertlist[$key]);
			$songcount = count($itemlist);
			if ($songcount == 1) {
				list($thiskey, $thisobjName, $thisobjAdditionalInfo, $songtype, $songnum) = explode('|', $itemlist[0]);
				switch ($type) {
					case 'vsb':
						$rightColValue = 'YP' . $REVERSE_YPSB[$songtype . $songnum];
						break;
					default:
						$rightColValue = $TYPE_TO_NUMBER_CODE[$songtype] . $songnum;
						break;
				}
				$file = getFile($type, $songtype, $songnum, 'song');
			} else {
				$file = getFile($type, $type, null, $singleType, $objName);
				$rightColValue = $songcount . ' songs';
			}
			$content .= '<tr><td><a href="' . $file . '">' . $objName . '</a>';
			if ($objAdditionalInfo != '') {
				$content .= ' (' . $objAdditionalInfo . ')';
			}
			$content .= '</td><td class="right">';
			$content .= $rightColValue;
			$content .= '</td></tr>';
			saveSongCategory($type, $songCategoryType, $objName, $itemlist, $count);
		}
		$content .= '</table>
	</div>';
		$content .= $letterbar;
		$content .= $FOOTER;
		saveFile(getFile($type, $type, $count, $songCategoryType), $content, -1, $title, 'yes');
		$count++;
	}
}

function saveSongCategory($type, $songCategoryType, $name, $list, $fileindex) {
	echo 'saveSongCategory: type=' . $type . ', songCategoryType=' . $songCategoryType . ', name=' . $name . ', fileindex=' . $fileindex . PHP_EOL;
	global $HEADER;
	global $FOOTER;
	global $TYPE_TO_NUMBER_CODE;
	global $REVERSE_YPSB;

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
	}

	$title = getContentTitle($type);
	$content = $HEADER;
	$content .= '<div id="top" class="heading"><span class="title"><a href="' . getFile($type, $type, $fileindex, $songCategoryType) . '">' . $title . '</a></span><br/>';
	$content .= '<span class="category">' . $categoryName . ': ' . $name . '</span></div>' . PHP_EOL;
	$content .= '<div class="content">' . PHP_EOL;
	$typenumlist = array();
	foreach ($list as $item) {
		list($key, $objName, $objAdditionalInfo, $songtype, $songnum) = explode('|', $item);
		if ($objName == $name) {
			$typenumlist[$songtype . ':' . $songnum] = 1;
		}
	}

	$content .= '<table>' . PHP_EOL;
	$titles = getSortedTitlesFromTypenumList($typenumlist);
	foreach ($titles as $lowertitle=>$titleTypeNum) {
		list($title, $songtype, $songnum) = explode('|', $titleTypeNum);
		switch ($type) {
			case 'vsb':
				$displayNum = 'YP' . $REVERSE_YPSB[$songtype . $songnum];
				break;
			default:
				$displayNum = $TYPE_TO_NUMBER_CODE[$songtype] . $songnum;
				break;
		}
		$file = getFile($type, $songtype, $songnum, 'song');
		$content .= '<tr><td><a href="' . $file . '">' . $title . '</a></td><td class="right">' . $displayNum . '</td></tr>';
	}
	$content .= '</table>
</div>' . PHP_EOL;
	$content .= $FOOTER;
	saveFile(getFile($type, $type, null, $singleType, $name), $content, -1, $title, 'yes');
}

function getSortedTitlesFromTypenumList($typenumlist) {
	global $songService;

	$titles = array();
	foreach ($typenumlist as $typenum=>$ignore) {
		list($songtype, $songnum) = explode(':', $typenum);
		$song = $songService->getSong($songtype, $songnum);
		$title = $song->title;
		$titles[convertToKey($title)] = $title . '|' . $songtype . '|' . $songnum;
	}
	ksort($titles);
	return $titles;
}

$INVALID_LANGS = array();
function saveSong($type, $song, $songindex, $numlist, $savetype = 'song') {
	echo 'saveSong: type=' . $type . ', songnum=' . $song->num . PHP_EOL;
	global $INCLUDE_MUSIC;
	global $INCLUDE_PIANO_SCORE;
	global $INCLUDE_GUITAR_SCORE;
	global $HEADER;
	global $FOOTER;

	$num = ($type == 'vsb') ? $songindex+1 : $song->num;

	$numberOfSongs = count($numlist);
	$realnum = preg_replace('/^([0-9]+)[^0-9]*$/', '${1}', $num);
	$isAlternateTune = ($realnum != $num);

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
        case 'hd':
            $title = 'Dutch Hymns, #' . $num;
            break;
		case 'hf':
			$title = 'French Hymns, #' . $num;
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

	$titleSuffix = getTitleSuffix($song, 'author');
	if (!$titleSuffix) {
		$title .= $titlesuffix;
	}

	$content = $HEADER;

/* 	$content .= getNavigationBar($type, $numlist, $songindex, true) . PHP_EOL; */

	$content .= '<div class="heading"><span class="title"><a href="' . getFile($type, $type, null, 'index') . '">' . $title . '</a></span></div>' . PHP_EOL;

	$excerptsContent = getSongExcerpts($song);
	$hasExcerpts = (strlen($excerptsContent) > 0);

	$content .= '<div class="content">' . PHP_EOL;
	$content .= '<div class="tabs segmentedControlBase">' . PHP_EOL;
	$content .= '<div class="button segmentedControl leftEnd tab"><span>Lyrics</span></div>' . PHP_EOL;
	if ($hasExcerpts) {
		$content .= '<div class="button segmentedControl tab"><span>Info</span></div>' . PHP_EOL;
		$content .= '<div class="button segmentedControl rightEnd tab"><span>Excerpts</span></div>' . PHP_EOL;
	} else {
		$content .= '<div class="button segmentedControl rightEnd tab"><span>Info</span></div>' . PHP_EOL;
	}
	$content .= '</div>' . PHP_EOL;

	$content .= '<div class="tabPanels">' . PHP_EOL;

	// Lyrics
	$content .= '<div class="tabPanel lyrics">' . PHP_EOL;
	$content .= getSongLyrics($song);
	$content .= '</div>' . PHP_EOL;

	// Info
	$content .= '<div class="tabPanel info">' . PHP_EOL;
	$content .= getSongInfoContent($type, $num, $song, $INCLUDE_MUSIC, $INCLUDE_PIANO_SCORE, $INCLUDE_GUITAR_SCORE);
	$content .= '</div>' . PHP_EOL;

	// Excerpts
	if ($hasExcerpts) {
		$content .= '<div class="tabPanel excerpts">' . PHP_EOL;
		$content .= $excerptsContent;
		$content .= '</div>' . PHP_EOL;
	}

	$content .= '</div>' . PHP_EOL;
	$content .= '</div>' . PHP_EOL;

/* 	$content .= getNavigationBar($type, $numlist, $songindex, false) . PHP_EOL; */
	$content .= $FOOTER;

	saveFile(getFile($type, $song->type, $song->num, $savetype), $content, -1, $title, 'yes');
}

function findPrevious($type, $numlist, $songindex, $modValue) {
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

function findNext($type, $numlist, $songindex, $modValue) {
	global $REVERSE_YPSB;
	$maxcount = count($numlist);
	switch ($type) {
		case 'vsb':
			$retindex = $songindex + $modValue + 1;
			if ($retindex <= $maxcount) {
				return $retindex;
			}
			return null;
	}
	for ($i = $songindex + 5; $i < $maxcount; $i++) {
		$num = preg_replace('/^([0-9]+)[^0-9]*$/', '${1}', $numlist[$i]);
		if ($num % $modValue == 0) {
			return $num;
		}
	}
	return null;
}

function getSectionForField($section, $field) {
	global $TYPE_TO_FILE_PREFIX;
	if (isHiddenType($section)) {
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
			case 'author':
			case 'composer':
			case 'category':
			case 'subcategory':
			case 'meter':
			case 'hymncode':
			case 'key':
			case 'time':
				return 'vsb';
		}
	}
	return $section;
}

function getFileLink($section, $type, $num, $field, $value, $displayValue) {
    if (isHiddenType($section)) {
        return $displayValue;
    }
    $link = getFile($section, $type, $num, $field, $value);
    if ($link == null) {
        return $displayValue;
    }
    return '<a href="' . $link . '">' . $displayValue . '</a>';
}

function getFile($section, $type, $num, $field, $value = null) {
	global $TYPE_TO_FILE_PREFIX;
	global $FIELD_TO_FILE_PREFIX;
	global $YPSB;
	global $REVERSE_YPSB;
	if ($type == 'vsb') {
		if (preg_match('/^([^0-9]+)([0-9]+[b]*)$/', $YPSB[$num], $regs)) {
			$type = $regs[1];
			$num = $regs[2];
		}
	}
	if ($section && !$TYPE_TO_FILE_PREFIX[$section]) {
		echo 'ERROR: getFile(', $section, ', ', $type, ', ', $num, ', ', $field, ', ', $value, '): invalid TYPE_TO_FILEPREFIX[', $section, ']=', $TYPE_TO_FILE_PREFIX[$section], PHP_EOL;
		return null;
	}
	$thissection = getSectionForField($section, $field);
	$filename = $TYPE_TO_FILE_PREFIX[$thissection] ? $TYPE_TO_FILE_PREFIX[$thissection] : '00';
	$filename .= $FIELD_TO_FILE_PREFIX[$field];
	switch ($section) {
		case 'vsb':
			$songnum = $REVERSE_YPSB[$type . $num];
			break;
		default:
			$songnum = $num;
			break;
	}
	if ($songnum == null) {
		$songnum = '0';
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
			$filename .= str_pad($num, 5, '0', STR_PAD_LEFT);
			break;
		case 'song':
		case 'melody':
		case 'excerpt':
			$realnum = preg_replace('/^([c]*[0-9]+)[^0-9]*$/', '${1}', $songnum);
			if ($songnum == '0') {
				echo 'ERROR: invalid num - getFile(', $section, ', ', $type, ', ', $num, ', ', $field, ($value ? ', ' . $value : ''), ')', PHP_EOL;
				return null;
			}
			$isAlternateTune = ($realnum != $songnum);
			if ($isAlternateTune) {
				$filename .= str_pad($songnum, 5, '0', STR_PAD_LEFT);
			} else {
				$filename .= str_pad($songnum, 4, '0', STR_PAD_LEFT) . '0';
			}
			break;
		case 'author':
		case 'composer':
		case 'category':
		case 'subcategory':
		case 'meter':
		case 'hymncode':
		case 'key':
		case 'time':
			$filename .= $value;
			break;
		default:
			echo 'ERROR: getFile(', $section, ', ', $type, ', ', $num, ', ', $field, ($value ? ', ' . $value : ''), ') - invalid field:' . $field . PHP_EOL;
			exit;
	}
	$filename = mb_convert_encoding($filename, 'html', 'utf-8');
	$filename = htmlentities($filename, ENT_QUOTES);
	$filename = str_replace(array('/', '#', '&', ';', ' ', '"', '?', 'ó', 'amp', '.'), array('_', '', '', '', '_', '', '', '', '', ''), $filename);
	if (strlen($filename) > 240) {
		$filename = substr($filename, 0, 240);
	}
	$filename .= '.xhtml';
	return $filename;
}

function parseMelody($melody) {
	$melody = str_replace(array('(break) '), array(''), $melody);
	$bars = explode('|', $melody);
	$retstr = '';
	$firstbar = true;
	foreach ($bars as $bar) {
		$bar = trim($bar);
		if (empty($bar)) {
			continue;
		}
		if ($firstbar) {
			$firstbar = false;
			$retstr .= '|';
		} else {
			$retstr .= '&nbsp;|';
		}
		$notes = explode(' ', $bar);
		$firstnote = true;
		foreach ($notes as $note) {
			$ultype = null;
			if (preg_match('/\/\//', $note, $regs)) {
				$ultype = 'dl';
			} else if (preg_match('/\//', $note, $regs)) {
				$ultype = 'ul';
			}
			$note = str_replace(array('(', ')', '/', 'b', '#', 'Chorus'), array('', '', '', '<sup>b</sup>', '<sup>#</sup>', '<b>(Chorus)</b>'), $note);
			if ($firstnote) {
				$firstnote = false;
				$retstr .= ' ';
			} else {
				$retstr .= '&nbsp;';
			}
			if (preg_match('/^\[([0-9][^\]]+)\]$/', $note, $regs)) {
				$retstr .= '<b>' . $regs[1] . '</b>';
				continue;
			}
			if (preg_match('/^\[([A-Z].*)$/', $note, $regs)) {
				$retstr .= '<b>' . $regs[1];
				continue;
			}
			if (preg_match('/^(Major)\]$/', $note, $regs) || preg_match('/^(Minor)\]$/', $note, $regs)) {
				$retstr .= $regs[1] . '</b>';
				continue;
			}
			if (preg_match('/^\([A-Za-z:]+\)$/', $note, $regs) || preg_match('/^\(vv\.[A-Za-z0-9:,_]+\)$/', $note, $regs) || preg_match('/^\(Verse[A-Za-z0-9:,_]+\)$/', $note, $regs)) {
				$retstr .= '<b><i>' . str_replace('_', ' ', $note) . '</i></b>';
				continue;
			}
			$note = str_replace(array('[', ']'), array('', ''), $note);
			if (preg_match('/^\._(.+)$/', $note, $regs)) {
				$retstr .= '<sup>*</sup>';
				if ($ultype != null) {
					if ($ultype == 'ul') {
						$retstr .= $regs[1] . '/';
					} else {
						$retstr .= $regs[1] . '//';
					}
				} else {
					$retstr .= $regs[1];
				}
				continue;
			}
			if (preg_match('/^(.+)_\.(.*)$/', $note, $regs)) {
				$retstr .= $regs[1] . $regs[2];
				if ($ultype != null) {
					if ($ultype == 'ul') {
						$retstr .= '/';
					} else {
						$retstr .= '//';
					}
				}
				$retstr .= '<sub>*</sub>';
				continue;
			}
			if ($ultype != null) {
				if ($ultype == 'ul') {
					$note .= '/';
				} else {
					$note .= '//';
				}
			}
			$retstr .= $note;
		}
	}
	$retstr .= '&nbsp;|';
	return $retstr;
}

function generateEPubFile() {
	global $TYPE_TO_FILE_PREFIX;
	global $FIELD_TO_FILE_PREFIX;
	global $tocList;
	global $manifestList;
	global $spineList;
	global $resourceFiles;

	$mimetypeFile = OUTPUT_DIR . DIRECTORY_SEPARATOR . 'mimetype';
	$fh = fopen($mimetypeFile, 'w');
	fwrite($fh, utf8_encode('application/epub+zip'));
	fclose($fh);

	// metadata.opf
	$contentOpfFile = OUTPUT_CONTENT_DIR . DIRECTORY_SEPARATOR . 'metadata.opf';
	$fh = fopen($contentOpfFile, 'w');
//		fwrite($fh, pack("CCC",0xef,0xbb,0xbf));
	fwrite($fh, utf8_encode('<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
'<package version="3.0" xmlns="http://www.idpf.org/2007/opf" unique-identifier="bookparser_id">' . PHP_EOL .
'	<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">' . PHP_EOL .
'		<dc:title>Hymns</dc:title>' . PHP_EOL .
'		<dc:identifier id="bookparser_id">' . '1234567890' . '</dc:identifier>' . PHP_EOL .
'		<dc:language>en</dc:language>' . PHP_EOL .
'		<meta property="dcterms:modified">' . date('Y-m-d\TH:i:s\Z') . '</meta>' . PHP_EOL .
'		<dc:creator>hymnal.net</dc:creator>' . PHP_EOL .
'	</metadata>' . PHP_EOL .
'	<manifest>' . PHP_EOL));
	if ($coverImageFile != null) {
		fwrite($fh, utf8_encode('		<item id="cover-image" href="' . $coverImageFile . '" media-type="' . getMediaType($coverImageFile) . '" properties="cover-image"/>' . PHP_EOL));
	}
	fwrite($fh, utf8_encode('		<item id="toc" href="toc.xhtml" media-type="application/xhtml+xml" properties="nav"/>' . PHP_EOL));
	foreach ($manifestList as $file) {
		$mediaType = getMediaType($file);
		$property = '';
		fwrite($fh, utf8_encode('		<item id="' . getItemId($file) . '" href="' .   $file . '" media-type="' . $mediaType . '"' . $property . '/>' . PHP_EOL));
	}
	fwrite($fh, utf8_encode('	</manifest>' . PHP_EOL .
'	<spine>' . PHP_EOL));
	foreach ($spineList as $fileAndLinearFlag) {
		list($file, $linearFlag) = explode('|', $fileAndLinearFlag);
		fwrite($fh, utf8_encode('		<itemref idref="' . getItemId($file) . '" linear="' . $linearFlag . '"/>' . PHP_EOL));
	}
	fwrite($fh, utf8_encode('	</spine>' . PHP_EOL .
'</package>' . PHP_EOL));
	fclose($fh);

	// Table of Contents
	$tocNavFile = OUTPUT_CONTENT_DIR . DIRECTORY_SEPARATOR . 'toc.xhtml';
	$fh = fopen($tocNavFile, 'w');
	fwrite($fh, utf8_encode('<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL . '<nav epub:type="toc">' . PHP_EOL . "\t" . '<ol>' . PHP_EOL));
	$currentLevel = 0;
	$first = true;
	$maxLevel = 1;
	foreach ($tocList as $item) {
		list($level, $filename, $title) = explode('|', $item);
		$title = processLine($title);
		$pad = str_pad('', $level + 1, "\t", STR_PAD_LEFT);
		if ($first) {
			$first = false;
		} else {
			if ($level < $currentLevel) { // Close tag
				for ($i = $currentLevel; $i > $level && $i > 0; $i--) {
					fwrite($fh, utf8_encode(str_pad('', $i + 1, "\t", STR_PAD_LEFT) . '</ol>' . PHP_EOL));
				}
			} else if ($level > $currentLevel) { // New tag
				if ($level > $maxLevel) {
					$maxLevel = $level;
				}
				fwrite($fh, utf8_encode($pad . '<ol>' . PHP_EOL));
			}
		}
		$currentLevel = $level;
		fwrite($fh, utf8_encode($pad . '	<li><a href="' . $filename . '">' . $title . '</a></li>' . PHP_EOL));
	}
	while ($currentLevel >= 0) {
		$pad = str_pad('', $currentLevel + 1, "\t", STR_PAD_LEFT);
		fwrite($fh, utf8_encode($pad . '</ol>' . PHP_EOL));
		$currentLevel--;
	}
	fwrite($fh, utf8_encode('</nav>' . PHP_EOL));
	fclose($fh);
}

?>

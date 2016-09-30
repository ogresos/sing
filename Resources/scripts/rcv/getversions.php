<?php

require_once(__DIR__ . '/../../../src/bootstrap.php');

$hasDBConnection = $dbService->startDB();
if (!$hasDBConnection) {
    echo 'The database is down.';
    exit();
}

echo '<html>';
echo '<head>';
echo '</head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=big5">';
echo '<body>';

function dropTables($tables)
{
    global $dbService;
    foreach ($tables as $table) {
        $query = 'DROP TABLE ' . $table;
        $result = mysqli_query($dbService->conn, $query);
        echo 'DROP TABLE ', $table, ': ' . $result . '<hr/>';
    }
}

function createTables($tables)
{
    global $config;
    global $BIBLE_VERSION_TABLE;
    global $BIBLE_TEXT_TABLE;
    global $CHINESE_BIBLE_TEXT_TABLE;
    foreach ($tables as $table) {
        switch ($table) {
            case $BIBLE_VERSION_TABLE:
                $query = 'CREATE TABLE ' . $BIBLE_VERSION_TABLE . ' ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'name VARCHAR(80) NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
            case $BIBLE_TEXT_TABLE:
                $query = 'CREATE TABLE ' . $BIBLE_TEXT_TABLE . ' ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'versionId INT NOT NULL REFERENCES bible_version(id), ' .
                    'bookName VARCHAR(4096) NOT NULL REFERENCES rcv_book(name), ' .
                    'chapterNum INT NOT NULL, ' .
                    'verseNum INT NOT NULL, ' .
                    'text VARCHAR(4096) NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
            case $CHINESE_BIBLE_TEXT_TABLE:
                $query = 'CREATE TABLE ' . $CHINESE_BIBLE_TEXT_TABLE . ' ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'versionId INT NOT NULL REFERENCES bible_version(id), ' .
                    'bookName VARCHAR(4096) NOT NULL REFERENCES rcv_book(name), ' .
                    'chapterNum INT NOT NULL, ' .
                    'verseNum INT NOT NULL, ' .
                    'text VARCHAR(4096) CHARACTER SET big5 COLLATE big5_chinese_ci NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
        }
        $result = mysqli_query($config->conn, $query) or die('Query failed: ' . $query);
        echo 'CREATE TABLE ', $table, ': ' . $result . '<hr/>';
    }
}

function insertVersion($versionName)
{
    global $config;
    global $BIBLE_VERSION_TABLE;
    $currentTimestamp = strtotime(date('Y-m-d H:i:s'));
    $query = "INSERT INTO $BIBLE_VERSION_TABLE (name, createdDate, lastUpdatedDate) VALUES ('$versionName', FROM_UNIXTIME($currentTimestamp), FROM_UNIXTIME($currentTimestamp))";
    $result = mysqli_query($config->conn, $query) or die('Query failed: ' . $query . '<hr/>');
    return $result;
}

function getVersionId($versionName)
{
    global $config;
    global $BIBLE_VERSION_TABLE;
    $query = "select * from $BIBLE_VERSION_TABLE where name = '$versionName'";
    $result = mysqli_query($config->conn, $query) or die('Query failed: ' . $query . '<hr/>');
    $row = mysqli_fetch_array($result);
    return $row['id'];
}

$tables = array($BIBLE_TEXT_TABLE, $CHINESE_BIBLE_TEXT_TABLE);
//	dropTables($tables);
//	createTables($tables);

$VERSIONS = array();
$VERSIONS['ASV'] = 8;
$VERSIONS['Darby'] = 16;
$VERSIONS['KJV'] = 9;
$VERSIONS['WYC'] = 53;
$VERSIONS['YLT'] = 15;
$VERSIONS['ChineseRcV'] = 999;

$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;
$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : 0;
$newTestamentFlag = ($_REQUEST['type'] == 'nt');

$versionName = 'ChineseRcV';
$version = $VERSIONS[$versionName];
$startChapter = isset($_REQUEST['startchapter']) ? $_REQUEST['startchapter'] : 1;
$deleteFlag = ($startChapter == 1);

$versionId = getVersionId($versionName);
if (!($versionId > 0)) {
    $result = insertVersion($versionName);
    echo '<p><strong>Insert New Version:</strong> ', $result, '<hr/>';
    $versionId = getVersionId($versionName);
}

for ($index = $start; $index <= $end; $index++) {
    if ($version == 999) {
        $base_url = 'http://www.recoveryversion.com.tw/Style0A/026/read_List.php?f_BookNo=';
    } else {
        $base_url = 'http://www.biblegateway.com/passage/?search=';
    }

    $bookName = $newTestamentFlag ? $NEW_TESTAMENT_BOOKS[$index] : $OLD_TESTAMENT_BOOKS[$index];
    $book = getBook(parseBook($bookName));

    if ($deleteFlag) {
        echo 'Delete ', $book->name, ' (', $versionName, '): id=', $versionId, '<br/>';
        $dbBookName = strToDb($book->name);
        $query = "DELETE FROM $BIBLE_TEXT_TABLE WHERE bookName = $dbBookName and versionId = $versionId";
        $result = mysqli_query($config->conn, $query);
        echo $query, ': ', $result, '<br/>';
        $query = "DELETE FROM $CHINESE_BIBLE_TEXT_TABLE WHERE bookName = $dbBookName and versionId = $versionId";
        $result = mysqli_query($config->conn, $query);
        echo $query, ': ', $result, '<br/>';
    }

    echo '<p><strong>', $bookName, ': ', $MAX_CHAPTERS[$bookName], ' chapter(s).</strong></p>';
    for ($chapter = $startChapter; $chapter <= $MAX_CHAPTERS[$bookName]; $chapter++) {
        $textVerses = null;
        if ($version == 999) {
            $url = $base_url . ($newTestamentFlag ? ($index + 40) : ($index + 1)) . '&f_ChapterNo=' . $chapter;
            echo '<strong>', $url, '</strong><br/>';
            $contents = file_get_contents($url) or die('Cannot get content for: ' . $url);
            $contents = str_replace('&nbsp;', ' ', $contents);
            $contents = preg_replace('/:<a name="[0-9]+">([0-9]+)<\/a>/i', ':\1', $contents);
            $contents = preg_replace('/<sup>[^<]+<\/sup>/i', '', $contents);
            $contents = preg_replace('/<a [^>]+><\/a>/i', '', $contents);
            $contents = preg_replace('/<TR><TD[^>]*>([0-9]+):([0-9]+)<\/TD><TD[^>]*>([^>]+)<\/TD><\/TR>/i', '!!!#\1:\2,\3#!!!', $contents);
            $tempArray = preg_split('/!!!/', $contents);
            $textVerses = array();
            foreach ($tempArray as $tmp) {
                if (preg_match('/^#([0-9]+):([0-9]+),([^#]+)#$/', $tmp, $regs)) {
                    array_push($textVerses, $regs[2] . ',' . $regs[3]);
                }
            }
        } else {
            $url = $base_url . str_replace(' ', '+', $bookName) . '%20' . $chapter . ';&version=' . $version . ';';
            echo '<strong>', $url, '</strong><br/>';
            $contents = file_get_contents($url) or die('Cannot get content for: ' . $url);
            $contents = str_replace(array('&nbsp;', 'ÃÂ¹'), array(' ', ''), $contents);
            $contents = preg_replace('/<sup>[^<]+<\/sup>/', '', $contents);
            $contents = preg_replace('/<h5>[^<]+<\/h5>/', '', $contents);
            if (preg_match('/<div class="result-text-style-normal">\n<p><h4>([^<]+)<\/h4>[ ]*<p \/>[^<]+([^\n]+)<\/div>/', $contents, $regs)) {
                if (trim($regs[1]) == '') {
                    die('Parsing Error for: ' . $url);
                }
                echo $regs[1], '/', $MAX_CHAPTERS[$bookName], '<br/>';
                $textVerses = preg_split('/<p \/> /', $regs[2]);
            } else if (preg_match('/<div class="result-text-style-normal">\n<p><h4>([^<]+)<\/h4>[ ]*<h5><b>[^<]+<\/b><\/h5>[ ]*([^\n]+)<\/div>/', $contents, $regs)) {
                if (trim($regs[1]) == '') {
                    die('Parsing Error for: ' . $url);
                }
                echo $regs[1], '/', $MAX_CHAPTERS[$bookName], '<br/>';
                $textVerses = preg_split('/<p \/> /', $regs[2]);
            } else if (preg_match('/<div class="result-text-style-normal">\n<p><h4>([^<]+)<\/h4>[ ]*([^\n]+)[ ]*<\/p><p \/><strong>Footnotes/', $contents, $regs)) {
                echo $regs[1], '/', $MAX_CHAPTERS[$bookName], '<br/>';
                $textVerses = preg_split('/<p \/> /', $regs[2]);
            } else if (preg_match('/<div class="result-text-style-normal">\n<p><h4>([^<]+)<\/h4>[ ]*[^<]+([^\n]+)<\/div>/', $contents, $regs)) {
                if (trim($regs[1]) == '') {
                    die('Parsing Error for: ' . $url);
                }
                echo $regs[1], '/', $MAX_CHAPTERS[$bookName], '<br/>';
                $textVerses = preg_split('/<p \/> /', $regs[2]);
            } else {
                echo '<hr/>', $contents . '<hr/>';
                die('No Match for: ' . $url);
            }
        }

        if ($textVerses) {
            $verseCount = 0;
            foreach ($textVerses as $textVerse) {
                $verseCount++;
                if (preg_match('/^[ ]*<span[^>]+>([0-9]+)<\/span>[ ]*(.+)[ ]*$/', $textVerse, $verseRegs)) {
                    $bibleText = new BibleText();
                    $bibleText->bookName = $bookName;
                    $bibleText->versionId = $versionId;
                    $verseNum = trim($verseRegs[1]);
                    $verseText = trim($verseRegs[2]);
                    $refs = getReferences($bookName . ' ' . $chapter . ':' . $verseNum);
//						$verses = $refs[0]->getVerses();
//						if (count($verses) != 1) {
//							die('count(verses) != 1: ' . $ref->toString());
//						}
                    $bibleText->chapterNum = $chapter;
                    $bibleText->verseNum = $verseNum;
                    $bibleText->text = $verseText;
                    $bibleText->save();
                } else if (preg_match('/^([0-9]+),(.+)[ ]*$/', $textVerse, $verseRegs)) { // Chinese
                    $bibleText = new BibleText();
                    $bibleText->bookName = $bookName;
                    $bibleText->versionId = $versionId;
                    $verseNum = trim($verseRegs[1]);
                    $verseText = trim($verseRegs[2]);
                    $refs = getReferences($bookName . ' ' . $chapter . ':' . $verseNum);
                    $verses = $refs[0]->getVerses();
//						if (count($verses) != 1) {
//							foreach ($verses as $verse) {
//								echo 'Verse: ', $verse->toString(), '<br/>';
//							}
//							die('count(verses) = ' . count($verses) . ': ' . $refs[0]->toString());
//						}
                    $bibleText->chapterNum = $chapter;
                    $bibleText->verseNum = $verseNum;
                    $bibleText->chineseText = $verseText;
                    $bibleText->save();
                } else {
                    echo '<b>Parse Error:</b> ', $bookName, ' ', $chapter, ': ', $textVerse, '<br/>';
                }
            }
        } else {
            echo '<b>ERROR:</b> no verses.<br/>';
        }
    }
}

$config->endDB();
echo '</body>';
echo '</html>';
?>

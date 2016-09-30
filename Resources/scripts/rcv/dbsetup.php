<?php

define('SCRIPT_MODE', true);
require_once(__DIR__ . '/../../../src/rcv-bootstrap.php');

use HymnalNet\RcV\Common\RcVConstants;
use HymnalNet\RcV\Domain\Book;
use HymnalNet\RcV\Domain\Note;
use HymnalNet\RcV\Domain\OldChapter;
use HymnalNet\RcV\Domain\OldFootnote;
use HymnalNet\RcV\Domain\OldVerse;
use HymnalNet\RcV\Domain\OldXref;
use HymnalNet\RcV\Domain\Outline;
use HymnalNet\RcV\Domain\Reference;
use HymnalNet\RcV\Domain\Verse;
use HymnalNet\RcV\Domain\Xref;
use HymnalNet\RcV\Services\RcVParser;

$hasDBConnection = $dbService->startDB();
if (!$hasDBConnection) {
    echo 'The database is down: ' . mysqli_error($dbService->conn) . PHP_EOL;
    exit();
}

$tables = array('rcv_book', 'rcv_outline', 'rcv_verse', 'rcv_note', 'rcv_xref');
//	$tables = array('rcv_outline');
dropTables($tables);
createTables($tables);

$BIBLE_BOOKS = array("Genesis", "Exodus", "Leviticus", "Numbers", "Deuteronomy", "Joshua", "Judges", "Ruth", "1 Samuel", "2 Samuel", "1 Kings", "2 Kings", "1 Chronicles", "2 Chronicles", "Ezra", "Nehemiah", "Esther", "Job", "Psalms", "Proverbs", "Ecclesiastes", "Song of Songs", "Isaiah", "Jeremiah", "Lamentations", "Ezekiel", "Daniel", "Hosea", "Joel", "Amos", "Obadiah", "Jonah", "Micah", "Nahum", "Habakkuk", "Zephaniah", "Haggai", "Zechariah", "Malachi", "Matthew", "Mark", "Luke", "John", "Acts", "Romans", "1 Corinthians", "2 Corinthians", "Galatians", "Ephesians", "Philippians", "Colossians", "1 Thessalonians", "2 Thessalonians", "1 Timothy", "2 Timothy", "Titus", "Philemon", "Hebrews", "James", "1 Peter", "2 Peter", "1 John", "2 John", "3 John", "Jude", "Revelation");

$rcvParser = new RcvParser();

for ($i = 0; $i <= 2; $i++) {
    for ($b = 0; $b < 66; $b++) {
        $bookName = $rcvManager->parseBook($BIBLE_BOOKS[$b]);
        $maxChapters = 0;
        switch ($i) {
            case 0: // Book info
                $rcvManager->deleteAllFromBook($bookName);
                insertBookInfo($bookName);
                break;
            case 1: // Book outline
                insertBookOutline($bookName);
                break;
            case 2: // Chapters
                if ($bookName) {
                    $maxChapters = RcVConstants::$MAX_CHAPTERS[ucwords($bookName)];
                }
                for ($j = 0; $j <= $maxChapters; $j++) {
                    insertChapterForBook($bookName, $j);
                }
                break;
        }
    }
}

function insertChapterForBook($bookName, $chapter)
{
    global $rcvParser;
    global $rcvManager;
    $oldBook = $rcvParser->getBook($bookName);
    $book = $rcvManager->getBook($bookName);
    echo ' > Inserting chapter ', $chapter, '/', RcVConstants::$MAX_CHAPTERS[ucwords($book->name)], ' for book: ', $book->name, PHP_EOL;
    $chapterCount = 0;
    $verseCount = 0;
    $noteCount = 0;
    $xrefCount = 0;

    echo ' >  Deleting chapter ', $chapter, PHP_EOL;
    $dbVerses = $book->getVersesInChapter($chapter);
    foreach ($dbVerses as $dbVerse) {
        /* @var $dbVerse Verse */
        $dbNotes = $dbVerse->getNotes();
        foreach ($dbNotes as $dbNote) {
            /* @var $dbNote Note */
            $dbNote->delete();
        }
        $dbXrefs = $dbVerse->getXrefs();
        foreach ($dbXrefs as $dbXref) {
            /* @var $dbXref Xref */
            $dbXref->delete();
        }
        $dbVerse->delete();
    }

    foreach ($oldBook->chapters as $oldChapter) {
        /* @var $oldChapter OldChapter */
        if ($oldChapter->num != $chapter) {
            continue;
        }
        foreach ($oldChapter->verses as $oldVerse) {
            /* @var $oldVerse OldVerse */
            $verse = new Verse();
            $verse->bookId = $book->id;
            $verse->chapterNum = $oldChapter->num;
            $verse->verseNum = $oldVerse->num;
            $verse->text = $oldVerse->text;
            $verse->save();
            $verseCount++;

            foreach ($oldVerse->footnotes as $oldNote) {
                /* @var $oldNote OldFootnote */
                $note = new Note();
                $note->verseId = $verse->id;
                $note->num = $oldNote->num;
                $note->text = $oldNote->text;
                $note->save();
                $noteCount++;
            }

            foreach ($oldVerse->xrefs as $oldXref) {
                /* @var $oldXref OldXref */
                $xref = new Xref();
                $xref->verseId = $verse->id;
                $xref->num = $oldXref->num;
                $xref->text = $oldXref->text;
                $xref->save();
                $xrefCount++;
            }
        }
        $chapterCount++;
    }
    echo ' >> Number of inserted verses: ', $chapter, ':1-', $verseCount, PHP_EOL;
    echo ' >> Number of inserted notes: ', $noteCount, PHP_EOL;
    echo ' >> Number of inserted xrefs: ', $xrefCount, PHP_EOL;
}

function insertBookInfo($bookName)
{
    global $rcvParser;
    $oldBook = $rcvParser->getBook($bookName);
    $book = new Book();
    $book->name = $oldBook->name;
    $book->description = $oldBook->description;
    echo 'Inserting book info: ', $bookName, PHP_EOL;
    foreach ($oldBook->info as $key => $value) {
        switch ($key) {
            case 'Author':
                $book->author = $value;
                break;
            case 'Time of Writing':
                $book->timeOfWriting = $value;
                break;
            case 'Place of Writing':
                $book->placeOfWriting = $value;
                break;
            case 'Place of the Record':
                $book->placeOfTheRecord = $value;
                break;
            case 'Time Period Covered':
                $book->timePeriodCovered = $value;
                break;
            case 'Time of His Ministry':
                $book->timeOfHisMinistry = $value;
                break;
            case 'Place of His Ministry':
                $book->placeOfHisMinistry = $value;
                break;
            case 'Object of His Ministry':
                $book->objectOfHisMinistry = $value;
                break;
            case 'Recipients':
                $book->recipients = $value;
                break;
            default:
                echo 'Unknown Info: ', $key, '=', $value, ' for book ', $book->name, PHP_EOL;
                break;
        }
    }
    $book->subject = $oldBook->subject;
    $book->numOfChapters = count($oldBook->chapters);
    $result = $book->save();
    echo ' -> Result of insertion into ', $bookName, ': ' . ($result ? 'success' : 'failed') . PHP_EOL;
}

function insertBookOutline($bookName)
{
    global $rcvParser;
    global $rcvManager;
    global $ACTIVE_REF;
    $oldBook = $rcvParser->getBook($bookName);
    $book = $rcvManager->getBook($bookName);
    echo 'Inserting outline for book: ', $book->name, PHP_EOL;
    echo 'Number of outline items: ', count($oldBook->outline), PHP_EOL;
    $outlineCount = 0;
    foreach ($oldBook->outline as $oldOutline) {
        $outline = new Outline();
        $outline->bookId = $book->id;
        $outline->type = $oldOutline->type;
        $outline->num = $oldOutline->num;
        $outline->ref = $oldOutline->ref;
        $outline->text = $oldOutline->text;
        $refs = $rcvManager->getReferences($oldOutline->ref);
        foreach ($refs as $thisRef) {
            $ref = new Reference();
            $ref->fromBookId = $thisRef->fromBookId;
            $ref->fromBook = $thisRef->fromBook;
            $ref->fromChapter = $thisRef->fromChapter;
            $ref->fromVerse = $thisRef->fromVerse;
            $ref->toBook = $thisRef->toBook;
            $ref->toChapter = $thisRef->toChapter;
            $ref->toVerse = $thisRef->toVerse;
            $backupRef = $ACTIVE_REF->replicate();
            $refBook = $rcvManager->getBook($rcvManager->parseBook($thisRef->fromBook));
            $ACTIVE_REF = $backupRef;
            if ($ref->fromBook != $ref->toBook && $ref->toBook != null) {
                $outline->fromBookId = $refBook->id;
                $outline->fromChapter = $ref->fromChapter;
                $outline->fromVerse = $ref->fromVerse;
                $outline->toChapter = $refBook->numOfChapters;
                $outline->toVerse = $refBook->getNumberOfVerses($refBook->numOfChapters);
                $outline->save();

                $refBook = $rcvManager->getBook($rcvManager->parseBook($ref->toBook));
                $ACTIVE_REF->toBook = $refBook->name;
                $outline->fromBookId = $refBook->id;
                $outline->fromChapter = 1;
                $outline->fromVerse = 1;
                $outline->toChapter = $ref->toChapter;
                $outline->toVerse = $ref->toVerse;
                $outline->save();
            } else {
                $outline->fromBookId = $refBook->id;
                $outline->fromChapter = $ref->fromChapter;
                $outline->fromVerse = $ref->fromVerse;
                $outline->toBookId = $refBook->id;
                $outline->toChapter = $ref->toChapter;
                $outline->toVerse = $ref->toVerse;
                $outline->save();
            }
        }
        $outlineCount++;
    }
    echo ' -> Number of inserted outline items: ', $outlineCount, PHP_EOL;
}

function dropTables($tables)
{
    global $dbService;
    foreach ($tables as $table) {
        $query = 'DROP TABLE ' . $table;
        $result = mysqli_query($dbService->conn, $query);
        echo 'DROP TABLE ', $table, ': ' . (string)$result . PHP_EOL;
    }
}

function createTables($tables)
{
    global $dbService;
    foreach ($tables as $table) {
        switch ($table) {
            case 'rcv_book':
                $query = 'CREATE TABLE rcv_book ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'name VARCHAR(128) NOT NULL, ' .
                    'description VARCHAR(512) NOT NULL, ' .
                    'author VARCHAR(1024), ' .
                    'timeOfWriting VARCHAR(512), ' .
                    'placeOfWriting VARCHAR(512), ' .
                    'placeOfTheRecord VARCHAR(512), ' .
                    'timePeriodCovered VARCHAR(1024), ' .
                    'timeOfHisMinistry VARCHAR(512), ' .
                    'placeOfHisMinistry VARCHAR(512), ' .
                    'objectOfHisMinistry VARCHAR(512), ' .
                    'recipients VARCHAR(512), ' .
                    'subject VARCHAR(1024) NOT NULL, ' .
                    'numOfChapters INT NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
            case 'rcv_outline':
                $query = 'CREATE TABLE rcv_outline ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'bookId INT NOT NULL REFERENCES rcv_book(id), ' .
                    'type VARCHAR(30) NOT NULL, ' .
                    'num INT, ' .
                    'ref VARCHAR(256) NOT NULL, ' .
                    'text VARCHAR(4096) NOT NULL, ' .
                    'fromBookId INT NOT NULL REFERENCES rcv_book(id), ' .
                    'fromChapter INT NOT NULL, ' .
                    'fromVerse VARCHAR(30) NOT NULL, ' .
                    'toChapter INT NOT NULL, ' .
                    'toVerse VARCHAR(30) NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
            case 'rcv_verse':
                $query = 'CREATE TABLE rcv_verse ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'bookId INT NOT NULL REFERENCES rcv_book(id), ' .
                    'chapterNum INT NOT NULL, ' .
                    'verseNum INT NOT NULL, ' .
                    'text VARCHAR(4096) NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
            case 'rcv_note':
                $query = 'CREATE TABLE rcv_note ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'verseId INT NOT NULL REFERENCES rcv_verse(id), ' .
                    'num INT NOT NULL, ' .
                    'text TEXT NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
            case 'rcv_xref':
                $query = 'CREATE TABLE rcv_xref ( ' .
                    'id INT NOT NULL AUTO_INCREMENT, ' .
                    'verseId INT NOT NULL REFERENCES rcv_verse(id), ' .
                    'num VARCHAR(1) NOT NULL, ' .
                    'text VARCHAR(4096) NOT NULL, ' .
                    'createdDate DATETIME NOT NULL, ' .
                    'lastUpdatedDate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id))';
                break;
            default:
                $query = '';
                break;
        }
        $query .= ' ENGINE = MYISAM';
        $result = mysqli_query($dbService->conn, $query) or die('Query failed: ' . mysqli_error($dbService->conn) . PHP_EOL);
        echo 'CREATE TABLE ', $table, ': ' . ($result ? 'success' : 'failed') . PHP_EOL;
    }
}

$dbService->endDB();

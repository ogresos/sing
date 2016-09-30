<?php
require('../global-lib.php');
require('../rcv-lib.php');

$hasDBConnection = $config->startDB();
if (!$hasDBConnection) {
    echo 'The database is down.';
    exit();
}

$query = "select * from $BIBLE_TEXT_TABLE where bookName = 'Genesis' and chapterNum = 28 and verseNum = 3;";
echo 'Query: ', $query, '<br/>';
$result = mysqli_query($config->conn, $query) or die('Query failed: ' . mysqli_error($config->conn) . PHP_EOL);
echo 'Result: ', ($result ? 'success' : 'failure'), '<br/>';
$versions = array();
while ($row = mysqli_fetch_array($result)) {
//		if (isset($row['id'])) {
    $bibleText = new BibleText();
    $bibleText->id = $row['id'];
    $bibleText->versionId = $row['versionId'];
    $bibleText->bookName = $row['bookName'];
    $bibleText->chapterNum = $row['chapterNum'];
    $bibleText->verseNum = $row['verseNum'];
    $bibleText->text = $row['text'];
    $bibleText->createdDate = $row['createdDate'];
    $bibleText->lastUpdatedDate = $row['lastUpdatedDate'];
    array_push($versions, $bibleText);
//		}
}
mysqli_free_result($result);
foreach ($versions as $version) {
    echo $version, '<br/>';
    /*
            if ($version->versionid == 1) {
                $version->text = 'And the Almighty God bless thee, and make thee fruitful and multiply thee, that thou mayest become a company of peoples.';
                $version->updateChanges();
            }
    */
}
$config->endDB();
?>

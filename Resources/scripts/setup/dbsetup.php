<?php

define('SCRIPT_MODE', true);
require_once(__DIR__ . '/../../../src/bootstrap.php');

use HymnalNet\Bootstrap\Constants;

$hasDBConnection = $dbService->startDB();
if (!$hasDBConnection) {
    echo 'The database is down.';
    echo mysqli_error($dbService->conn);
    exit();
}

$tables = array(Constants::DB_SONG_TABLE, Constants::DB_SONG_NUMBERS_TABLE, Constants::DB_SONG_WRITER_TABLE, Constants::DB_SONG_GROUP_TABLE, Constants::DB_SONG_LINK_TABLE, Constants::DB_SONG_MEDIA_TABLE, Constants::DB_SONG_STANZA_TABLE, Constants::DB_SONG_SEARCH_TABLE);
//	dropTables($tables);
createTables($tables);

/*
	$query = "ALTER TABLE $SONG_TABLE CHANGE COLUMN category category VARCHAR(4096) CHARACTER SET utf8 COLLATE utf8_general_ci";
	$result = mysqli_query($config->conn, $query) or die('Query failed: ' . $query . '<br/>');
	echo $query, ': result=', $result, '<br/>';
	$query = "ALTER TABLE $SONG_TABLE CHANGE COLUMN subcategory subcategory VARCHAR(4096) CHARACTER SET utf8 COLLATE utf8_general_ci";
	$result = mysqli_query($config->conn, $query) or die('Query failed: ' . $query . '<br/>');
	echo $query, ': result=', $result, '<br/>';
*/

function getKeywords($string)
{
    $string = preg_replace("/([\"#\*\/\(\),;:\.\?!\-])/", " ", $string);
    $parts = explode(" ", $string);
    return array_unique($parts);
}

function dropTables($tables)
{
    global $dbService;
    foreach ($tables as $table) {
        $query = 'DROP TABLE ' . $table;
        $result = mysqli_query($dbService->conn, $query);
        echo 'DROP TABLE ', $table, ': ' . (string)$result . '<hr/>';
    }
}

function createTables($tables)
{
    global $dbService;
    foreach ($tables as $table) {
        $query = 'CREATE TABLE ' . $table . ' ( ';
        switch ($table) {
            case Constants::DB_USER_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'firstname VARCHAR(30) NOT NULL, ' .
                    'lastname VARCHAR(30) NOT NULL, ' .
                    'email VARCHAR(50) NOT NULL, ' .
                    'cryptedemail VARCHAR(255) NOT NULL, ' .
                    'password VARCHAR(50) NOT NULL, ' .
                    'gender VARCHAR(1) NOT NULL, ' .
                    'country VARCHAR(30) NOT NULL, ' .
                    'ipaddress VARCHAR(20), ' .
                    'host VARCHAR(80), ' .
                    'referrer VARCHAR(80), ' .
                    'browser VARCHAR(80), ' .
                    'canaccesslyrics BOOL NOT NULL DEFAULT false, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id)';
                break;
            case Constants::DB_PENDING_USER_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'firstname VARCHAR(80) NOT NULL, ' .
                    'lastname VARCHAR(80) NOT NULL, ' .
                    'email VARCHAR(50) NOT NULL, ' .
                    'password VARCHAR(50) NOT NULL, ' .
                    'gender VARCHAR(1) NOT NULL, ' .
                    'country VARCHAR(30) NOT NULL, ' .
                    'ipaddress VARCHAR(20), ' .
                    'host VARCHAR(80), ' .
                    'referrer VARCHAR(80), ' .
                    'browser VARCHAR(80), ' .
                    'canaccesslyrics BOOL NOT NULL DEFAULT false, ' .
                    'confirmationkey VARCHAR(32) NOT NULL, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id), ' .
                    'UNIQUE KEY (email)';
                break;
            case Constants::DB_USER_FAVS_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'userid INT NOT NULL REFERENCES hn_user(id), ' .
                    'category VARCHAR(80) NOT NULL DEFAULT \'default\', ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id)';
                break;
            case Constants::DB_SONG_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(5) NOT NULL, ' .
                    'title VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, ' .
                    'category VARCHAR(4096) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'subcategory VARCHAR(4096) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'source VARCHAR(80), ' .
                    'sourceurl VARCHAR(80), ' .
                    'meter VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'hymncode VARCHAR(80) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'timesig VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'keysig VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'notes VARCHAR(4096) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'date VARCHAR(80), ' .
                    'copyright VARCHAR(1024), ' .
                    'publicdomain BOOL NOT NULL DEFAULT false, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id), ' .
                    'KEY(type), ' .
                    'KEY(num)';
                break;
            case Constants::DB_SONG_NUMBERS_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'langtype VARCHAR(20) NOT NULL, ' .
                    'langnum VARCHAR(10) NOT NULL, ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id)';
                break;
            case Constants::DB_SONG_WRITER_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'name VARCHAR(80) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, ' .
                    'writertype VARCHAR(1) NOT NULL, ' .
                    'biodate VARCHAR(20), ' .
                    'url VARCHAR(80), ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id), ' .
                    'KEY(name), ' .
                    'KEY(writertype)';
                break;
            case Constants::DB_SONG_GROUP_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'name VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, ' .
                    'description VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id), ' .
                    'KEY(name), ' .
                    'KEY(type), ' .
                    'KEY(num)';
                break;
            case Constants::DB_SONG_LINK_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'name VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, ' .
                    'description VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci, ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'url VARCHAR(80), ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id), ' .
                    'KEY(name), ' .
                    'KEY(type), ' .
                    'KEY(num)';
                break;
            case Constants::DB_SONG_MEDIA_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'name VARCHAR(40) NOT NULL, ' .
                    'location VARCHAR(3) NOT NULL, ' .
                    'attr VARCHAR(20) NOT NULL, ' .
                    'format VARCHAR(20) NOT NULL, ' .
                    'length INT, ' .
                    'size INT, ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id)';
                break;
            case Constants::DB_SONG_STANZA_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'seqid INT NOT NULL, ' .
                    'stanzatype VARCHAR(20) NOT NULL, ' .
                    'stanzanum INT, ' .
                    'text VARCHAR(8192) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, ' .
                    'createdate DATETIME NOT NULL, ' .
                    'lastupdateddate DATETIME NOT NULL, ' .
                    'PRIMARY KEY(id), ' .
                    'KEY(seqid)';
                break;
            case Constants::DB_SONG_SEARCH_TABLE:
                $query .= 'id INT NOT NULL AUTO_INCREMENT, ' .
                    'type VARCHAR(3) NOT NULL, ' .
                    'num VARCHAR(6) NOT NULL, ' .
                    'text TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, ' .
                    'PRIMARY KEY(id), ' .
                    'KEY(type), ' .
                    'KEY(num), ' .
                    'FULLTEXT (text)';
                break;
            case Constants::DB_COUNTRY_TABLE:
                $query .= 'start_ip CHAR(15) NOT NULL, ' .
                    'end_ip CHAR(15) NOT NULL, ' .
                    'start INT UNSIGNED NOT NULL, ' .
                    'end INT UNSIGNED NOT NULL, ' .
                    'country_code CHAR(2) NOT NULL, ' .
                    'country_name VARCHAR(50) NOT NULL';
                break;
        }
        $query .= ' ) ENGINE = MYISAM';
        $result = mysqli_query($dbService->conn, $query) or die('Query failed: ' . $query);
        echo 'CREATE TABLE ', $table, ': ' . (string)$result . '<hr/>';
    }
}

$dbService->endDB();
?>

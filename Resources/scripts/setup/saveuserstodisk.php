<?php

use HymnalNet\Bootstrap\Constants;
use HymnalNet\Services\SongService;
use HymnalNet\Domain\User;

require_once(__DIR__ . '/../../../src/bootstrap.php');

$start = 5001;
$end = 5500;

$query = "SELECT * FROM " . Constants::DB_USER_TABLE;
$result = mysqli_query($dbService->conn, $query);
$users = array();
while ($row = mysqli_fetch_array($result)) {
    if ($row['id']) {
        $user = new User();
        $user->id = $row['id'];
        $user->roles = $row['roles'];
        $user->firstName = $row['firstname'];
        $user->lastName = $row['lastname'];
        $user->email = strtolower($row['email']);
        $user->password = $row['password'];
        $user->gender = $row['gender'];
        $user->country = $row['country'];
        $user->ipAddress = $row['ipaddress'];
        $user->createdDate = $row['createdate'];
        $user->lastUpdatedDate = $row['lastupdateddate'];
        array_push($users, $user);
    }
}
mysqli_free_result($result);

$userCount = count($users);
$count = 0;
foreach ($users as $user) {
    $count++;
    if ($count >= $start && $count <= $end) {
        echo 'User ', $count, '/', $userCount, ': ', $user, "\n";
        saveUserToDisk($user);
    }
}

$dbService->endDB();

function saveUserToDisk(User $user)
{
    if (!$user->createdDate) {
        $user->createdDate = date('Y-m-d H:i:s');
    }
    $user->lastUpdatedDate = date('Y-m-d H:i:s');
    $favStr = '';
    $first = true;
    $favList = $user->getFavorites();
    $songService = new SongService();
    foreach ($favList as $fav) {
        if ($first) {
            $first = false;
        } else {
            $favStr .= "\n";
        }
        $song = $songService->getSong($fav->type, $fav->num);
        $favStr .= "\t\t" . '<fav category="' . $fav->category . '" type="' . $fav->type . '" num="' . $fav->num . '">' . str_replace('&', '&amp;', $song->title) . '</fav>';
    }
    if ($favStr != '') {
        $favStr .= "\n";
    }
    $userFirstName = utf8_encode($user->firstName);
    $userLastName = utf8_encode($user->lastName);
    if ($user->email == 'gralve@yahoo.com') {
        $userFirstName = 'Thai';
        $userLastName = 'Nguyen';
    }
    $xmlStr = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<user>
<firstname>$userFirstName</firstname>
<lastname>$userLastName</lastname>
<email>$user->email</email>
<password>$user->password</password>
<gender>$user->gender</gender>
<country>$user->country</country>
<ipaddress>$user->ipAddress</ipaddress>
<createdate>$user->createdDate</createdate>
<lastupdateddate>$user->lastUpdatedDate</lastupdateddate>
<lastlogindate>$user->lastLoginDate</lastlogindate>
<failedlogincount>$user->failedLoginCount</failedlogincount>
<favourites>
$favStr	</favourites>
</user>
XML;
    try {
        $xml = new SimpleXMLElement($xmlStr);
        $fp = fopen('../../../resources/generated/Users/' . $user->email . '.xml', 'w');
        fwrite($fp, $xml->asXML());
        fclose($fp);
    } catch (Exception $e) {
        echo 'XML: ', $xmlStr, "\n";
        echo 'ERROR: ', $e->getMessage(), "\n";
        exit;
    }
}

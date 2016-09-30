<?php

define('SCRIPT_MODE', true);
require_once(__DIR__ . '/../../../src/bootstrap.php');

use HymnalNet\Book\BookGenerator;

$bookGenerator = new BookGenerator();
$bookGenerator->build();

?>

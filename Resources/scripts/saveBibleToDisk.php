<?php
require('../../html/lib/global-lib.php');
require('../../html/rcv/bible-lib.php');

$parser = new BibleParser();
$parser->getBook('Ruth');
?>

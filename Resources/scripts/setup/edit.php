<?php

require_once(__DIR__ . '/../../../src/bootstrap.php');

use HymnalNet\Bootstrap\Util;
use HymnalNet\Services\SongService;

if (!$userService->isAdmin()) {
    echo "Please log in.";
    exit;
}

$songService = new SongService();

if ($_POST) {
    $filename = $songService->getSongFile($_POST['t'], $_POST['n']);
    $fp = fopen($filename, 'w');
    fwrite($fp, urldecode(stripslashes($_POST['data'])));
    fclose($fp);
    echo Util::toJSON('success', substr($filename, strlen(ROOT) + 1) . ' is saved.');
    exit;
}

if (count($params->params) >= 2) {
    $paramType = $params->params[0];
    $paramNum = $params->params[1];
} else {
    echo 'Invalid parameters.';
    exit;
}

$filename = $songService->getSongFile($paramType, $paramNum);
$content = str_replace('&', '&amp;', file_get_contents($filename));
$song = $songService->getSong($paramType, $paramNum);
$title = $song->title;
$link = $config->LANG_BASE_URL . '/hymn/' . $paramType . '/' . $paramNum;

echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-us" lang="en-us">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="content-language" content="en-us" />
<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/themes/cupertino/jquery-ui.css" />
</head>
<body style="margin: 0 auto 0 auto;">
<h2>Edit Song: <a href="$link" target="_new">$title</a></h2>
<form id="edit">
<textarea style="width: 100%">$content</textarea>
<br/>
<input type="submit" name="submit" value="Save"/>
</form>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script>!window.jQuery && document.write('<script src="../../js/jquery-1.4.2.min.js"><\/script>')</script>
<script>
$(document).ready(function() {
	$('#edit textarea').css('height', ($(window).height() - 100) + 'px');
	$('#edit').submit(function() {
		$.ajax({
			url : 'http://www.hymnal.net/lib/setup/edit.php',
			type : 'POST',
			data : 't=$paramType&n=$paramNum&data=' + encodeURIComponent($('#edit textarea').val()),
			dataType : 'json',
			beforeSend : function() {
				$('#edit input[type="submit"]').val('Saving...');
			},
			success : function(json) {
				$('#edit').before('<div class="ui-state-default ui-corner-all ui-state-highlight"><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>' + json.msg + '</div>');
				$('#edit input[type="submit"]').val('Save');
				window.scroll(0, 0);
			}
		});
		return false;
	});
});
</script>
</body>
</html>
END;

?>

<?php

require_once(__DIR__ . '/../../src/bootstrap.php');

if (count($argv) <= 1) {
    echo 'Usage: sudo -u www-data php ' . $argv[0] . ' <dir>' . PHP_EOL;
    exit();
}

$baseDir = $argv[1];
if (substr($baseDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
    $baseDir .= DIRECTORY_SEPARATOR;
}
foreach (scandir($baseDir) as $file) {
    if ($file == '.' || $file == '..') {
        continue;
    }

    $sourceFilePath = $baseDir . DIRECTORY_SEPARATOR . $file;
    if (is_file($sourceFilePath)) {
        $targetFilePath = $userService->getUserFilePath($baseDir, basename($sourceFilePath), false);
        echo 'Move: ' . $sourceFilePath . ' ===> ' . $targetFilePath . PHP_EOL;
        $dir = dirname($targetFilePath);
        if (!file_exists($dir)) {
            mkdir($dir, 0770, true);
        }
        rename($sourceFilePath, $targetFilePath);
    }
}

?>

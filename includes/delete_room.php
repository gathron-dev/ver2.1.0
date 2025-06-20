<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$roomId = $_GET['roomId'] ?? '';
if (!$roomId || !preg_match('/^[a-zA-Z0-9_]+$/', $roomId)) exit;

$dir = __DIR__ . '/../data/' . $roomId;

// 再帰的にディレクトリを削除する関数
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) rrmdir($path);
        else unlink($path);
    }
    rmdir($dir);
}

if (is_dir($dir)) {
    rrmdir($dir);
    echo 'deleted';
} else {
    echo 'notfound';
}
?>

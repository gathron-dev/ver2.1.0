<?php
$base = __DIR__ . '/../data';
$now  = time();

// includes/get_countdown.php からカウントダウン時間（分）を取得
$countdownFile = __DIR__ . '/../includes/get_countdown.php';
$durationMin = 15; // デフォルト15分
if (is_file($countdownFile)) {
    $code = file_get_contents($countdownFile);
    if (preg_match('/(\d+)\s*\*\s*60/', $code, $m)) {
        $durationMin = (int)$m[1];
    }
}

// ディレクトリ削除までの猶予（カウントダウン終了後5秒表示分を加算）
$expire = ($durationMin * 60) + 5;

foreach (glob($base . '/*', GLOB_ONLYDIR) as $dir) {
    $startFile = $dir . '/start_time.txt';
    if (file_exists($startFile)) {
        $start = (int)trim(file_get_contents($startFile));
    } else {
        // フォルダの作成時刻（ctimeまたはbirthtime）をフォールバック利用
        $stat  = stat($dir);
        $start = $stat['ctime'];
    }

    if ($now - $start >= $expire) {
        rrmdir($dir); // ディレクトリごと再帰削除
    }
}

function rrmdir($dir) {
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file)) rrmdir($file);
        else unlink($file);
    }
    rmdir($dir);
}
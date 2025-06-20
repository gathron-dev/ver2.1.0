<?php
// includes/shutdown_room.php
$roomId = $_GET['roomId'] ?? '';
if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId)) { http_response_code(400); exit; }

// shutdown.txt フラグON
$flag = __DIR__ . "/../data/$roomId/shutdown.txt";
file_put_contents($flag, '1');

// メッセージログにシステムメッセージ追加
$logFile = __DIR__ . "/../data/$roomId/messages.json";
$log = is_file($logFile) ? json_decode(file_get_contents($logFile), true) : [];
$log[] = [
    'type' => 'system',
    'user' => '',
    'message' => 'このグループチャットは参加者により強制退室されました。5秒後にトップページへリダイレクトします。内容は全て削除されます。',
    'timestamp' => time()
];
file_put_contents($logFile, json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo 'OK';

<?php
// api/mask_message.php

header('Content-Type: application/json');

$roomId = $_POST['roomId'] ?? '';
$messageId = $_POST['messageId'] ?? '';

// バリデーション
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $roomId) || !preg_match('/^[a-zA-Z0-9_-]+$/', $messageId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// メッセージログのパス
$logPath = __DIR__ . "/../data/{$roomId}/message_log.json";
if (!file_exists($logPath)) {
    echo json_encode(['success' => false, 'error' => 'Log not found']);
    exit;
}

// ログ読み込み
$log = json_decode(file_get_contents($logPath), true);
if (!is_array($log)) $log = [];

// メッセージIDで伏せ文字化
$updated = false;
foreach ($log as &$msg) {
    if (isset($msg['id']) && $msg['id'] === $messageId) {
        $msg['content'] = "？";
        $updated = true;
        break;
    }
}
unset($msg);

// 書き戻し
if ($updated) {
    file_put_contents($logPath, json_encode($log, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Message not found']);
}
?>

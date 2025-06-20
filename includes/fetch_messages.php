<?php
// includes/fetch_messages.php
session_start();
header('Content-Type: application/json');

$roomId = $_GET['roomId'] ?? '';
$since  = isset($_GET['since']) ? (float)$_GET['since'] : 0;
if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId)) {
  http_response_code(400);
  echo '[]';
  exit;
}

$logFile = __DIR__ . "/../data/$roomId/messages.json";
if (!file_exists($logFile)) {
  echo '[]';
  exit;
}

$all = json_decode(file_get_contents($logFile), true);
$new = [];

// --- タイムスタンプが $since より後のメッセージだけ返す ---
// mask イベントも、mask_message.php で付けられた新しい timestamp を使うので
// このフィルタでリアルタイムに 1 度だけ取得できます。
foreach ($all as $msg) {
  if (isset($msg['timestamp']) && $msg['timestamp'] > $since) {
    $new[] = $msg;
  }
}

echo json_encode($new, JSON_UNESCAPED_UNICODE);
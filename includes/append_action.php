<?php
// includes/append_action.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false]);
  exit;
}

$in     = json_decode(file_get_contents('php://input'), true);
$room   = $in['roomId']  ?? '';
$target = $in['msgId']   ?? '';
$action = $in['action']  ?? '';

if (
  !preg_match('/^[a-f0-9]{16,}$/i', $room) ||
  !is_numeric($target) ||
  !$action
) {
  http_response_code(400);
  echo json_encode(['ok'=>false]);
  exit;
}

$path = __DIR__ . "/../data/$room/messages.json";
$log  = file_exists($path)
  ? json_decode(file_get_contents($path), true)
  : [];

// ① action イベントを1件だけ追加
$log[] = [
  'clientId'  => 'system',
  'type'      => 'action',
  'target'    => (int)$target,
  'action'    => $action,
  'timestamp' => time(),
];

file_put_contents($path, json_encode($log, JSON_UNESCAPED_UNICODE));
echo json_encode(['ok'=>true]);
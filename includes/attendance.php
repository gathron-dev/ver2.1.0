<?php
// includes/attendance.php
session_start();
header('Content-Type: application/json; charset=utf-8');

$room = $_GET['roomId'] ?? '';
if (!preg_match('/^[a-f0-9]{16,}$/i', $room)) {
  echo json_encode(['count'=>0]);
  exit;
}

$dir  = __DIR__ . "/../data/$room";
$file = "$dir/attendance.json";

// 既存データ読み込み
if (is_file($file)) {
  $data = json_decode(file_get_contents($file), true);
  if (!is_array($data)) $data = [];
} else {
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $data = [];
}

// 自分のセッションIDをタイムスタンプで記録
$data[session_id()] = time();

// 〆切：2分以上更新がないセッションは削除
$threshold = time() - 120;
foreach ($data as $sid => $ts) {
  if ($ts < $threshold) {
    unset($data[$sid]);
  }
}

// ファイルへ書き戻し
file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));

// 参加者数を返す
echo json_encode(['count' => count($data)]);
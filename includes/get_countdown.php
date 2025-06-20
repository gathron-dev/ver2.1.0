<?php
// includes/get_countdown.php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$roomId = $_GET['id'] ?? '';
if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId)) {
  echo json_encode(['remaining'=>0]);
  exit;
}

$dir = __DIR__ . '/../data/' . $roomId;
$startFile = "$dir/start_time.txt";
if (!file_exists($startFile)) {
  $remaining = 0;
} else {
  $start     = (int)trim(file_get_contents($startFile));
  $elapsed   = time() - $start;
  $duration  = 15*60; // 15分 = 900秒
  $remaining = max(0, $duration - $elapsed);
}

echo json_encode(['remaining'=>$remaining]);
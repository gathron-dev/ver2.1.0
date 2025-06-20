<?php
// includes/append_message.php
session_start();
header('Content-Type: application/json');

/* ---------- 1. ルーム検証 ---------- */
$roomId = $_GET['roomId'] ?? '';
if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid room']);
  exit;
}
$dataDir = __DIR__ . "/../data/$roomId";
if (!is_dir($dataDir)) {
  http_response_code(404);
  echo json_encode(['error' => 'Room not found']);
  exit;
}

/* ---------- 2. 参加者ごとに “写真＋名前” を一度だけ割り当て ---------- */
if (!isset($_SESSION['avatarIdx'])) {
  // 画像 0-24:女性, 25-49:男性
  $idx = random_int(0, 49);
  $femaleNames = ['Emma','Olivia','Ava','Sophia','Mia','Amelia','Harper','Evelyn','Abigail','Emily','Ella','Elizabeth','Sofia','Madison','Avery','Scarlett','Victoria','Grace','Chloe','Natalie','Hannah','Zoey','Lillian','Addison','Lily'];
  $maleNames   = ['Liam','Noah','Oliver','Elijah','Lucas','Mason','Logan','James','Aiden','Ethan','Jacob','Jackson','Michael','Alexander','Benjamin','Daniel','Matthew','Henry','Sebastian','Jack','Samuel','David','Joseph','Carter','Wyatt'];

  if ($idx < 25) {
    $name = $femaleNames[array_rand($femaleNames)];          // 女性
  } else {
    $name = $maleNames[array_rand($maleNames)];              // 男性
  }
  $_SESSION['avatarIdx'] = $idx;
  $_SESSION['uname']     = mb_strlen($name) > 7 ? mb_substr($name,0,7) : $name; // 保険
}

/* ---------- 3. 受信パラメータ ---------- */
$logFile = "$dataDir/messages.json";
$messages = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

$input = $_POST['text'] ?? null;
$type  = $input !== null ? 'text' : (isset($_POST['imageUrl']) ? 'image' : null);
$content = ($type === 'text') ? trim($input) : ($_POST['imageUrl'] ?? '');

if (!$type || ($type === 'text' && $content === '')) {
  http_response_code(400);
  echo json_encode(['error' => 'No content']);
  exit;
}

/* ---------- 4. メッセージ保存 ---------- */
$now = microtime(true);
/* ユーザー情報をメッセージに添付 */
$messages[] = [
  'clientId'  => session_id(),
  'avatar'    => $_SESSION['rooms'][$roomId]['avatar'],
  'uname'     => $_SESSION['rooms'][$roomId]['uname'],
  'type'      => $type,
  'content'   => $content,
  'timestamp' => $now,
];

file_put_contents($logFile, json_encode($messages, JSON_UNESCAPED_UNICODE));

/* ---------- 5. 返却 ---------- */
echo json_encode(['ok' => true, 'timestamp' => $now]);
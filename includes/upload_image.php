<?php
// includes/upload_image.php

header('Content-Type: application/json');
session_start();

// 1. ルームID取得（POST優先、なければSESSION）
$roomId = $_POST['roomId'] ?? ($_SESSION['roomId'] ?? '');

if (!preg_match('/^[a-zA-Z0-9]{8,64}$/', $roomId)) {
  http_response_code(400);
  echo json_encode(['error' => 'ルームIDが正しくありません: ' . htmlspecialchars($roomId)]);
  exit;
}

// 2. 画像受け取りチェック
if (empty($_FILES['image'])) {
  http_response_code(400);
  echo json_encode(['error' => '画像が選択されていません']);
  exit;
}

$file = $_FILES['image'];
if ($file['size'] > 5 * 1024 * 1024) {
  http_response_code(413);
  echo json_encode(['error' => '5MB 以下の画像を選択してください']);
  exit;
}

// 3. MIMEタイプで画像として読み込み
switch (mime_content_type($file['tmp_name'])) {
  case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
  case 'image/png':  $src = imagecreatefrompng($file['tmp_name']);  break;
  case 'image/gif':  $src = imagecreatefromgif($file['tmp_name']);  break;
  default:
    http_response_code(415);
    echo json_encode(['error' => '対応していない画像形式です']);
    exit;
}

// 4. 保存ディレクトリ準備（/data/ROOM_ID/uploads/）
$uploadDir = __DIR__ . "/../data/{$roomId}/uploads";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// 5. ファイル名生成＆WebP変換保存（品質80）
$filename = bin2hex(random_bytes(8)) . '.webp';
$destPath = "$uploadDir/$filename";
if (!imagewebp($src, $destPath, 80)) {
  http_response_code(500);
  echo json_encode(['error' => '画像変換に失敗しました']);
  exit;
}
imagedestroy($src);

// 6. アップロード完了URL返却（クライアントで表示用）
echo json_encode(['url' => "/data/{$roomId}/uploads/{$filename}"]);
?>

<?php
// includes/get_public_status.php
header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');

$roomId = $_GET['id'] ?? '';
$dir    = __DIR__ . '/../data/' . $roomId;
if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId) || !is_dir($dir)) {
  echo '0';
  exit;
}

$file = "$dir/is_public.txt";
echo (file_exists($file) && trim(file_get_contents($file)) === '1') ? '1' : '0';
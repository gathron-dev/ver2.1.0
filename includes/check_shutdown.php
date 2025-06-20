<?php
// 強制閉鎖 (= shutdown.txt が「1」) なら 1 を返すだけ
$roomId = $_GET['roomId'] ?? '';
if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId)) { http_response_code(400); exit; }

$flag = __DIR__ . "/../data/$roomId/shutdown.txt";
echo (is_file($flag) && trim(file_get_contents($flag)) === '1') ? '1' : '0';
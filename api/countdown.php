<?php
// api/countdown.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db-access.php';
header('Content-Type: application/json; charset=utf-8');

$room = $_GET['room'] ?? '';
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS remaining FROM rooms WHERE room_id = ?');
$stmt->execute([$room]);
$remaining = $stmt->fetchColumn();
echo json_encode(['remaining' => max(0, (int)$remaining)]);
?>
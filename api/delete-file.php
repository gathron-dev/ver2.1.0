<?php
// api/delete-file.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db-access.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$room = $data['room'] ?? '';
$file = $data['file'] ?? '';
$path = __DIR__ . '/../data/' . basename($room) . '/' . basename($file);

if ($room && file_exists($path)) {
    unlink($path);
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
}
?>
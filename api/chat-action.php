<?php
// api/chat-action.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db-access.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $room = $data['room'] ?? '';
    $message = $data['message'] ?? '';
    if ($room && $message) {
        $stmt = $pdo->prepare(
            'INSERT INTO messages (room_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$room, null, $message]);
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
    exit;
}

// GET: fetch messages
$room = $_GET['room'] ?? '';
$stmt = $pdo->prepare(
    'SELECT id, user_id, content, created_at FROM messages WHERE room_id = ? ORDER BY created_at DESC LIMIT 50'
);
$stmt->execute([$room]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$messages = array_reverse($rows);

echo json_encode(['messages' => $messages]);
exit;
?>
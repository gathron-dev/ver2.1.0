<?php
// create-room.php
require_once __DIR__ . '/../db-access.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 新規ルームを SHA‑256 主キーで作成して返す
 *
 * @return string 64文字の hex
 */
function createRoom(): string {
    $pdo = getPDO();
    do {
        $random = random_bytes(16);
        $roomKey = hash('sha256', $random);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_id = ?');
        $stmt->execute([$roomKey]);
    } while ($stmt->fetchColumn() > 0);

    $stmt = $pdo->prepare(
        'INSERT INTO rooms (room_id, is_public, created_at, expires_at)
         VALUES (?, 1, NOW(), DATE_ADD(NOW(), INTERVAL 15 MINUTE))'
    );
    $stmt->execute([$roomKey]);

    return $roomKey;
}

echo json_encode(['id' => createRoom()]);
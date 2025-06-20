<?php
// JSONでPOSTされたデータを取得
$data = json_decode(file_get_contents('php://input'), true);

$roomId = $data['roomId'] ?? '';
$lang = $data['lang'] ?? '';

// roomIdバリデーション（セキュリティ対策）
if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId)) {
    http_response_code(400);
    echo 'Invalid roomId';
    exit;
}

// 言語コードバリデーション（2～8文字程度の英字とハイフン）
if (!preg_match('/^[a-zA-Z\-]{2,8}$/', $lang)) {
    http_response_code(400);
    echo 'Invalid language code';
    exit;
}

$roomDir = __DIR__ . '/../data/' . $roomId;
if (!is_dir($roomDir)) {
    http_response_code(404);
    echo 'Room directory not found';
    exit;
}

// language.txtに保存
file_put_contents($roomDir . '/language.txt', $lang);

echo 'OK';

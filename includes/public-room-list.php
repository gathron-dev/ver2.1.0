<?php
// includes/public-room-list.php
require_once __DIR__ . '/../config.php';

$dataDir = __DIR__ . '/../data';
$rooms   = [];
foreach (glob($dataDir . '/*', GLOB_ONLYDIR) as $roomDir) {
    $roomId        = basename($roomDir);
    $isPublicFile  = $roomDir . '/is_public.txt';
    if (file_exists($isPublicFile) && trim(file_get_contents($isPublicFile)) === '1') {
        $languageFile = $roomDir . '/language.txt';
        $language     = (file_exists($languageFile))
                        ? strtolower(trim(file_get_contents($languageFile)))
                        : '--';
        $rooms[] = ['id' => $roomId, 'language' => $language];
    }
}

// 文言の多言語対応キー
$titleKey      = 'public_rooms_title';
$noRoomsKey    = 'no_public_rooms';
$countRoomsKey = 'public_rooms_count_label';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(cfg('lang_code') ?: 'ja') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(cfg($titleKey) ?: 'Public Room ID List') ?></title>
    <style>
        .room-list { max-width: 250px; margin: 0 auto; }
        .room-entry { background: #e3e3e3; margin: 12px 0; padding: 7px 5px; border-radius: 18px; }
        .room-id-label { color: #888; display: block; }
        .lang-badge { display: inline-block; background: linear-gradient(to right, #6ba2f9 0%, #cc64ff 100%); color: #fff; border-radius: 7px; padding: 2px 10px; font-size: 13px; float: right; margin-right: 12px; }
        .count-badge { background: #c667fe; color: #fff; border-radius: 50%; padding: 5px; font-weight: bold; margin-left: 8px; font-size: 14px; width: 25px; height: 25px; display: inline-block; text-align: center; }
    </style>
</head>
<body>
    <div class="room-list">
        <h5 style="color:#888;display:inline-block;">
            <?= htmlspecialchars(cfg($titleKey) ?: 'Public Room ID List') ?>
            <?php if (count($rooms) > 0): ?>
                <span class="count-badge"><?= count($rooms) ?></span>
            <?php endif; ?>
        </h5>

        <?php if (count($rooms) === 0): ?>
            <div style="color:#bbb;font-size:14px;">
                <?= htmlspecialchars(cfg($noRoomsKey) ?: 'No public rooms') ?>
            </div>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <a class="room-link" href="/room.php?id=<?= urlencode($room['id']) ?>">
                    <div class="room-entry">
                        <span class="room-id-label" style="text-align:left;">
                            <span style="font-size:12px;color:#999;margin-left:12px;">
                                <?= htmlspecialchars(cfg('label_room_id') ?: 'Room ID') ?>:
                            </span>
                            <span style="font-size:18px;color:#555;">
                                <?= htmlspecialchars(shortRoomId($room['id'])) ?>
                            </span>
                            <span class="lang-badge"><?= htmlspecialchars($room['language']) ?></span>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
/**
 * 部分的に短く表示するためのユーティリティ
 */
function shortRoomId(string $roomId): string {
    return (strlen($roomId) <= 8)
        ? $roomId
        : substr($roomId, 0, 4) . '...' . substr($roomId, -4);
}
?>
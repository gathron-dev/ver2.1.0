<?php
date_default_timezone_set('Asia/Tokyo');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/hash.php';
header('Cache-Control: no-cache, no-store, must-revalidate');
$lang     = Config::detectLanguage();
$settings = Config::get();
$hash   = generateRoomHash();
$url    = 'https://' . $_SERVER['HTTP_HOST'] . '/room.php?id=' . $hash;
$roomId = $hash;
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<body style="margin:0px 10px;text-align:center;">
<?php include __DIR__ . '/includes/room-id-bar.php';?>
<?php include __DIR__ . '/includes/menu-bar.php';?>
<?php include __DIR__ . '/includes/enter-button.php';?>
<img style="padding:30px;" src="includes/qr-code.php?data=<?php echo urlencode($url); ?>" alt="QR Code">
<div id="public-room-list-container">
  <?php include __DIR__ . '/includes/public-room-list.php'; ?>
</div>
<script>
function refreshPublicRoomList() {
  fetch('includes/public-room-list.php?ts=' + Date.now(), { cache: 'no-store' })
    .then(response => response.text())
    .then(html => {
      document.getElementById('public-room-list-container').innerHTML = html;
    })
    .catch(err => console.error('公開ルーム一覧の更新に失敗しました', err));
}

// 初回実行＋10秒ごとに更新
refreshPublicRoomList();
setInterval(refreshPublicRoomList, <?php echo polling_interval('public_room_list_update', 10000); ?>);
</script>
<footer style="font-size:12px; color:#666;margin-top:30px;">
 &copy; <?= date('Y'); ?> Gathron!
</footer>
</body>
</html>
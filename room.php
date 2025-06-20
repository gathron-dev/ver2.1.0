<?php
session_start();
require_once __DIR__ . '/config.php';      // 多言語 UI
require_once __DIR__ . '/includes/hash.php';
require_once __DIR__ . '/db-access.php';   // ← $pdo と deleteOldRecords()

/* ────────── 言語・設定 ────────── */
$lang     = Config::detectLanguage();
$settings = Config::get();

/* ────────── ルーム ID ────────── */
if (isset($_GET['id']) && preg_match('/^[a-f0-9]{16,}$/i', $_GET['id'])) {
    $roomId = $_GET['id'];
} else {
    $roomId = generateRoomHash();
}

/* ────────── データディレクトリ ────────── */
$dataDir = __DIR__ . "/data/$roomId";
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

/* ────────── 開始時刻を一度だけ書き込む ────────── */
$startFile = "$dataDir/start_time.txt";
if (!file_exists($startFile)) {
    file_put_contents($startFile, time());
}

/* ═══════════ アクセスログ挿入 ═══════════ */
$ipAddress = $_SERVER['REMOTE_ADDR']      ?? 'UNKNOWN';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$time      = date('Y-m-d H:i:s');

try {
    $stmt = $pdo->prepare(
        "INSERT INTO gtr_001 (roomId, ipAddress, `time`, userAgent)
         VALUES (:roomId, :ipAddress, :time, :userAgent)"
    );
    $stmt->execute([
        ':roomId'    => $roomId,
        ':ipAddress' => $ipAddress,
        ':time'      => $time,
        ':userAgent' => $userAgent
    ]);

    // 古いログをクリーンアップ
    deleteOldRecords($pdo);
} catch (PDOException $e) {
    error_log('[アクセスログ失敗] ' . $e->getMessage());
}

/* ────────── 共有 URL ────────── */
$url = 'https://' . $_SERVER['HTTP_HOST'] . '/room.php?id=' . $roomId;
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<body style="margin:0px 10px;text-align:center;">
<div id="shake-container">
  <?php include __DIR__ . '/includes/room-id-bar.php'; ?>
  <div class="d-flex mt-3">
    <div class="me-auto p-2 align-content-end flex-wrap pt-1" style="width:200px;max-width:100%;">
      <?php include __DIR__ . '/includes/countdown.php'; ?>
    </div>
    <style>
      .attendance-1{padding:8px 10px 5px 10px;border:none;border-radius:13px;background:#c27ce7;width:37px;}
      .attendance-2{color:#fff;font-weight:600;}
      .swap-selected{outline:2px dashed #ff5722;}
      .swapped{border:2px dotted red;}
    </style>
    <div class="p-2 pe-1">
      <button id="attendance" class="attendance-1">
        <span id="attendance-count" class="attendance-2">0</span>
      </button><br>
      <div style="font-size:10px;margin-top:2px;">People</div>
    </div>
    <div class="p-2 pe-1"><?php include __DIR__ . '/includes/kickout.php'; ?></div>
    <div class="p-2 pe-1"><?php include __DIR__ . '/includes/public-toggle.php'; ?></div>
    <div class="p-2 pe-1"><?php include __DIR__ . '/includes/share-button.php'; ?></div>
  </div>
  <?php include __DIR__ . '/includes/chat-board.php'; ?>
</div>
<?php include __DIR__ . '/includes/message-input.php'; ?>
<div id="messages" style="padding-bottom:0px;"></div>

<script>
let kickoutActive = false;
let timeoutActive = false;

/* ────────── 強制退室チェック ────────── */
setInterval(() => {
  fetch(`includes/check_shutdown.php?roomId=<?php echo htmlspecialchars($roomId); ?>&t=${Date.now()}`, {cache:'no-store'})
    .then(r=>r.text())
    .then(flag=>{
      if(flag.trim()==="1"&&!kickoutActive){
        kickoutActive=true;
        displaySystemMessage("<?php echo htmlspecialchars($settings['kick_out'],ENT_QUOTES,'UTF-8');?>");
        setTimeout(()=>{
          fetch('includes/delete_room.php?roomId=<?=htmlspecialchars($roomId)?>',{method:'POST'});
          location.href='/';
        },5000);
      }
    });
}, <?php echo polling_interval('shutdown_check', 1000); ?>);

/* ────────── タイムアウト監視 ────────── */
function checkTimeout(c){
  if(c<=0&&!timeoutActive){
    timeoutActive=true;
    displaySystemMessage("<?php echo htmlspecialchars($settings['time_out'],ENT_QUOTES,'UTF-8');?>");
    setTimeout(()=>{
      const url='includes/delete_room.php?roomId=<?=htmlspecialchars($roomId)?>';
      navigator.sendBeacon?navigator.sendBeacon(url,''):fetch(url,{method:'POST'});
      location.href='/';
    },5000);
  }
}

/* システムメッセージ表示 */
function displaySystemMessage(t){
  const list=document.getElementById('messages');
  const el=document.createElement('div');
  el.classList.add('chat-bubble','other','system');
  el.innerHTML=`<strong>Automatic message</strong><br>${t.replace(/\n/g,'<br>')}<br>`;
  list.appendChild(el);
  list.scrollTop=list.scrollHeight;
}

/* 初回言語保存 */
const roomIdJS="<?=htmlspecialchars($roomId)?>";
const lang=navigator.language||navigator.userLanguage;
if(!localStorage.getItem('language_saved_'+roomIdJS)){
  fetch('/includes/save_language.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({roomId:roomIdJS,lang})
  }).then(()=>localStorage.setItem('language_saved_'+roomIdJS,'1'));
}
</script>
</body>
</html>

<?php
/*
 |---------------------------------------------------------
 | includes/mask_message.php
 |  POST: 伏せ文字イベントを1件だけ追加（実行者はセッションから取得）
 |  GET : JavaScript（script src用）
 |---------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // セッションから実行者を取得
    session_start();
    $room = $_GET['roomId'] ?? '';
    $data = json_decode(file_get_contents('php://input'), true);
    $msg  = $data['msgId']  ?? '';

    // バリデーション
    if (
      !preg_match('/^[a-f0-9]{16,}$/i', $room) ||
      !is_numeric($msg)
    ) {
        http_response_code(400);
        exit;
    }

    // 実行者名はセッションのユーザ名を使う
    $user = $_SESSION['rooms'][$room]['uname']
          ?? $_SESSION['name']
          ?? 'Anon';

    $path = __DIR__ . "/../data/$room/messages.json";
    $log  = file_exists($path)
      ? json_decode(file_get_contents($path), true)
      : [];

    // ── 伏せ文字イベントを1件だけ追加 ──
    $log[] = [
      'clientId'  => 'system',
      'type'      => 'mask',
      'target'    => $msg,
      'user'      => $user,
      'timestamp' => time(),
    ];

    file_put_contents($path, json_encode($log, JSON_UNESCAPED_UNICODE));
    echo 'ok';
    exit;
}

// ── GET: クライアント用 JS を返す ──
header('Content-Type: application/javascript; charset=utf-8');
?>
export function enableMask(roomId) {
  const box = document.getElementById('messages');
  if (!box) return;
  let lastTap = 0;

  box.addEventListener('dblclick',  fireMask);
  box.addEventListener('touchend', e => {
    if (Date.now() - lastTap < 300) fireMask(e);
    lastTap = Date.now();
  });

  function fireMask(e) {
    const b = e.target.closest('.chat-bubble');
    if (!b) return;
    // roomId はクエリパラメータで渡す
    fetch(`/includes/mask_message.php?roomId=${roomId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ msgId: b.dataset.id })
    }).catch(() => {});
  }
}
<?php
// includes/public-toggle.php
// 多言語対応：config.php の cfg() で文言を取得
require_once __DIR__ . '/../config.php';

session_start();
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
?>
<style>
.public-toggle-btn-1 {
    padding: 6px 11px 7px 11px;
    border: none;
    border-radius: 13px;
    background: #c27ce7;
    color: #fff;
}
.public-toggle-btn-2 {
    filter: invert(1);
}
@keyframes shake {
    0%   { transform: translateX(0); }
    25%  { transform: translateX( 5px); }
    50%  { transform: translateX(0); }
    75%  { transform: translateX(-5px); }
    100% { transform: translateX(0); }
}
.shake {
    animation: shake .5s;
}
</style>

<button id="public-toggle-btn" class="public-toggle-btn-1">
  <img id="public-toggle-icon" class="public-toggle-btn-2"
       src="/assets/img/lock-solid.svg" alt="" width="14">
</button>
<br>
<div id="public-status-label" style="font-size:10px;margin-top:2px;">
  <?= htmlspecialchars(cfg('status_label') ?: 'Status') ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn    = document.getElementById('public-toggle-btn');
  const icon   = document.getElementById('public-toggle-icon');
  const label  = document.getElementById('public-status-label');
  const box    = document.getElementById('shake-container') || document.body;
  const roomId = '<?= $roomId ?>';
  const csrf   = '<?= $_SESSION["csrf"] ?>';

  let isPublic = false;  // サーバーが返す真値
  let locked   = false;  // POST 実行中フラグ

  /* 画面上のアイコンとラベルを更新 */
  function draw () {
    icon.src = isPublic
      ? '/assets/img/lock-open-solid.svg'
      : '/assets/img/lock-solid.svg';
    icon.alt = isPublic
      ? <?= json_encode(cfg('action_close_room') ?: 'ルームを非公開') ?>
      : <?= json_encode(cfg('action_open_room')  ?: 'ルームを公開') ?>;

    if (label) {
      label.textContent = isPublic
        ? <?= json_encode(cfg('label_public')  ?: 'Public') ?>
        : <?= json_encode(cfg('label_private') ?: 'Private') ?>;
    }
  }

  /* 揺れ演出＋システムメッセージ */
  function shakeAndAnnounce () {
    box.classList.add('shake');
    setTimeout(() => box.classList.remove('shake'), 500);
    if (typeof displaySystemMessage === 'function') {
      displaySystemMessage(
        isPublic
          ? <?= json_encode(cfg('open_room')  ?: 'ルームを公開しました') ?>
          : <?= json_encode(cfg('close_room') ?: 'ルームを非公開にしました') ?>
      );
    }
  }

  /* サーバーから現在値を取得。失敗時は 2 秒後に再試行 */
  async function getStatus () {
    if (locked) return;
    try {
      const res = await fetch(
        `/includes/get_public_status.php?id=${roomId}&ts=${Date.now()}`,
        { cache: 'no-store' }
      );
      if (!res.ok) throw new Error(`status fetch failed: ${res.status}`);
      const now = (await res.text()).trim() === '1';
      if (now !== isPublic) {
        isPublic = now;
        draw();
        shakeAndAnnounce();
      }
    } catch (e) {
      console.warn('status retry in 2s', e);
      setTimeout(getStatus, 2000);
    }
  }

  /* ボタンクリックで公開状態をトグル */
  async function toggle () {
    if (locked) return;
    locked = true;
    btn.disabled = true;

    const desired = isPublic ? '0' : '1';
    const fd = new FormData();
    fd.append('roomId', roomId);  // toggle_public.php 側の実装に合わせて roomId
    fd.append('csrf',   csrf);
    fd.append('public', desired);

    try {
      const res  = await fetch('/includes/toggle_public.php', {
        method: 'POST',
        body:   fd,
        credentials: 'same-origin'
      });
      const text = await res.text();
      if (!res.ok) throw new Error(`${res.status}: ${text}`);
      await getStatus();
    } catch (e) {
      console.error('toggle error', e);
      alert(
        <?= json_encode(cfg('toggle_error') ?: '状態を変更できませんでした。再度お試しください。') ?>
      );
    } finally {
      locked = false;
      btn.disabled = false;
    }
  }

  /* 初期化 */
  btn.addEventListener('click', toggle);
  getStatus();
  setInterval(getStatus, <?php echo polling_interval('public_status_update', 1000); ?>); // 1 秒ごとに最新状態をポーリング
});
</script>
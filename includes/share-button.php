<?php
// includes/share-button.php
// 多言語対応用設定読み込み
require_once __DIR__ . '/../config.php';

// ────────── 共有 URL を自動取得（$shareUrl が未定義の場合） ──────────
if (!isset($shareUrl)) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $shareUrl = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// ────────── 共有テキスト（多言語対応） ──────────
// config.php に以下のキーを追加してください：
// 'share_message' => 'Gathron!チャットが共有されました😁\n会話に参加しましょう🤪',
$shareText = cfg('share_message')
    ?: "Gathron!チャットが共有されました😁\n会話に参加しましょう🤪";

// URL エンコード
$shareLink = rawurlencode($shareUrl);
$shareMsg  = rawurlencode($shareText);
?>

<style>
/* ───── 元のスタイル ───── */
#share-btn-wrap      { position: relative; display: inline-block; }
#share-toggle-btn    { padding:6px 10px; border:none; border-radius:13px; background:#c27ce7; color:#fff; cursor:pointer; }
#share-list          {
  position:absolute;
  top:45%;
  right:45px;
  display:flex;
  gap:8px;
  z-index:10;
  padding:10px 15px;
  border-radius:21px;
  box-shadow:0 0 15px #b9b9b9;
  /* ───── iOS26風 磨りガラス効果 ───── */
  background: rgba(255,255,255,0.3);
  border: 1px solid rgba(255,255,255,0.5);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
#share-list.hidden   { display:none; }
.share-item          { display:inline-flex; align-items:center; justify-content:center; font-size:25px; text-decoration:none; margin:0 10px; }
.share-toggle-btn-1  { padding:6px 10px 7px; border:none; border-radius:13px; background-image:linear-gradient(to right,#4facfe 0%,#00f2fe 100%); color:#fff; }
.share-toggle-btn-2  { filter:invert(1); }

/* ───── アニメーション ───── */
@keyframes popIn {
  0%   { transform: translate(150px, -60px) scale(0); opacity:0; }
  100%  { transform: translateY(0px) scale(1); opacity:1; }
}
@keyframes popHide {
  0%   { transform: scale(1); opacity:1; }
  100% { transform: scale(0); opacity:0; }
}

#share-list.pop-in   { animation: popIn   0.3s ease-out; }
#share-list.pop-out  { animation: popHide 0.2s ease-in forwards; }
</style>

<div id="share-btn-wrap">
  <!-- オリジナルのトグルボタン -->
  <button id="share-toggle-btn" class="share-toggle-btn-1">
    <img class="share-toggle-btn-2"
         src="/assets/img/share-nodes-solid.svg"
         width="16"
         alt="<?= htmlspecialchars(cfg('btn_share') ?: 'Share') ?>">
  </button>
  <div style="font-size:10px;margin-top:2px;">
    <?= htmlspecialchars(cfg('share_label') ?: 'Share') ?>
  </div>

  <!-- 展開アイコン -->
  <div id="share-list" class="hidden">
    <!-- X (旧 Twitter) -->
    <div>
      <a class="share-item" style="font-size:21px;margin-top:7px;color:#5e87f1;"
         href="https://twitter.com/intent/tweet?url=<?=$shareLink?>&text=<?=$shareMsg?>"
         target="_blank" rel="noopener">
        <i class="bi bi-twitter-x"></i>
      </a>
      <div style="font-size:10px;width:100%;text-align:center;margin-top:-1px;">X</div>
    </div>
    <!-- WhatsApp -->
    <div>
      <a class="share-item" style="color:#5e87f1;margin-top:2px;"
         href="https://wa.me/?text=<?=$shareMsg?>%20<?=$shareLink?>"
         target="_blank" rel="noopener">
        <i class="bi bi-whatsapp"></i>
      </a>
      <div style="font-size:10px;width:100%;text-align:center;margin-top:-2px;">WhatsApp</div>
    </div>
    <!-- LINE -->
    <div>
      <a class="share-item" style="font-size:26px;margin-top:4px;color:#5e87f1;"
         href="https://social-plugins.line.me/lineit/share?url=<?=$shareLink?>&text=<?=$shareMsg?>"
         target="_blank" rel="noopener">
        <i class="bi bi-line"></i>
      </a>
      <div style="font-size:10px;width:100%;text-align:center;margin-top:-5px;">LINE</div>
    </div>
    <!-- SMS -->
    <div>
      <a class="share-item" style="color:#5e87f1;margin-top:3px;"
         href="sms:?&body=<?=$shareMsg?>%20<?=$shareLink?>">
        <i class="bi bi-chat-dots"></i>
      </a>
      <div style="font-size:10px;width:100%;text-align:center;margin-top:-2px;">SMS</div>
    </div>
  </div>
</div>

<script>
(() => {
  const toggleBtn = document.getElementById('share-toggle-btn');
  const list      = document.getElementById('share-list');

  /* 開閉トグル */
  toggleBtn.addEventListener('click', e => {
    e.stopPropagation();
    if (list.classList.contains('hidden')) {
      list.classList.remove('hidden', 'pop-out');
      void list.offsetWidth;  // reflow for animation restart
      list.classList.add('pop-in');
    } else {
      list.classList.remove('pop-in');
      list.classList.add('pop-out');
    }
  });

  /* アニメ終了後の後処理 */
  list.addEventListener('animationend', e => {
    if (e.animationName === 'popHide') {
      list.classList.add('hidden');
      list.classList.remove('pop-out');
    } else if (e.animationName === 'popIn') {
      list.classList.remove('pop-in');
    }
  });

  /* 他クリックで閉じる */
  document.addEventListener('click', () => {
    if (!list.classList.contains('hidden')) {
      list.classList.remove('pop-in');
      list.classList.add('pop-out');
    }
  });
})();
</script>

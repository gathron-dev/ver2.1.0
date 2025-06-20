<?php
if (defined('SYSTEM_MESSAGE_MODULE_LOADED')) return;
define('SYSTEM_MESSAGE_MODULE_LOADED', true);
?>
<!-- ==== System-Message module ==== -->
<style>
.chat-bubble.system{
  background: linear-gradient(to right, #e943c5 0%, #9538f9 100%) !important;
  padding: 10px 18px 12px 18px;
  margin: 4px 10px;
  max-width: 70%;
  font-size: .8em;
  word-break: break-word;
  color: #ffffff;
  border-radius: 20px;
}
/* ── const isMask = txt.includes('伏せ文字を発動しました'); ── */
.chat-bubble.system.mask {
  background: linear-gradient(to right, #e943c5 0%, #9538f9 100%) !important;
  padding: 10px 18px 12px 18px;
  margin: 4px 10px;
  max-width: 70%;
  font-size: .8em;
  word-break: break-word;
  color: #ffffff;
  border-radius: 20px;
}
</style>
<script>
(() => {
  /* ---------- 汎用コピー ---------- */
  function copyTextSync(text,node){
    try{ if(navigator.clipboard){ navigator.clipboard.writeText(text); return true; } }catch(_){}
    try{
      const ta = document.createElement('textarea');
      ta.value = text; ta.readOnly = true;
      ta.style.position = 'fixed'; ta.style.top = '-999px';
      document.body.appendChild(ta); ta.select();
      const ok = document.execCommand('copy'); document.body.removeChild(ta);
      if(ok) return true;
    }catch(_){}
    try{
      const sel = window.getSelection(), rg = document.createRange();
      rg.selectNodeContents(node); sel.removeAllRanges(); sel.addRange(rg);
      const ok = document.execCommand('copy'); sel.removeAllRanges();
      if(ok) return true;
    }catch(_){}
    return false;
  }

  /* ---------- システムメッセージ ---------- */
  window.displaySystemMessage = txt => {
    const box = document.getElementById('messages');
    if (!box) return;

    const isMask = txt.includes('伏せ文字を発動しました');
    const row = document.createElement('div');
    row.className = 'chat-row other';
    row.innerHTML =
      `<div class="chat-bubble system${isMask ? ' mask' : ''}">
         <strong style="filter: opacity(0.6);">Automatic message</strong><br>
         ${txt.replace(/\n/g, '<br>')}
       </div>`;
    box.appendChild(row);
    box.scrollTop = box.scrollHeight;
  };

  /* ---------- コピー操作 ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    const box = document.getElementById('messages');
    if (!box) return;

    const HOLD = 500;
    let tStart = 0, tgt = null;
    let lastCopyTxt = '', lastCopyAt = 0;

    const isBubble = el => el && el.closest('.chat-bubble');

    function doCopy(node){
      const txt = node.innerText;
      const now = Date.now();
      if (txt === lastCopyTxt && now - lastCopyAt < 1000) return;

      if (copyTextSync(txt, node)){
        displaySystemMessage(`" ${txt} " をコピーしました。`);
        lastCopyTxt = txt; lastCopyAt = now;
      } else {
        alert('コピーに失敗しました');
      }
    }

    box.addEventListener('pointerdown', e => {
      const b = isBubble(e.target);
      if (!b || b.classList.contains('destroyed')) return;
      tgt = b; tStart = performance.now();
    }, { passive: true });

    box.addEventListener('pointerup', e => {
      if (!tgt) return;
      if (performance.now() - tStart >= HOLD) {
        e.preventDefault();
        doCopy(tgt);
      }
      tgt = null;
    });
    box.addEventListener('pointercancel', () => tgt = null);
    box.addEventListener('pointerleave', () => tgt = null);

    box.addEventListener('contextmenu', e => {
      const b = isBubble(e.target);
      if (!b || b.classList.contains('destroyed')) return;
      e.preventDefault();
      doCopy(b);
    }, true);
  });
})();
</script>
<!-- ==== /System-Message module ==== -->
<?php
session_start();
// 多言語対応用設定読み込み
require_once __DIR__ . '/../config.php';
// システムメッセージ表示関数
include_once __DIR__ . '/system-message.php';

/* ---------- ルームごとのセッション領域 ---------- */
$roomId = $_GET['id'] ?? ($_SESSION['roomId'] ?? '');
$_SESSION['roomId'] = $roomId;
if (!isset($_SESSION['rooms'])) {
    $_SESSION['rooms'] = [];
}

if (!isset($_SESSION['rooms'][$roomId])) {
    $idx = random_int(0, 49);
    $female = ['Emma','Olivia','Ava','Sophia','Mia','Amelia','Harper','Evelyn','Abigail','Emily','Ella','Elizabeth','Sofia','Madison','Avery','Scarlett','Victoria','Grace','Chloe','Natalie','Hana','Mio','Yui','Riko','Nao'];
    $male   = ['Liam','Noah','Oliver','Elijah','Lucas','Mason','Logan','James','Aiden','Ethan','Jacob','Jackson','Michael','Alexander','Benjamin','Daniel','Matthew','Henry','Sebastian','Haruto','Yuto','Sota','Ren','Kaito','Hinata'];
    $name   = $idx < 25 ? $female[array_rand($female)] : $male[array_rand($male)];
    $maxLen = preg_match('/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $name) ? 5 : 7;
    $name   = mb_substr($name, 0, $maxLen);

    $_SESSION['rooms'][$roomId] = [
        'avatar' => $idx,
        'uname'  => $name
    ];
}
$myAvatar = $_SESSION['rooms'][$roomId]['avatar'];
$myName   = $_SESSION['rooms'][$roomId]['uname'];
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars(cfg('lang_code') ?: 'ja') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(cfg('chat_title') ?: 'Chat Room') ?></title>
    <style>
        
        #message-input-container{
            position:fixed;
            inset-inline-start:0;
            inset-block-end:0;
            width:100%;
            display:flex;
            align-items:center;
            gap:8px;
            padding:8px env(safe-area-inset-right) calc(8px + env(safe-area-inset-bottom)) env(safe-area-inset-left);
            background:#fff;
            box-shadow:0 -1px 5px rgba(0,0,0,.1);
            z-index:1000;
        }
        #image-upload-btn{border:none;background:transparent;cursor:pointer;}
        #image-input{display:none;}
        #image-preview{display:flex;align-items:center;margin-left:0;}
        #image-preview img{max-height:60px;border-radius:4px;margin-right:8px;}
        #message-input{flex:1;padding:8px 12px;margin:0 5px 0 10px;border:1px solid #ccc;border-radius:8px;resize:none;height:auto;min-height:34px;max-height:200px;overflow-y:hidden;}
        #send-btn{border:none;background:#fff;cursor:pointer;margin-top:5px;}
        #messages{display:flex;flex-direction:column;padding-bottom:20px;overflow-y:auto;max-height:calc(100vh - 310px);}
        .chat-row{display:flex;flex-direction:column;margin-bottom:4px;}
        .avatar-wrap{display:flex;align-items:center;gap:4px;margin-bottom:-14px;position:relative;z-index:1;}
        .avatar-wrap .uname{font-size:11px;color:#444;}
        .avatar-wrap img{width:32px;height:32px;border-radius:50%;border: 5px solid #fff;}
        .chat-row.other .uname { color:#888; }
        .chat-row.other .avatar-wrap {margin-bottom:-18px;margin-left: 15px; }
        .chat-row.other .chat-bubble{background:#f0f0f0;align-self:flex-start;text-align: left;}
        .chat-row.me{align-items:flex-end;}
        .chat-row.me .uname { color:#9c27b0; }
        .chat-row.me .avatar-wrap{flex-direction:row-reverse;margin-bottom:-18px;margin-right: 15px;}
        .chat-row.me .chat-bubble{background:#e6d9ff;align-self:flex-end;text-align: left;}
        .chat-bubble{position:relative;display:inline-block;max-width:70%;padding:10px 20px;border-radius:20px;word-break:break-word;font-size:12px;line-height:1.5;-webkit-user-select:none;user-select:none;}
        .chat-bubble img{max-width:100%;border-radius:8px;display:block;}
        .chat-image {display: block;max-width: 50%;border-radius: 20px;margin: 10px;}
        .term{font-size: 10px;position: absolute;top: -17px;background: #fff;width: 97%;text-align: left;}
        body{margin-bottom:70px;}
    </style>

    <div id="messages" data-room-id="<?= htmlspecialchars($roomId, ENT_QUOTES) ?>"></div>
    <div id="message-input-container">
      <button id="image-upload-btn"><img src="/assets/img/image-solid.svg" width="20" alt="<?= htmlspecialchars(cfg('btn_image_upload') ?: 'Upload Image') ?>"></button>
      <input type="file" id="image-input" accept="image/*">
      <div id="image-preview"></div>
      <textarea id="message-input" placeholder="<?= htmlspecialchars(cfg('message_input') ?: 'メッセージを入力') ?>" style="line-height:20px;"></textarea>
      <button id="send-btn"><img src="/assets/img/paper-plane-solid.svg" width="20" alt="<?= htmlspecialchars(cfg('btn_send') ?: 'Send') ?>"></button>
      <div class="term"><?= htmlspecialchars(cfg('terms_of_service_1'), ENT_QUOTES) ?><a href="/howto.html" target="_blank"><?= htmlspecialchars(cfg('terms_of_service_2'), ENT_QUOTES) ?></a><?= htmlspecialchars(cfg('terms_of_service_3'), ENT_QUOTES) ?></div>
    </div>
    <div id="image-loading" style="display:none;position:fixed;inset:0;backdrop-filter:blur(4px);background:rgba(255,255,255,0.6);font-size:20px;align-items:center;justify-content:center;z-index:2000;">Loading...Loading...</div>

    <script type="module" src="/includes/action_command.php"></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const roomId   = '<?= $roomId ?>';
      const clientId = '<?= session_id() ?>';
      let myAvatar   = <?= $myAvatar ?>;
      let myName     = '<?= $myName ?>';
      let lastTs     = 0;
      const displayedIds = new Set();

      function linkify(t) {
        return t
          .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
          .replace(/(https?:\/\/[^\s]+)/g,'<a target="_blank" href="$1">$1</a>')
          .replace(/\n/g,'<br>');
      }

      function addRow(msg, isMe) {
        if (displayedIds.has(msg.timestamp)) return;
        displayedIds.add(msg.timestamp);

        const container = document.getElementById('messages');
        const row       = document.createElement('div');
        row.className   = 'chat-row ' + (isMe ? 'me' : 'other');

        if (msg.type === 'image') {
          row.innerHTML =
            `<div class="avatar-wrap"${isMe?' style="flex-direction:row-reverse"':''}><img src="/assets/avatars/${msg.avatar}.png"><span class="uname">${msg.uname}</span></div>` +
            `<img src="${msg.content}" class="chat-image" style="max-width:50%;height:auto;">`;
        } else {
          row.innerHTML =
            `<div class="avatar-wrap"${isMe?' style="flex-direction:row-reverse"':''}><img src="/assets/avatars/${msg.avatar}.png"><span class="uname">${msg.uname}</span></div>` +
            `<div class="chat-bubble" data-id="${msg.timestamp}" data-original="${msg.content}">${linkify(msg.content)}</div>`;
        }

        container.appendChild(row);
        container.scrollTop = container.scrollHeight;
      }

      async function sendText(text) {
        const fd  = new FormData();
        fd.append('text', text);
        const res = await fetch(`includes/append_message.php?roomId=${roomId}`, { method: 'POST', body: fd });
        const o   = await res.json();
        if (o.ok) {
          addRow({
            timestamp: o.timestamp,
            clientId:  clientId,
            avatar:    myAvatar,
            uname:     myName,
            type:      'text',
            content:   text
          }, true);
          lastTs = o.timestamp;
        }
      }

      const imageInput = document.getElementById('image-input');
      const preview    = document.getElementById('image-preview');
      document.getElementById('image-upload-btn').addEventListener('click', () => imageInput.click());
      imageInput.addEventListener('change', () => {
        preview.innerHTML = '';
        const file = imageInput.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
          alert(<?= json_encode(cfg('image_size_limit_message') ?: '5MB 以下の画像を選択してください') ?>);
          imageInput.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = e => {
          const img = document.createElement('img');
          img.src = e.target.result;
          preview.appendChild(img);
        };
        reader.readAsDataURL(file);
      });

      const ta  = document.getElementById('message-input');
      const btn = document.getElementById('send-btn');
      const loader = document.getElementById('image-loading');
      const defH = '33px';
      ta.style.height = defH;

        // ▼ Shift+Enter で送信機能を追加
  ta.addEventListener('keydown', e => {
    if (e.key === 'Enter' && e.shiftKey) {
      e.preventDefault();   // 改行を抑制
        handleSend();         // 送信ボタンを実行
      }
    });
    // ▲ ここまで
      let isSending = false;
      async function handleSend() {
        if (isSending) return;
        const text = ta.value.trim();
        const hasImage = !!imageInput.files[0];
        if (!hasImage && !text) return;

        isSending = true;
        try {
          if (hasImage) {
            if (loader) loader.style.display = 'flex';
            const fdImg  = new FormData();
            fdImg.append('image', imageInput.files[0]);
            const resImg = await fetch('includes/upload_image.php', { method:'POST', body:fdImg });
            const dataImg= await resImg.json();
            if (dataImg.url) {
              const fd2  = new FormData();
              fd2.append('imageUrl', dataImg.url);
              const res2 = await fetch(`includes/append_message.php?roomId=${roomId}`, { method:'POST', body:fd2 });
              const o2   = await res2.json();
              if (o2.ok) {
                addRow({
                  timestamp: o2.timestamp,
                  clientId:  clientId,
                  avatar:    myAvatar,
                  uname:     myName,
                  type:      'image',
                  content:   dataImg.url
                }, true);
                lastTs = o2.timestamp;
              }
            } else {
              alert(dataImg.error || <?= json_encode(cfg('image_upload_failed') ?: '画像アップロードに失敗しました') ?>);
            }
            imageInput.value = '';
            preview.innerHTML = '';
            if (loader) loader.style.display = 'none';
          }

          if (text) {
            await sendText(text);
            ta.value = '';
            ta.style.height = defH;
          }
        } finally {
          isSending = false;
        }
      }

      btn.addEventListener('click', handleSend);
      btn.addEventListener('touchstart', handleSend);

      const box = document.getElementById('messages');
      const basePad = parseInt(getComputedStyle(box).paddingBottom) || 0;
      let lastTap = 0;
      function fireMask(e) {
        const b = e.target.closest('.chat-bubble');
        if (!b) return;
        fetch(`/includes/mask_message.php?roomId=${roomId}`, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ msgId: b.dataset.id })
        }).catch(() => {});
      }
      box.addEventListener('dblclick', fireMask);
      box.addEventListener('touchend', e => { if (Date.now() - lastTap < 300) fireMask(e); lastTap = Date.now(); });

      (async () => {
        const res  = await fetch(`includes/fetch_messages.php?roomId=${roomId}&since=0`, { cache:'no-store' });
        const list = await res.json();
        if (list.length) {
          list.sort((a,b) => a.timestamp - b.timestamp);
          for (const msg of list) {
            if (msg.clientId !== 'system' && msg.type !== 'mask') {
              addRow(msg, msg.clientId === clientId);
            }
            lastTs = msg.timestamp;
          }
        }
      })();

      async function poll() {
        const res  = await fetch(`includes/fetch_messages.php?roomId=${roomId}&since=${lastTs}`, { cache:'no-store' });
        const msgs = await res.json();
        let maskHandled = false;
        for (const msg of msgs) {
          if (msg.type === 'mask' && !maskHandled) {
            const b = document.querySelector(`.chat-bubble[data-id="${msg.target}"]`);
            if (b) {
              const chars = Array.from(b.dataset.original || b.textContent);
              let idx = 0;
              const iv = setInterval(() => {
                b.textContent = '?'.repeat(idx+1);
                if (++idx >= chars.length) clearInterval(iv);
              }, 50);
            }
            const maskTpl = <?= json_encode(cfg('mask_activated') ?: '{user} が伏せ文字を発動しました。') ?>;
            displaySystemMessage(maskTpl.replace('{user}', msg.user));
            maskHandled = true;
          } else if (msg.clientId !== 'system') {
            addRow(msg, msg.clientId === clientId);
          }
          lastTs = Math.max(lastTs, msg.timestamp);
        }
      }
      poll();
      setInterval(poll, <?php echo polling_interval('message_poll', 1000); ?>);

      async function updateAttendance() {
        try {
          const res = await fetch(`/includes/attendance.php?roomId=${roomId}`, { cache:'no-store' });
          const j   = await res.json();
          document.getElementById('attendance-count').textContent = j.count;
        } catch (e) {
          console.error(e);
        }
      }
      updateAttendance();
      setInterval(updateAttendance, <?php echo polling_interval('attendance_update', 5000); ?>);

      // モバイルキーボード対応
      const container = document.getElementById('message-input-container');
      if (ta && container) {
        const isIOS  = /iP(hone|od|ad)/.test(navigator.userAgent);
        const isEdge = /EdgA?\//.test(navigator.userAgent) && /Android/.test(navigator.userAgent);
        const adjust = () => {
          let offset = 0;
          const vv = window.visualViewport;
          if (vv) {
            if (isEdge) {
              offset = vv.offsetTop;
            } else {
              offset = Math.max(0, window.innerHeight - (vv.height + vv.offsetTop));
            }
          }
          if (isIOS) {
            container.style.bottom = offset ? `${offset}px` : '0';
          } else {
            container.style.transform = offset ? `translateY(-${offset}px)` : '';
          }
          box.style.paddingBottom = `${basePad + container.offsetHeight + offset}px`;
          box.scrollTop = box.scrollHeight;
        };
        ta.addEventListener('focus', () => {
          setTimeout(adjust, 50);
          window.addEventListener('resize', adjust);
          if (window.visualViewport) window.visualViewport.addEventListener('resize', adjust);
        });
        ta.addEventListener('blur', () => {
          window.removeEventListener('resize', adjust);
          if (window.visualViewport) window.visualViewport.removeEventListener('resize', adjust);
          if (isIOS) {
            container.style.bottom = '0';
          } else {
            container.style.transform = '';
          }
          box.style.paddingBottom = `${basePad + container.offsetHeight}px`;
        });
        // 初期パディング
        box.style.paddingBottom = `${basePad + container.offsetHeight}px`;
      }
    });
    </script>

    <script type="module" src="/includes/mask_message.php"></script>
    <script type="module">
      import { enableMask } from '/includes/mask_message.php';
      enableMask('<?= $roomId ?>');
    </script>

</body>
</html>
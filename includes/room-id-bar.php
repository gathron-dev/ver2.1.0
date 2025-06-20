<?php
$displayId = htmlspecialchars($roomId ?? '', ENT_QUOTES, 'UTF-8');
?>
<style>
body {
  font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
}
.custom-btn5 {
  border: none;
  background: transparent;
  color: #fff;
  font-size: 22px;
}
.room-id-bar {
  display: flex;
  align-items: center;
  padding: 2px;
  margin: 20px 0 0 0;
  border: 1px solid #ccc;
  border-radius: 15px;
  font-size: .9rem;
  color: #777;
  background: #eee;
  width: 100%;
}
.room-logo {
  padding-left: 6px;
  height: 20px;
  margin-right: 5px;
}
.room-text {
  padding: 0 5px 0 2px;
  display: inline-block;
  max-width: 550px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  padding-top: 3px;
}
.copy-icon {
  filter: contrast(0.3);
  padding-bottom: 2px;
  height: 20px;
  margin-right: 10px;
}
.qr-icon {
  filter: contrast(0.3);
  padding-bottom: 2px;
  height: 20px;
  margin-right: 8px;
  margin-left: 8px;
}
.navbar-custom {
  background: #fff !important;
}
.navbar-brand {
  margin-bottom: -5px;
}
.room-id {
  position: absolute;
  left: 45px;
  top: 14px;
  font-size: 8px;
  color: #fff;
  background-color: #777;
  border-radius: 15px;
  padding: 1px 9px;
  z-index: 1;
  letter-spacing: 0.5px;
  font-weight: 400;
}
.navbar {
  --bs-navbar-padding-x: none !important;
}

/* モーダル背景 */
#gathron-qr-modal-bg {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100vw;
  height: 100vh;
  /* frosted-glass overlay */
  background: rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  /* for fade in/out */
  opacity: 0;
  transition: opacity 200ms ease;
  justify-content: center;
  align-items: center;
  pointer-events: none;
}
#gathron-qr-modal-bg.show {
  opacity: 1;
  pointer-events: auto;
}

/* モーダルコンテンツ */
#gathron-qr-modal-content {
  background: #ffffffab;
  padding: 30px 20px;
  border-radius: 20px;
  box-shadow: 0 2px 18px #8888;
  text-align: center;
  min-width: 250px;
  min-height: 260px;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  /* pop in/out */
  transform: scale(0.8);
  opacity: 0;
  transition: transform 200ms ease, opacity 200ms ease;
}
#gathron-qr-modal-content.show {
  transform: scale(1);
  opacity: 1;
}

#gathron-qr-close-btn {
  border: none;
  background: transparent;
  color: #555;
  font-size: 30px;
  position: absolute;
  right: 16px;
  top: 8px;
  cursor: pointer;
  z-index: 1;
}
#gathron-qrcode {
  margin: 0 auto 14px auto;
}
</style>

<div class="room-id">Room ID</div>
<nav class="navbar navbar-expand navbar-dark navbar-custom" aria-label="Second navbar example">
  <div class="container-fluid p-0">
    <div class="room-id-bar">
      <img src="/assets/img/gathron_mark.svg" alt="gathron_logo" class="room-logo">
      <span class="room-text"><?php echo $displayId; ?></span>
      <!-- QRコードボタン（コピーの左）-->
      <button type="button" class="custom-btn5" onclick="gathronShowQrModal()">
        <img src="/assets/img/qrcode-solid.svg" alt="qr" class="qr-icon">
      </button>
      <button type="button" class="custom-btn5" onclick="gathronCopyToClipboard()">
        <img src="/assets/img/copy-solid.svg" alt="copy" class="copy-icon">
      </button>
    </div>
  </div>
</nav>

<!-- QRコードモーダル -->
<div id="gathron-qr-modal-bg">
  <div id="gathron-qr-modal-content">
    <button id="gathron-qr-close-btn" onclick="gathronCloseQrModal()">&times;</button>
    <div style="margin-bottom:12px;">ルーム招待QRコード</div>
    <div id="gathron-qrcode"></div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  // プレフィックス付きで衝突を防止
  const gathronMsgSuccess = '<?php echo cfg("copy_success"); ?>';
  const gathronMsgError   = '<?php echo cfg("copy_error"); ?>';
  const gathronRoomId  = document.querySelector('.room-text').textContent.trim();
  const gathronRoomUrl = `${window.location.protocol}//${window.location.host}/room.php?id=${gathronRoomId}`;

  function gathronCopyToClipboard() {
    navigator.clipboard.writeText(gathronRoomUrl)
      .then(() => {
        alert(gathronMsgSuccess);
      })
      .catch(() => {
        alert(gathronMsgError);
      });
  }

  // QR表示 with frosted-glass and pop animation
  function gathronShowQrModal() {
    if (!window.gathronQrGenerated) {
      const qrDiv = document.getElementById('gathron-qrcode');
      qrDiv.innerHTML = '';
      new QRCode(qrDiv, {
        text: gathronRoomUrl,
        width: 180,
        height: 180,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.M
      });
      window.gathronQrGenerated = true;
    }
    const bg = document.getElementById('gathron-qr-modal-bg');
    const content = document.getElementById('gathron-qr-modal-content');
    bg.style.display = 'flex';
    // trigger transition
    requestAnimationFrame(() => {
      bg.classList.add('show');
      content.classList.add('show');
    });
  }

  function gathronCloseQrModal() {
    const bg = document.getElementById('gathron-qr-modal-bg');
    const content = document.getElementById('gathron-qr-modal-content');
    bg.classList.remove('show');
    content.classList.remove('show');
    // after fade-out
    setTimeout(() => {
      bg.style.display = 'none';
    }, 200);
  }
</script>

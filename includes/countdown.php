<?php
// includes/countdown.php
?>
<style>
.timer{  padding: 10px 20px 5px 20px; border-radius: 25px; color: #fff; background-image: linear-gradient(to right, #79e0e2 0%, #c47be7 100%);}
</style>
<div id="countdown-timer" class="timer" style="font-size:2.2rem;">
  <div style="font-size:14px;margin-bottom: -60px;position: static;letter-spacing: 0.5px;text-align: center;">Countdown</div><br><span id="time-display">--:--</span>
</div>

<script>
let timeoutHandled = false;

// タイマー更新
function updateTimer() {
  fetch('includes/get_countdown.php?id=<?php echo $roomId; ?>', { cache: 'no-store' })
    .then(res => res.json())
    .then(data => {
      const t = data.remaining;
      const m = Math.floor(t / 60);
      const s = t % 60;
      document.getElementById('time-display').textContent = `${m}:${s.toString().padStart(2,'0')}`;

      if (t <= 0 && !timeoutHandled) {
        timeoutHandled = true;
        // システムメッセージ
        if (typeof displaySystemMessage === 'function') {
          displaySystemMessage('<?php echo htmlspecialchars($settings['time_out'], ENT_QUOTES, 'UTF-8'); ?>');
        } else {
          alert('<?php echo htmlspecialchars($settings['time_out'], ENT_QUOTES, 'UTF-8'); ?>');
        }
        // 5秒後リダイレクト
        setTimeout(() => { window.location.href = 'index.php'; }, 5000);
      }
    });
}

// 初回実行＋1秒ごとに同期
updateTimer();
setInterval(updateTimer, <?php echo polling_interval('countdown_update', 1000); ?>);
</script>
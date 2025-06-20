<?php
require_once __DIR__ . '/../config.php';
$lang     = Config::detectLanguage();
$settings = Config::get();
?>

<style>
a {text-decoration: none;}
.custom-button-1 {border-radius: 20px;border: none;color: #ffffff;background:linear-gradient(to right, #79e0e2 0%, #c47be7 100%);width: 250px;font-size: 20px;text-decoration: none;padding: 15px 0px;}
.custom-button-2 {border: none;background-color: transparent;color: #fff;font-size: 22px;}
.custom-button-3 {margin-top: 5rem !important;margin-bottom:5px;}
</style>

<div class="custom-button-3"><?php echo htmlspecialchars($settings['hero_text'], ENT_QUOTES, 'UTF-8'); ?></div>
<div class="d-flex justify-content-center align-items-center">
<a id="enter-room-btn" href="<?php echo $url; ?>">
<div class="custom-button-1"><?php echo htmlspecialchars($settings['hero_button'], ENT_QUOTES, 'UTF-8'); ?></div>
</a>
</div>

<script>
document.getElementById('enter-room-btn').addEventListener('click', () => {
  // 1) まず全画面化
  if (document.documentElement.requestFullscreen) {
    document.documentElement.requestFullscreen();
  }
  // 2) その後、実際の入室処理など
  //    window.location.href = 'room.php?id=<?php echo $roomId; ?>';
});
</script>
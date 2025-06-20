<?php
// input-field.php
$room = htmlspecialchars($_GET['room'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div id="input-field">
  <input type="text" id="message-input" style="line-height:19px!important;" placeholder="<?php echo Config::__('Type your message', $lang); ?>">
  <button id="send-btn"><?php echo Config::__('Send', $lang); ?></button>
</div>
<script>
document.getElementById('send-btn').addEventListener('click', async () => {
  const input = document.getElementById('message-input');
  const msg = input.value.trim();
  if (!msg) return;
  await fetch('api/chat-action.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({room: '<?php echo $room; ?>', message: msg})
  });
  input.value = '';
});
</script>
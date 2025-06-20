<?php
// includes/kickout.php
?>
<style>
.kickout-button-1 {padding: 6px 10px 7px 10px; border: none; border-radius: 13px; background: #c27ce7; color: #fff;}
.kickout-button-2 {filter: invert(1);}
</style>
<button id="kickout-button" class="kickout-button-1">
    <img class="kickout-button-2" src="/../assets/img/poo-solid.svg" width="16px">
</button>
<br><div style="font-size:10px;margin-top:2px;">Nuke</div>
<script>
document.getElementById('kickout-button').addEventListener('click', () => {
  // shutdown_room.php にキックアウト通知
  fetch(`includes/shutdown_room.php?roomId=<?php echo htmlspecialchars($roomId); ?>`, { cache: 'no-store' });
});
</script>
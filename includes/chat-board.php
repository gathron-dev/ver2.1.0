<?php
// chat-board.php: メッセージリスト取得・描画コンテナ
$room = htmlspecialchars($_GET['room'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<style>
#messages { max-width: 600px; margin: auto; }
.chat-bubble { max-width: 75%; padding: 10px 15px; margin: 10px; border-radius: 20px; word-break: break-word; line-height: 1.5; position: relative; }
.chat-bubble.me { background-color: #e6d9ff; color: #333; margin-left: auto; text-align: left; }
.chat-bubble.other { background-color: #f0f0f0; color: #333; margin-right: auto; text-align: left; }

</style>

<div id="messages">
  <!-- 自分のメッセージ -->
  <div class="chat-bubble me">
    Hello, it's me!
  </div>

  <!-- 他人のメッセージ -->
  <div class="chat-bubble other">
    Hey there!
  </div>
</div>
<?php
$lang = $lang ?? Config::detectLanguage();
?>

<style>
.container-fluid {padding: 0px;}
.menu-bar {padding-top:10px;}    
</style>

<nav class="navbar navbar-expand navbar-dark menu-bar" aria-label="Second navbar example">
  <div class="container-fluid">
    <a class="navbar-brand" href="https://gathron.com"><img src="/assets/img/gathron_logo.svg" width="120px" alt="gathron_logo"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample02" aria-controls="navbarsExample02" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navbarsExample02">
      <ul class="navbar-nav me-auto"></ul>
      <a type="button" onclick="window.open('https://line.me/R/msg/text/?<?php echo urlencode($url); ?>', '_blank')"><img src="/assets/img/line-brands.svg" height="25px" alt="copy" style="filter: contrast(0.3); padding-left:0px;"></a>
      <a type="button" onclick="window.open('https://twitter.com/intent/tweet?url=<?php echo urlencode($url); ?>&text=グループチャットを共有しましたっ😁%0A「ギャザロン！」でみんな待ってるよっ🤪', '_blank')"><img src="/assets/img/square-x-twitter-brands.svg" height="25px" alt="copy" style="filter: contrast(0.3); padding-left:15px;"></a>
      <a type="button" onclick="window.open('sms:?body=<?php echo urlencode($url); ?>')"><img src="/assets/img/comment-sms-solid.svg" height="25px" alt="copy" style="filter: contrast(0.3); padding-left:15px;"></a>
      <span style="padding: 0 5px 0 13px;">|</span>
      <a href="terms.html"><img src="/assets/img/file-lines-regular.svg" height="23px" alt="info" style="filter: contrast(0.3); padding-left:10px;"></a>
      <a href="howto.html"><img src="/assets/img/circle-info-solid.svg" height="25px" alt="info" style="filter: contrast(0.3); padding-left:15px;"></a>
    </div>
  </div>
</nav>
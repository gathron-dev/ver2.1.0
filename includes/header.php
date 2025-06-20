<?php
require_once __DIR__ . '/../config.php';
$lang     = Config::detectLanguage();
$settings = Config::get();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:site"        content="<?php echo htmlspecialchars($settings['twitter_site'], ENT_QUOTES); ?>">
  <meta name="twitter:title"       content="<?php echo htmlspecialchars($settings['twitter_title'], ENT_QUOTES); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($settings['twitter_description'], ENT_QUOTES); ?>">
  <meta name="twitter:image"       content="<?php echo htmlspecialchars($settings['twitter_image'], ENT_QUOTES); ?>">
  <meta property="og:title"        content="<?php echo htmlspecialchars($settings['og_title'], ENT_QUOTES); ?>">
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://gathron.com" />
  <meta property="og:image"        content="<?php echo htmlspecialchars($settings['og_image'], ENT_QUOTES); ?>">
  <meta property="og:site_name"    content="<?php echo htmlspecialchars($settings['og_site_name'], ENT_QUOTES); ?>">
  <meta property="og:description"  content="<?php echo htmlspecialchars($settings['og_description'], ENT_QUOTES); ?>">
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="mobile-web-app-capable" content="yes" />
  <title><?php echo htmlspecialchars($settings['title'], ENT_QUOTES); ?></title>
  <link rel=”icon” href=“https://gathron.com/gathron_mark.ico”>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4278182802506241"
     crossorigin="anonymous"></script>
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-KXVCPJ3LE0"></script>
  <script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-KXVCPJ3LE0');
  </script>
  <style>
  .chat-bubble { position: relative; }
  .missile { position: absolute; width: 12px; height: 12px; background: red; border-radius: 50%; z-index: 1000; transition: transform 0.7s ease-out; }
  .explosion { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, orange 0%, transparent 70%); opacity: 0; animation: blast 0.5s forwards; }
  @keyframes blast { 0% { opacity: 1; transform: scale(0.5); } 100% { opacity: 0; transform: scale(2); } }
  .chat-bubble.destroyed { background: #111 !important; color: transparent !important; pointer-events: none; }
  .chat-bubble.destroyed.other::before { border-right-color: #111 !important; }
  .chat-bubble.destroyed.me::after    { border-left-color:  #111 !important; }
  .chat-row { display:flex; flex-direction:column; align-items:flex-start; margin-bottom:6px; } 
.chat-row.me   { align-items:flex-end; } 
.avatar-wrap { display:flex; align-items:center; gap:4px; margin-bottom:-8px; position:relative; z-index:1; } 
.avatar-wrap img { width:32px; height:32px; border-radius:50%; } 
.avatar-wrap .uname { font-size:11px; color:#444; } 
.chat-bubble { position:relative; max-width:70%; padding:10px 15px; border-radius:20px; word-break:break-word; } 
.chat-row.me   .chat-bubble { align-self:flex-end; background:#e6d9ff; } 
.chat-row.other .chat-bubble { align-self:flex-start; background:#f0f0f0; } 
  </style>
</head>
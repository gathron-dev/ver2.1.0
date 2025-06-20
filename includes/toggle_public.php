<?php
// includes/toggle_public.php
session_start();
header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');

/* ───────────────────────────────────────────────────────────────
   1.  リクエスト検証  (POST + CSRF)
   ───────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);      // Method Not Allowed
  exit;
}
if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
  http_response_code(403);      // Forbidden
  exit;
}

/* ───────────────────────────────────────────────────────────────
   2.  パラメータ検証
   ───────────────────────────────────────────────────────────── */
$roomId  = $_POST['roomId']  ?? '';
$desired = $_POST['public'] ?? '';      // '1' 公開 / '0' 非公開

if (!preg_match('/^[a-f0-9]{16,}$/i', $roomId) || !in_array($desired, ['0', '1'], true)) {
  http_response_code(400);      // Bad Request
  echo '0';
  exit;
}

$dir = __DIR__ . '/../data/' . $roomId;
if (!is_dir($dir)) { mkdir($dir, 0755, true); }

/* ───────────────────────────────────────────────────────────────
   3.  SQLite で状態を原子的に更新
   ───────────────────────────────────────────────────────────── */
$dbFile = $dir . '/state.db';
$pdo    = new PDO('sqlite:' . $dbFile, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
          ]);
$pdo->exec('PRAGMA journal_mode = WAL;');   // 書込ロックを短縮
$pdo->exec('PRAGMA busy_timeout = 2000;');  // 2 秒までは待機

/* 1 行だけのテーブルを用意（無ければ作成） */
$pdo->exec('CREATE TABLE IF NOT EXISTS room_state (
              id INTEGER PRIMARY KEY CHECK (id = 1),
              is_public INTEGER
            );');
$pdo->exec('INSERT OR IGNORE INTO room_state (id, is_public) VALUES (1, 0);');

/* 望ましい値を書き込む（トランザクションで排他制御） */
$pdo->beginTransaction();
$stm = $pdo->prepare('UPDATE room_state SET is_public = :p WHERE id = 1;');
$stm->execute([':p' => $desired]);
$pdo->commit();

/* ───────────────────────────────────────────────────────────────
   4.  後方互換: is_public.txt も同じ値で保存
   ───────────────────────────────────────────────────────────── */
file_put_contents($dir . '/is_public.txt', $desired . PHP_EOL, LOCK_EX);

/* ───────────────────────────────────────────────────────────────
   5.  クライアントへ応答
   ───────────────────────────────────────────────────────────── */
echo $desired;   // '1' = 公開, '0' = 非公開
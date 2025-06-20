<?php
// db_connect.php

// 接続情報
$host = 'mysql86.onamae.ne.jp';
$dbname = '3zo9z_gtr_db_001';
$user = '3zo9z_63n64878';
$password = 'Onksk25aki15om#';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ✅ 追加：91日以上前のレコードを削除する関数
function deleteOldRecords(PDO $pdo) {
    try {
        $sql = "DELETE FROM gtr_001 WHERE time < NOW() - INTERVAL 91 DAY";
        $count = $pdo->exec($sql);
//         echo "[DB削除] 91日以上前のレコードを {$count} 件削除しました。\n";
    } catch (PDOException $e) {
//         echo "[DB削除エラー] " . $e->getMessage() . "\n";
    }
}
?>
<?php
// ---------------------- ファイルツリー管理・Web IDE 完全版 -----------------------
$rolesFile   = __DIR__ . '/roles.json';
$historyDir  = __DIR__ . '/history';
if (!file_exists($rolesFile)) file_put_contents($rolesFile, '{}');
if (!is_dir($historyDir)) mkdir($historyDir, 0777, true);
$rolesData = json_decode(file_get_contents($rolesFile), true) ?: [];
date_default_timezone_set('Asia/Tokyo');

// --- Utility functions ---
function getFileMtime($path) {
    $full = __DIR__ . '/' . $path;
    if (!file_exists($full)) return 0;
    return filemtime($full);
}
function formatMtime($time) {
    return date('m.d H:i:s', $time);
}
function getDirTree($dir, $base = '', $level = 0) {
    $result = [];
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if ($file === 'blog') continue; // history は表示する
        $fullPath = $dir . '/' . $file;
        $relPath = ltrim($base . '/' . $file, '/');
        $mtime = getFileMtime($relPath);
        if (is_dir($fullPath)) {
            $children = getDirTree($fullPath, $relPath, $level + 1);
            $result[] = [
                'type'     => 'dir',
                'name'     => $file,
                'path'     => $relPath,
                'level'    => $level,
                'mtime'    => $mtime,
                'children' => $children,
            ];
        } else {
            $result[] = [
                'type'  => 'file',
                'name'  => $file,
                'path'  => $relPath,
                'level' => $level,
                'mtime' => $mtime,
            ];
        }
    }
    
    // ---- apply custom manual order (.order.json) ----
    $orderFile = $dir . '/.order.json';
    $customOrder = [];
    if (file_exists($orderFile)) {
        $json = json_decode(file_get_contents($orderFile), true);
        if (is_array($json)) $customOrder = $json;
    }
    if ($customOrder) {
        $ordered = [];
        foreach ($customOrder as $oname) {
            foreach ($result as $k => $node) {
                if ($node['name'] === $oname) {
                    $ordered[] = $node;
                    unset($result[$k]);
                    break;
                }
            }
        }
        // append the rest (new files/folders)
        $result = array_merge($ordered, array_values($result));
    }
return $result;
}
function flattenTree($tree, &$out = [], $parentId = null) {
    foreach ($tree as $node) {
        $id = md5($node['path'] . '-' . $node['level']);
        $out[] = [
            'id'       => $id,
            'parentId' => $parentId,
            'type'     => $node['type'],
            'name'     => $node['name'],
            'path'     => $node['path'],
            'level'    => $node['level'],
            'mtime'    => $node['mtime'],
            'children' => $node['children'] ?? [],
        ];
        if ($node['type'] === 'dir' && !empty($node['children'])) {
            flattenTree($node['children'], $out, $id);
        }
    }
    return $out;
}
function getDefaultRole($path) {
    $roles = [
        "index.php"                => "新規ルームIDを発行し、ユーザーを各ルームに案内するトップページ。",
        "room.php"                 => "チャット・カウントダウン・画像送信等を担当するメインルーム画面。",
        "get_countdown.php"        => "現在ルームの残り時間（カウントダウン）を返すAPI。残秒数を応答。",
        "includes/delete_room.php" => "指定ルームのディレクトリと内部ファイルをすべて削除する管理用スクリプト。",
        "includes/kickout.php"     => "強制退室の管理API・UIボタン提供。対象ユーザーを退出させる。",
        "data"                     => "ルームごとの状態（ユーザー、チャット履歴、カウントダウン）などのJSONを保存。",
        "assets"                   => "アプリ内で使う画像・アイコン・静的ファイル。",
        "roles.json"               => "各ファイルの役割説明をJSONで保存。説明編集の保存先。",
        "history"                  => "ファイル編集履歴（最大5世代）を保存するディレクトリ。",
    ];
    foreach ($roles as $key => $desc) {
        if (stripos($path, $key) !== false) return $desc;
    }
    return is_dir(__DIR__ . '/' . $path)
        ? "サブディレクトリ。機能ごとのファイルを格納。"
        : "PHPまたは静的ファイル。詳細は内容を確認。";
}
function getParamEditor($path) {
    if (preg_match('/get_countdown\.php$/', $path)) {
        $code = @file_get_contents(__DIR__ . '/' . $path);
        if (preg_match('/(\d+)\s*\*\s*60/', $code, $m)) {
            $val = intval($m[1]);
            return "<form method='POST' style='margin:0;display:inline;' action='?r=" . time() . "'>"
                . "<input type='hidden' name='target' value='$path'>"
                . "<input type='number' name='countdown' value='$val' min='1' max='120' style='width:50px'>"
                . "<button type='submit'>変更</button>"
                . "</form>";
        }
    }
    if (preg_match('/polling_config\.php$/', $path)) {
        $cfg = include __DIR__ . '/' . $path;
        $inputs = '';
        foreach ($cfg as $k => $v) {
            $kEsc = htmlspecialchars($k, ENT_QUOTES);
            $inputs .= "<label style='color:#fff;margin-right:4px;'>$kEsc:";
            $inputs .= " <input type='number' name='$kEsc' value='" . intval($v) . "' style='width:60px'></label> ";
        }
        return "<form method='POST' style='margin:0;display:inline;' action='?r=" . time() . "'>"
            . "<input type='hidden' name='target' value='$path'>"
            . "$inputs"
            . "<button type='submit'>変更</button>"
            . "</form>";
    }
    return '';
}
function zipFolder($dir, $zip, $base='') {
    $files = scandir($dir);
    $entries = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'blog') continue;
        $entries[] = $file;
    }
    if ($base !== '') $zip->addEmptyDir($base); // keep directory even if empty
    foreach ($entries as $file) {
        $full = $dir . '/' . $file;
        $rel  = $base ? $base . '/' . $file : $file;
        if (is_dir($full)) {
            zipFolder($full, $zip, $rel);
        } else {
            $zip->addFile($full, $rel);
        }
    }
}
function getHistoryTimes($file) {
    global $historyDir;
    $ts = [];
    for ($i=0; $i<5; $i++) {
        $hfile = $historyDir . '/' . str_replace(['/', '\\'], '_', $file) . ".$i.bak";
        if (file_exists($hfile)) {
            $ts[$i] = filemtime($hfile);
        }
    }
    return $ts;
}

// --- FS操作API/履歴API/ダウンロードAPI ---
if (isset($_POST['fs_api'])) {
    $base   = __DIR__;
    $target = $_POST['target']  ?? '';
    $name   = $_POST['name']    ?? '';
    // 新規ファイル/ディレクトリ
    if ($_POST['fs_api'] === 'create') {
        if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $name)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Invalid filename']));
        }
        $parentDir = dirname($base . '/' . $target);
        if (is_dir($base . '/' . $target)) {
            $parentDir = $base . '/' . $target;
        }
        $newPath = $parentDir . '/' . $name;
        if (file_exists($newPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Already exists']));
        }
        if (substr($name, -1) === '/') {
            mkdir($newPath, 0777, true);
        } else {
            file_put_contents($newPath, '');
        }
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }
    // 削除
    if ($_POST['fs_api'] === 'delete') {
        $delPath = $base . '/' . $target;
        if (strpos(realpath($delPath), $base) !== 0 || !file_exists($delPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'not found']));
        }
        if (is_dir($delPath)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($delPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) rmdir($file->getRealPath());
                else unlink($file->getRealPath());
            }
            rmdir($delPath);
        } else {
            unlink($delPath);
        }
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }
    // まとめて削除
    if ($_POST['fs_api'] === 'delete_multi') {
        $items = json_decode($_POST['items'] ?? '[]', true);
        $errs  = 0;
        foreach ($items as $t) {
            $delPath = $base . '/' . $t;
            if (strpos(realpath($delPath), $base) !== 0 || !file_exists($delPath)) {
                $errs++;
                continue;
            }
            if (is_dir($delPath)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($delPath, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    if ($file->isDir()) rmdir($file->getRealPath());
                    else unlink($file->getRealPath());
                }
                rmdir($delPath);
            } else {
                unlink($delPath);
            }
        }
        clearstatcache();
        exit(json_encode(['status' => 'ok', 'errors' => $errs]));
    }
    // リネーム
    if ($_POST['fs_api'] === 'rename') {
        $oldPath = $base . '/' . $target;
        $newName = $_POST['newName'] ?? '';
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $newName)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Invalid name']));
        }
        $newPath = dirname($oldPath) . '/' . $newName;
        if (!file_exists($oldPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Not found']));
        }
        if (file_exists($newPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Already exists']));
        }
        rename($oldPath, $newPath);
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }
    // アップロード
    if ($_POST['fs_api'] === 'upload') {
        $destDir = $_POST['target'] ?? '';
        if ($destDir !== '' && !preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $destDir)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Invalid path']));
        }
        $absDest = $base . '/' . ($destDir === '' ? '.' : $destDir);
        if (!is_dir($absDest)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Destination not dir']));
        }
        if (!isset($_FILES['file'])) {
            exit(json_encode(['status' => 'ng', 'msg' => 'No file']));
        }
        $name = basename($_FILES['file']['name']);
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $name)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Invalid filename']));
        }
        $targetPath = $absDest . '/' . $name;
        if (file_exists($targetPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Already exists']));
        }
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Move failed']));
        }
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }
    // 移動
    if ($_POST['fs_api'] === 'move') {
        $src  = $_POST['source'] ?? '';
        $dest = $_POST['dest']   ?? '';
        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $src) || !preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $dest)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Invalid path']));
        }
        $absSrc  = realpath($base . '/' . $src);
        $absDest = realpath($base . '/' . $dest);
        if ($absSrc === false || $absDest === false || strpos($absSrc, $base) !== 0 || strpos($absDest, $base) !== 0) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Out of base']));
        }
        if (!is_dir($absDest)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Destination not dir']));
        }
        // Prevent moving dir into itself
        if (is_dir($absSrc) && strpos($absDest, $absSrc) === 0) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Cannot move dir into itself']));
        }
        $name = basename($absSrc);
        $newPath = $absDest . '/' . $name;
        if (file_exists($newPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Already exists']));
        }
        if (!rename($absSrc, $newPath)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Move failed']));
        }
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }

    
    // 並び順保存
    if ($_POST['fs_api'] === 'save_order') {
        $dirTarget = $_POST['target'] ?? '';
        $orderArr  = json_decode($_POST['order'] ?? '[]', true);
        if (!is_array($orderArr)) $orderArr = [];
        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]*$/', $dirTarget)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Invalid path']));
        }
        $absDir = realpath($base . '/' . ($dirTarget === '' ? '.' : $dirTarget));
        if ($absDir === false || strpos($absDir, $base) !== 0 || !is_dir($absDir)) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Invalid dir']));
        }
        $orderFile = $absDir . '/.order.json';
        if (file_put_contents($orderFile, json_encode($orderArr, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) === false) {
            exit(json_encode(['status' => 'ng', 'msg' => 'Save failed']));
        }
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }
// 履歴取得
    if ($_POST['fs_api'] === 'history') {
        $file = $_POST['file'] ?? '';
        $hist = [];
        for ($i = 0; $i < 5; $i++) {
            $hfile = $historyDir . '/' . str_replace(['/', '\\'], '_', $file) . ".$i.bak";
            if (file_exists($hfile)) {
                $hist[] = [
                    'index'   => $i,
                    'content' => file_get_contents($hfile),
                    'mtime'   => filemtime($hfile)
                ];
            }
        }
        exit(json_encode(['status'=>'ok','history'=>$hist]));
    }
    exit(json_encode(['status' => 'ng']));
}

// --- その他API（パラメータ/role保存/ファイル編集/履歴保存API/ダウンロードAPI） ---
if (isset($_GET['api'])) {
    $base = __DIR__;
    if ($_GET['api'] === 'load') {
        $file = $_POST['file'] ?? '';
        $full = realpath($base . '/' . $file);
        if ($full === false) {
            $full = $base . '/' . ltrim($file, '/');
        }
        if (strpos($full, $base) !== 0 || !is_file($full)) {
            exit(json_encode(['status' => 'ng']));
        }
        $code = file_get_contents($full);
        exit(json_encode(['status' => 'ok', 'code' => $code]));
    }
    if ($_GET['api'] === 'save') {
        $file = $_POST['file'] ?? '';
        $code = $_POST['code'] ?? '';
        $full = realpath($base . '/' . $file);
        if ($full === false) {
            $full = $base . '/' . ltrim($file, '/');
        }
        if (strpos($full, $base) !== 0 || !is_file($full)) {
            exit(json_encode(['status' => 'ng']));
        }
        // バックアップ
        $hfile = $historyDir . '/' . str_replace(['/', '\\'], '_', $file);
        for ($i = 4; $i >= 0; $i--) {
            $src = $hfile . ".$i.bak";
            if ($i == 4 && file_exists($src)) unlink($src);
            if ($i > 0 && file_exists($hfile . "." . ($i-1) . ".bak")) {
                rename($hfile . "." . ($i-1) . ".bak", $src);
            }
        }
        if (file_exists($full)) {
            file_put_contents($hfile . ".0.bak", file_get_contents($full));
        }
        file_put_contents($full, $code);
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }
    if ($_GET['api'] === 'role_save') {
        $rolesJson = $_POST['roles'] ?? '{}';
        file_put_contents($rolesFile, $rolesJson);
        clearstatcache();
        exit(json_encode(['status' => 'ok']));
    }
    exit;
}

// -- ダウンロード処理（ファイル/フォルダ/全体バックアップ） --
if (isset($_GET['download'])) {
    $f = $_GET['download'];
    $full = realpath(__DIR__ . '/' . $f);
    if ($full === false || strpos($full, __DIR__) !== 0 || !is_file($full)) die('Not found');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($f).'"');
    readfile($full);
    exit;
}
if (isset($_GET['download_dir'])) {
    $dir = $_GET['download_dir'];
    $zipname = date('Y.m.d_H.i.s') . '_' . preg_replace('/[^a-zA-Z0-9_]/','_',basename($dir)).'.zip';
    $full = realpath(__DIR__ . '/' . $dir);
    if ($full === false || strpos($full, __DIR__) !== 0 || !is_dir($full)) die('Not found');
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    $zip->open($tmp, ZipArchive::CREATE|ZipArchive::OVERWRITE);
    zipFolder($full, $zip, basename($dir));
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipname.'"');
    readfile($tmp); unlink($tmp);
    exit;
}
if (isset($_GET['backup_all'])) {
    $zipname = date('Y.m.d_H.i.s')."_gathron.zip";
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    $zip->open($tmp, ZipArchive::CREATE|ZipArchive::OVERWRITE);
    zipFolder(__DIR__, $zip, "");
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipname.'"');
    readfile($tmp); unlink($tmp);
    exit;
}

// -- パラメータ編集 --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target']) && !isset($_POST['fs_api'])) {
    $path = $_POST['target'];
    if (preg_match('/get_countdown\.php$/', $path) && isset($_POST['countdown'])) {
        $code = file_get_contents(__DIR__ . '/' . $path);
        $new = preg_replace('/(\d+)\s*\*\s*60/', intval($_POST['countdown']) . '*60', $code);
        file_put_contents(__DIR__ . '/' . $path, $new);
        clearstatcache();
        echo "<script>location.href='?r=" . time() . "';</script>";
        exit;
    }
    if (preg_match('/polling_config\.php$/', $path)) {
        $cfg = include __DIR__ . '/' . $path;
        foreach ($cfg as $k => $v) {
            if (isset($_POST[$k]) && is_numeric($_POST[$k])) {
                $cfg[$k] = intval($_POST[$k]);
            }
        }
        $php = "<?php\n";
        $php .= "// polling_config.php\n";
        $php .= "// Centralized polling interval configuration (milliseconds)\n";
        $php .= "return [\n";
        foreach ($cfg as $k => $v) {
            $php .= "    '" . $k . "' => " . $v . ",\n";
        }
        $php .= "];\n";
        file_put_contents(__DIR__ . '/' . $path, $php);
        clearstatcache();
        echo "<script>location.href='?r=" . time() . "';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Gathron Web IDE</title>
<link rel="stylesheet" data-name="vs/editor/editor.main"
      href="https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/editor/editor.main.min.css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body, .table-container, .tree-table th, .tree-table td,
.role-edit-input, .modal-content, #font-size-label, #font-size-input {
  font-size:12px!important; font-weight:400!important; background:#222;
}
.table-container { width:98vw; margin:2vw auto; overflow-x:auto; }
.tree-table { border-collapse:collapse; width:100%; min-width:900px; background:#292929; }
.tree-table th, .tree-table td { border:1px solid #444; padding:7px 8px; text-align:left; }
.tree-table th { background:#333; color:#fff; position:relative; user-select:none; }
.tree-table td { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#999; width: 50%!important;}
.tree-filecell { max-width:540px; min-width:40px; }
.dir, .file { font-weight:400; cursor:pointer; color:#777; }
.file-new { color:#98c379!important; }
.mtime { margin-left: 10px; font-size:10px; width:66px; text-align:right; display:inline-block; margin-right:10px; color:#777; }
.mtime-new { color:#98c379!important; }
.btn-fs-wrap { float:right; display:inline-flex; gap:7px; align-items:center; }
.btn-fs { font-size:11px; border-radius:7px; border:none; background:#483a55; color:#b3b3b3; padding:3px 12px; cursor:pointer; transition:filter .14s, background .14s; }
.btn-fs:hover { filter: brightness(1.3); background:#60476c; color:#fff; }
.btn-fs-rename { background:#2c4465; }
.btn-fs-rename:hover { background:#3c5980; color:#fff; }
.btn-fs-delete { background:#732020; }
.btn-fs-delete:hover { background:#a63232; color:#fff; }
.btn-fs-checkbox { width:18px; height:18px; margin-left:4px; accent-color:#e54b4b; }
#multi-del-btn { background:#e54b4b; color:#fff; border:none; border-radius:12px; padding:9px 36px; font-size:15px; font-weight:600; margin:0 0 18px; cursor:pointer; }
#multi-del-btn:disabled { opacity:0.5; cursor:not-allowed; }
#backup-btn { float:right; margin-left:5px; background:#483a55; color:#fff; border:none; border-radius:12px; padding: 8px 12px 5px 12px; margin-right: 10px; font-size:20px; font-weight:600; cursor:pointer; transition:background .13s; }
#backup-btn:hover { background:#60476c; }
#upload-root-btn { float:right; margin-left:22px; background:#483a55; color:#fff; border:none; border-radius:12px; padding: 8px 12px 5px 12px; margin-right: 10px; font-size:20px; font-weight:600; cursor:pointer; transition:background .13s; }
#upload-root-btn:hover { background:#60476c; }
#fs-modal { position:fixed; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.65); z-index:2000; display:none; align-items:center; justify-content:center; }
#fs-modal-inner { background:#1e2228; border-radius:18px; box-shadow:0 0 22px #000b; padding:40px 44px; color:#fff; text-align:center; font-size:17px; min-width:420px;}
.fs-modal-btn { font-size:14px; border-radius:9px; border:none; margin:0 9px 0 0; background:#3f6375; color:#fff; padding:6px 28px; cursor:pointer; margin-top:10px;}
.fs-modal-btn:hover { background:#40bc6b; color:#fff; }
#fs-create-name, #fs-rename-name { width:400px; font-size:15px; padding:7px 8px; margin-top:12px; margin-bottom:8px; border-radius:9px; border:1px solid #2a3555; background:#222; color:#fff; }
#modal-save-info {color:#27b852; font-size:18px; margin:0; text-align:center; position:absolute; left:0; right:0; top:45%; z-index:10; font-weight:bold; background:rgba(34,50,34,0.87); padding: 30px 0;}
.modal-bg { position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.68); display:none; z-index:3000; align-items:center; justify-content:center; }
.modal-content { position:relative; width:90vw; max-width:1300px; min-width:360px; background:#1e2228; border-radius:16px; box-shadow:0 0 42px #000b; display:flex; flex-direction:column; height:80vh; font-size:12px!important; font-weight:400!important; }
.modal-title { color:#fff; background:#25292e; padding:14px 26px; font-size:18px!important; border-top-left-radius:16px; border-top-right-radius:16px; display:flex; align-items:center; }
.modal-close { position:absolute; top:15px; right:30px; color:#fff; cursor:pointer; font-size:15px; z-index:20; padding: 1px 5px; border-radius: 20px; border: 2px solid #fff; background: #000; }
#modal-editor-area { flex:1; width:100%; }
#decision-btn { background:#8f42a9; color:#fff; border:none; border-radius:12px; padding:12px 42px; font-size:18px; cursor:pointer; box-shadow:0 2px 12px #222b; position:absolute; right:38px; bottom:19px; }
#decision-btn:hover { background:#c17dd9; }
#editor-history { position:absolute; left:16px; top:13px; z-index:15; display:flex; gap:7px; }
#editor-history button {background:#353; color:#fff; font-size:12px; border-radius:10px; padding:5px 13px; border:none; cursor:pointer; opacity:0.88;}
#editor-history button.active { background:#2de37a; color:#233;}
#editor-history .hist-time { color:#aaa; font-size:11px; margin-left:4px; }
#editor-history button.latest { background:#333; color:#fff; border:2px solid #27b852; font-weight:700;}
#editor-history button.latest.active { background:#27b852; color:#fff;}
.marked { background:#3c3728!important; border: 2px solid #73772f !important;}
#reload-btn {margin-left:18px; background:#483a55; color:#fff; border:none; border-radius:12px; padding:9px 36px; font-size:15px; font-weight:600; cursor:pointer; transition:background .13s;}
#reload-btn:hover {background:#60476c;}
span.ellipsis {
    width: 30%;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-flex;
}
span.file {
    margin-left: 12px!important;
}
input.role-edit-input {
    background-color: #666 !important;
    color: #fff;
    width: 450px !important;
    padding: 5px !important;
}
td.tree-filecell {
    width: 10% !important;
    font-size: 14px !important;
}
/* 差分ハイライト */
.diff-add   { background: #1f3531 !important; color: #66e47c; }
.diff-del   { background: #3c222b !important; color: #ff636b; text-decoration:line-through; }
.diff-mod   { background: #333f21 !important; color: #ffe97e; }
.context-menu {
    position:absolute; z-index:9999; background:#222; border:1px solid #444; box-shadow:0 0 10px #0007;
    padding:7px 0; border-radius:7px; min-width:140px;
}
.context-menu-item {
    padding:7px 23px; color:#fff; font-size:13px; cursor:pointer;
}
.context-menu-item:hover { background:#444; color:#0fd; }
span.dir-toggle {
    font-size: 8px;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/loader.min.js"></script>
<script>
// ----------------------------- JS部（エディタ管理、履歴、DL, モーダル, 右クリック）-------------------------
let openDirs = [], markedRows = {}, rolesData = {};
let editor = null, currentFile='', currentFileName='', isHistoryMode=false, curHistIndex=-1, histCache=[], histTimes=[];
let monacoLoaded=false, diffDecorations=[];
function saveOpenDirs() { localStorage.setItem('gathron_open_dirs', JSON.stringify(openDirs)); }
function loadOpenDirs() {
    try { openDirs = JSON.parse(localStorage.getItem('gathron_open_dirs')) || []; }
    catch(e){ openDirs = []; }
}
function saveMarkedRows() { localStorage.setItem('gathron_marked', JSON.stringify(markedRows)); }
function loadMarkedRows() {
    try { markedRows = JSON.parse(localStorage.getItem('gathron_marked')) || {}; }
    catch(e){ markedRows = {}; }
}
function multiDelete() {
    let items = Array.from(document.querySelectorAll('.del-multi-cb:checked')).map(cb=>cb.value);
    if (!items.length) return;
    if (!confirm(items.length + ' 個をまとめて削除しますか？')) return;
    fetch('', {
        method: 'POST',
        cache: 'no-store',
        body: new URLSearchParams({ fs_api: 'delete_multi', items: JSON.stringify(items) })
    })
    .then(r=>r.json()).then(res=>{
        if (res.status==='ok') reloadFileTree();
        else alert('削除に失敗しました');
    });
}
function reloadFileTree() { location.href='?r='+Date.now(); }
function toggleDir(id, path) {
    let idx = openDirs.indexOf(path);
    if (idx===-1) openDirs.push(path);
    else openDirs.splice(idx,1);
    saveOpenDirs();
    renderTreeRows();
}
function renderTreeRows() {
    document.querySelectorAll('.tree-table tr').forEach(r=>{
        let type = r.classList.contains('dir') ? 'dir' : 'file';
        let path = r.dataset.path;
        let pp   = r.dataset.parentPath;
        if (type==='dir') {
            let open = openDirs.includes(path);
            r.style.display = (!pp || openDirs.includes(pp)) ? '' : 'none';
            r.classList.toggle('dir-open', open);
            let togg = document.getElementById('toggle_'+r.dataset.id);
            if (togg) togg.textContent = open ? '▼' : '▶';
        } else {
            r.style.display = (!pp || openDirs.includes(pp)) ? '' : 'none';
        }
        if (markedRows[path]) r.querySelector('.tree-filecell').classList.add('marked');
        else r.querySelector('.tree-filecell').classList.remove('marked');
    });
}
window.addEventListener('DOMContentLoaded',()=>{
    loadRolesData(renderTreeRows);
});
function loadRolesData(cb) {
    fetch('roles.json?r=' + Date.now(), {cache:'no-store'})
      .then(r => r.json())
      .then(json => {
          rolesData = json || {};
          if (cb) cb();
          document.querySelectorAll('.tree-rolecell').forEach(td=>{
            let path = td.parentNode.dataset.path;
            td.textContent = rolesData[path]||'';
            if (!td.textContent) td.textContent = td.getAttribute('data-default');
          });
      });
}
function roleEditOpen(path, td) {
    let cur = rolesData[path] || td.textContent || '';
    let inp = document.createElement('input');
    inp.type = 'text'; inp.value = cur; inp.className='role-edit-input';
    let sv = document.createElement('button'); sv.textContent='Save'; sv.className='role-edit-save';
    let cn = document.createElement('button'); cn.textContent='Cancel'; cn.className='role-edit-cancel';
    let wrap = document.createElement('div'); wrap.className='role-edit-area';
    wrap.append(inp, sv, cn);
    td.innerHTML=''; td.appendChild(wrap);
    inp.focus(); inp.select();
    sv.onclick = ()=>{
      rolesData[path] = inp.value.trim();
      td.textContent = rolesData[path]||'';
      fetch('?api=role_save',{method:'POST',cache:'no-store',body:new URLSearchParams({roles:JSON.stringify(rolesData)})})
      .then(()=>{loadRolesData();});
    };
    cn.onclick = ()=> td.textContent = cur;
}
// --- Create/Rename/Deleteモーダル
let fsModalAction='', fsModalTarget='', fsModalTr=null, fsModalType='', fsModalParentPath='';
function showFsModal(act, target, tr, type, parentPath) {
    fsModalAction=act; fsModalTarget=target; fsModalTr=tr; fsModalType=type; fsModalParentPath=parentPath;
    let html='';
    if(act==='create'){
        html='<div>新規ファイルまたはディレクトリ名を入力<br>'+
        '<input type="text" id="fs-create-name" placeholder="filename.php"><br>'+
        '<div style="font-size:12px;color:#aaa;">作成パス: <b>'+(type==='dir'?target:parentPath||'/')+'</b></div>'+
        '<div style="margin-top:20px;">'+
        '<button class="fs-modal-btn" onclick="doFsCreate()">Create</button> '+
        '<button class="fs-modal-btn" onclick="closeFsModal()">Cancel</button></div>';
    }else if(act==='rename'){
        html='<div>新しいファイル・ディレクトリ名を入力<br>'+
        '<input type="text" id="fs-rename-name" value="'+fsModalTarget.split("/").pop()+'" placeholder="newname.php"><br>'+
        '<div style="font-size:12px;color:#aaa;">変更前: <b>'+fsModalTarget+'</b></div>'+
        '<div style="margin-top:20px;">'+
        '<button class="fs-modal-btn" onclick="doFsRename()">Rename</button> '+
        '<button class="fs-modal-btn" onclick="closeFsModal()">Cancel</button></div>';
    }else if(act==='delete'){
        html='<div>削除しますか？<br><b>'+fsModalTarget+'</b></div>'+
        '<div style="margin-top:20px;"><button class="fs-modal-btn" onclick="doFsDelete()">Delete</button> <button class="fs-modal-btn" onclick="closeFsModal()">Cancel</button></div>';
    }
    document.getElementById('fs-modal-inner').innerHTML=html;
    document.getElementById('fs-modal').style.display='flex';
    setTimeout(()=>{let inp=document.getElementById('fs-create-name')||document.getElementById('fs-rename-name');if(inp)inp.focus();},200);
}
function closeFsModal() { document.getElementById('fs-modal').style.display='none'; }
function doFsCreate() {
    let name = document.getElementById('fs-create-name').value.trim();
    if(!name){alert('名前を入力');return;}
    fetch('',{
        method:'POST',cache:'no-store',
        body:new URLSearchParams({fs_api:'create',target:fsModalTarget,name})
    }).then(r=>r.json()).then(res=>{
        if(res.status==='ok'){location.href='?r='+Date.now();}
        else alert('作成失敗: '+(res.msg||''));
    });
}
function doFsRename() {
    let newName = document.getElementById('fs-rename-name').value.trim();
    if(!newName){alert('名前を入力');return;}
    fetch('',{
        method:'POST',cache:'no-store',
        body:new URLSearchParams({fs_api:'rename',target:fsModalTarget,newName})
    }).then(r=>r.json()).then(res=>{
        if(res.status==='ok'){location.href='?r='+Date.now();}
        else alert('リネーム失敗: '+(res.msg||''));
    });
}
function doFsDelete() {
    fetch('',{
        method:'POST',cache:'no-store',
        body:new URLSearchParams({fs_api:'delete',target:fsModalTarget})
    }).then(r=>r.json()).then(res=>{
        if(res.status==='ok'){location.href='?r='+Date.now();}
        else alert('削除失敗: '+(res.msg||''));
    });
}

// --- エディタ＋履歴 ---
// 差分可視化用: 行単位diff
function getLineDiffs(oldText, newText) {
    let o = oldText.split('\n'), n = newText.split('\n');
    let diffs = [], l=Math.max(o.length, n.length);
    for(let i=0;i<l;i++) {
        if(typeof o[i]==='undefined') diffs.push({type:'add', text:n[i]});
        else if(typeof n[i]==='undefined') diffs.push({type:'del', text:o[i]});
        else if(o[i]!==n[i]) diffs.push({type:'mod', old:o[i], text:n[i]});
    }
    return diffs;
}
function renderHistoryBtns() {
    let wrap = document.getElementById('editor-history');
    if (!wrap) return;
    wrap.innerHTML = '';
    // Latest
    let latestBtn = document.createElement('button');
    latestBtn.textContent = 'Latest';
    latestBtn.className = 'latest'+((curHistIndex===-1)?' active':'');
    latestBtn.onclick = ()=>{ if(curHistIndex===-1) return; showEditorAndHistory(currentFile, currentFileName); };
    wrap.appendChild(latestBtn);

    for (let i=0; i<5; i++) {
        let btn = document.createElement('button');
        btn.textContent = `H${i+1}`;
        if(histTimes[i]) {
            let t = new Date(histTimes[i]*1000);
            let dstr = (t.getMonth()+1)+'.'+t.getDate()+' '+t.getHours().toString().padStart(2,'0')+':'+t.getMinutes().toString().padStart(2,'0');
            let span = document.createElement('span');
            span.className='hist-time';
            span.textContent = ' ('+dstr+')';
            btn.appendChild(span);
        }
        btn.className += (i === curHistIndex) ? ' active' : '';
        btn.onclick = ()=>{ if (isHistoryMode && curHistIndex===i) return; loadHistoryVer(i); };
        btn.disabled = !histCache[i];
        wrap.appendChild(btn);
    }
}
function openEditorModal(path,name) {
    document.getElementById('modal-bg').style.display='flex';
    document.getElementById('modal-title-filename').textContent=name;
    document.getElementById('modal-editor-area').innerHTML='<div style="color:#fff;padding:60px;text-align:center;">Loading...</div>';
    fetch('',{
        method:'POST',cache:'no-store',
        body:new URLSearchParams({fs_api:'history',file:path})
    })
    .then(r=>r.json()).then(res=>{
        histCache=[null,null,null,null,null];
        histTimes=[null,null,null,null,null];
        if(res.status==='ok') {
            res.history.forEach((h,i)=>{histCache[h.index]=h.content; histTimes[h.index]=h.mtime; });
        }
        curHistIndex=-1; isHistoryMode=false;
        showEditorAndHistory(path,name);
    });
}
function showEditorAndHistory(path,name) {
    fetch('?api=load',{method:'POST',cache:'no-store',body:new URLSearchParams({file:path})})
    .then(r=>r.json()).then(res=>{
        if(res.status!=='ok') {
            document.getElementById('modal-editor-area').textContent='読み込みエラー';
            return;
        }
        loadMonaco(()=>{
            document.getElementById('modal-editor-area').innerHTML='<div id="editor-history" style="left:10px;top:-52px;position:absolute;"></div><div id="modal-editor-monaco" style="height:94%;"></div>';
            if(editor) editor.dispose();
            editor = monaco.editor.create(document.getElementById('modal-editor-monaco'), {
                value: res.code,
                language: getLangByFile(path),
                theme: 'vs-dark',
                automaticLayout: true,
                fontSize: 14,
                fontWeight: '400',
                minimap: { enabled: false }
            });
            currentFile = path; currentFileName=name;
            isHistoryMode = false; curHistIndex=-1;
            renderHistoryBtns();
            // 最新時は差分消去
            if(diffDecorations.length) {
                diffDecorations = editor.deltaDecorations(diffDecorations,[]);
            }
        });
    });
}
function loadHistoryVer(idx) {
    if (!histCache[idx]) return;
    isHistoryMode=true; curHistIndex=idx;
    editor.setValue(histCache[idx]);
    renderHistoryBtns();
    // 差分表示
    fetch('?api=load',{method:'POST',cache:'no-store',body:new URLSearchParams({file:currentFile})})
    .then(r=>r.json()).then(res=>{
        let diffs = getLineDiffs(histCache[idx], res.code);
        let decorations = [];
        let lines = editor.getModel().getLinesContent();
        for(let i=0,l=lines.length;i<l;i++){
            if(i<diffs.length){
                if(diffs[i].type==='add') decorations.push({range:new monaco.Range(i+1,1,i+1,1), options:{isWholeLine:true, className:'diff-add'}});
                else if(diffs[i].type==='mod') decorations.push({range:new monaco.Range(i+1,1,i+1,1), options:{isWholeLine:true, className:'diff-mod'}});
            }
        }
        diffDecorations = editor.deltaDecorations(diffDecorations, decorations);
    });
}
function closeEditorModal() {
    document.getElementById('modal-bg').style.display='none';
    if(editor) editor.dispose();
    editor = null; currentFile=''; currentFileName='';
    isHistoryMode = false; curHistIndex=-1;
}
function saveFileByDecision() {
    if(!editor||!currentFile) return;
    let code = editor.getValue();
    fetch('?api=save',{method:'POST',cache:'no-store',body:new URLSearchParams({file:currentFile,code})})
    .then(r=>r.json()).then(res=>{
        let info = document.getElementById('modal-save-info');
        if(res.status==='ok') {
            if (!info) {
                info = document.createElement('div');
                info.id = 'modal-save-info';
                info.textContent = '保存しました。';
                document.querySelector('.modal-content').appendChild(info);
            } else {
                info.textContent = '保存しました。';
            }
            info.style.display = 'block';
            setTimeout(()=>{info.style.display='none';}, 3000);
            openEditorModal(currentFile, currentFileName);
        } else {
            if (!info) {
                info = document.createElement('div');
                info.id = 'modal-save-info';
                info.textContent = '保存失敗';
                document.querySelector('.modal-content').appendChild(info);
            } else {
                info.textContent = '保存失敗';
            }
            info.style.display = 'block';
            setTimeout(()=>{info.style.display='none';}, 3000);
        }
    });
}
function loadMonaco(cb) {
    if(monacoLoaded) return cb();
    require.config({ paths:{ vs:'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs' }});
    window.MonacoEnvironment = {
        getWorkerUrl: ()=>'data:text/javascript;charset=utf-8,'+
            encodeURIComponent('self.MonacoEnvironment={baseUrl:"https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/"};importScripts("https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/base/worker/workerMain.min.js");')
    };
    require(['vs/editor/editor.main'],()=>{
        monacoLoaded=true;
        monaco.editor.setTheme('vs-dark');
        cb();
    });
}
function getLangByFile(f) {
    if(f.endsWith('.php')) return 'php';
    if(f.endsWith('.js'))  return 'javascript';
    if(f.endsWith('.json'))return 'json';
    if(f.endsWith('.css')) return 'css';
    if(f.endsWith('.html'))return 'html';
    return 'plaintext';
}
window.addEventListener('DOMContentLoaded',()=>{
    loadOpenDirs();
    loadMarkedRows();
    renderTreeRows();
    document.getElementById('reload-btn').onclick = ()=>reloadFileTree();
    document.querySelectorAll('.tree-filecell').forEach(cell=>{
        cell.ondblclick = ()=>{
            let tr  = cell.parentNode;
            let pth = tr.dataset.path;
            if(cell.classList.toggle('marked')) markedRows[pth]=1;
            else delete markedRows[pth];
            saveMarkedRows();
        };
        // 右クリックDLメニュー
        cell.oncontextmenu = function(e){
            e.preventDefault();
            closeContextMenu();
            let path = this.parentNode.dataset.path, type=this.parentNode.classList.contains('dir')?'dir':'file';
            let menu = document.createElement('div');
            menu.className='context-menu';
            const enc = encodeURIComponent(path);
            menu.innerHTML = (type==='file'
                ? '<div class="context-menu-item" onclick="downloadFile(\''+enc+'\')">Download File</div>'
                : '<div class="context-menu-item" onclick="downloadDir(\''+enc+'\')">Download Folder (zip)</div>'
            );
            menu.style.left = e.pageX+'px';
            menu.style.top = e.pageY+'px';
            menu.id = 'rightclick-menu';
            document.body.appendChild(menu);
            window.addEventListener('click', closeContextMenu, {once:true});
        };
    });
    document.querySelectorAll('.del-multi-cb').forEach(cb=>{
        cb.onchange = ()=> document.getElementById('multi-del-btn').disabled = !document.querySelector('.del-multi-cb:checked');
    });
    document.getElementById('backup-btn').onclick = ()=>window.location.href='?backup_all=1';
});
function downloadFile(encPath){ window.location.href='?download='+encPath; closeContextMenu(); }
function downloadDir(encPath){ window.location.href='?download_dir='+encPath; closeContextMenu(); }
function closeContextMenu(){
    let m=document.getElementById('rightclick-menu'); if(m) m.remove();
}
</script>
</head>
<body>
<div class="table-container">
<div style="margin-bottom:20px;">
  <img style="top: 10px; position: relative; margin-right: 10px;" src="/assets/img/gathron_logo.svg" width="120px" alt="gathron_logo">
  <span style="margin-bottom:20px;color:#fff;font-weight:normal;font-size:24px;">Gathron Web IDE</span>
</div>
  <button id="multi-del-btn" onclick="multiDelete()" disabled>Delete</button>
  <button id="reload-btn">Reload</button>
  <button id="backup-btn"><i class="bi bi-cloud-download"></i></button>
  <button id="upload-root-btn"><i class="bi bi-cloud-upload"></i></button>
  <label style="color:#fff;margin-left:10px;">Sort:</label>
  <select id="sort-select">
    <option value="">--</option>
    <option value="mtime">更新時間順</option>
    <option value="type">種類順</option>
    <option value="name">名前順</option>
  </select>

  <table class="tree-table">
    <thead>
      <tr>
        <th class="tree-filecell">File Tree</th>
        <th>Description</th>
        <th>Parameters</th>
      </tr>
    </thead>
    <tbody>
<?php
$tree     = getDirTree(__DIR__);
$flatTree = flattenTree($tree);
$now      = time();
foreach ($flatTree as $node) {
    $id         = htmlspecialchars($node['id'], ENT_QUOTES);
    $parent     = $node['parentId'] ? htmlspecialchars($node['parentId'], ENT_QUOTES) : '';
    $type       = $node['type'];
    $name       = htmlspecialchars($node['name'], ENT_QUOTES);
    $path       = htmlspecialchars($node['path'], ENT_QUOTES);
    $pathJs     = htmlspecialchars(json_encode($node['path']), ENT_QUOTES);
    $nameJs     = htmlspecialchars(json_encode($node['name']), ENT_QUOTES);
    $level      = $node['level'];
    $mtime      = $node['mtime'];
    $mtimeStr   = $mtime ? formatMtime($mtime) : '';
    $isNew      = ($mtime && ($now - $mtime) < 86400);
    $nameClass  = $isNew ? 'file-new' : '';
    $mtimeClass = $isNew ? 'mtime mtime-new' : 'mtime';
    $parentPath = dirname($node['path']);
    if ($parentPath === '.') $parentPath = '';
    $parentPathEsc = htmlspecialchars($parentPath, ENT_QUOTES);
    $parentPathJs  = htmlspecialchars(json_encode($parentPath), ENT_QUOTES);
    echo "<tr class='{$type}' data-id='{$id}' data-path='{$path}' data-parent-path='{$parentPathEsc}' data-parent='{$parent}' data-name='{$name}' data-mtime='{$mtime}'>";
    echo "<td class='tree-filecell' style='padding-left:" . (22*$level) . "px;'>";
    echo "<span class='{$mtimeClass}'>{$mtimeStr}</span>";
    if ($type === 'dir') {
        echo "<span class='dir {$nameClass}' onclick=\"toggleDir('{$id}',{$pathJs});event.stopPropagation();\">";
        echo "<span class='dir-toggle' id='toggle_{$id}' style='margin-right:4px;cursor:pointer;'>▶</span>";
        echo "📁 <span class='ellipsis' title='{$path}'>{$name}</span></span>";
    } else {
        echo "<span class='file {$nameClass}' onclick=\"openEditorModal({$pathJs},{$nameJs});event.stopPropagation();\">";
        echo "📄 <span class='ellipsis' title='{$path}'>{$name}</span></span>";
    }
    echo "<span class='btn-fs-wrap'>";
    echo "<button class='btn-fs' onclick=\"showFsModal('create',{$pathJs},null,'{$type}',{$parentPathJs});event.stopPropagation();\">New</button>";
    if ($name !== '.' && $name !== '..') {
        echo "<button class='btn-fs btn-fs-rename' onclick=\"showFsModal('rename',{$pathJs},null,'{$type}',{$parentPathJs});event.stopPropagation();\">ReName</button>";
        echo "<button class='btn-fs btn-fs-delete' onclick=\"showFsModal('delete',{$pathJs},null,'{$type}',{$parentPathJs});event.stopPropagation();\">DEL</button>";
        echo "<input type='checkbox' class='btn-fs-checkbox del-multi-cb' value='{$path}' style='vertical-align:middle;'>";
    }
    echo "</span>";
    echo "</td>";
    $desc = $rolesData[$node['path']] ?? getDefaultRole($node['path']);
    if(mb_strlen($desc)>80) $desc = mb_substr($desc,0,80).'...';
    echo "<td class='tree-rolecell' ondblclick=\"roleEditOpen({$pathJs},this)\" data-default=\"" . htmlspecialchars(getDefaultRole($node['path']), ENT_QUOTES) . "\">" . $desc . "</td>";
    echo "<td class='tree-paramcell'>" . getParamEditor($node['path']) . "</td>";
    echo "</tr>";
}
?>
    </tbody>
  </table>
</div>
<div id="fs-modal"><div id="fs-modal-inner"></div></div>
<div class="modal-bg" id="modal-bg">
  <div class="modal-content">
    <div class="modal-title">
      <span id="modal-title-filename" style="margin-left:18px;color:#fff;"></span>
    </div>
    <span class="modal-close" onclick="closeEditorModal()">&times;</span>
    <div id="modal-editor-area"></div>
    <div class="modal-footer">
      <button id="decision-btn" onclick="saveFileByDecision()">Update</button>
    </div>
  </div>
</div>


<script>
// --------- Upload, Drag-Move, Reorder & Sort Extensions -------------
let dragSrcPath = '';
let dragSrcTr = null;

function uploadFile(destPath){
    const inp = document.createElement('input');
    inp.type = 'file';
    inp.onchange = (ev) => {
        const f = ev.target.files[0];
        if(!f) return;
        const fd = new FormData();
        fd.append('fs_api','upload');
        fd.append('target', destPath);
        fd.append('file', f);
        fetch('', {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
            if(res.status==='ok') location.href='?r='+Date.now();
            else alert('Upload failed: '+(res.msg||''));});
    };
    inp.click();
}

document.addEventListener('DOMContentLoaded', ()=>{
    const rootBtn = document.getElementById('upload-root-btn');
    if(rootBtn) rootBtn.addEventListener('click', ()=>uploadFile('.'));

    document.querySelectorAll('.tree-table tbody tr').forEach(tr=>{
        tr.draggable = true;
        tr.addEventListener('dragstart', e=>{
            dragSrcPath = tr.dataset.path;
            dragSrcTr   = tr;
            e.dataTransfer.setData('text/plain', dragSrcPath);
        });
        tr.addEventListener('dragover', e=>{ e.preventDefault(); });
        tr.addEventListener('drop', e=>{
            e.preventDefault();
            if(!dragSrcPath) return;
            const sameDir = (tr.dataset.parentPath||'') === (dragSrcTr.dataset.parentPath||'');
            if(sameDir){
                // Reorder in same directory
                tr.parentNode.insertBefore(dragSrcTr, tr);
                const parentPath = tr.dataset.parentPath || '';
                const rows = [...document.querySelectorAll(`tr[data-parent-path="${parentPath}"]`)];
                const order = rows.map(r=>r.dataset.name);
                fetch('', {
                    method:'POST', cache:'no-store',
                    body:new URLSearchParams({fs_api:'save_order', target:parentPath, order:JSON.stringify(order)})
                });
                dragSrcPath = '';
                return;
            }
            // Move to other directory (drop on dir or sibling)
            const destType = tr.classList.contains('dir') ? 'dir' : 'file';
            let destPath = tr.dataset.path;
            if(destType !== 'dir'){
                destPath = tr.dataset.parentPath || '.';
            }
            if(!destPath) destPath = '.';
            fetch('', {
                method:'POST', cache:'no-store',
                body:new URLSearchParams({fs_api:'move', source:dragSrcPath, dest:destPath})
            }).then(r=>r.json()).then(res=>{
                if(res.status==='ok') location.href='?r='+Date.now();
                else alert('移動失敗: '+(res.msg||''));});
            dragSrcPath = '';
        });
    });

    // Sorting
    const sortSel = document.getElementById('sort-select');
    if(sortSel){
        sortSel.addEventListener('change', e=>{
            const val = e.target.value;
            const tbody = document.querySelector('.tree-table tbody');
            const rows = [...tbody.querySelectorAll('tr')];
            rows.sort((a,b)=>{
                if(val==='name'){
                    return a.dataset.name.localeCompare(b.dataset.name);
                }
                if(val==='type'){
                    const dirA = a.classList.contains('dir')?0:1;
                    const dirB = b.classList.contains('dir')?0:1;
                    if(dirA!==dirB) return dirA-dirB;
                    return a.dataset.name.localeCompare(b.dataset.name);
                }
                if(val==='mtime'){
                    return b.dataset.mtime - a.dataset.mtime;
                }
                return 0;
            });
            rows.forEach(r=>tbody.appendChild(r));
        });
    }
});
</script>


</body>
</html>
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
@@ -115,56 +115,64 @@ function getParamEditor($path) {
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
@@ -330,75 +338,77 @@ if (isset($_POST['fs_api'])) {
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
        if ($full === false || strpos($full, $base) !== 0 || !is_file($full)) {
            exit(json_encode(['status' => 'ng']));
        }
        $code = file_get_contents($full);
        exit(json_encode(['status' => 'ok', 'code' => $code]));
    }
    if ($_GET['api'] === 'save') {
        $file = $_POST['file'] ?? '';
        $code = $_POST['code'] ?? '';
        $full = realpath($base . '/' . $file);
        if ($full === false || strpos($full, $base) !== 0 || !is_file($full)) {
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
    $full = __DIR__ . '/' . $f;
    if (!is_file($full)) die('Not found');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($f).'"');
    readfile($full);
    exit;
}
if (isset($_GET['download_dir'])) {
    $dir = $_GET['download_dir'];
    $zipname = date('Y.m.d_H.i.s') . '_' . preg_replace('/[^a-zA-Z0-9_]/','_',basename($dir)).'.zip';
@@ -869,141 +879,146 @@ function getLangByFile(f) {
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
    $id         = htmlspecialchars($node['id']);
    $parent     = $node['parentId'] ? htmlspecialchars($node['parentId']) : '';
    $type       = $node['type'];
    $name       = htmlspecialchars($node['name']);
    $path       = htmlspecialchars($node['path']);
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
    $parentPathEsc = htmlspecialchars($parentPath);
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
    echo "<td class='tree-rolecell' ondblclick=\"roleEditOpen({$pathJs},this)\" data-default=\"" . htmlspecialchars(getDefaultRole($node['path'])) . "\">" . $desc . "</td>";
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
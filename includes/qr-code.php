<?php
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../phpqrcode/qrlib.php';
$data = $_GET['data'] ?? '';
if ($data === '') {
    exit;
}
QRcode::png($data, false, QR_ECLEVEL_L, 6, 2);
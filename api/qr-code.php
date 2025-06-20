<?php
// api/qr-code.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../phpqrcode/qrlib.php';
// Example: generate QR PNG to browser
$data = $_GET['data'] ?? '';
QRcode::png($data);
?>
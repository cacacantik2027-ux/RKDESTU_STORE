<?php
// proof_image.php — menampilkan foto bukti TF yang MASIH di folder privat
// (data/chat/uploads) HANYA untuk admin yang sudah login. Ini supaya foto
// bukti transfer tidak bisa diakses siapa pun lewat URL langsung sebelum
// di-approve dan dipublikasikan.

session_start();
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/config.php';

if (empty($_SESSION['rk_admin'])) {
    http_response_code(403);
    exit('Forbidden');
}

$file = basename($_GET['file'] ?? '');
if ($file === '' || !preg_match('/^[a-zA-Z0-9_\-]+\.(png|jpe?g|webp)$/i', $file)) {
    http_response_code(400);
    exit('Bad request');
}

$path = CHAT_UPLOAD_DIR . '/' . $file;
if (!file_exists($path)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=60');
readfile($path);

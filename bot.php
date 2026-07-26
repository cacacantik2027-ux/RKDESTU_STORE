<?php
// bot.php — Webhook Telegram Bot untuk RK Destu Store.
//
// PENTING: Telegram MEWAJIBKAN URL webhook pakai https://. Kalau
// hosting-mu masih http:// biasa (mis. SSL InfinityFree belum aktif),
// file ini TIDAK akan pernah dipanggil Telegram sama sekali — pakai
// bot_poll.php sebagai gantinya (lihat README-TELEGRAM.md bagian
// "Mode Polling"), itu jalan normal di http:// biasa.
//
// Kalau hosting-mu SUDAH https://, file ini yang dipakai (lebih real-time
// daripada polling). Set sekali lewat browser (ganti TOKEN, DOMAIN, SECRET):
// https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://domainmu.com/bot.php&secret_token=<TELEGRAM_WEBHOOK_SECRET>
//
// Seluruh logika pemrosesan pesan (mode admin & mode klien) ada di
// rk_tg_process_update() dalam functions.php — dipakai bareng oleh
// bot.php (webhook) dan bot_poll.php (polling), supaya perilaku bot
// selalu sama persis di kedua mode.

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin/config.php';

header('Content-Type: application/json');

// Verifikasi request benar-benar dari Telegram (kalau secret diisi)
if (TELEGRAM_WEBHOOK_SECRET !== '') {
    $incomingSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals(TELEGRAM_WEBHOOK_SECRET, $incomingSecret)) {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        exit;
    }
}

$raw = file_get_contents('php://input');
$update = json_decode($raw, true);
if (is_array($update)) {
    rk_tg_process_update($update);
}

echo json_encode(['ok' => true]);

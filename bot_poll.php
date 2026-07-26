<?php
// bot_poll.php — Alternatif dari bot.php UNTUK HOSTING YANG BELUM/TIDAK
// PUNYA HTTPS (mis. InfinityFree http:// biasa).
//
// KENAPA INI PERLU:
// Telegram MEWAJIBKAN webhook pakai https:// — ini aturan dari pihak
// Telegram sendiri, bukan sesuatu yang bisa "diakalin" dari sisi kode
// kita. Kalau domainmu masih http://, setWebhook ke bot.php TIDAK
// akan pernah berhasil menerima pesan.
//
// SOLUSINYA: bukan Telegram yang "mengetuk" website kita (itu yang perlu
// https://), tapi SEBALIKNYA — SCRIPT INI yang aktif "menjemput" pesan
// baru ke server Telegram (curl ke api.telegram.org, yang memang selalu
// https:// di sisi Telegram — itu tidak masalah, karena itu permintaan
// KELUAR dari hosting kita, bukan permintaan MASUK ke hosting kita).
// URL untuk MEMANGGIL script ini boleh http:// biasa, karena yang
// memanggilnya bukan Telegram, tapi layanan CRON EKSTERNAL GRATIS
// (lihat README-TELEGRAM.md bagian "Mode Polling" untuk caranya).
//
// CARA PAKAI:
// 1. Pastikan webhook LAMA (kalau pernah di-set) sudah dihapus dulu:
//    https://api.telegram.org/bot<TOKEN>/deleteWebhook
//    (Telegram menolak getUpdates selama webhook masih aktif.)
// 2. Daftar di cron-job.org (gratis), buat cronjob baru yang memanggil:
//    http://domainmu.com/bot_poll.php?secret=<TELEGRAM_WEBHOOK_SECRET>
//    setiap 1–5 menit.
// 3. Selesai — pesan baru akan otomatis dijemput & diproses tiap kali
//    cronjob itu jalan (delay wajar: ±1-5 menit, tergantung interval
//    cronjob-mu, TIDAK real-time seperti webhook).

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin/config.php';

header('Content-Type: application/json');

// Verifikasi pemanggil tahu secret-nya (siapa saja yang tahu URL ini kalau
// tanpa secret bisa memicu bot memproses pesan berulang / membebani server).
$incomingSecret = $_GET['secret'] ?? '';
if (TELEGRAM_WEBHOOK_SECRET === '' || !hash_equals(TELEGRAM_WEBHOOK_SECRET, (string)$incomingSecret)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'secret tidak cocok atau TELEGRAM_WEBHOOK_SECRET belum diisi di admin/config.php']);
    exit;
}

$offset = rk_tg_get_offset();
$updates = rk_tg_get_updates($offset);

$processed = 0;
foreach ($updates as $update) {
    rk_tg_process_update($update);
    $processed++;
    if (!empty($update['update_id'])) {
        $offset = (int)$update['update_id'] + 1;
    }
}

if ($processed > 0) {
    rk_tg_set_offset($offset);
}

echo json_encode(['ok' => true, 'processed' => $processed, 'next_offset' => $offset]);

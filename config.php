<?php
// config.php — Membaca setting dari ENVIRONMENT VARIABLE (Railway) kalau
// tersedia, atau fallback ke nilai default di bawah (dipakai kalau jalan
// di hosting biasa seperti InfinityFree tanpa environment variable).
//
// Di Railway: isi semua ini lewat file .env yang ditempel di tab
// "Variables" -> "Raw Editor", TIDAK perlu edit file ini sama sekali.
// Lihat README-RAILWAY.md untuk panduan lengkap.

function rk_env($key, $default = '') {
    $v = getenv($key);
    if ($v === false || $v === '') return $default;
    return $v;
}

// Password login panel admin (admin/index.php).
define('ADMIN_PASSWORD', rk_env('ADMIN_PASSWORD', 'gantipassword123'));

// ===========================================================
// TELEGRAM BOT — dipakai untuk fitur Live Chat & pengaturan
// link testimoni lewat bot. Lihat README-TELEGRAM.md untuk
// panduan lengkap cara membuat bot dan mendapatkan nilai ini.
// ===========================================================

define('TELEGRAM_BOT_TOKEN', rk_env('TELEGRAM_BOT_TOKEN', ''));
define('TELEGRAM_ADMIN_CHAT_ID', rk_env('TELEGRAM_ADMIN_CHAT_ID', ''));
define('TELEGRAM_WEBHOOK_SECRET', rk_env('TELEGRAM_WEBHOOK_SECRET', ''));
define('TELEGRAM_BOT_USERNAME', rk_env('TELEGRAM_BOT_USERNAME', ''));

// ===========================================================
// CHANNEL TESTI — dipakai untuk auto-post foto bukti transfer
// yang terdeteksi di live chat / chat langsung Telegram.
// WAJIB: bot harus dijadikan ADMIN di channel ini dulu, kalau
// tidak, sendPhoto ke channel akan gagal.
// ===========================================================
define('TELEGRAM_TESTI_CHANNEL_ID', rk_env('TELEGRAM_TESTI_CHANNEL_ID', ''));

// Nama toko yang dicari di teks chat pengunjung untuk mendeteksi
// bukti transfer (dipakai bareng nominal Rp).
define('RK_STORE_NAME', rk_env('RK_STORE_NAME', 'RKDESTU STORE'));

// ===========================================================
// BACKUP OTOMATIS — zip folder data/ + foto (portofolio, testimoni,
// chat uploads) dikirim sebagai file ke Telegram terjadwal.
// Lihat backup.php & README-RAILWAY.md.
// ===========================================================

// Ke mana zip backup dikirim. Kosongkan untuk pakai TELEGRAM_ADMIN_CHAT_ID
// (chat pribadi admin dengan bot) sebagai default.
define('TELEGRAM_BACKUP_CHAT_ID', rk_env('TELEGRAM_BACKUP_CHAT_ID', rk_env('TELEGRAM_ADMIN_CHAT_ID', '')));

// Jadwal cron backup ini HANYA dipakai sebagai catatan/dokumentasi di sini —
// jadwal ASLI diatur di Railway (Settings -> Cron Schedule) pada service
// backup terpisah, lihat README-RAILWAY.md. Format cron Railway pakai UTC.
define('RK_BACKUP_SCHEDULE_NOTE', rk_env('RK_BACKUP_SCHEDULE_NOTE', '06:00 WIB setiap hari (= 23:00 UTC)'));

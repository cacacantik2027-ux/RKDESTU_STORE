<?php
// backup.php — dijalankan sebagai SERVICE CRON TERPISAH di Railway
// (bukan lewat browser/HTTP), lihat README-RAILWAY.md bagian
// "Auto-Backup Terjadwal" untuk cara setup Cron Schedule-nya.
//
// Railway menjalankan Cron Schedule dalam UTC. Jadwal 06:00 WIB setiap
// hari = 23:00 UTC (hari sebelumnya), karena WIB = UTC+7.
// Cron expression yang dipakai di Railway: 0 23 * * *
//
// Script ini WAJIB selesai & keluar (exit) sendiri — tidak boleh nyala
// terus — supaya Railway tidak menganggapnya "masih jalan" dan melewati
// jadwal berikutnya.

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin/config.php';

echo "[" . date('Y-m-d H:i:s') . " UTC] Menjalankan backup terjadwal...\n";

$result = rk_run_backup();

if ($result['ok']) {
    echo "Backup berhasil dikirim ke Telegram.\n";
    exit(0);
} else {
    echo "Backup GAGAL: " . ($result['error'] ?? 'unknown') . "\n";
    exit(1);
}

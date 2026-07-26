<?php
// chat_send.php — dipanggil via fetch() oleh widget live chat.
// 1) Menyimpan pesan (teks dan/atau foto) ke sesi lokal.
// 2) Mengirim NOTIFIKASI ke Telegram admin (chat pribadi) — admin
//    membalas lewat Panel Admin di website, BUKAN lewat Reply Telegram.
// 3) Kalau pesan berisi FOTO + teks yang menyebut nominal & nama toko,
//    otomatis dianggap "bukti transfer" -> auto-post ke Channel Telegram
//    Testi, dan masuk antrian review admin untuk ditayangkan di halaman
//    Testimoni website (setelah admin approve manual).

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$session = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input['session'] ?? '');
$name = rk_clean($input['name'] ?? 'Pengunjung');
$tgUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $input['tg_username'] ?? '');
$text = trim($input['message'] ?? '');
$page = rk_clean($input['page'] ?? '');
$imageDataUri = $input['image'] ?? '';

if ($session === '' || ($text === '' && $imageDataUri === '')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}
if (mb_strlen($text) > 800) $text = mb_substr($text, 0, 800);

// --- simpan foto (kalau ada) ke folder terproteksi ---
$imageFilename = null;
if ($imageDataUri !== '') {
    $imageFilename = rk_save_chat_image($session, $imageDataUri);
    if (!$imageFilename) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'image_invalid']);
        exit;
    }
}

// --- simpan pesan pengunjung ke sesi lokal (dibaca oleh chat_poll.php) ---
$entry = ['from' => 'user', 'name' => $name];
if ($imageFilename) {
    $entry['type'] = 'image';
    $entry['image'] = $imageFilename;
    $entry['text'] = $text; // caption, boleh kosong
} else {
    $entry['type'] = 'text';
    $entry['text'] = $text;
}
rk_chat_append($session, $entry);

// --- notifikasi teks ke Telegram admin (chat pribadi, hanya info) ---
$bodyLine = $imageFilename
    ? ('📷 Mengirim foto' . ($text !== '' ? " + pesan:\n{$text}" : '.'))
    : $text;
$label = "💬 <b>Live Chat — Website</b>\n" .
         "👤 Username/Nama: <b>" . htmlspecialchars($name, ENT_QUOTES) . "</b>\n" .
         ($tgUsername !== '' ? "🔗 Telegram: @{$tgUsername}\n" : '') .
         ($page !== '' ? "📄 Halaman: {$page}\n" : '') .
         "\n{$bodyLine}\n\n" .
         "<i>Balas lewat Panel Admin di website, bukan di sini.</i>";
rk_tg_send(defined('TELEGRAM_ADMIN_CHAT_ID') ? TELEGRAM_ADMIN_CHAT_ID : '', $label);

// --- deteksi otomatis bukti transfer (foto + teks sebut nominal & nama toko) ---
$proofDetected = false;
if ($imageFilename && rk_detect_payment_proof($text)) {
    $proofDetected = true;
    $context = rk_chat_recent_text($session, 8);

    rk_add_testi_queue([
        'session'    => $session,
        'name'       => $name,
        'tg_username'=> $tgUsername,
        'image'      => $imageFilename,
        'caption'    => $text,
        'context'    => $context,
    ]);

    // Auto-post ke Channel Testi — TANPA approval, langsung begitu terdeteksi.
    if (defined('TELEGRAM_TESTI_CHANNEL_ID') && TELEGRAM_TESTI_CHANNEL_ID !== '') {
        $caption = "🧾 <b>Bukti Transfer Terdeteksi</b>\n" .
                   "👤 " . htmlspecialchars($name, ENT_QUOTES) .
                   ($tgUsername !== '' ? " (@{$tgUsername})" : '') . "\n" .
                   ($text !== '' ? "\n" . htmlspecialchars($text, ENT_QUOTES) : '') .
                   "\n\n<i>Menunggu review admin sebelum tayang di halaman Testimoni website.</i>";
        rk_tg_send_photo(TELEGRAM_TESTI_CHANNEL_ID, CHAT_UPLOAD_DIR . '/' . $imageFilename, $caption);
    }
}

echo json_encode(['ok' => true, 'proof_detected' => $proofDetected]);

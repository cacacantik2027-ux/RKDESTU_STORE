<?php
// functions.php — helper baca/tulis data testimoni (format JSON, tanpa database)

define('DATA_DIR', __DIR__ . '/data');
define('APPROVED_FILE', DATA_DIR . '/testimonials.json');
define('PENDING_FILE', DATA_DIR . '/pending.json');

function rk_read_json($path) {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function rk_write_json($path, $data) {
    // file_put_contents mengembalikan false kalau folder data/ belum writable —
    // lihat catatan izin folder di README.
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function rk_get_approved() {
    $items = rk_read_json(APPROVED_FILE);
    usort($items, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));
    return $items;
}

// ===========================================================
// Logo Website — dikelola via bot Telegram (/settings -> Kelola Logo).
// Kalau belum pernah diupload, website tetap pakai badge teks "RK"
// seperti default (lihat partials/nav.php & footer.php).
// ===========================================================
define('LOGO_FILE', DATA_DIR . '/logo.json');
define('LOGO_IMG_DIR', __DIR__ . '/assets/img/logo');

function rk_get_logo() {
    $data = rk_read_json(LOGO_FILE);
    return !empty($data['image']) ? $data : null;
}

function rk_save_logo($rel) {
    return rk_write_json(LOGO_FILE, ['image' => $rel, 'updated_at' => date('c')]);
}

function rk_reset_logo() {
    $old = rk_get_logo();
    if ($old && !empty($old['image'])) {
        $oldPath = __DIR__ . '/' . ltrim($old['image'], '/');
        if (is_file($oldPath)) @unlink($oldPath);
    }
    return rk_write_json(LOGO_FILE, []);
}

function rk_save_logo_image($bin, $ext) {
    if (!$bin) return null;
    $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$ext));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'], true)) $ext = 'png';
    if (!is_dir(LOGO_IMG_DIR)) @mkdir(LOGO_IMG_DIR, 0755, true);
    $filename = 'logo_' . time() . '_' . substr(md5($bin), 0, 8) . '.' . $ext;
    $path = LOGO_IMG_DIR . '/' . $filename;
    if (file_put_contents($path, $bin) === false) return null;
    return 'assets/img/logo/' . $filename;
}

function rk_get_pending() {
    return rk_read_json(PENDING_FILE);
}

// ===========================================================
// Slider Beranda (hero-slider di index.php) — dikelola via bot
// Telegram dengan perintah /slide, sama seperti /portofolio.
// ===========================================================
define('SLIDES_FILE', DATA_DIR . '/slides.json');
define('SLIDE_IMG_DIR', __DIR__ . '/assets/img/slides');

function rk_get_slides() {
    return rk_read_json(SLIDES_FILE);
}

function rk_save_slides($items) {
    return rk_write_json(SLIDES_FILE, $items);
}

// Simpan binary foto slide ke folder publik assets/img/slides/ dan
// kembalikan path relatif (dipakai sebagai field "image" di slides.json).
function rk_save_slide_image($bin, $ext) {
    if (!$bin) return null;
    $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$ext));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) $ext = 'jpg';
    if (!is_dir(SLIDE_IMG_DIR)) @mkdir(SLIDE_IMG_DIR, 0755, true);
    $filename = 'tg_' . time() . '_' . substr(md5($bin), 0, 8) . '.' . $ext;
    $path = SLIDE_IMG_DIR . '/' . $filename;
    if (file_put_contents($path, $bin) === false) return null;
    return 'assets/img/slides/' . $filename;
}

function rk_next_id($items) {
    $max = 0;
    foreach ($items as $it) $max = max($max, $it['id'] ?? 0);
    return $max + 1;
}

function rk_clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

// ===========================================================
// State Menu /settings — supaya admin bisa atur SEMUA (portofolio,
// slider beranda, testimoni, backup, restore) lewat tombol di
// Telegram, tanpa perlu hafal/ketik perintah /portofolio dll manual.
//
// Cara kerja: begitu admin tap tombol di menu /settings, kita simpan
// "state" kecil (lagi nunggu apa dari admin) ke data/admin_state.json.
// Pesan/foto/file BERIKUTNYA yang dikirim admin akan diproses sesuai
// state itu, lalu state dihapus lagi. Perintah lama (/portofolio,
// /slide, /testimoni, /backup, kirim zip+/restore) TETAP jalan seperti
// biasa juga, untuk yang lebih suka ketik langsung.
// ===========================================================
define('ADMIN_STATE_FILE', DATA_DIR . '/admin_state.json');

function rk_get_admin_state($chatId) {
    $all = rk_read_json(ADMIN_STATE_FILE);
    return $all[$chatId] ?? null;
}

function rk_set_admin_state($chatId, $state) {
    $all = rk_read_json(ADMIN_STATE_FILE);
    $all[$chatId] = $state;
    rk_write_json(ADMIN_STATE_FILE, $all);
}

function rk_clear_admin_state($chatId) {
    $all = rk_read_json(ADMIN_STATE_FILE);
    unset($all[$chatId]);
    rk_write_json(ADMIN_STATE_FILE, $all);
}

// Menu utama /settings — tombol-tombol inline.
function rk_settings_menu_markup() {
    return [
        'inline_keyboard' => [
            [['text' => '🖼 Kelola Portofolio', 'callback_data' => 'settings_portofolio_menu']],
            [['text' => '🎞 Kelola Slider Beranda', 'callback_data' => 'settings_slide_menu']],
            [['text' => '🏷 Ganti Logo Website', 'callback_data' => 'settings_logo_menu']],
            [['text' => '⭐ Kelola Testimoni', 'callback_data' => 'settings_testi_menu']],
            [['text' => '🗄 Backup Sekarang', 'callback_data' => 'settings_backup']],
            [['text' => '♻️ Restore dari Backup', 'callback_data' => 'settings_restore']],
            [['text' => '❌ Tutup Menu', 'callback_data' => 'settings_cancel']],
        ],
    ];
}

// Tombol "🔙 Kembali" universal, ditempel di HAMPIR SEMUA pesan menu
// /settings supaya admin selalu bisa mundur 1 langkah tanpa harus ketik
// /batal atau /settings ulang. $extraRows = baris tombol tambahan di
// ATAS tombol kembali (mis. daftar postingan portofolio).
function rk_settings_back_markup($extraRows = [], $backTarget = 'settings_menu') {
    $rows = $extraRows;
    $rows[] = [['text' => '🔙 Kembali', 'callback_data' => $backTarget]];
    return ['inline_keyboard' => $rows];
}

// Submenu Kelola Portofolio.
function rk_settings_portofolio_menu_markup() {
    return [
        'inline_keyboard' => [
            [['text' => '➕ Tambah Foto Baru', 'callback_data' => 'settings_portofolio_add']],
            [['text' => '✏️ Edit Postingan', 'callback_data' => 'settings_portofolio_edit_list']],
            [['text' => '🗑 Hapus Postingan', 'callback_data' => 'settings_portofolio_delete_list']],
            [['text' => '🔙 Kembali', 'callback_data' => 'settings_menu']],
        ],
    ];
}

function rk_settings_slide_menu_markup() {
    return [
        'inline_keyboard' => [
            [['text' => '✏️ Edit / Tambah Slide', 'callback_data' => 'settings_slide_edit']],
            [['text' => '🔙 Kembali', 'callback_data' => 'settings_menu']],
        ],
    ];
}

function rk_settings_logo_menu_markup() {
    return [
        'inline_keyboard' => [
            [['text' => '⬆️ Upload Logo Baru', 'callback_data' => 'settings_logo_upload']],
            [['text' => '♻️ Kembalikan ke Default (teks "RK")', 'callback_data' => 'settings_logo_reset']],
            [['text' => '🔙 Kembali', 'callback_data' => 'settings_menu']],
        ],
    ];
}

function rk_settings_testi_menu_markup() {
    return [
        'inline_keyboard' => [
            [['text' => '✏️ Set/Ubah Link Manual', 'callback_data' => 'settings_testimoni_manual']],
            [['text' => 'ℹ️ Cara Kerja Auto-Deteksi', 'callback_data' => 'settings_testimoni_info']],
            [['text' => '🔙 Kembali', 'callback_data' => 'settings_menu']],
        ],
    ];
}

// Daftar postingan portofolio sbg tombol (dipakai buat pilih mana yang
// mau di-edit/hapus). $callbackPrefix mis. "settings_portofolio_edit_item_".
function rk_portfolio_list_markup($callbackPrefix, $backTarget) {
    $items = array_slice(array_reverse(rk_get_portfolio()), 0, 15); // 15 terbaru
    $rows = [];
    foreach ($items as $p) {
        $label = '#' . $p['id'] . ' ' . mb_substr($p['title'] ?? '', 0, 30);
        $rows[] = [['text' => $label, 'callback_data' => $callbackPrefix . (int)$p['id']]];
    }
    if (empty($rows)) {
        $rows[] = [['text' => '(belum ada postingan portofolio)', 'callback_data' => 'settings_noop']];
    }
    return rk_settings_back_markup($rows, $backTarget);
}

function rk_tg_answer_callback($callbackId, $text = '') {
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '' || !$callbackId) return null;
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/answerCallbackQuery';
    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'callback_query_id' => $callbackId,
        'text' => $text,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_exec($ch);
    curl_close($ch);
    return null;
}

// Menangani tap tombol di menu /settings (callback_query update).
function rk_tg_process_callback($cb) {
    $chatId = (string)($cb['message']['chat']['id'] ?? '');
    $callbackId = $cb['id'] ?? null;
    $data = $cb['data'] ?? '';

    if ($chatId === '' || $chatId !== (string)TELEGRAM_ADMIN_CHAT_ID) {
        rk_tg_answer_callback($callbackId, 'Menu ini khusus admin.');
        return;
    }

    // --- Item dinamis: settings_portofolio_edit_item_<id> / settings_portofolio_delete_item_<id> ---
    if (strpos($data, 'settings_portofolio_edit_item_') === 0) {
        $id = (int) substr($data, strlen('settings_portofolio_edit_item_'));
        $item = rk_find_portfolio_item($id);
        if (!$item) {
            rk_tg_answer_callback($callbackId, 'Postingan tidak ditemukan.');
            return;
        }
        rk_set_admin_state($chatId, ['action' => 'await_portofolio_edit', 'id' => $id]);
        rk_tg_answer_callback($callbackId);
        rk_tg_send($chatId,
            "✏️ <b>Edit Postingan #{$id}</b> — \"" . htmlspecialchars($item['title'] ?? '', ENT_QUOTES) . "\"\n\n".
            "Kirim FOTO baru untuk ganti fotonya (caption boleh <code>Judul | Harga | Kategori | Label</code> untuk sekalian ubah info), ATAU cukup ketik teks <code>Judul | Harga | Kategori | Label</code> saja kalau cuma mau ubah info tanpa ganti foto.\n\n".
            "Kosongkan bagian yang tidak mau diubah (info lama dipertahankan). Ketik /batal untuk membatalkan.",
            rk_settings_back_markup([], 'settings_portofolio_menu')
        );
        return;
    }

    if (strpos($data, 'settings_portofolio_delete_item_') === 0) {
        $id = (int) substr($data, strlen('settings_portofolio_delete_item_'));
        $ok = rk_delete_portfolio_item($id);
        rk_clear_admin_state($chatId);
        rk_tg_answer_callback($callbackId, $ok ? 'Dihapus.' : 'Gagal / tidak ditemukan.');
        rk_tg_send($chatId,
            $ok ? "🗑 Postingan #{$id} berhasil dihapus (foto ikut dihapus dari server)." : "❌ Postingan #{$id} tidak ditemukan.",
            rk_settings_back_markup([], 'settings_portofolio_menu')
        );
        return;
    }

    switch ($data) {
        // ================= MENU UTAMA =================
        case 'settings_menu':
            rk_clear_admin_state($chatId);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId, "⚙️ <b>Menu Pengaturan RK Destu Store</b>\n\nPilih salah satu:", rk_settings_menu_markup());
            break;

        case 'settings_noop':
            rk_tg_answer_callback($callbackId);
            break;

        // ================= PORTOFOLIO =================
        case 'settings_portofolio_menu':
            rk_clear_admin_state($chatId);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId, "🖼 <b>Kelola Portofolio</b>\n\nPilih aksi:", rk_settings_portofolio_menu_markup());
            break;

        case 'settings_portofolio_add':
            rk_set_admin_state($chatId, ['action' => 'await_portofolio_photo']);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId,
                "🖼 <b>Tambah Foto Portofolio</b>\n\n".
                "Kirim FOTO-nya sekarang, dengan caption:\n".
                "<code>Judul | Harga | Kategori | Label</code>\n\n".
                "Yang wajib cuma Judul, sisanya boleh dikosongkan.",
                rk_settings_back_markup([], 'settings_portofolio_menu')
            );
            break;

        case 'settings_portofolio_edit_list':
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId, "✏️ <b>Pilih postingan yang mau di-edit:</b>",
                rk_portfolio_list_markup('settings_portofolio_edit_item_', 'settings_portofolio_menu'));
            break;

        case 'settings_portofolio_delete_list':
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId, "🗑 <b>Pilih postingan yang mau dihapus:</b>\n⚠️ Aksi ini langsung menghapus, tidak ada undo.",
                rk_portfolio_list_markup('settings_portofolio_delete_item_', 'settings_portofolio_menu'));
            break;

        // ================= SLIDER BERANDA =================
        case 'settings_slide_menu':
            rk_clear_admin_state($chatId);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId, "🎞 <b>Kelola Slider Beranda</b>\n\nPilih aksi:", rk_settings_slide_menu_markup());
            break;

        case 'settings_slide_edit':
            rk_set_admin_state($chatId, ['action' => 'await_slide_slot']);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId,
                "🎞 <b>Edit/Tambah Slide</b>\n\n".
                "Ketik nomor slide yang mau diganti/tambah (1-5), balas pesan ini dengan angka saja, misal: <code>1</code>",
                rk_settings_back_markup([], 'settings_slide_menu')
            );
            break;

        // ================= LOGO WEBSITE =================
        case 'settings_logo_menu':
            rk_clear_admin_state($chatId);
            rk_tg_answer_callback($callbackId);
            $current = rk_get_logo();
            rk_tg_send($chatId,
                "🏷 <b>Ganti Logo Website</b>\n\n" .
                ($current ? "Logo custom sedang aktif." : "Saat ini masih pakai badge teks default \"RK\".") .
                "\n\nPilih aksi:",
                rk_settings_logo_menu_markup()
            );
            break;

        case 'settings_logo_upload':
            rk_set_admin_state($chatId, ['action' => 'await_logo_photo']);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId,
                "⬆️ <b>Upload Logo Baru</b>\n\n".
                "Kirim FOTO logo-nya sekarang (disarankan gambar persegi/kotak, min 200x200px, background transparan kalau ada — format PNG).",
                rk_settings_back_markup([], 'settings_logo_menu')
            );
            break;

        case 'settings_logo_reset':
            rk_reset_logo();
            rk_clear_admin_state($chatId);
            rk_tg_answer_callback($callbackId, 'Logo dikembalikan ke default.');
            rk_tg_send($chatId, "♻️ Logo website dikembalikan ke badge teks default \"RK\".", rk_settings_back_markup([], 'settings_logo_menu'));
            break;

        // ================= TESTIMONI =================
        case 'settings_testi_menu':
            rk_clear_admin_state($chatId);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId, "⭐ <b>Kelola Testimoni</b>\n\nPilih aksi:", rk_settings_testi_menu_markup());
            break;

        case 'settings_testimoni_manual':
            rk_set_admin_state($chatId, ['action' => 'await_testimoni_input']);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId,
                "⭐ <b>Set Link Testimoni</b>\n\n".
                "Ketik ID postingan portofolio dan link testimoninya, dipisah spasi, contoh:\n".
                "<code>1 https://t.me/testimonirkdestu/123</code>\n\n".
                "ID bisa dilihat di halaman Portofolio atau panel admin.",
                rk_settings_back_markup([], 'settings_testi_menu')
            );
            break;

        case 'settings_testimoni_info':
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId,
                "ℹ️ <b>Auto-Deteksi Link Testimoni</b>\n\n".
                "Setiap kali kamu posting testimoni di Channel Testi dengan caption yang menyebutkan <b>Label</b> yang sama persis dengan Label postingan Portofolio (contoh: <code>Terlaris</code>), bot otomatis mengisi link testimoni postingan Portofolio itu — tidak perlu diset manual lagi.\n\n".
                "Kalau mau override manual kapan saja, tetap bisa lewat menu \"Set/Ubah Link Manual\".",
                rk_settings_back_markup([], 'settings_testi_menu')
            );
            break;

        // ================= BACKUP / RESTORE =================
        case 'settings_backup':
            rk_tg_answer_callback($callbackId, 'Membuat backup...');
            rk_tg_send($chatId, "⏳ Membuat file backup, mohon tunggu...");
            $result = rk_run_backup();
            rk_tg_send($chatId,
                $result['ok'] ? "✅ Backup selesai dikirim." : "❌ Backup gagal: " . htmlspecialchars($result['error'] ?? 'unknown', ENT_QUOTES),
                rk_settings_back_markup()
            );
            break;

        case 'settings_restore':
            rk_set_admin_state($chatId, ['action' => 'await_restore_file']);
            rk_tg_answer_callback($callbackId);
            rk_tg_send($chatId,
                "♻️ <b>Restore dari Backup</b>\n\n".
                "Kirim FILE .zip hasil backup sekarang (cukup kirim file-nya saja, tidak perlu caption apa-apa lagi).\n\n".
                "⚠️ Ini akan MENIMPA data yang sedang berjalan.",
                rk_settings_back_markup()
            );
            break;

        case 'settings_cancel':
            rk_clear_admin_state($chatId);
            rk_tg_answer_callback($callbackId, 'Menu ditutup.');
            rk_tg_send($chatId, "Menu ditutup. Ketik /settings kapan saja untuk buka lagi.");
            break;

        default:
            rk_tg_answer_callback($callbackId);
    }
}

// ===========================================================
// Helper curl KHUSUS untuk semua panggilan ke api.telegram.org /
// file.telegram.org.
//
// KENAPA INI PERLU: beberapa hosting gratis (termasuk InfinityFree) punya
// DNS resolver internal yang GAGAL menerjemahkan nama domain seperti
// "api.telegram.org" ke alamat IP-nya (curl error 6: "Could not resolve
// host"), padahal koneksi keluarnya sendiri sebenarnya TIDAK diblokir.
//
// Solusinya: kita kasih tahu curl alamat IP Telegram secara langsung lewat
// CURLOPT_RESOLVE (skip proses DNS lookup yang bermasalah itu), TAPI tetap
// pakai nama domain aslinya untuk proses handshake SSL (SNI) dan verifikasi
// sertifikat — jadi tetap aman, TIDAK perlu mematikan SSL_VERIFYPEER.
//
// Kalau hosting kamu DNS-nya normal (curl error 6 tidak muncul), baris
// CURLOPT_RESOLVE ini tidak mengganggu apa pun — curl otomatis tetap jalan
// seperti biasa.
// ===========================================================
define('RK_TG_IP_FALLBACKS', [
    'api.telegram.org'  => ['149.154.167.220', '149.154.175.50', '149.154.167.99'],
    'file.telegram.org' => ['149.154.167.220', '149.154.175.50'],
]);

function rk_tg_curl_init($url) {
    $ch = curl_init($url);
    $host = parse_url($url, PHP_URL_HOST);
    if ($host && isset(RK_TG_IP_FALLBACKS[$host])) {
        // curl (>=7.59) menerima beberapa IP dipisah koma dalam SATU entry
        // "host:port:ip1,ip2,ip3" — curl otomatis coba IP berikutnya kalau
        // IP pertama gagal dihubungi.
        $ips = implode(',', RK_TG_IP_FALLBACKS[$host]);
        curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:443:{$ips}"]);
    }
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    return $ch;
}

// ===========================================================
// Produk & Portofolio (dipakai di layanan.php, portofolio.php,
// dan tombol Pesan/Keranjang/Tanya di seluruh halaman)
// ===========================================================
define('PRODUCTS_FILE', DATA_DIR . '/products.json');
define('PORTFOLIO_FILE', DATA_DIR . '/portfolio.json');

function rk_get_products() {
    return rk_read_json(PRODUCTS_FILE);
}

function rk_get_portfolio() {
    return rk_read_json(PORTFOLIO_FILE);
}

function rk_save_portfolio($items) {
    return rk_write_json(PORTFOLIO_FILE, $items);
}

// Tambah 1 postingan portofolio baru (dipakai saat admin upload foto lewat
// bot Telegram dengan caption /portofolio ...). Return ID postingan baru.
function rk_add_portfolio_item($item) {
    $items = rk_get_portfolio();
    $item['id'] = rk_next_id($items);
    $items[] = $item;
    rk_save_portfolio($items);
    return $item['id'];
}

// ===========================================================
// Auto-Deteksi Link Testimoni dari Channel Testi
// Setiap kali ADA POSTINGAN BARU di Channel Testi (channel_post update,
// bot harus jadi admin channel supaya menerima update ini), kita cek
// caption-nya: kalau MENGANDUNG teks Label yang sama persis (case-
// insensitive) dengan Label salah satu postingan Portofolio, otomatis
// isi testimoni_link postingan itu dengan link ke post channel ini —
// tidak perlu /testimoni manual lagi untuk kasus ini.
// ===========================================================
function rk_build_channel_post_link($chatId, $messageId) {
    if (defined('TELEGRAM_TESTI_CHANNEL_USERNAME') && TELEGRAM_TESTI_CHANNEL_USERNAME !== '') {
        return 'https://t.me/' . ltrim(TELEGRAM_TESTI_CHANNEL_USERNAME, '@') . '/' . $messageId;
    }
    // Fallback link internal (perlu jadi anggota channel utk bisa buka),
    // dipakai kalau channel testi tidak punya username publik.
    $internal = preg_replace('/^-100/', '', (string)$chatId);
    return 'https://t.me/c/' . $internal . '/' . $messageId;
}

function rk_tg_process_channel_post($post) {
    $chatId = (string)($post['chat']['id'] ?? '');
    if ($chatId === '' || !defined('TELEGRAM_TESTI_CHANNEL_ID') || TELEGRAM_TESTI_CHANNEL_ID === '') return;
    if ($chatId !== (string)TELEGRAM_TESTI_CHANNEL_ID) return; // bukan post di channel testi kita

    $caption = trim($post['caption'] ?? $post['text'] ?? '');
    if ($caption === '') return;

    $messageId = $post['message_id'] ?? null;
    if (!$messageId) return;

    $link = rk_build_channel_post_link($chatId, $messageId);

    // Kumpulkan Label unik yang ada di Portofolio (yang belum kosong),
    // lalu cek satu-satu apakah Label itu disebut di caption post ini.
    $items = rk_get_portfolio();
    $updated = [];
    foreach ($items as &$p) {
        $label = trim($p['label'] ?? '');
        if ($label === '') continue;
        if (stripos($caption, $label) !== false) {
            $p['testimoni_link'] = $link;
            $updated[] = $p['id'];
        }
    }
    unset($p);

    if (!empty($updated)) {
        rk_save_portfolio($items);
        // Beri tahu admin biar tahu ada yang ke-update otomatis.
        if (defined('TELEGRAM_ADMIN_CHAT_ID') && TELEGRAM_ADMIN_CHAT_ID !== '') {
            $ids = implode(', #', $updated);
            rk_tg_send(TELEGRAM_ADMIN_CHAT_ID,
                "🔗 Link testimoni postingan Portofolio #{$ids} otomatis di-set ke post terbaru di Channel Testi (label cocok)."
            );
        }
    }
}

function rk_find_portfolio_item($id) {
    foreach (rk_get_portfolio() as $p) {
        if ((int)$p['id'] === (int)$id) return $p;
    }
    return null;
}

// Update sebagian field postingan portofolio yang SUDAH ADA (dipakai
// menu /settings -> Edit Portofolio). $fields hanya berisi key yang mau
// diubah (mis. ['image' => '...', 'title' => '...']).
function rk_update_portfolio_item($id, $fields) {
    $items = rk_get_portfolio();
    $found = false;
    foreach ($items as &$p) {
        if ((int)$p['id'] === (int)$id) {
            foreach ($fields as $k => $v) {
                if ($v !== null && $v !== '') $p[$k] = $v;
            }
            $found = true;
            break;
        }
    }
    unset($p);
    if ($found) rk_save_portfolio($items);
    return $found;
}

// Hapus 1 postingan portofolio (dan foto fisiknya di server) berdasarkan ID.
function rk_delete_portfolio_item($id) {
    $items = rk_get_portfolio();
    $kept = [];
    $deleted = null;
    foreach ($items as $p) {
        if ((int)$p['id'] === (int)$id) { $deleted = $p; continue; }
        $kept[] = $p;
    }
    if (!$deleted) return false;
    rk_save_portfolio($kept);
    if (!empty($deleted['image'])) {
        $imgPath = __DIR__ . '/' . ltrim($deleted['image'], '/');
        if (is_file($imgPath)) @unlink($imgPath);
    }
    return true;
}

// ===========================================================
// Upload Foto Portofolio LANGSUNG lewat Bot Telegram
// Alur: admin kirim FOTO ke bot (chat pribadi admin) dengan caption
// "/portofolio Judul | Harga | Kategori | Label" -> bot download foto
// itu dari server Telegram (pakai file_id), simpan ke assets/img/portofolio/,
// lalu tambahkan entry baru ke data/portfolio.json -> langsung tayang di
// halaman Portofolio website, tanpa perlu buka file manager hosting.
// ===========================================================
define('PORTFOLIO_IMG_DIR', __DIR__ . '/assets/img/portofolio');

// Ambil path file di server Telegram dari sebuah file_id (foto/dokumen).
function rk_tg_get_file_path($fileId) {
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '' || !$fileId) return null;
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/getFile?file_id=' . urlencode($fileId);
    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($res, true);
    return $decoded['result']['file_path'] ?? null;
}

// Download isi file (binary) dari server Telegram lewat file_path di atas.
function rk_tg_download_file($filePath) {
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '' || !$filePath) return null;
    $url = 'https://api.telegram.org/file/bot' . TELEGRAM_BOT_TOKEN . '/' . $filePath;
    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $bin = curl_exec($ch);
    curl_close($ch);
    return ($bin !== false && $bin !== null && strlen((string)$bin) > 0) ? $bin : null;
}

// Simpan binary foto ke folder publik assets/img/portofolio/ dan kembalikan
// path relatif (dipakai langsung sebagai field "image" di portfolio.json).
function rk_save_portfolio_image($bin, $ext) {
    if (!$bin) return null;
    $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$ext));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) $ext = 'jpg';
    if (!is_dir(PORTFOLIO_IMG_DIR)) @mkdir(PORTFOLIO_IMG_DIR, 0755, true);
    $filename = 'tg_' . time() . '_' . substr(md5($bin), 0, 8) . '.' . $ext;
    $path = PORTFOLIO_IMG_DIR . '/' . $filename;
    if (file_put_contents($path, $bin) === false) return null;
    return 'assets/img/portofolio/' . $filename;
}

// ===========================================================
// Live Chat <-> Telegram Bot
// Alur: pengunjung kirim pesan lewat widget chat di website ->
// diteruskan sebagai NOTIFIKASI ke chat Telegram admin (lengkap
// dengan username & isi percakapan) -> admin BALAS lewat PANEL
// ADMIN di website (bukan lewat Telegram) -> widget chat di
// website polling & menampilkan balasan itu. Pengunjung TIDAK
// perlu buka Telegram / DM sama sekali.
// ===========================================================
define('CHAT_DIR', DATA_DIR . '/chat');
define('CHAT_MAP_FILE', CHAT_DIR . '/map.json');

function rk_chat_session_file($session) {
    $session = preg_replace('/[^a-zA-Z0-9_\-]/', '', $session ?? '');
    if ($session === '') return null;
    return CHAT_DIR . '/session_' . $session . '.json';
}

function rk_chat_load($session) {
    $file = rk_chat_session_file($session);
    if (!$file) return null;
    return rk_read_json($file);
}

function rk_chat_append($session, $entry) {
    $file = rk_chat_session_file($session);
    if (!$file) return false;
    $data = rk_read_json($file);
    if (empty($data)) $data = ['session' => $session, 'name' => $entry['name'] ?? 'Pengunjung', 'messages' => []];
    $entry['time'] = time();
    $data['messages'][] = $entry;
    if (isset($entry['name']) && $entry['from'] === 'user') $data['name'] = $entry['name'];
    return rk_write_json($file, $data);
}

// Map: telegram message_id (pesan yang diteruskan ke admin) -> session id.
// Dipakai supaya saat admin nge-reply pesan itu di Telegram, kita tahu
// balasan itu untuk sesi/pengunjung yang mana.
function rk_chat_map_set($tgMessageId, $session) {
    $map = rk_read_json(CHAT_MAP_FILE);
    if (!is_array($map)) $map = [];
    $map[(string)$tgMessageId] = $session;
    // batasi ukuran map biar tidak membengkak selamanya
    if (count($map) > 500) $map = array_slice($map, -300, null, true);
    rk_write_json(CHAT_MAP_FILE, $map);
}

function rk_chat_map_get($tgMessageId) {
    $map = rk_read_json(CHAT_MAP_FILE);
    return $map[(string)$tgMessageId] ?? null;
}

// ===========================================================
// Live Chat LANGSUNG di Telegram (mode baru)
// Alur: pengunjung mengonfirmasi "Ya, saya punya Telegram" di widget ->
// diarahkan (redirect) ke link bot Telegram -> pengunjung chat LANGSUNG
// dengan bot itu -> bot.php meneruskan tiap pesan pengunjung ke chat
// pribadi admin (TELEGRAM_ADMIN_CHAT_ID) -> admin tinggal REPLY pesan
// itu di Telegram (bukan lewat Panel Admin) -> bot.php meneruskan
// balasan admin itu balik ke pengunjung. Map di bawah ini menghubungkan
// message_id pesan yang diteruskan ke admin -> chat_id pengunjung asli,
// supaya bot tahu balasan admin itu harus dikirim ke siapa.
// ===========================================================
define('TG_RELAY_MAP_FILE', CHAT_DIR . '/tg_relay_map.json');

function rk_tg_relay_map_set($adminMessageId, $customerChatId, $customerLabel = '') {
    $map = rk_read_json(TG_RELAY_MAP_FILE);
    if (!is_array($map)) $map = [];
    $map[(string)$adminMessageId] = ['chat_id' => (string)$customerChatId, 'name' => $customerLabel];
    // batasi ukuran map biar tidak membengkak selamanya
    if (count($map) > 500) $map = array_slice($map, -300, null, true);
    rk_write_json(TG_RELAY_MAP_FILE, $map);
}

function rk_tg_relay_map_get($adminMessageId) {
    $map = rk_read_json(TG_RELAY_MAP_FILE);
    return $map[(string)$adminMessageId] ?? null;
}

// Bangun link "https://t.me/<bot>?start=..." dipakai tombol "Ya, saya
// punya Telegram" di live chat website. $startParam opsional (mis. nama
// pengunjung) supaya bot bisa menyapa dengan nama itu di pesan pertama.
function rk_tg_bot_link($startParam = '') {
    if (!defined('TELEGRAM_BOT_USERNAME') || TELEGRAM_BOT_USERNAME === '') return '';
    $url = 'https://t.me/' . TELEGRAM_BOT_USERNAME;
    $startParam = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$startParam);
    if ($startParam !== '') $url .= '?start=' . rawurlencode(substr($startParam, 0, 60));
    return $url;
}

// Kirim ulang FOTO ke chat lain pakai file_id yang sudah ada di server
// Telegram (tanpa perlu download lalu upload ulang) — dipakai untuk
// meneruskan foto dari pengunjung ke admin, dan sebaliknya.
function rk_tg_send_photo_by_file_id($chatId, $fileId, $caption = '') {
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '' || !$chatId || !$fileId) return null;
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendPhoto';
    $payload = [
        'chat_id' => $chatId,
        'photo' => $fileId,
        'caption' => $caption,
        'parse_mode' => 'HTML',
    ];
    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    $res = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($res, true);
    return $decoded['result'] ?? null;
}

// ===========================================================
// Mode POLLING (alternatif dari webhook) — dipakai kalau hosting
// TIDAK punya HTTPS aktif (Telegram MEWAJIBKAN https:// untuk webhook,
// jadi di hosting http:// biasa, webhook TIDAK akan pernah bisa jalan).
// Sebagai gantinya, bot_poll.php dipanggil berkala oleh layanan cron
// eksternal gratis (mis. cron-job.org) lewat URL biasa (boleh http://),
// lalu SCRIPT KITA yang aktif menjemput pesan baru ke Telegram (curl
// ke api.telegram.org selalu https://, ini tidak masalah karena itu
// permintaan KELUAR dari server kita, bukan permintaan MASUK).
// ===========================================================
define('TG_OFFSET_FILE', CHAT_DIR . '/tg_update_offset.json');

function rk_tg_get_offset() {
    $data = rk_read_json(TG_OFFSET_FILE);
    return (int)($data['offset'] ?? 0);
}

function rk_tg_set_offset($offset) {
    rk_write_json(TG_OFFSET_FILE, ['offset' => (int)$offset]);
}

// Ambil update-update baru dari Telegram (short-poll, timeout 0 — cocok
// dipanggil sebentar-sebentar oleh cron, BUKAN long-polling yang nunggu lama).
function rk_tg_get_updates($offset, $limit = 20) {
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '') return [];
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN .
           '/getUpdates?offset=' . (int)$offset . '&limit=' . (int)$limit . '&timeout=0';
    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($res, true);
    return $decoded['result'] ?? [];
}

// ===========================================================
// Logika inti pemrosesan 1 update Telegram — dipakai BERSAMA oleh
// bot.php (mode webhook, butuh https://) dan bot_poll.php (mode polling,
// jalan juga di http:// biasa). Supaya perilaku bot selalu sama persis
// di kedua mode, dan tidak ada logika yang keduplikasi/berbeda.
// ===========================================================
function rk_tg_process_update($update) {
    if (isset($update['callback_query'])) {
        rk_tg_process_callback($update['callback_query']);
        return;
    }

    if (isset($update['channel_post'])) {
        rk_tg_process_channel_post($update['channel_post']);
        return;
    }

    $message = $update['message'] ?? null;
    if (!$message) return;

    $chatId = (string)($message['chat']['id'] ?? '');
    if ($chatId === '') return;

    $text = trim($message['text'] ?? '');
    $caption = trim($message['caption'] ?? '');
    $photo = $message['photo'] ?? null; // array ukuran foto, terbesar ada di elemen terakhir
    $document = $message['document'] ?? null;

    // =====================================================================
    // MENU /settings — pintu masuk utama untuk admin (semua fitur pengaturan
    // lewat tombol, bukan hafal perintah). Dicek paling awal supaya berlaku
    // di semua kondisi.
    // =====================================================================
    if ($chatId === (string)TELEGRAM_ADMIN_CHAT_ID) {
        if ($text === '/settings') {
            rk_clear_admin_state($chatId);
            rk_tg_send($chatId,
                "⚙️ <b>Menu Pengaturan RK Destu Store</b>\n\nPilih salah satu:",
                rk_settings_menu_markup()
            );
            return;
        }
        if ($text === '/batal') {
            rk_clear_admin_state($chatId);
            rk_tg_send($chatId, "Dibatalkan. Ketik /settings untuk buka menu lagi.");
            return;
        }

        // --- Kalau ada state /settings yang sedang menunggu input, proses di sini dulu ---
        $state = rk_get_admin_state($chatId);
        if ($state) {
            $action = $state['action'] ?? '';

            if ($action === 'await_logo_photo' && $photo) {
                $fileId = end($photo)['file_id'] ?? null;
                $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
                $bin = $filePath ? rk_tg_download_file($filePath) : null;
                if (!$bin) {
                    rk_tg_send($chatId, "❌ Gagal mengambil foto dari server Telegram. Coba kirim ulang, atau /batal.");
                    return;
                }
                $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
                $rel = rk_save_logo_image($bin, $ext);
                if (!$rel) {
                    rk_tg_send($chatId, "❌ Gagal menyimpan logo di server.");
                } else {
                    $old = rk_get_logo();
                    if ($old && !empty($old['image'])) {
                        $oldPath = __DIR__ . '/' . ltrim($old['image'], '/');
                        if (is_file($oldPath)) @unlink($oldPath);
                    }
                    rk_save_logo($rel);
                    rk_clear_admin_state($chatId);
                    rk_tg_send($chatId,
                        "✅ Logo website berhasil diganti &amp; langsung tayang di semua halaman.\n\nKetik /settings untuk pengaturan lain.",
                        rk_settings_back_markup([], 'settings_logo_menu')
                    );
                }
                return;
            }

            if ($action === 'await_portofolio_edit' && ($photo || $text !== '')) {
                $id = (int)($state['id'] ?? 0);
                $item = rk_find_portfolio_item($id);
                if (!$item) {
                    rk_clear_admin_state($chatId);
                    rk_tg_send($chatId, "❌ Postingan #{$id} sudah tidak ada (mungkin terhapus). Ketik /settings untuk mulai lagi.");
                    return;
                }
                $rest = trim($photo ? $caption : $text);
                $fields = [];
                if ($rest !== '') {
                    $parts = array_map('trim', explode('|', $rest));
                    if (($parts[0] ?? '') !== '') $fields['title'] = $parts[0];
                    if (($parts[1] ?? '') !== '') $fields['price'] = $parts[1];
                    if (($parts[2] ?? '') !== '') $fields['category'] = $parts[2];
                    if (($parts[3] ?? '') !== '') $fields['label'] = $parts[3];
                }
                if ($photo) {
                    $fileId = end($photo)['file_id'] ?? null;
                    $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
                    $bin = $filePath ? rk_tg_download_file($filePath) : null;
                    if (!$bin) {
                        rk_tg_send($chatId, "❌ Gagal mengambil foto dari server Telegram. Coba kirim ulang, atau /batal.");
                        return;
                    }
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
                    $rel = rk_save_portfolio_image($bin, $ext);
                    if (!$rel) {
                        rk_tg_send($chatId, "❌ Gagal menyimpan foto baru di server.");
                        return;
                    }
                    // Hapus file foto lama supaya tidak menumpuk sampah di server.
                    if (!empty($item['image'])) {
                        $oldPath = __DIR__ . '/' . ltrim($item['image'], '/');
                        if (is_file($oldPath)) @unlink($oldPath);
                    }
                    $fields['image'] = $rel;
                }
                rk_update_portfolio_item($id, $fields);
                rk_clear_admin_state($chatId);
                rk_tg_send($chatId,
                    "✅ Postingan #{$id} berhasil diperbarui.\n\nKetik /settings untuk pengaturan lain.",
                    rk_settings_back_markup([], 'settings_portofolio_menu')
                );
                return;
            }

            if ($action === 'await_portofolio_photo' && $photo) {
                $rest = trim($caption);
                $parts = array_map('trim', explode('|', $rest));
                $title = $parts[0] ?? '';
                if ($title === '') {
                    rk_tg_send($chatId, "Judul wajib diisi di caption foto. Contoh caption: <code>Retouch Produk | Rp15.000 | Foto | Terlaris</code>\n\nKirim ulang foto + caption, atau /batal.");
                    return;
                }
                $price = $parts[1] ?? '';
                $category = ($parts[2] ?? '') !== '' ? $parts[2] : 'Foto';
                $label = $parts[3] ?? '';
                $fileId = end($photo)['file_id'] ?? null;
                $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
                $bin = $filePath ? rk_tg_download_file($filePath) : null;
                if (!$bin) {
                    rk_tg_send($chatId, "❌ Gagal mengambil foto dari server Telegram. Coba kirim ulang, atau /batal.");
                    return;
                }
                $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
                $rel = rk_save_portfolio_image($bin, $ext);
                if (!$rel) {
                    rk_tg_send($chatId, "❌ Gagal menyimpan foto di server.");
                } else {
                    $newId = rk_add_portfolio_item([
                        'category' => $category, 'title' => $title, 'image' => $rel,
                        'fallback' => 'linear-gradient(155deg,#0a5cff,#38d6ff)',
                        'price' => $price, 'label' => $label, 'testimoni_link' => '',
                    ]);
                    rk_clear_admin_state($chatId);
                    rk_tg_send($chatId, "✅ Portofolio #{$newId} \"" . htmlspecialchars($title, ENT_QUOTES) . "\" berhasil ditambahkan &amp; langsung tayang.\n\nKetik /settings untuk pengaturan lain.");
                }
                return;
            }

            if ($action === 'await_slide_slot' && $text !== '') {
                $slot = (int) preg_replace('/[^0-9]/', '', $text);
                if ($slot < 1 || $slot > 20) {
                    rk_tg_send($chatId, "Ketik angka nomor slide saja (1-5), misal: <code>1</code>. Atau /batal.");
                    return;
                }
                rk_set_admin_state($chatId, ['action' => 'await_slide_photo', 'slot' => $slot]);
                rk_tg_send($chatId, "Oke, slide #{$slot}. Sekarang kirim FOTO-nya, caption boleh diisi <code>Judul | Subjudul</code> atau dikosongkan (teks lama dipertahankan).");
                return;
            }

            if ($action === 'await_slide_photo' && $photo) {
                $slot = (int) ($state['slot'] ?? 0);
                $parts = array_map('trim', explode('|', trim($caption)));
                $newTitle = $parts[0] ?? '';
                $newSubtitle = $parts[1] ?? '';
                $fileId = end($photo)['file_id'] ?? null;
                $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
                $bin = $filePath ? rk_tg_download_file($filePath) : null;
                if (!$bin) {
                    rk_tg_send($chatId, "❌ Gagal mengambil foto dari server Telegram. Coba kirim ulang, atau /batal.");
                    return;
                }
                $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
                $rel = rk_save_slide_image($bin, $ext);
                if (!$rel) {
                    rk_tg_send($chatId, "❌ Gagal menyimpan foto di server.");
                } else {
                    $slides = rk_get_slides();
                    $idx = $slot - 1;
                    if (isset($slides[$idx])) {
                        $slides[$idx]['image'] = $rel;
                        if ($newTitle !== '') $slides[$idx]['title'] = $newTitle;
                        if ($newSubtitle !== '') $slides[$idx]['subtitle'] = $newSubtitle;
                        $action2 = 'diperbarui';
                    } else {
                        $slides[$idx] = [
                            'image' => $rel, 'fallback' => 'linear-gradient(155deg,#0a5cff,#38d6ff)',
                            'title' => $newTitle !== '' ? $newTitle : 'RK Destu Store',
                            'subtitle' => $newSubtitle,
                        ];
                        ksort($slides);
                        $action2 = 'ditambahkan';
                    }
                    rk_save_slides(array_values($slides));
                    rk_clear_admin_state($chatId);
                    rk_tg_send($chatId, "✅ Slide #{$slot} berhasil {$action2} &amp; langsung tayang di Beranda.\n\nKetik /settings untuk pengaturan lain.");
                }
                return;
            }

            if ($action === 'await_testimoni_input' && $text !== '') {
                $parts = preg_split('/\s+/', $text, 2);
                $id = isset($parts[0]) ? (int)$parts[0] : null;
                $link = isset($parts[1]) ? trim($parts[1]) : '';
                if (!$id || $link === '') {
                    rk_tg_send($chatId, "Format: <code>ID LINK</code>, contoh: <code>1 https://t.me/testimonirkdestu/123</code>. Atau /batal.");
                    return;
                }
                $portfolio = rk_get_portfolio();
                $found = false;
                foreach ($portfolio as &$p) {
                    if ((int)$p['id'] === $id) { $p['testimoni_link'] = $link; $found = true; break; }
                }
                unset($p);
                if (!$found) {
                    rk_tg_send($chatId, "❌ Portofolio ID #{$id} tidak ditemukan. Coba lagi atau /batal.");
                    return;
                }
                rk_save_portfolio($portfolio);
                rk_clear_admin_state($chatId);
                rk_tg_send($chatId, "✅ Link testimoni untuk Portofolio #{$id} berhasil disimpan.\n\nKetik /settings untuk pengaturan lain.");
                return;
            }

            if ($action === 'await_restore_file' && $document) {
                $fileName = $document['file_name'] ?? '';
                if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'zip') {
                    rk_tg_send($chatId, "❌ File harus .zip hasil backup. Kirim ulang, atau /batal.");
                    return;
                }
                rk_tg_send($chatId, "⏳ Memulihkan data dari backup, mohon tunggu...");
                $fileId = $document['file_id'] ?? null;
                $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
                $bin = $filePath ? rk_tg_download_file($filePath) : null;
                if (!$bin) {
                    rk_tg_send($chatId, "❌ Gagal mengunduh file dari Telegram. Coba kirim ulang.");
                    return;
                }
                $result = rk_run_restore($bin);
                rk_clear_admin_state($chatId);
                rk_tg_send($chatId, $result['ok']
                    ? "✅ Restore selesai (" . htmlspecialchars($result['when'] ?? '', ENT_QUOTES) . ").\n\nKetik /settings untuk pengaturan lain."
                    : "❌ Restore gagal: " . htmlspecialchars($result['error'] ?? 'unknown', ENT_QUOTES));
                return;
            }
            // Kalau input tidak cocok dengan yang ditunggu (mis. admin malah ketik
            // command lain), biarkan lanjut ke pemrosesan normal di bawah.
        }
    }

    // =====================================================================
    // MODE ADMIN — pesan datang dari chat pribadi admin dengan bot
    // =====================================================================
    if ($chatId === (string)TELEGRAM_ADMIN_CHAT_ID) {

        // --- Admin REPLY salah satu pesan relay klien -> teruskan balasan ---
        $replyToId = $message['reply_to_message']['message_id'] ?? null;
        if ($replyToId) {
            $target = rk_tg_relay_map_get($replyToId);
            if ($target && !empty($target['chat_id'])) {
                if ($photo) {
                    $fileId = end($photo)['file_id'] ?? null;
                    if ($fileId) rk_tg_send_photo_by_file_id($target['chat_id'], $fileId, $caption);
                } elseif ($text !== '') {
                    rk_tg_send($target['chat_id'], $text);
                }
                return;
            }
            // Kalau reply-nya bukan ke pesan relay klien (mis. reply pesan lain
            // di chat admin), biarkan lanjut ke pengecekan perintah di bawah.
        }

        // --- Perintah: /backup (memicu backup manual, di luar jadwal cron) ---
        if ($text === '/backup') {
            rk_tg_send($chatId, "⏳ Membuat file backup, mohon tunggu...");
            $result = rk_run_backup();
            if ($result['ok']) {
                rk_tg_send($chatId, "✅ Backup manual selesai dikirim.");
            } else {
                rk_tg_send($chatId, "❌ Backup gagal: " . htmlspecialchars($result['error'] ?? 'unknown', ENT_QUOTES));
            }
            return;
        }

        // --- Restore: kirim FILE .zip (dokumen, bukan foto) dengan caption /restore ---
        $document = $message['document'] ?? null;
        if ($document && strpos($caption, '/restore') === 0) {
            $fileName = $document['file_name'] ?? '';
            if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'zip') {
                rk_tg_send($chatId, "❌ File harus berformat .zip (hasil dari /backup atau backup otomatis).");
                return;
            }
            rk_tg_send($chatId, "⏳ Memulihkan data dari backup, mohon tunggu...");
            $fileId = $document['file_id'] ?? null;
            $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
            $bin = $filePath ? rk_tg_download_file($filePath) : null;
            if (!$bin) {
                rk_tg_send($chatId, "❌ Gagal mengunduh file dari Telegram. Coba kirim ulang.");
                return;
            }
            $result = rk_run_restore($bin);
            if ($result['ok']) {
                rk_tg_send($chatId, "✅ Restore selesai. Data & foto sudah dipulihkan dari backup (" . htmlspecialchars($result['when'] ?? '', ENT_QUOTES) . ").");
            } else {
                rk_tg_send($chatId, "❌ Restore gagal: " . htmlspecialchars($result['error'] ?? 'unknown', ENT_QUOTES));
            }
            return;
        }

        // --- Upload foto portofolio langsung: kirim FOTO + caption /portofolio ---
        if ($photo && strpos($caption, '/portofolio') === 0) {
            $rest = trim(substr($caption, strlen('/portofolio')));
            $parts = array_map('trim', explode('|', $rest));
            $title = $parts[0] ?? '';
            $price = $parts[1] ?? '';
            $category = ($parts[2] ?? '') !== '' ? $parts[2] : 'Foto';
            $label = $parts[3] ?? '';

            if ($title === '') {
                rk_tg_send($chatId,
                    "Format salah. Kirim FOTO dengan caption seperti ini:\n\n".
                    "<code>/portofolio Retouch Produk | Rp15.000 | Foto | Terlaris</code>\n\n".
                    "Yang wajib cuma Judul. Harga, Kategori, dan Label boleh dikosongkan (pisahkan pakai tanda | )."
                );
            } else {
                $fileId = end($photo)['file_id'] ?? null;
                $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
                $bin = $filePath ? rk_tg_download_file($filePath) : null;

                if (!$bin) {
                    rk_tg_send($chatId, "❌ Gagal mengambil foto dari server Telegram. Coba kirim ulang.");
                } else {
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
                    $rel = rk_save_portfolio_image($bin, $ext);
                    if (!$rel) {
                        rk_tg_send($chatId, "❌ Gagal menyimpan foto di server (cek folder <code>assets/img/portofolio/</code> bisa ditulis PHP, permission 755/777).");
                    } else {
                        $newId = rk_add_portfolio_item([
                            'category' => $category,
                            'title' => $title,
                            'image' => $rel,
                            'fallback' => 'linear-gradient(155deg,#0a5cff,#38d6ff)',
                            'price' => $price,
                            'label' => $label,
                            'testimoni_link' => '',
                        ]);
                        rk_tg_send($chatId,
                            "✅ Portofolio #{$newId} \"" . htmlspecialchars($title, ENT_QUOTES) . "\" berhasil ditambahkan &amp; langsung tayang di halaman Portofolio website.\n\n".
                            "Mau pasang link testimoni untuk postingan ini? Ketik:\n<code>/testimoni {$newId} https://t.me/...</code>"
                        );
                    }
                }
            }
            return;
        }

        // --- Ganti/tambah foto slider Beranda: kirim FOTO + caption /slide ---
        // Format: /slide 1 | Judul Baru | Subjudul baru
        // Nomor 1..5 = urutan slide di Beranda (1 = paling pertama tampil).
        // Judul & subjudul BOLEH dikosongkan -> teks lama pada slide itu
        // dipertahankan (cuma foto yang diganti). Kalau nomor melebihi
        // jumlah slide yang ada, slide BARU akan ditambahkan di urutan itu.
        if ($photo && strpos($caption, '/slide') === 0) {
            $rest = trim(substr($caption, strlen('/slide')));
            $parts = array_map('trim', explode('|', $rest));
            $slotRaw = $parts[0] ?? '';
            $newTitle = $parts[1] ?? '';
            $newSubtitle = $parts[2] ?? '';
            $slot = (int) preg_replace('/[^0-9]/', '', $slotRaw);

            if ($slot < 1) {
                rk_tg_send($chatId,
                    "Format salah. Kirim FOTO dengan caption seperti ini:\n\n".
                    "<code>/slide 1 | Judul Baru | Subjudul baru</code>\n\n".
                    "Angka 1-5 = urutan slide di Beranda. Judul &amp; Subjudul boleh dikosongkan (biarkan teks lama, cuma ganti foto). Kalau nomor slide belum ada, slide baru akan dibuat."
                );
            } else {
                $fileId = end($photo)['file_id'] ?? null;
                $filePath = $fileId ? rk_tg_get_file_path($fileId) : null;
                $bin = $filePath ? rk_tg_download_file($filePath) : null;

                if (!$bin) {
                    rk_tg_send($chatId, "❌ Gagal mengambil foto dari server Telegram. Coba kirim ulang.");
                } else {
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
                    $rel = rk_save_slide_image($bin, $ext);
                    if (!$rel) {
                        rk_tg_send($chatId, "❌ Gagal menyimpan foto di server (cek folder <code>assets/img/slides/</code> bisa ditulis PHP).");
                    } else {
                        $slides = rk_get_slides();
                        $idx = $slot - 1; // slot 1 => index 0
                        if (isset($slides[$idx])) {
                            $slides[$idx]['image'] = $rel;
                            if ($newTitle !== '') $slides[$idx]['title'] = $newTitle;
                            if ($newSubtitle !== '') $slides[$idx]['subtitle'] = $newSubtitle;
                            $action = "diperbarui";
                        } else {
                            $slides[$idx] = [
                                'image' => $rel,
                                'fallback' => 'linear-gradient(155deg,#0a5cff,#38d6ff)',
                                'title' => $newTitle !== '' ? $newTitle : 'RK Destu Store',
                                'subtitle' => $newSubtitle !== '' ? $newSubtitle : '',
                            ];
                            ksort($slides);
                            $action = "ditambahkan";
                        }
                        rk_save_slides(array_values($slides));
                        rk_tg_send($chatId, "✅ Slide #{$slot} berhasil {$action} &amp; langsung tayang di halaman Beranda.");
                    }
                }
            }
            return;
        }

        // --- Perintah: /testimoni <id> <link> ---
        if (strpos($text, '/testimoni') === 0) {
            $parts = preg_split('/\s+/', $text, 3);
            $id = isset($parts[1]) ? (int)$parts[1] : null;
            $link = isset($parts[2]) ? trim($parts[2]) : '';

            if (!$id || $link === '') {
                rk_tg_send($chatId, "Format salah. Contoh:\n<code>/testimoni 1 https://t.me/testimonirkdestu/123</code>\n\nID lihat di admin panel atau di bawah tiap foto portofolio.");
            } else {
                $portfolio = rk_get_portfolio();
                $found = false;
                foreach ($portfolio as &$p) {
                    if ((int)$p['id'] === $id) {
                        $p['testimoni_link'] = $link;
                        $found = true;
                        break;
                    }
                }
                unset($p);
                if ($found) {
                    rk_save_portfolio($portfolio);
                    rk_tg_send($chatId, "✅ Link testimoni untuk portofolio #$id sudah disimpan.");
                } else {
                    rk_tg_send($chatId, "❌ Portofolio dengan ID #$id tidak ditemukan.");
                }
            }
            return;
        }

        // --- Perintah bantuan ---
        if ($text === '/start' || $text === '/help') {
            rk_tg_send($chatId,
                "🤖 <b>Bot RK Destu Store</b>\n\n".
                "⚙️ Cara termudah: ketik <b>/settings</b> untuk buka menu tombol — tambah/EDIT/HAPUS foto portofolio, ganti slider beranda, ganti logo website, atur testimoni, backup & restore, semua tinggal pilih & ikuti instruksinya. Ada tombol 🔙 Kembali di setiap langkah.\n\n".
                "🔗 Link testimoni juga bisa terisi OTOMATIS: kalau Label postingan Portofolio sama dengan yang disebut di caption post Channel Testi, bot otomatis mengisikan linknya.\n\n".
                "• Pengunjung yang punya Telegram akan chat <b>langsung ke sini</b>. Setiap pesan mereka masuk sebagai pesan baru dari bot — cukup <b>tekan &amp; tahan pesan itu → Reply</b>, ketik balasanmu, kirim. Balasanmu otomatis diteruskan ke Telegram klien.\n".
                "• Pengunjung yang TIDAK punya Telegram tetap dibalas lewat <b>Panel Admin</b> di website (bagian Live Chat), seperti biasa.\n".
                "• Bukti transfer (foto + nominal + nama toko) otomatis diteruskan ke Channel Testi, dan menunggu approve kamu di Panel Admin sebelum tayang di halaman Testimoni.\n\n".
                "<b>Perintah manual (kalau lebih suka ketik langsung, tanpa menu):</b>\n".
                "• <code>/portofolio Judul | Harga | Kategori | Label</code> (kirim bareng FOTO)\n".
                "• <code>/slide 1 | Judul | Subjudul</code> (kirim bareng FOTO)\n".
                "• <code>/testimoni ID LINK</code>\n".
                "• <code>/backup</code>\n".
                "• Kirim FILE .zip + caption <code>/restore</code>\n"
            );
        }

        return;
    }

    // =====================================================================
    // MODE KLIEN — pesan datang dari pengunjung yang chat LANGSUNG ke bot
    // (mereka sampai di sini karena klik "Ya, saya punya Telegram" di
    // widget live chat website, yang mengarahkan mereka ke link bot ini)
    // =====================================================================

    $from = $message['from'] ?? [];
    $firstName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
    $username = trim((string)($from['username'] ?? ''));
    $label = $firstName !== '' ? $firstName : ($username !== '' ? '@' . $username : 'Pengunjung Website');

    // --- /start (termasuk /start dengan parameter nama dari link website) ---
    if (strpos($text, '/start') === 0) {
        $startPayload = trim(substr($text, strlen('/start')));
        $greetName = $startPayload !== '' ? preg_replace('/[_\-]+/', ' ', $startPayload) : '';

        // Tombol "🛍 Buka Toko" -> buka website ini sebagai Mini App di
        // dalam Telegram (butuh RK_WEBAPP_URL terisi & https://).
        $startMarkup = null;
        if (defined('RK_WEBAPP_URL') && RK_WEBAPP_URL !== '' && stripos(RK_WEBAPP_URL, 'https://') === 0) {
            $startMarkup = [
                'inline_keyboard' => [
                    [['text' => '🛍 Buka Toko', 'web_app' => ['url' => RK_WEBAPP_URL]]],
                ],
            ];
        }

        rk_tg_send($chatId,
            "👋 Halo" . ($greetName !== '' ? ' ' . htmlspecialchars($greetName, ENT_QUOTES) : '') . "! Kamu terhubung dengan <b>RK Destu Store</b>.\n\n" .
            "Silakan tulis pesanmu atau kirim foto (mis. bukti transfer) langsung di chat ini — admin kami akan membalas di sini juga. 🙌" .
            ($startMarkup ? "\n\nAtau lihat-lihat dulu katalog & harga di toko kami:" : ""),
            $startMarkup
        );

        // Kirim juga notifikasi singkat ke admin supaya tahu ada klien baru masuk.
        rk_tg_send(TELEGRAM_ADMIN_CHAT_ID,
            "🆕 <b>Klien baru dari website</b> membuka chat Telegram: " .
            htmlspecialchars($label, ENT_QUOTES) . ($username !== '' ? " (@{$username})" : '') .
            "\n<i>Tunggu pesan pertamanya, lalu balas dengan Reply seperti biasa.</i>"
        );

        return;
    }

    // Tidak ada teks maupun foto (mis. stiker/voice) -> abaikan saja
    if ($text === '' && !$photo) return;

    $header = "💬 <b>Klien Telegram:</b> " . htmlspecialchars($label, ENT_QUOTES) .
              ($username !== '' ? " (@{$username})" : '') .
              "\n<i>Balas (Reply) pesan ini untuk menjawab langsung ke klien.</i>\n\n";

    if ($photo) {
        $fileId = end($photo)['file_id'] ?? null;
        if ($fileId) {
            $sent = rk_tg_send_photo_by_file_id(TELEGRAM_ADMIN_CHAT_ID, $fileId, $header . ($caption !== '' ? htmlspecialchars($caption, ENT_QUOTES) : ''));
            if ($sent && !empty($sent['message_id'])) {
                rk_tg_relay_map_set($sent['message_id'], $chatId, $label);
            }

            // Deteksi otomatis bukti transfer (foto + caption sebut nominal & nama toko),
            // sama seperti di live chat website -> auto-post ke Channel Testi.
            if ($caption !== '' && rk_detect_payment_proof($caption) &&
                defined('TELEGRAM_TESTI_CHANNEL_ID') && TELEGRAM_TESTI_CHANNEL_ID !== '') {
                $testiCaption = "🧾 <b>Bukti Transfer Terdeteksi (via Telegram)</b>\n👤 " .
                                htmlspecialchars($label, ENT_QUOTES) .
                                ($username !== '' ? " (@{$username})" : '') .
                                "\n\n" . htmlspecialchars($caption, ENT_QUOTES) .
                                "\n\n<i>Ini otomatis tayang di Channel Testi. Kalau perlu ditampilkan juga di halaman Testimoni website, tambahkan manual lewat Panel Admin.</i>";
                rk_tg_send_photo_by_file_id(TELEGRAM_TESTI_CHANNEL_ID, $fileId, $testiCaption);
            }
        }
    } elseif ($text !== '') {
        $sent = rk_tg_send(TELEGRAM_ADMIN_CHAT_ID, $header . htmlspecialchars($text, ENT_QUOTES));
        if ($sent && !empty($sent['message_id'])) {
            rk_tg_relay_map_set($sent['message_id'], $chatId, $label);
        }
    }
}

// ===========================================================
// Bukti Transfer (foto) dari Live Chat -> antrian review admin
//   -> auto-post ke Channel Telegram Testi (otomatis, begitu
//      terdeteksi nominal + nama toko)
//   -> tayang di halaman Testimoni website (SETELAH admin approve
//      manual lewat panel admin)
//
// CATATAN PENTING soal "deteksi": ini BUKAN OCR (sistem tidak
// membaca tulisan di dalam foto/gambar). Yang dicek adalah TEKS
// yang diketik pengunjung bersamaan dengan foto (mis. pengunjung
// ketik "tf ke RKDESTU STORE 150rb" lalu upload foto). Hosting
// gratis tidak mendukung OCR sungguhan (butuh API berbayar seperti
// Google Vision) — kalau nanti mau upgrade ke OCR asli, fungsi
// rk_detect_payment_proof() di bawah ini titik yang perlu diganti.
// ===========================================================
define('CHAT_UPLOAD_DIR', CHAT_DIR . '/uploads');
define('TESTI_QUEUE_FILE', DATA_DIR . '/testi_queue.json');
define('TESTI_PUBLIC_DIR', __DIR__ . '/assets/img/testimoni');

function rk_get_testi_queue() {
    return rk_read_json(TESTI_QUEUE_FILE);
}

function rk_save_testi_queue($items) {
    return rk_write_json(TESTI_QUEUE_FILE, $items);
}

// Simpan foto yang diupload pengunjung (format data URI base64 dari browser).
// Disimpan di folder TERPROTEKSI (data/chat/uploads, sudah "Require all denied"
// lewat .htaccess) supaya bukti TF tidak bisa diakses lewat URL langsung
// sebelum di-approve admin. Return nama file saja (bukan path lengkap).
function rk_save_chat_image($session, $dataUri) {
    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', (string)$dataUri, $m)) return null;
    $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
    $bin = base64_decode($m[2]);
    if ($bin === false || $bin === '' || strlen($bin) > 6 * 1024 * 1024) return null; // maks ~6MB

    if (!is_dir(CHAT_UPLOAD_DIR)) @mkdir(CHAT_UPLOAD_DIR, 0755, true);
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $session) . '_' . time() . '_' . substr(md5($bin), 0, 8) . '.' . $ext;
    $path = CHAT_UPLOAD_DIR . '/' . $filename;
    if (file_put_contents($path, $bin) === false) return null;
    return $filename;
}

// Deteksi sederhana berbasis teks (lihat catatan besar di atas).
// Deteksi "bukti transfer": diberi teks pesan saat ini + beberapa pesan
// TEKS terakhir di sesi yang sama sbg konteks (supaya tetap terdeteksi
// walau nominal/kata "transfer" ditulis di pesan TERPISAH dari foto,
// yang ternyata adalah pola paling umum dilakukan pengunjung).
//
// PERBAIKAN: sebelumnya WAJIB nama toko (RK_STORE_NAME) disebut ulang
// persis di pesan yang sama dengan foto -> di praktiknya pengunjung
// hampir tidak pernah mengetik ulang nama toko saat memang sedang chat
// DI WEBSITE toko itu sendiri, jadi bukti TF nyaris tidak pernah
// terdeteksi. Sekarang nama toko jadi SINYAL TAMBAHAN (bonus), bukan
// syarat wajib — cukup ada sinyal nominal ATAU kata kunci transfer.
function rk_detect_payment_proof($text, $recentContext = '') {
    $combined = trim((string)$text . ' ' . (string)$recentContext);
    if ($combined === '') return false;

    $hasNominal = (bool)preg_match('/rp\.?\s?[\d.,]{4,}|\b\d{1,3}([.,]\d{3}){1,}\b|\b\d{4,}\s?(rb|k|ribu|jt|juta)?\b/i', $combined);
    $hasKeyword = (bool)preg_match('/\b(transfer|tf|bayar|lunas|pembayaran|bukti\s?(tf|bayar|transfer)?)\b/i', $combined);

    return $hasNominal || $hasKeyword;
}

function rk_add_testi_queue($entry) {
    $queue = rk_get_testi_queue();
    $entry['id'] = rk_next_id($queue);
    $entry['detected_at'] = date('Y-m-d H:i');
    $queue[] = $entry;
    rk_save_testi_queue($queue);
    return $entry['id'];
}

function rk_remove_testi_queue($id) {
    $queue = rk_get_testi_queue();
    $queue = array_values(array_filter($queue, fn($q) => (int)($q['id'] ?? 0) !== (int)$id));
    rk_save_testi_queue($queue);
}

function rk_get_testi_queue_item($id) {
    foreach (rk_get_testi_queue() as $q) {
        if ((int)($q['id'] ?? 0) === (int)$id) return $q;
    }
    return null;
}

// Pindahkan foto dari folder privat (data/chat/uploads) ke folder publik
// (assets/img/testimoni) — HANYA dipanggil saat admin approve manual.
function rk_publish_proof_image($filename) {
    $src = CHAT_UPLOAD_DIR . '/' . basename($filename);
    if (!file_exists($src)) return null;
    if (!is_dir(TESTI_PUBLIC_DIR)) @mkdir(TESTI_PUBLIC_DIR, 0755, true);
    $dest = TESTI_PUBLIC_DIR . '/' . basename($filename);
    if (!copy($src, $dest)) return null;
    return 'assets/img/testimoni/' . basename($filename);
}

// Ambil beberapa pesan teks terakhir dari sebuah sesi chat (untuk konteks
// caption saat auto-post ke channel, dan untuk ditampilkan admin saat review).
function rk_chat_recent_text($session, $limit = 8) {
    $data = rk_chat_load($session);
    if (!$data || empty($data['messages'])) return '';
    $msgs = array_slice($data['messages'], -$limit);
    $lines = [];
    foreach ($msgs as $m) {
        if (($m['type'] ?? 'text') !== 'text' || trim($m['text'] ?? '') === '') continue;
        $who = ($m['from'] ?? '') === 'admin' ? 'Admin' : ($m['name'] ?? 'Pengunjung');
        $lines[] = "{$who}: " . $m['text'];
    }
    return implode("\n", $lines);
}

// --- Kirim FOTO ke Telegram (dipakai untuk auto-post ke channel testi) ---
function rk_tg_send_photo($chatId, $photoPath, $caption = '') {
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '' || !$chatId) return null;
    if (!file_exists($photoPath)) return null;
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendPhoto';
    $payload = [
        'chat_id' => $chatId,
        'caption' => $caption,
        'parse_mode' => 'HTML',
        'photo' => new CURLFile($photoPath),
    ];
    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($res, true);
    return $decoded['result'] ?? null;
}

// --- Telegram Bot API ---
function rk_tg_send($chatId, $text, $replyMarkup = null) {
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '' || !$chatId) return null;
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
    ];
    if ($replyMarkup) $payload['reply_markup'] = json_encode($replyMarkup);

    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $res = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($res, true);
    return $decoded['result'] ?? null;
}

// ===========================================================
// BACKUP & RESTORE — dipakai bareng oleh:
//  - backup.php (dijalankan Railway Cron Service terjadwal, lihat
//    README-RAILWAY.md)
//  - perintah manual /backup dan /restore lewat Telegram (lihat
//    rk_tg_process_update() di atas)
//
// Yang di-backup: seluruh folder data/ (semua .json + data/chat/uploads,
// tempat offset bot & pemetaan chat disimpan) DAN foto-foto yang sudah
// diupload (assets/img/portofolio, assets/img/testimoni) — karena di
// Railway folder-folder inilah yang perlu PERSISTENT VOLUME (lihat
// Dockerfile & README-RAILWAY.md), backup ini jaga-jaga kalau Volume
// bermasalah atau kamu ingin pindah/kloning environment.
// ===========================================================

define('RK_BACKUP_PATHS', [
    'data'                    => DATA_DIR,
    'assets/img/portofolio'   => __DIR__ . '/assets/img/portofolio',
    'assets/img/testimoni'    => __DIR__ . '/assets/img/testimoni',
    'assets/img/slides'       => __DIR__ . '/assets/img/slides',
    'assets/img/logo'         => __DIR__ . '/assets/img/logo',
]);

function rk_zip_add_dir($zip, $realDir, $zipDir) {
    if (!is_dir($realDir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($realDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = $zipDir . '/' . substr($item->getPathname(), strlen($realDir) + 1);
        if ($item->isDir()) {
            $zip->addEmptyDir($rel);
        } else {
            $zip->addFile($item->getPathname(), $rel);
        }
    }
}

// Membuat file zip backup di disk, kirim ke Telegram (chat backup),
// lalu hapus file sementara itu. Return ['ok'=>bool, 'error'=>string?].
function rk_run_backup() {
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'Ekstensi PHP zip tidak aktif di server.'];
    }
    $chatId = defined('TELEGRAM_BACKUP_CHAT_ID') && TELEGRAM_BACKUP_CHAT_ID !== ''
        ? TELEGRAM_BACKUP_CHAT_ID
        : (defined('TELEGRAM_ADMIN_CHAT_ID') ? TELEGRAM_ADMIN_CHAT_ID : '');
    if ($chatId === '') {
        return ['ok' => false, 'error' => 'TELEGRAM_BACKUP_CHAT_ID / TELEGRAM_ADMIN_CHAT_ID belum diisi.'];
    }

    $stamp = date('Ymd_His');
    $zipPath = sys_get_temp_dir() . "/rkdestu_backup_{$stamp}.zip";

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['ok' => false, 'error' => 'Gagal membuat file zip sementara.'];
    }
    foreach (RK_BACKUP_PATHS as $zipDir => $realDir) {
        rk_zip_add_dir($zip, $realDir, $zipDir);
    }
    // Simpan penanda waktu di dalam zip, dipakai saat restore untuk konfirmasi.
    $zip->addFromString('backup_meta.json', json_encode([
        'created_at' => date('c'),
        'created_at_wib' => date('Y-m-d H:i:s', time() + 7 * 3600) . ' WIB',
    ], JSON_PRETTY_PRINT));
    $zip->close();

    $caption = "🗄 <b>Backup Otomatis RK Destu Store</b>\n" . date('Y-m-d H:i:s', time() + 7 * 3600) . " WIB\n\n".
               "Untuk restore: forward/kirim file ini ke bot dengan caption <code>/restore</code>.";
    $sent = rk_tg_send_document($chatId, $zipPath, $caption);
    @unlink($zipPath);

    if (!$sent) {
        return ['ok' => false, 'error' => 'Gagal mengirim file zip ke Telegram (cek TELEGRAM_BACKUP_CHAT_ID & bot punya akses ke chat itu).'];
    }
    return ['ok' => true];
}

// Menerima ISI BINARY file zip (hasil download dari Telegram), lalu
// menimpa folder data/ + foto dengan isi zip tsb. HATI-HATI: ini
// operasi MENIMPA, dipanggil hanya dari chat admin (sudah dicek di
// rk_tg_process_update sebelum memanggil fungsi ini).
function rk_run_restore($zipBinary) {
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'Ekstensi PHP zip tidak aktif di server.'];
    }
    $tmpZip = sys_get_temp_dir() . '/rkdestu_restore_' . uniqid() . '.zip';
    if (file_put_contents($tmpZip, $zipBinary) === false) {
        return ['ok' => false, 'error' => 'Gagal menyimpan file zip sementara di server.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpZip) !== true) {
        @unlink($tmpZip);
        return ['ok' => false, 'error' => 'File bukan zip yang valid.'];
    }

    $meta = null;
    $metaRaw = $zip->getFromName('backup_meta.json');
    if ($metaRaw !== false) $meta = json_decode($metaRaw, true);

    $extractTo = sys_get_temp_dir() . '/rkdestu_restore_extract_' . uniqid();
    @mkdir($extractTo, 0755, true);
    $zip->extractTo($extractTo);
    $zip->close();
    @unlink($tmpZip);

    // Salin tiap folder hasil extract ke lokasi aslinya, menimpa yang lama.
    foreach (RK_BACKUP_PATHS as $zipDir => $realDir) {
        $src = $extractTo . '/' . $zipDir;
        if (!is_dir($src)) continue;
        if (!is_dir($realDir)) @mkdir($realDir, 0755, true);
        rk_copy_dir_overwrite($src, $realDir);
    }

    rk_rrmdir($extractTo);

    return ['ok' => true, 'when' => $meta['created_at_wib'] ?? 'tidak diketahui'];
}

function rk_copy_dir_overwrite($src, $dst) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $target = $dst . '/' . substr($item->getPathname(), strlen($src) + 1);
        if ($item->isDir()) {
            if (!is_dir($target)) @mkdir($target, 0755, true);
        } else {
            @copy($item->getPathname(), $target);
        }
    }
}

function rk_rrmdir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($dir);
}

// Kirim dokumen (file apa saja, di sini dipakai untuk .zip backup) ke
// Telegram lewat sendDocument (multipart upload dari file di disk).
function rk_tg_send_document($chatId, $filePath, $caption = '') {
    if (!TELEGRAM_BOT_TOKEN || !file_exists($filePath)) return null;
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendDocument";
    $ch = rk_tg_curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'chat_id' => $chatId,
        'caption' => $caption,
        'parse_mode' => 'HTML',
        'document' => new CURLFile($filePath, 'application/zip', basename($filePath)),
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $res = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode($res, true);
    return $decoded['result'] ?? null;
}

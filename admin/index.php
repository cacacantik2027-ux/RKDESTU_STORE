<?php
session_start();
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/config.php';

$msg = '';

// --- LOGIN ---
if (isset($_POST['login'])) {
    if (hash_equals(ADMIN_PASSWORD, $_POST['password'] ?? '')) {
        $_SESSION['rk_admin'] = true;
    } else {
        $msg = 'Password salah.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$isLoggedIn = !empty($_SESSION['rk_admin']);

// --- ACTIONS (hanya kalau sudah login) ---
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['approve_id'])) {
        $id = (int)$_POST['approve_id'];
        $pending = rk_get_pending();
        $approved = rk_get_approved();
        $remaining = [];
        foreach ($pending as $p) {
            if ($p['id'] == $id) { $approved[] = $p; }
            else { $remaining[] = $p; }
        }
        rk_write_json(PENDING_FILE, $remaining);
        rk_write_json(APPROVED_FILE, $approved);
        $msg = 'Testimoni disetujui dan tayang di halaman testimoni.';
    }

    if (isset($_POST['reject_id'])) {
        $id = (int)$_POST['reject_id'];
        $pending = array_values(array_filter(rk_get_pending(), fn($p) => $p['id'] != $id));
        rk_write_json(PENDING_FILE, $pending);
        $msg = 'Testimoni ditolak dan dihapus.';
    }

    if (isset($_POST['delete_approved_id'])) {
        $id = (int)$_POST['delete_approved_id'];
        $approved = array_values(array_filter(rk_get_approved(), fn($a) => $a['id'] != $id));
        rk_write_json(APPROVED_FILE, $approved);
        $msg = 'Testimoni dihapus dari halaman publik.';
    }

    // --- Balas live chat lewat panel admin (bukan Telegram) ---
    if (isset($_POST['reply_session'])) {
        $session = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['reply_session']);
        $replyText = trim($_POST['reply_text'] ?? '');
        if ($session !== '' && $replyText !== '') {
            rk_chat_append($session, ['from' => 'admin', 'text' => $replyText]);
            $msg = 'Balasan terkirim ke pengunjung.';
        }
    }

    // --- Antrian bukti TF: setujui -> foto jadi publik + masuk testimoni ---
    if (isset($_POST['approve_proof_id'])) {
        $id = (int)$_POST['approve_proof_id'];
        $item = rk_get_testi_queue_item($id);
        if ($item) {
            $publicPath = rk_publish_proof_image($item['image'] ?? '');
            $approved = rk_get_approved();
            $approved[] = [
                'id' => rk_next_id(array_merge($approved, rk_get_pending())),
                'name' => $item['name'] ?? 'Pengunjung',
                'comment' => $item['caption'] !== '' ? $item['caption'] : 'Bukti transfer — pembelian terverifikasi.',
                'rating' => 5,
                'photo' => $publicPath,
                'submitted_at' => date('Y-m-d H:i'),
            ];
            rk_write_json(APPROVED_FILE, $approved);
            rk_remove_testi_queue($id);
            $msg = 'Bukti transfer disetujui dan tayang di halaman Testimoni.';
        }
    }

    if (isset($_POST['reject_proof_id'])) {
        $id = (int)$_POST['reject_proof_id'];
        rk_remove_testi_queue($id);
        $msg = 'Bukti transfer ditolak dan dihapus dari antrian.';
    }

    if (isset($_POST['manual_add'])) {
        $name = rk_clean($_POST['name'] ?? '');
        $comment = rk_clean($_POST['comment'] ?? '');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        if ($name !== '' && $comment !== '') {
            $approved = rk_get_approved();
            $approved[] = [
                'id' => rk_next_id(array_merge($approved, rk_get_pending())),
                'name' => $name,
                'comment' => $comment,
                'rating' => $rating,
                'submitted_at' => date('Y-m-d H:i'),
            ];
            rk_write_json(APPROVED_FILE, $approved);
            $msg = 'Testimoni manual ditambahkan.';
        }
    }
}

$pendingList = $isLoggedIn ? rk_get_pending() : [];
$approvedList = $isLoggedIn ? rk_get_approved() : [];
$portfolioList = $isLoggedIn ? rk_get_portfolio() : [];
$testiQueue = $isLoggedIn ? rk_get_testi_queue() : [];

// Live chat: baca semua sesi tersimpan supaya admin bisa lihat percakapan
// penuh dan membalas langsung lewat panel ini (lihat handler 'reply_session').
$chatSessions = [];
if ($isLoggedIn) {
    foreach (glob(CHAT_DIR . '/session_*.json') as $file) {
        $d = rk_read_json($file);
        if (!empty($d['messages'])) {
            $last = end($d['messages']);
            $chatSessions[] = [
                'session' => $d['session'] ?? basename($file),
                'name' => $d['name'] ?? 'Pengunjung',
                'last_text' => $last['text'] ?? '',
                'last_from' => $last['from'] ?? 'user',
                'last_time' => $last['time'] ?? 0,
                'count' => count($d['messages']),
            ];
        }
    }
    usort($chatSessions, fn($a, $b) => $b['last_time'] <=> $a['last_time']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Testimoni — RK Destu Store</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  body { background: var(--bg-alt); }
  .admin-wrap { max-width: 880px; margin: 0 auto; padding: 60px 24px; }
  .admin-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 28px; margin-bottom: 22px; }
  .admin-card h2 { font-size: 18px; margin-bottom: 4px; }
  .admin-card .hint { color: var(--ink-faint); font-size: 13px; margin-bottom: 18px; }
  .login-box { max-width: 360px; margin: 100px auto; }
  .row-item { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; padding: 16px 0; border-top: 1px solid var(--line-soft); }
  .row-item:first-of-type { border-top: none; }
  .row-item p { margin: 4px 0; font-size: 14.2px; color: var(--ink-soft); max-width: 480px; }
  .row-item .meta { font-family: var(--font-mono); font-size: 12px; color: var(--ink-faint); }
  .row-actions { display: flex; gap: 8px; flex-shrink: 0; }
  .row-actions button { border: none; border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; }
  .btn-approve { background: #14804a; color: #fff; }
  .btn-reject { background: #fdeceb; color: #b3261e; }
  input, textarea, select { font-family: var(--font-body); padding: 11px 13px; border-radius: 9px; border: 1px solid var(--line); width: 100%; margin-top: 6px; margin-bottom: 14px; background: var(--bg); }
  label { font-size: 13px; font-weight: 600; }
  .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
  .empty-note { color: var(--ink-faint); font-size: 14px; }
  .stars { color: var(--blue); letter-spacing: 1px; font-size: 13px; }
</style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
  <div class="login-box">
    <div class="admin-card">
      <h2>Login Admin</h2>
      <p class="hint">Khusus pengelola RK Destu Store.</p>
      <?php if ($msg): ?><p style="color:#b3261e;font-size:13.5px;"><?= rk_clean($msg) ?></p><?php endif; ?>
      <form method="POST">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" name="login" value="1" class="btn btn-primary" style="width:100%;justify-content:center;">Masuk</button>
      </form>
    </div>
  </div>

<?php else: ?>
  <div class="admin-wrap">
    <div class="top-bar">
      <div>
        <span class="eyebrow">Panel Admin</span>
        <h1 style="font-size:24px;margin-top:8px;">Kelola Testimoni</h1>
      </div>
      <a href="?logout=1" class="btn btn-ghost">Keluar</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-ok" style="margin-bottom:20px;"><?= rk_clean($msg) ?></div><?php endif; ?>

    <div class="admin-card">
      <h2>Menunggu Persetujuan (<?= count($pendingList) ?>)</h2>
      <p class="hint">Testimoni kiriman pelanggan lewat halaman publik.</p>
      <?php if (empty($pendingList)): ?>
        <p class="empty-note">Tidak ada testimoni yang menunggu.</p>
      <?php else: foreach ($pendingList as $p): ?>
        <div class="row-item">
          <div>
            <div class="stars"><?= str_repeat('★', (int)$p['rating']) . str_repeat('☆', 5 - (int)$p['rating']) ?></div>
            <p>&ldquo;<?= rk_clean($p['comment']) ?>&rdquo;</p>
            <div class="meta"><?= rk_clean($p['name']) ?> · <?= rk_clean($p['submitted_at'] ?? '') ?></div>
          </div>
          <div class="row-actions">
            <form method="POST"><input type="hidden" name="approve_id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn-approve">Setujui</button></form>
            <form method="POST"><input type="hidden" name="reject_id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn-reject">Tolak</button></form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="admin-card">
      <h2>Tambah Testimoni Manual</h2>
      <p class="hint">Untuk testimoni yang kamu dapat langsung dari chat/DM pelanggan.</p>
      <form method="POST">
        <label>Nama</label>
        <input type="text" name="name" maxlength="60" required>
        <label>Rating</label>
        <select name="rating">
          <option value="5">★★★★★ (5)</option>
          <option value="4">★★★★☆ (4)</option>
          <option value="3">★★★☆☆ (3)</option>
          <option value="2">★★☆☆☆ (2)</option>
          <option value="1">★☆☆☆☆ (1)</option>
        </select>
        <label>Komentar</label>
        <textarea name="comment" maxlength="600" required style="min-height:90px;"></textarea>
        <button type="submit" name="manual_add" value="1" class="btn btn-primary">Tambahkan</button>
      </form>
    </div>

    <div class="admin-card">
      <h2>Live Chat — Percakapan Pengunjung (<?= count($chatSessions) ?>)</h2>
      <p class="hint">Notifikasi otomatis masuk ke Telegram admin, tapi <strong>balas di sini</strong> lewat Panel Admin — bukan lewat Telegram.</p>
      <?php if (empty($chatSessions)): ?>
        <p class="empty-note">Belum ada percakapan live chat.</p>
      <?php else: foreach ($chatSessions as $c):
        $full = rk_chat_load($c['session']);
        $msgs = $full['messages'] ?? [];
      ?>
        <details class="row-item" style="display:block;">
          <summary style="cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:16px;">
            <div>
              <div class="meta"><?= rk_clean($c['name']) ?> · <?= (int)$c['count'] ?> pesan · <?= $c['last_time'] ? date('Y-m-d H:i', $c['last_time']) : '' ?></div>
              <p><?= $c['last_from'] === 'admin' ? '<strong>Kamu:</strong> ' : '' ?><?= rk_clean($c['last_text']) ?></p>
            </div>
          </summary>
          <div style="margin-top:14px;padding:14px;background:var(--bg-alt);border-radius:10px;max-height:280px;overflow-y:auto;">
            <?php foreach ($msgs as $m): ?>
              <div style="margin-bottom:10px;font-size:13.5px;">
                <strong><?= $m['from'] === 'admin' ? 'Kamu' : rk_clean($m['name'] ?? 'Pengunjung') ?>:</strong>
                <?php if (($m['type'] ?? 'text') === 'image'): ?>
                  <br><img src="proof_image.php?file=<?= urlencode($m['image']) ?>" alt="Foto" style="max-width:220px;border-radius:8px;margin-top:4px;display:block;">
                  <?php if (!empty($m['text'])): ?><span><?= rk_clean($m['text']) ?></span><?php endif; ?>
                <?php else: ?>
                  <?= rk_clean($m['text'] ?? '') ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <form method="POST" style="margin-top:10px;display:flex;gap:8px;">
            <input type="hidden" name="reply_session" value="<?= rk_clean($c['session']) ?>">
            <input type="text" name="reply_text" placeholder="Tulis balasan untuk pengunjung ini..." maxlength="800" required style="margin:0;">
            <button type="submit" class="btn btn-primary" style="flex-shrink:0;">Kirim</button>
          </form>
        </details>
      <?php endforeach; endif; ?>
    </div>

    <div class="admin-card">
      <h2>🧾 Bukti Transfer Terdeteksi (<?= count($testiQueue) ?>)</h2>
      <p class="hint">Otomatis terdeteksi dari live chat (foto + nominal + nama toko disebut) dan sudah diteruskan ke Channel Telegram Testi. Setujui di sini untuk menayangkannya juga di halaman Testimoni website.</p>
      <?php if (empty($testiQueue)): ?>
        <p class="empty-note">Belum ada bukti transfer yang terdeteksi.</p>
      <?php else: foreach ($testiQueue as $q): ?>
        <div class="row-item">
          <div>
            <img src="proof_image.php?file=<?= urlencode($q['image']) ?>" alt="Bukti TF" style="max-width:200px;border-radius:8px;display:block;margin-bottom:8px;">
            <div class="meta"><?= rk_clean($q['name']) ?><?= !empty($q['tg_username']) ? ' · @' . rk_clean($q['tg_username']) : '' ?> · <?= rk_clean($q['detected_at'] ?? '') ?></div>
            <?php if (!empty($q['caption'])): ?><p>&ldquo;<?= rk_clean($q['caption']) ?>&rdquo;</p><?php endif; ?>
          </div>
          <div class="row-actions">
            <form method="POST"><input type="hidden" name="approve_proof_id" value="<?= (int)$q['id'] ?>"><button type="submit" class="btn-approve">Setujui &amp; Tayangkan</button></form>
            <form method="POST"><input type="hidden" name="reject_proof_id" value="<?= (int)$q['id'] ?>"><button type="submit" class="btn-reject">Tolak</button></form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="admin-card">
      <h2>Portofolio &amp; Link Testimoni (<?= count($portfolioList) ?>)</h2>
      <p class="hint">Kelola foto/harga/label di <code>data/portfolio.json</code>. Set link testimoni per postingan lewat bot Telegram: <code>/testimoni ID LINK</code>.</p>
      <?php foreach ($portfolioList as $pf): ?>
        <div class="row-item">
          <div>
            <div class="meta">ID #<?= (int)$pf['id'] ?> · <?= rk_clean($pf['category']) ?> · <?= rk_clean($pf['price'] ?? '') ?></div>
            <p><strong><?= rk_clean($pf['title']) ?></strong> — <?= $pf['testimoni_link'] ? '<a href="' . rk_clean($pf['testimoni_link']) . '" target="_blank">' . rk_clean($pf['testimoni_link']) . '</a>' : '<span style="color:var(--ink-faint);">belum ada link testimoni</span>' ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="admin-card">
      <h2>Tayang di Halaman Publik (<?= count($approvedList) ?>)</h2>
      <?php if (empty($approvedList)): ?>
        <p class="empty-note">Belum ada testimoni yang tayang.</p>
      <?php else: foreach ($approvedList as $a): ?>
        <div class="row-item">
          <div>
            <div class="stars"><?= str_repeat('★', (int)$a['rating']) . str_repeat('☆', 5 - (int)$a['rating']) ?></div>
            <p>&ldquo;<?= rk_clean($a['comment']) ?>&rdquo;</p>
            <div class="meta"><?= rk_clean($a['name']) ?> · <?= rk_clean($a['submitted_at'] ?? '') ?></div>
          </div>
          <div class="row-actions">
            <form method="POST" onsubmit="return confirm('Hapus testimoni ini dari halaman publik?');">
              <input type="hidden" name="delete_approved_id" value="<?= (int)$a['id'] ?>">
              <button type="submit" class="btn-reject">Hapus</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
<?php endif; ?>

</body>
</html>

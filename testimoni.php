<?php
require_once __DIR__ . '/functions.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = rk_clean($_POST['name'] ?? '');
    $comment = rk_clean($_POST['comment'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $rating = max(1, min(5, $rating));
    $honeypot = trim($_POST['website'] ?? ''); // anti-bot: field tersembunyi, harus kosong

    if ($honeypot !== '') {
        // kemungkinan bot — diam-diam abaikan, jangan kasih tahu bot-nya kenapa
        $success = true;
    } elseif ($name === '' || $comment === '') {
        $error = 'Nama dan komentar wajib diisi.';
    } elseif (mb_strlen($comment) > 600) {
        $error = 'Komentar maksimal 600 karakter.';
    } else {
        $pending = rk_get_pending();
        $pending[] = [
            'id' => rk_next_id(array_merge($pending, rk_get_approved())),
            'name' => $name,
            'comment' => $comment,
            'rating' => $rating,
            'submitted_at' => date('Y-m-d H:i'),
        ];
        if (rk_write_json(PENDING_FILE, $pending)) {
            $success = true;
        } else {
            $error = 'Gagal menyimpan — folder data/ belum bisa ditulis. Hubungi admin.';
        }
    }
}

$approved = rk_get_approved();
$assetBase = '';
$active = 'testimoni';
$pageTitle = 'Testimoni';
$pageDesc = 'Testimoni pelanggan RK Destu Store.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<?php include __DIR__ . '/partials/head.php'; ?>
<style>
  .testi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 8px; }
  .testi-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 26px; }
  .testi-photo { width: 100%; border-radius: 10px; margin-bottom: 14px; display: block; }
  .testi-stars { color: var(--blue); letter-spacing: 2px; font-size: 14px; margin-bottom: 12px; }
  .testi-card p { color: var(--ink-soft); font-size: 14.8px; margin: 0 0 16px; }
  .testi-meta { font-family: var(--font-mono); font-size: 12px; color: var(--ink-faint); }
  .testi-empty { color: var(--ink-faint); font-size: 15px; padding: 40px 0; text-align: center; border: 1px dashed var(--line); border-radius: var(--radius-md); }
  .testi-form { max-width: 560px; margin-top: 20px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 32px; }
  .testi-form label { display: block; font-size: 13.5px; font-weight: 600; margin-bottom: 8px; margin-top: 18px; }
  .testi-form label:first-child { margin-top: 0; }
  .testi-form input[type="text"], .testi-form textarea, .testi-form select {
    width: 100%; padding: 12px 14px; border-radius: var(--radius-sm); border: 1px solid var(--line);
    font-family: var(--font-body); font-size: 14.5px; background: var(--bg); color: var(--ink);
  }
  .testi-form textarea { min-height: 110px; resize: vertical; }
  .testi-form .hp { position: absolute; left: -9999px; }
  .alert { padding: 14px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-top: 20px; }
  .alert-ok { background: #e7f7ee; color: #14804a; border: 1px solid #bfe8d2; }
  .alert-err { background: #fdeceb; color: #b3261e; border: 1px solid #f6c9c5; }
  @media (max-width: 720px) { .testi-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<main>
  <section>
    <div class="wrap">
      <div class="section-head">
        <span class="eyebrow">Testimoni</span>
        <h2>Kata mereka yang sudah pakai jasa kami.</h2>
        <p>Testimoni ditinjau dulu oleh admin sebelum tayang, supaya halaman ini tetap bersih dari spam.</p>
      </div>

      <?php if (empty($approved)): ?>
        <div class="testi-empty">Belum ada testimoni yang tayang. Jadilah yang pertama mengisi form di bawah!</div>
      <?php else: ?>
        <div class="testi-grid">
          <?php foreach ($approved as $t): ?>
            <div class="testi-card">
              <?php if (!empty($t['photo'])): ?>
                <img src="<?= rk_clean($t['photo']) ?>" alt="Bukti transfer" class="testi-photo">
              <?php endif; ?>
              <div class="testi-stars"><?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?></div>
              <p>&ldquo;<?= rk_clean($t['comment']) ?>&rdquo;</p>
              <div class="testi-meta"><?= rk_clean($t['name']) ?> · <?= rk_clean($t['submitted_at'] ?? '') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="testi-form">
        <h3 style="margin-bottom:4px;">Tulis testimonimu</h3>
        <p style="color:var(--ink-soft);font-size:14px;margin:0;">Testimoni tayang setelah disetujui admin.</p>

        <?php if ($success): ?>
          <div class="alert alert-ok">Terima kasih! Testimonimu sudah masuk dan menunggu persetujuan admin.</div>
        <?php elseif ($error): ?>
          <div class="alert alert-err"><?= rk_clean($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="testimoni.php#kirim" id="kirim">
          <label for="name">Nama</label>
          <input type="text" id="name" name="name" maxlength="60" required>

          <label for="rating">Rating</label>
          <select id="rating" name="rating">
            <option value="5">★★★★★ (5)</option>
            <option value="4">★★★★☆ (4)</option>
            <option value="3">★★★☆☆ (3)</option>
            <option value="2">★★☆☆☆ (2)</option>
            <option value="1">★☆☆☆☆ (1)</option>
          </select>

          <label for="comment">Komentar</label>
          <textarea id="comment" name="comment" maxlength="600" required></textarea>

          <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off">

          <div style="margin-top:22px;">
            <button type="submit" class="btn btn-primary">Kirim Testimoni</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

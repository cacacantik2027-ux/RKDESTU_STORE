<?php
require_once __DIR__ . '/functions.php';
$assetBase = '';
$active = 'kontak';
$pageTitle = 'Kontak';
$pageDesc = 'Hubungi RK Destu Store lewat live chat atau Telegram.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<main>
  <section class="contact" style="padding-top:96px;">
    <div class="wrap">
      <div class="contact-panel reveal">
        <span class="eyebrow">Mulai Proyek</span>
        <h2>Siap ubah rekamanmu jadi karya final?</h2>
        <p>Pakai live chat di pojok kanan bawah (langsung terhubung ke Telegram kami, tanpa perlu buka DM sendiri), atau chat langsung di Telegram.</p>
        <div class="contact-actions">
          <a href="https://t.me/gosahsoknal" target="_blank" rel="noopener" class="btn btn-primary">Chat @gosahsoknal</a>
          <button type="button" class="btn btn-ghost" style="border-color:rgba(255,255,255,.35);color:#fff;" onclick="window.RKChat?.openWithMessage('Halo, saya mau tanya-tanya dulu.', {autoSend:false})">Buka Live Chat</button>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

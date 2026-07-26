<?php
require_once __DIR__ . '/functions.php';
$assetBase = '';
$active = 'proses';
$pageTitle = 'Proses';
$pageDesc = 'Empat langkah kerja RK Destu Store dari brief sampai file final.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<main>
  <section class="page-header">
    <div class="wrap">
      <span class="eyebrow">Alur Kerja</span>
      <h1>Empat langkah dari brief ke file final.</h1>
      <p>Prosesnya sederhana dan transparan — kamu tahu persis ada di tahap mana pesananmu.</p>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="process-grid">
        <div class="process-step reveal">
          <div class="process-num">01</div>
          <h4>Kirim Brief</h4>
          <p>Ceritakan kebutuhanmu lewat live chat atau Telegram: jenis jasa, referensi, dan tenggat waktu.</p>
        </div>
        <div class="process-step reveal">
          <div class="process-num">02</div>
          <h4>Konfirmasi &amp; Mulai</h4>
          <p>Kami konfirmasi cakupan kerja dan harga sebelum mulai proses editing.</p>
        </div>
        <div class="process-step reveal">
          <div class="process-num">03</div>
          <h4>Draft &amp; Revisi</h4>
          <p>Draft dikirim untuk direview, revisi minor mengikuti sampai kamu puas.</p>
        </div>
        <div class="process-step reveal">
          <div class="process-num">04</div>
          <h4>File Final</h4>
          <p>File resolusi penuh dikirim dalam format yang kamu butuhkan.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="contact" style="padding-top:0;">
    <div class="wrap">
      <div class="contact-panel reveal">
        <span class="eyebrow">Mulai Proyek</span>
        <h2>Siap ubah rekamanmu jadi karya final?</h2>
        <p>Kirim brief singkat lewat live chat — biasanya dibalas dalam hitungan jam, bukan hari.</p>
        <div class="contact-actions">
          <a href="layanan.php" class="btn btn-primary">Lihat Layanan</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

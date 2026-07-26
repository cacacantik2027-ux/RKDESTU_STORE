<?php
require_once __DIR__ . '/functions.php';
$assetBase = '';
$active = 'beranda';
$pageTitle = 'Beranda';
$pageDesc = 'RK Destu Store — jasa editing foto, video, dan desain grafis. Lihat iklan produk terbaru kami.';
$slides = rk_read_json(__DIR__ . '/data/slides.json');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<main id="top">
  <section class="hero-slider-wrap">
    <div class="wrap">
      <div class="section-head reveal" style="margin-bottom:26px;">
        <span class="eyebrow">Iklan Produk</span>
        <h2>Lihat karya &amp; penawaran terbaru kami.</h2>
        <p>Geser otomatis setiap beberapa detik, atau klik titik di bawah untuk pindah slide manual.</p>
      </div>

      <div class="rk-slider reveal" id="rkSlider">
        <div class="rk-slider-track" id="rkSlides">
          <?php foreach ($slides as $i => $s): ?>
            <div class="rk-slide"
                 style="background-image:url('<?= rk_clean($s['image']) ?>'), <?= $s['fallback'] ?? '' ?>;">
              <div class="rk-slide-caption">
                <span class="eyebrow">RK Destu Store</span>
                <h3><?= rk_clean($s['title']) ?></h3>
                <p><?= rk_clean($s['subtitle']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="rk-slide-dots" id="rkSlideDots"></div>
      </div>

      <div class="hero-actions" style="margin-top:30px;">
        <a href="layanan.php" class="btn btn-primary">Lihat Semua Layanan</a>
        <a href="portofolio.php" class="btn btn-ghost">Lihat Portofolio</a>
      </div>
    </div>
  </section>

  <div class="strip">
    <div class="strip-track" aria-hidden="true">
      <span>Editing Foto</span><span>·</span>
      <span>Editing Video</span><span>·</span>
      <span>Desain Grafis</span><span>·</span>
      <span>Poster & Feed IG</span><span>·</span>
      <span>Color Grading</span><span>·</span>
      <span>Logo & Branding</span><span>·</span>
      <span>Editing Foto</span><span>·</span>
      <span>Editing Video</span><span>·</span>
      <span>Desain Grafis</span><span>·</span>
      <span>Poster & Feed IG</span><span>·</span>
      <span>Color Grading</span><span>·</span>
      <span>Logo & Branding</span><span>·</span>
    </div>
  </div>

  <section class="contact" style="padding-top:64px;">
    <div class="wrap">
      <div class="contact-panel reveal">
        <span class="eyebrow">Mulai Proyek</span>
        <h2>Siap ubah rekamanmu jadi karya final?</h2>
        <p>Jelajahi layanan, portofolio, dan harga kami — atau langsung tanya lewat live chat di pojok kanan bawah.</p>
        <div class="contact-actions">
          <a href="layanan.php" class="btn btn-primary">Jelajahi Layanan</a>
          <a href="kontak.php" class="btn btn-ghost" style="border-color:rgba(255,255,255,.35);color:#fff;">Kontak</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php $pageScripts = ['assets/js/slider.js']; include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

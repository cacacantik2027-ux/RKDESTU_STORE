<?php
require_once __DIR__ . '/functions.php';
$assetBase = '';
$active = 'harga';
$pageTitle = 'Harga';
$pageDesc = 'Paket harga transparan RK Destu Store — Basic, Standard, dan Premium.';
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
      <span class="eyebrow">Harga</span>
      <h1>Paket transparan, tanpa biaya tersembunyi.</h1>
      <p>Harga dapat disesuaikan tergantung kompleksitas — konfirmasi akhir selalu lewat live chat atau Telegram.</p>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="pricing-grid">
        <div class="price-card reveal">
          <h3>Basic</h3>
          <div class="price-sub">Untuk kebutuhan cepat &amp; ringan</div>
          <div class="price-tag">Rp15K <small>/ item</small></div>
          <ul>
            <li>1 jenis jasa (foto/video/desain)</li>
            <li>1x revisi minor</li>
            <li>Estimasi 1–2 hari kerja</li>
          </ul>
          <button type="button" class="btn btn-ghost btn-order" data-product="Paket Basic" data-price="Rp15K/item">Pilih Basic</button>
        </div>
        <div class="price-card featured reveal">
          <h3>Standard</h3>
          <div class="price-sub">Paling banyak dipakai klien</div>
          <div class="price-tag">Rp50K <small>/ project</small></div>
          <ul>
            <li>Kombinasi 2 jasa</li>
            <li>3x revisi minor</li>
            <li>Estimasi 2–3 hari kerja</li>
            <li>Konsultasi konsep singkat</li>
          </ul>
          <button type="button" class="btn btn-primary btn-order" data-product="Paket Standard" data-price="Rp50K/project">Pilih Standard</button>
        </div>
        <div class="price-card reveal">
          <h3>Premium</h3>
          <div class="price-sub">Untuk kebutuhan bisnis / batch</div>
          <div class="price-tag">Custom</div>
          <ul>
            <li>Ketiga jasa sekaligus</li>
            <li>Revisi unlimited (wajar)</li>
            <li>Prioritas antrian</li>
            <li>Harga per volume pekerjaan</li>
          </ul>
          <button type="button" class="btn btn-ghost btn-ask" data-product="Paket Premium (custom)">Diskusikan Kebutuhan</button>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

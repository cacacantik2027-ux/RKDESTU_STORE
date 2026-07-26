<?php
require_once __DIR__ . '/functions.php';
$assetBase = '';
$active = 'layanan';
$pageTitle = 'Layanan';
$pageDesc = 'Editing foto, editing video, dan desain grafis — lengkap dengan harga dan cara pesan.';
$products = rk_get_products();
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
      <span class="eyebrow">Layanan</span>
      <h1>Tiga jalur produksi, satu meja kerja.</h1>
      <p>Pilih satu jasa atau gabungkan ketiganya untuk kebutuhan konten yang lebih besar. Klik tombol di tiap produk untuk pesan, masukkan ke keranjang, atau tanya-tanya dulu lewat live chat.</p>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="services-grid">
        <?php foreach ($products as $p): ?>
          <div class="service-card reveal">
            <div class="product-card-photo" style="border-radius:var(--radius-sm);overflow:hidden;aspect-ratio:16/10;margin-bottom:18px;background:<?= $p['fallback'] ?? 'var(--bg-alt)' ?>;">
              <img src="<?= rk_clean($p['image']) ?>" alt="<?= rk_clean($p['title']) ?>"
                   style="width:100%;height:100%;object-fit:cover;"
                   onerror="this.style.display='none';">
              <?php if (!empty($p['label'])): ?>
                <span class="photo-badge"><?= rk_clean($p['label']) ?></span>
              <?php endif; ?>
              <span class="photo-price"><?= rk_clean($p['price']) ?> <?= rk_clean($p['priceUnit'] ?? '') ?></span>
            </div>

            <span class="eyebrow" style="margin-bottom:8px;"><?= rk_clean($p['category']) ?></span>
            <h3><?= rk_clean($p['title']) ?></h3>
            <p><?= rk_clean($p['desc']) ?></p>
            <div class="service-price"><?= rk_clean($p['price']) ?> <?= rk_clean($p['priceUnit'] ?? '') ?></div>

            <div class="product-actions">
              <button type="button" class="btn btn-primary btn-order"
                      data-product="<?= rk_clean($p['title']) ?>"
                      data-price="<?= rk_clean($p['price']) ?>">
                Pesan Sekarang
              </button>
              <button type="button" class="btn btn-cart btn-cart"
                      data-id="<?= rk_clean($p['id']) ?>"
                      data-product="<?= rk_clean($p['title']) ?>"
                      data-price="<?= rk_clean($p['price']) ?>">
                + Keranjang
              </button>
              <button type="button" class="btn btn-ask btn-ask"
                      data-product="<?= rk_clean($p['title']) ?>">
                Tanyakan Produk
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

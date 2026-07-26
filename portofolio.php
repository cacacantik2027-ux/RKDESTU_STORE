<?php
require_once __DIR__ . '/functions.php';
$assetBase = '';
$active = 'portofolio';
$pageTitle = 'Portofolio';
$pageDesc = 'Contoh hasil kerja editing foto, video, dan desain grafis RK Destu Store.';
$portfolio = rk_get_portfolio();
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
      <span class="eyebrow">Portofolio</span>
      <h1>Contoh hasil kerja terakhir.</h1>
      <p>Beberapa cuplikan proyek dari tiap kategori layanan. Link testimoni di bawah tiap postingan diatur langsung lewat bot Telegram.</p>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="folio-grid">
        <?php foreach ($portfolio as $item): ?>
          <div class="folio-card-wrap reveal">
            <div class="folio-card">
              <img class="swatch" src="<?= rk_clean($item['image']) ?>"
                   onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                   alt="<?= rk_clean($item['title']) ?>">
              <div class="swatch" style="display:none;background:<?= $item['fallback'] ?? 'var(--bg-alt)' ?>;"></div>

              <?php if (!empty($item['label'])): ?>
                <span class="photo-badge"><?= rk_clean($item['label']) ?></span>
              <?php endif; ?>
              <?php if (!empty($item['price'])): ?>
                <span class="photo-price"><?= rk_clean($item['price']) ?></span>
              <?php endif; ?>

              <div class="folio-info"><span><?= rk_clean($item['category']) ?></span><strong><?= rk_clean($item['title']) ?></strong></div>
            </div>

            <div class="folio-card-actions">
              <div class="product-actions">
                <button type="button" class="btn btn-primary btn-order"
                        data-product="<?= rk_clean($item['title']) ?>"
                        data-price="<?= rk_clean($item['price'] ?? '') ?>">
                  Pesan Sekarang
                </button>
                <button type="button" class="btn btn-cart btn-cart"
                        data-id="portofolio-<?= (int)$item['id'] ?>"
                        data-product="<?= rk_clean($item['title']) ?>"
                        data-price="<?= rk_clean($item['price'] ?? '') ?>">
                  + Keranjang
                </button>
                <button type="button" class="btn btn-ask btn-ask"
                        data-product="<?= rk_clean($item['title']) ?>">
                  Tanyakan Produk
                </button>
              </div>

              <?php if (!empty($item['testimoni_link'])): ?>
                <a class="folio-testimoni-link" href="<?= rk_clean($item['testimoni_link']) ?>" target="_blank" rel="noopener">
                  ★ Lihat testimoni untuk hasil ini
                </a>
              <?php else: ?>
                <a class="folio-testimoni-link" href="testimoni.php" style="color:var(--ink-faint);">
                  Belum ada link testimoni khusus — lihat semua testimoni
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="folio-note">Upload foto ke folder <code>assets/img/</code> sesuai nama file di <code>data/portfolio.json</code>, atau tambah item baru langsung di file JSON tersebut (boleh copy salah satu blok). Link testimoni tiap postingan diatur admin lewat perintah <code>/testimoni ID LINK</code> di bot Telegram.</p>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

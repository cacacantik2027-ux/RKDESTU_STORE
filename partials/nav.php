<?php
if (!function_exists('rk_get_logo')) require_once __DIR__ . '/../functions.php';
$rkLogo = rk_get_logo();
// Variabel yang dipakai: $active (string, salah satu dari kunci $navItems)
$base = $assetBase ?? '';
$navItems = [
  'beranda'    => [$base . 'index.php', 'Beranda'],
  'layanan'    => [$base . 'layanan.php', 'Layanan'],
  'proses'     => [$base . 'proses.php', 'Proses'],
  'portofolio' => [$base . 'portofolio.php', 'Portofolio'],
  'harga'      => [$base . 'harga.php', 'Harga'],
  'testimoni'  => [$base . 'testimoni.php', 'Testimoni'],
  'kontak'     => [$base . 'kontak.php', 'Kontak'],
];
$active = $active ?? '';
?>
<nav class="nav">
  <div class="wrap">
    <a href="<?= $base ?>index.php" class="brand">
      <?php if ($rkLogo): ?>
        <img src="<?= $base . $rkLogo['image'] ?>" class="brand-mark-img" alt="RK Destu Store">
      <?php else: ?>
        <span class="brand-mark">RK</span>
      <?php endif; ?>
      RK DESTU STORE
    </a>
    <ul class="nav-links" id="navLinks">
      <?php foreach ($navItems as $key => [$href, $label]): ?>
        <li><a href="<?= $href ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= $label ?></a></li>
      <?php endforeach; ?>
    </ul>
    <div class="nav-cta">
      <a href="https://t.me/gosahsoknal" target="_blank" rel="noopener" class="btn btn-primary">Pesan via Telegram</a>
      <button class="nav-toggle" id="navToggle" aria-label="Buka menu">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M2 5H16M2 9H16M2 13H16" stroke="#0B1130" stroke-width="1.6" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>
</nav>

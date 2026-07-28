<?php
if (!function_exists('rk_get_logo')) require_once __DIR__ . '/../functions.php';
$rkLogo = rk_get_logo();
$base = $assetBase ?? '';
?>
<footer>
  <div class="wrap">
    <a href="<?= $base ?>index.php" class="brand">
      <?php if ($rkLogo): ?>
        <img src="<?= $base . $rkLogo['image'] ?>" class="brand-mark-img" style="width:28px;height:28px;" alt="RK Destu Store">
      <?php else: ?>
        <span class="brand-mark" style="width:28px;height:28px;font-size:11px;">RK</span>
      <?php endif; ?>
      RK DESTU STORE
    </a>
    <p>© <span id="year">2022</span> RK Destu Store. Semua hasil kerja untuk keperluan portofolio ditampilkan atas izin klien.</p>
  </div>
</footer>

<?php include __DIR__ . '/cart-drawer.php'; ?>
<?php include __DIR__ . '/chat-widget.php'; ?>

<script src="<?= $base ?>assets/js/main.js"></script>
<script src="<?= $base ?>assets/js/cart.js"></script>
<script src="<?= $base ?>assets/js/chat.js"></script>
<script src="<?= $base ?>assets/js/img-protect.js"></script>
<?php if (!empty($pageScripts)) foreach ($pageScripts as $s): ?>
<script src="<?= $base . $s ?>"></script>
<?php endforeach; ?>

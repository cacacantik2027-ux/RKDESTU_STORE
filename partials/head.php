<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? rk_clean($pageTitle) . ' — RK Destu Store' : 'RK Destu Store' ?></title>
<meta name="description" content="<?= isset($pageDesc) ? rk_clean($pageDesc) : 'RK Destu Store — jasa editing foto, video, dan desain grafis.' ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- Font Google dimuat ASINKRON (tidak memblokir render halaman) — kalau
     koneksi ke fonts.googleapis.com lambat/gagal dari jaringan pengunjung,
     halaman tetap tampil normal (cuma font fallback dulu, font custom
     menyusul begitu selesai dimuat), TIDAK bikin halaman "nyangkut". -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
      media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"></noscript>
<link rel="stylesheet" href="<?= $assetBase ?? '' ?>style.css">

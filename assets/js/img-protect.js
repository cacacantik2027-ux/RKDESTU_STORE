// img-protect.js — Proteksi sederhana untuk foto hasil edit & bukti transfer
// di halaman Layanan, Portofolio, dan Testimoni.
//
// Cara kerja (murni sisi browser, tanpa perlu server khusus):
// 1. Klik-kanan & drag pada foto dinonaktifkan.
// 2. Di atas setiap foto "berharga" (produk/portofolio/bukti TF), kita
//    tempelkan lapisan <img> TAK TERLIHAT (opacity ~0) yang isinya adalah
//    GAMBAR PERINGATAN ("ANDA MENCURI PROPERTI PRIBADI RKDESTU STORE"),
//    bukan foto aslinya. Foto asli tetap tampil normal di baliknya, tapi
//    begitu pengunjung klik-kanan "Simpan Gambar" atau men-drag foto itu
//    ke luar, yang benar-benar ter-download/ter-drag adalah lapisan gambar
//    peringatan tadi — BUKAN foto asli.
//
// Catatan jujur: ini bukan pengaman 100% (screenshot atau DevTools tetap
// bisa dipakai orang yang cukup niat), tapi cukup menghalangi cara paling
// umum orang "mencuri" foto lewat klik-kanan / drag biasa.

(function () {
  const WARNING_SRC = ['assets/img/anti-theft-warning.png', 'assets/img/anti-theft-warning.svg'];

  const rkChatEl = document.getElementById('rkChat');
  const base = (rkChatEl && rkChatEl.dataset.base) || '';

  function makeOverlay(container) {
    const overlay = document.createElement('img');
    overlay.src = base + WARNING_SRC[0];
    overlay.alt = '';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.setAttribute('draggable', 'true');
    Object.assign(overlay.style, {
      position: 'absolute', inset: '0', width: '100%', height: '100%',
      objectFit: 'cover', opacity: '0.01', zIndex: '3', margin: '0',
    });
    overlay.addEventListener('error', function onErr() {
      overlay.removeEventListener('error', onErr);
      overlay.src = base + WARNING_SRC[1];
    }, { once: true });
    container.appendChild(overlay);
  }

  function lockImg(img) {
    img.setAttribute('draggable', 'false');
    img.style.webkitUserDrag = 'none';
    img.addEventListener('dragstart', (e) => e.preventDefault());
    img.addEventListener('contextmenu', (e) => e.preventDefault());
  }

  // Pola A: foto MENGISI PENUH kotak induknya (Layanan & Portofolio —
  // .product-card-photo / .folio-card sudah berbentuk kotak foto itu
  // sendiri, badge & harga cuma teks overlay di atasnya). Overlay cukup
  // ditempel langsung ke elemen induk.
  function protectFillImage(img) {
    if (!img || img.dataset.rkProtected) return;
    img.dataset.rkProtected = '1';
    lockImg(img);
    const parent = img.parentElement;
    if (!parent) return;
    if (window.getComputedStyle(parent).position === 'static') {
      parent.style.position = 'relative';
    }
    makeOverlay(parent);
  }

  // Pola B: foto adalah bagian ATAS dari kartu yang lebih besar berisi
  // teks lain di bawahnya (Testimoni — foto bukti TF + bintang + komentar
  // dalam satu .testi-card). Foto perlu dibungkus wrapper sendiri supaya
  // overlay tidak ikut menutupi teks testimoni di bawahnya.
  function protectStandaloneImage(img) {
    if (!img || img.dataset.rkProtected) return;
    img.dataset.rkProtected = '1';
    lockImg(img);
    if (!img.parentNode) return;

    const marginBottom = window.getComputedStyle(img).marginBottom;
    const wrapper = document.createElement('span');
    Object.assign(wrapper.style, {
      display: 'block', position: 'relative', width: '100%', marginBottom,
    });
    img.parentNode.insertBefore(wrapper, img);
    wrapper.appendChild(img);
    img.style.marginBottom = '0';
    img.style.display = 'block';

    makeOverlay(wrapper);
  }

  function protectAll() {
    document.querySelectorAll('.product-card-photo img, img.swatch').forEach(protectFillImage);
    document.querySelectorAll('img.testi-photo').forEach(protectStandaloneImage);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', protectAll);
  } else {
    protectAll();
  }

  // Kalau ada foto yang dimuat belakangan tanpa reload penuh, pantau juga.
  // PENTING: protectAll() sendiri menambah elemen (wrapper + overlay), yang
  // kalau tidak dijaga akan memicu observer ini lagi, lagi, dan lagi tanpa
  // henti (loop mutasi DOM) — inilah yang bikin browser terasa freeze di
  // halaman yang banyak foto (Portofolio/Testimoni), apalagi ditambah
  // slider auto-geser & polling chat tiap 4 detik yang juga mengubah DOM.
  //
  // Solusi: hanya proses ulang kalau ada IMG BARU yang belum ditandai
  // rkProtected, dan matikan observer sesaat selama protectAll() berjalan
  // supaya perubahan yang kita buat sendiri tidak dianggap "mutasi baru".
  let rkProtecting = false;
  const observer = new MutationObserver((mutations) => {
    if (rkProtecting) return;

    let hasNewImg = false;
    for (const m of mutations) {
      for (const node of m.addedNodes) {
        if (node.nodeType !== 1) continue;
        if (
          (node.matches && node.matches('.product-card-photo img, img.swatch, img.testi-photo') && !node.dataset.rkProtected) ||
          (node.querySelector && node.querySelector('.product-card-photo img:not([data-rk-protected]), img.swatch:not([data-rk-protected]), img.testi-photo:not([data-rk-protected])'))
        ) {
          hasNewImg = true;
          break;
        }
      }
      if (hasNewImg) break;
    }
    if (!hasNewImg) return;

    rkProtecting = true;
    protectAll();
    rkProtecting = false;
  });
  observer.observe(document.body, { childList: true, subtree: true });

  // Blokir klik-kanan di area galeri foto secara umum (jaga-jaga untuk
  // elemen background-image seperti slider iklan), tanpa mengganggu form/
  // tombol lain di website.
  document.addEventListener('contextmenu', (e) => {
    const guarded = e.target.closest('.folio-card, .product-card-photo, .testi-photo, .rk-slide');
    if (guarded) e.preventDefault();
  });
})();

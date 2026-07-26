// RK Destu Store — interaksi ringan (no framework, aman untuk hosting statis/PHP)

const yearEl = document.getElementById('year');
if (yearEl) yearEl.textContent = '2022';

// Toggle menu mobile
const navToggle = document.getElementById('navToggle');
const navLinks = document.getElementById('navLinks');
if (navToggle) {
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => navLinks.classList.remove('open'));
  });
}

// Reveal on scroll
const revealEls = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  revealEls.forEach(el => io.observe(el));
} else {
  revealEls.forEach(el => el.classList.add('in'));
}

// ---------- Tombol produk: Pesan Sekarang / + Keranjang / Tanyakan Produk ----------
// Dipakai di kartu layanan (layanan.php) dan postingan portofolio (portofolio.php)
document.addEventListener('click', (e) => {
  const orderBtn = e.target.closest('.btn-order');
  const askBtn = e.target.closest('.btn-ask');
  const cartBtn = e.target.closest('.btn-cart');

  if (orderBtn) {
    const product = orderBtn.dataset.product || 'produk';
    const price = orderBtn.dataset.price || '';
    const text = `🛒 Saya mau PESAN: ${product}${price ? ' (' + price + ')' : ''}`;
    window.RKChat?.openWithMessage(text, { autoSend: true });
  }

  if (askBtn) {
    const product = askBtn.dataset.product || 'produk ini';
    const text = `Halo, saya mau tanya soal produk: ${product}`;
    window.RKChat?.openWithMessage(text, { autoSend: false });
  }

  if (cartBtn) {
    window.RKCart?.add({
      id: cartBtn.dataset.id || cartBtn.dataset.product,
      title: cartBtn.dataset.product || 'Produk',
      price: cartBtn.dataset.price || '',
    });
  }
});

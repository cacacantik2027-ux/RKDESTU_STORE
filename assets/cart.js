// RK Destu Store — Keranjang sederhana (client-side, tanpa payment gateway).
// "Checkout" merangkum isi keranjang jadi satu pesan lalu mengirimkannya
// lewat live chat (yang otomatis diteruskan ke Telegram admin).

(function () {
  const CART_KEY = 'rk_cart';
  const fab = document.getElementById('rkCartFab');
  const drawer = document.getElementById('rkCartDrawer');
  const overlay = document.getElementById('rkCartOverlay');
  const closeBtn = document.getElementById('rkCartClose');
  const itemsEl = document.getElementById('rkCartItems');
  const countEl = document.getElementById('rkCartCount');
  const checkoutBtn = document.getElementById('rkCartCheckout');
  if (!fab) return;

  function getCart() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }
    catch { return []; }
  }
  function setCart(items) {
    localStorage.setItem(CART_KEY, JSON.stringify(items));
    render();
  }

  function render() {
    const items = getCart();
    countEl.textContent = items.reduce((n, it) => n + it.qty, 0);
    itemsEl.innerHTML = '';
    if (!items.length) {
      itemsEl.innerHTML = '<p class="rk-cart-empty">Keranjang masih kosong. Klik "+ Keranjang" di produk yang kamu mau.</p>';
      return;
    }
    items.forEach((it, idx) => {
      const row = document.createElement('div');
      row.className = 'rk-cart-item';
      row.innerHTML = `
        <div class="rk-cart-item-info">
          <strong>${it.title}</strong>
          <span>${it.price || ''}</span>
        </div>
        <div class="rk-cart-item-qty">
          <button data-act="dec" aria-label="Kurangi">−</button>
          <span>${it.qty}</span>
          <button data-act="inc" aria-label="Tambah">+</button>
          <button data-act="del" aria-label="Hapus" class="rk-cart-del">&times;</button>
        </div>`;
      row.querySelector('[data-act="inc"]').addEventListener('click', () => { it.qty++; setCart(items); });
      row.querySelector('[data-act="dec"]').addEventListener('click', () => { it.qty = Math.max(1, it.qty - 1); setCart(items); });
      row.querySelector('[data-act="del"]').addEventListener('click', () => { items.splice(idx, 1); setCart(items); });
      itemsEl.appendChild(row);
    });
  }

  function open() { drawer.classList.add('open'); overlay.classList.add('open'); }
  function close() { drawer.classList.remove('open'); overlay.classList.remove('open'); }

  fab.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);

  checkoutBtn.addEventListener('click', () => {
    const items = getCart();
    if (!items.length) return;
    const lines = items.map(it => `• ${it.title} x${it.qty} ${it.price ? '(' + it.price + ')' : ''}`).join('\n');
    const text = `🧾 PESANAN dari Keranjang:\n${lines}\n\nMohon info total & langkah selanjutnya ya. Terima kasih!`;
    close();
    window.RKChat?.openWithMessage(text, { autoSend: true });
    setCart([]);
  });

  window.RKCart = {
    add(product) {
      const items = getCart();
      const existing = items.find(it => it.id === product.id);
      if (existing) existing.qty++;
      else items.push({ id: product.id, title: product.title, price: product.price, qty: 1 });
      setCart(items);
      open();
    },
  };

  render();
})();

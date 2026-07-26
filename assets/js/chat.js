// RK Destu Store — Live Chat.
// Saat pertama dibuka, widget menanyakan: "Apakah kamu punya akun Telegram?"
// - YA  -> pengunjung langsung diarahkan (redirect) ke chat Telegram bot kami.
//          Sejak itu SELURUH proses chat (termasuk balasan admin) berlangsung
//          langsung di Telegram (lihat bot.php) — website hanya menampilkan
//          layar "sudah dialihkan" dengan tombol untuk membuka Telegram lagi.
// - TIDAK -> lanjut seperti sebelumnya: live chat di website ini, pesan
//          diteruskan sebagai notifikasi ke Telegram admin (chat_send.php),
//          dan admin membalas lewat Panel Admin (muncul di sini via polling
//          chat_poll.php).

(function () {
  const root = document.getElementById('rkChat');
  if (!root) return;

  const base = root.dataset.base || '';
  const tgBotUsername = root.dataset.tgBot || '';

  const fab = document.getElementById('rkChatToggle');
  const panel = document.getElementById('rkChatPanel');
  const closeBtn = document.getElementById('rkChatClose');

  const tgGate = document.getElementById('rkChatTgGate');
  const tgYesBtn = document.getElementById('rkChatTgYes');
  const tgNoBtn = document.getElementById('rkChatTgNo');

  const tgActive = document.getElementById('rkChatTgActive');
  const tgOpenAgain = document.getElementById('rkChatTgOpenAgain');
  const tgSwitchBtn = document.getElementById('rkChatTgSwitch');

  const nameGate = document.getElementById('rkChatNameGate');
  const nameInput = document.getElementById('rkChatNameInput');
  const tgInput = document.getElementById('rkChatTgInput');
  const nameSubmit = document.getElementById('rkChatNameSubmit');
  const body = document.getElementById('rkChatBody');
  const form = document.getElementById('rkChatForm');
  const input = document.getElementById('rkChatInput');
  const fileInput = document.getElementById('rkChatFileInput');
  const previewWrap = document.getElementById('rkChatImagePreview');
  const previewImg = document.getElementById('rkChatImagePreviewImg');
  const previewRemove = document.getElementById('rkChatImageRemove');
  const dot = document.querySelector('.rk-chat-dot');

  const SESSION_KEY = 'rk_chat_session';
  const NAME_KEY = 'rk_chat_name';
  const TG_KEY = 'rk_chat_tg';
  const SINCE_KEY = 'rk_chat_since';
  const MODE_KEY = 'rk_chat_mode'; // '' (belum pilih) | 'telegram' | 'website'

  let session = localStorage.getItem(SESSION_KEY);
  if (!session) {
    session = 'v' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    localStorage.setItem(SESSION_KEY, session);
  }
  let name = localStorage.getItem(NAME_KEY) || '';
  let tgUsername = localStorage.getItem(TG_KEY) || '';
  let since = parseInt(localStorage.getItem(SINCE_KEY) || '0', 10);
  let mode = localStorage.getItem(MODE_KEY) || '';
  let pollTimer = null;
  let pendingImageDataUri = null;

  function buildTelegramLink() {
    if (!tgBotUsername) return '';
    let url = 'https://t.me/' + tgBotUsername;
    if (name) url += '?start=' + encodeURIComponent(name.replace(/[^a-zA-Z0-9_\-]/g, '').slice(0, 60) || 'web');
    return url;
  }

  function hideAllGates() {
    tgGate.hidden = true;
    tgActive.hidden = true;
    nameGate.hidden = true;
    body.hidden = true;
    form.hidden = true;
  }

  function showTelegramGate() {
    hideAllGates();
    tgGate.hidden = false;
  }

  function showTelegramActive() {
    hideAllGates();
    tgActive.hidden = false;
    const link = buildTelegramLink();
    tgOpenAgain.href = link || '#';
  }

  function showChatUI() {
    hideAllGates();
    body.hidden = false;
    form.hidden = false;
    input.focus();
  }

  function showNameGate() {
    hideAllGates();
    nameGate.hidden = false;
  }

  // Kalau bot Telegram belum dikonfigurasi (TELEGRAM_BOT_USERNAME kosong di
  // admin/config.php), jangan tampilkan pertanyaan Telegram sama sekali —
  // langsung ke alur live chat website seperti sebelumnya.
  function goToInitialScreen() {
    if (!tgBotUsername) {
      if (name) { showChatUI(); startPolling(); } else { showNameGate(); }
      return;
    }
    if (mode === 'telegram') { showTelegramActive(); return; }
    if (mode === 'website') {
      if (name) { showChatUI(); startPolling(); } else { showNameGate(); }
      return;
    }
    showTelegramGate();
  }

  function renderMessage(m) {
    const row = document.createElement('div');
    row.className = 'rk-msg ' + (m.from === 'admin' ? 'rk-msg-admin' : 'rk-msg-user');
    if (m.type === 'image' && m.imageUrl) {
      const img = document.createElement('img');
      img.src = m.imageUrl;
      img.className = 'rk-msg-img';
      img.alt = 'Foto';
      row.appendChild(img);
      if (m.text) {
        const p = document.createElement('div');
        p.textContent = m.text;
        p.style.marginTop = '6px';
        row.appendChild(p);
      }
    } else {
      row.textContent = m.text;
    }
    body.appendChild(row);
    body.scrollTop = body.scrollHeight;
  }

  function openPanel() {
    panel.classList.add('open');
    if (dot) dot.style.display = 'none';
    goToInitialScreen();
  }
  function closePanel() {
    panel.classList.remove('open');
  }

  fab.addEventListener('click', () => panel.classList.contains('open') ? closePanel() : openPanel());
  closeBtn.addEventListener('click', closePanel);

  // ---------- Gate: konfirmasi punya Telegram atau tidak ----------
  tgYesBtn.addEventListener('click', () => {
    mode = 'telegram';
    localStorage.setItem(MODE_KEY, mode);
    const link = buildTelegramLink();
    if (link) window.open(link, '_blank', 'noopener');
    showTelegramActive();
  });

  tgNoBtn.addEventListener('click', () => {
    mode = 'website';
    localStorage.setItem(MODE_KEY, mode);
    if (name) { showChatUI(); startPolling(); } else { showNameGate(); }
  });

  tgOpenAgain.addEventListener('click', (e) => {
    const link = buildTelegramLink();
    if (!link) { e.preventDefault(); alert('Link Telegram belum tersedia. Hubungi kami lewat halaman Kontak ya.'); }
  });

  tgSwitchBtn.addEventListener('click', () => {
    mode = 'website';
    localStorage.setItem(MODE_KEY, mode);
    if (name) { showChatUI(); startPolling(); } else { showNameGate(); }
  });

  nameSubmit.addEventListener('click', () => {
    const val = nameInput.value.trim();
    if (!val) { nameInput.focus(); return; }
    name = val;
    tgUsername = (tgInput.value || '').trim().replace(/^@/, '');
    localStorage.setItem(NAME_KEY, name);
    localStorage.setItem(TG_KEY, tgUsername);
    showChatUI();
    startPolling();
  });
  nameInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') nameSubmit.click(); });

  // ---------- Upload foto (mis. bukti transfer) ----------
  fileInput.addEventListener('change', () => {
    const file = fileInput.files && fileInput.files[0];
    if (!file) return;
    if (file.size > 6 * 1024 * 1024) {
      alert('Ukuran foto maksimal 6MB.');
      fileInput.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      pendingImageDataUri = reader.result;
      previewImg.src = pendingImageDataUri;
      previewWrap.hidden = false;
    };
    reader.readAsDataURL(file);
  });

  previewRemove.addEventListener('click', () => {
    pendingImageDataUri = null;
    fileInput.value = '';
    previewWrap.hidden = true;
  });

  async function sendMessage(text) {
    const hasImage = !!pendingImageDataUri;
    if (!hasImage && (!text || !text.trim())) return;

    renderMessage({
      from: 'user',
      type: hasImage ? 'image' : 'text',
      text,
      imageUrl: hasImage ? pendingImageDataUri : null,
    });

    const imageToSend = pendingImageDataUri;
    pendingImageDataUri = null;
    fileInput.value = '';
    previewWrap.hidden = true;

    try {
      await fetch(base + 'chat_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session, name, tg_username: tgUsername,
          message: text || '', page: document.title,
          image: imageToSend || undefined,
        }),
      });
    } catch (err) {
      renderMessage({ from: 'admin', text: 'Gagal mengirim pesan. Coba lagi atau hubungi kami langsung via Telegram @gosahsoknal.' });
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = input.value;
    input.value = '';
    sendMessage(text);
  });

  async function poll() {
    try {
      const res = await fetch(base + `chat_poll.php?session=${encodeURIComponent(session)}&since=${since}`);
      const data = await res.json();
      if (data.ok && data.messages?.length) {
        data.messages.forEach(m => {
          // Balasan admin dikirim lewat Panel Admin di website (selalu teks).
          if (m.from === 'admin') renderMessage(m);
          since = Math.max(since, m.time || since);
        });
        localStorage.setItem(SINCE_KEY, String(since));
      }
    } catch (err) { /* diam-diam, coba lagi di polling berikutnya */ }
  }

  function startPolling() {
    if (mode === 'telegram') return; // mode Telegram: tidak perlu polling website
    if (pollTimer) return;
    poll();
    pollTimer = setInterval(poll, 4000);
  }

  // ---------- API publik dipakai tombol Pesan/Tanyakan Produk ----------
  window.RKChat = {
    openWithMessage(text, { autoSend } = {}) {
      openPanel();
      if (mode === 'telegram') return; // biarkan pengunjung lanjut di Telegram
      if (!name) {
        const pending = text;
        nameSubmit.addEventListener('click', function once() {
          if (autoSend) sendMessage(pending);
          else { input.value = pending; input.focus(); }
          nameSubmit.removeEventListener('click', once);
        });
        if (mode === 'website' || !tgBotUsername) nameInput.focus();
        return;
      }
      if (autoSend) sendMessage(text);
      else { input.value = text; input.focus(); }
    },
    sendMessage,
  };
})();

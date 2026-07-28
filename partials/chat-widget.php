<?php
$base = $assetBase ?? '';
// TELEGRAM_BOT_USERNAME dipakai supaya tombol "Ya, saya punya Telegram" di
// bawah tahu bot mana yang harus dibuka. Konstanta ini ada di admin/config.php.
if (!defined('TELEGRAM_BOT_USERNAME')) require_once __DIR__ . '/../admin/config.php';
$rkTgBotUsername = defined('TELEGRAM_BOT_USERNAME') ? TELEGRAM_BOT_USERNAME : '';
?>
<div id="rkChat" class="rk-chat" data-base="<?= $base ?>" data-tg-bot="<?= htmlspecialchars($rkTgBotUsername, ENT_QUOTES) ?>">
  <button id="rkChatToggle" class="rk-chat-fab" aria-label="Buka live chat">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 12C4 7.58 8.03 4 13 4C17.97 4 22 7.58 22 12C22 16.42 17.97 20 13 20C11.68 20 10.44 19.75 9.34 19.29L4 21L5.42 16.54C4.53 15.28 4 13.7 4 12Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"/></svg>
    <span class="rk-chat-dot"></span>
  </button>

  <div id="rkChatPanel" class="rk-chat-panel">
    <div class="rk-chat-head">
      <div>
        <strong>Live Chat</strong>
        <span>Terhubung ke Telegram RK Destu Store</span>
      </div>
      <div class="rk-chat-head-actions">
        <button id="rkChatEndSession" type="button" aria-label="Akhiri sesi chat" title="Akhiri sesi chat" hidden>⟲</button>
        <button id="rkChatClose" aria-label="Tutup live chat">&times;</button>
      </div>
    </div>

    <div id="rkChatTgGate" class="rk-chat-gate" hidden>
      <p>Apakah kamu punya akun Telegram? 📱</p>
      <p class="rk-chat-gate-sub">Kalau punya, kamu akan diarahkan langsung ke chat Telegram kami — lebih cepat, dan admin bisa balas real-time di sana.</p>
      <div class="rk-chat-gate-btns">
        <button id="rkChatTgYes" type="button" class="btn btn-primary">Ya, saya punya</button>
        <button id="rkChatTgNo" type="button" class="btn btn-ghost">Tidak, chat di sini saja</button>
      </div>
    </div>

    <div id="rkChatTgActive" class="rk-chat-gate" hidden>
      <p>Chat kamu sudah dialihkan ke Telegram ✈️</p>
      <p class="rk-chat-gate-sub">Lanjutkan percakapanmu di aplikasi Telegram — admin akan membalas langsung di sana.</p>
      <div class="rk-chat-gate-btns">
        <a id="rkChatTgOpenAgain" href="#" target="_blank" rel="noopener" class="btn btn-primary">Buka Telegram Lagi</a>
        <button id="rkChatTgSwitch" type="button" class="btn btn-ghost">Chat di website saja</button>
      </div>
    </div>

    <div id="rkChatNameGate" class="rk-chat-gate" hidden>
      <p>Masukkan nama/username kamu supaya admin tahu harus balas ke siapa 👋</p>
      <input id="rkChatNameInput" type="text" placeholder="Nama atau username kamu (wajib)" maxlength="40">
      <input id="rkChatTgInput" type="text" placeholder="Username Telegram kamu (opsional, tanpa @)" maxlength="40">
      <button id="rkChatNameSubmit" class="btn btn-primary">Mulai Chat</button>
    </div>

    <div id="rkChatBody" class="rk-chat-body" hidden></div>

    <div id="rkChatImagePreview" class="rk-chat-preview" hidden>
      <img id="rkChatImagePreviewImg" alt="Preview foto">
      <button type="button" id="rkChatImageRemove" aria-label="Batal kirim foto">&times;</button>
    </div>

    <form id="rkChatForm" class="rk-chat-form" hidden>
      <label for="rkChatFileInput" class="rk-chat-attach" aria-label="Lampirkan foto (mis. bukti transfer)" title="Lampirkan foto">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 12.5v5A3.5 3.5 0 0 1 17.5 21h-11A3.5 3.5 0 0 1 3 17.5v-11A3.5 3.5 0 0 1 6.5 3H12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M16 3l5 5M21 3l-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8.5" cy="10.5" r="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M4 17l4.5-4.5a2 2 0 0 1 2.8 0L15 16" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      </label>
      <input id="rkChatFileInput" type="file" accept="image/png,image/jpeg,image/webp" hidden>
      <input id="rkChatInput" type="text" placeholder="Tulis pesan... (foto bukti TF: sebutkan nominal & RKDESTU STORE)" maxlength="800" autocomplete="off">
      <button type="submit" aria-label="Kirim">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M2 9L16 2L11 16L8 10L2 9Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
      </button>
    </form>
  </div>
</div>

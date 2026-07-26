# Panduan Pindah ke Railway — RK Destu Store

Semua fitur bot (live chat, auto-post bukti TF ke channel testi, upload
foto lewat Telegram, dll) sudah ada di kode — panduan ini fokus ke
**cara deploy-nya di Railway**, plus fitur **baru**: backup & restore
otomatis.

---

## 0. Sebelum mulai

⚠️ **Catatan keamanan**: kamu sudah kirim `TELEGRAM_BOT_TOKEN`,
`ADMIN_PASSWORD`, dll langsung di chat ini. Itu tidak masalah untuk
saya proses, tapi supaya lebih aman ke depannya:
- Jangan share ulang token/password itu ke tempat lain.
- `TELEGRAM_WEBHOOK_SECRET` kamu (`rkdestu2026`) agak mudah ditebak.
  Boleh tetap dipakai, tapi kalau mau lebih aman, ganti ke string acak
  panjang (saya bisa buatkan kalau mau).

---

## 1. Buat Project di Railway

1. Buka [railway.app](https://railway.app), login (bisa pakai GitHub).
2. **New Project** → **Deploy from GitHub repo** (paling gampang: upload
   dulu folder project ini ke repo GitHub baru), ATAU **Empty Project**
   lalu upload lewat Railway CLI (`railway up`) dari folder project ini
   di komputermu.
3. Railway akan otomatis mendeteksi `Dockerfile` yang sudah saya
   siapkan dan build project-nya dari situ.

## 2. Tambahkan Persistent Volume (WAJIB)

Tanpa ini, semua data (chat, testimoni, foto upload) akan **hilang**
tiap kali Railway re-deploy.

1. Di service website kamu di Railway → tab **Settings** → scroll ke
   **Volumes** → **New Volume**.
2. **Mount path**: isi persis `/var/www/html/data`
3. Simpan. Railway akan restart service otomatis.

> Foto upload portofolio & testimoni juga ikut aman — `entrypoint.sh`
> sudah saya atur supaya foto-foto itu otomatis disimpan di dalam
> volume ini juga (lewat symlink), jadi cukup **satu** volume saja.

## 3. Isi Environment Variables

1. Tab **Variables** di service yang sama → klik ikon **Raw Editor**
   (biasanya di pojok kanan atas kotak variables).
2. Paste seluruh isi file **`.env`** yang saya kirimkan terpisah di
   chat ini.
3. Save. Railway akan re-deploy otomatis dengan variable barumu.

## 4. Cek Domain & Set Webhook Telegram

1. Tab **Settings** → **Networking** → **Generate Domain** (dapat URL
   seperti `https://rkdestustore-production.up.railway.app`), atau
   tambahkan **Custom Domain** kalau mau pakai domain kamu sendiri
   (lihat pembahasan awal kita soal DNS CNAME).
2. Set webhook Telegram (ganti `<DOMAIN>` dengan domain Railway/domain
   custom kamu):
   ```
   https://api.telegram.org/bot8913410369:AAF5ri9VBsXhdAyHGrKKkC38CE6XV9ooH_A/setWebhook?url=https://<DOMAIN>/bot.php&secret_token=rkdestu2026
   ```
3. Buka URL itu di browser, harus muncul `{"ok":true,"result":true,...}`.
4. Cek status kapan saja:
   ```
   https://api.telegram.org/bot8913410369:AAF5ri9VBsXhdAyHGrKKkC38CE6XV9ooH_A/getWebhookInfo
   ```

Selesai — **tidak perlu cron-job.org sama sekali**, Railway selalu
HTTPS jadi langsung pakai Mode Webhook (real-time).

## 5. Coba Semua Fitur

- **Live chat & auto-relay ke Telegram**: buka website → klik ikon
  chat → kirim pesan test → harus masuk ke chat pribadimu di Telegram.
- **Upload foto portofolio via bot**: kirim FOTO ke bot dengan caption
  `/portofolio Judul Test | Rp10.000` → cek halaman Portofolio website.
- **Auto-post bukti TF ke channel testi**: di live chat, upload foto +
  ketik `tf ke RKDESTU STORE Rp150.000` → cek channel testi kamu.
- **Ketik `/help` ke bot** → lihat semua perintah termasuk `/backup`
  dan `/restore` yang baru.

---

## 6. Backup & Restore (Manual lewat Bot)

Backup tidak lagi dijadwalkan otomatis lewat cron service Railway —
cukup dipicu manual kapan pun kamu mau, langsung dari chat Telegram
dengan bot (tidak perlu bikin service tambahan di Railway).

### Cara Backup

Kirim `/backup` ke bot (dari chat admin) → bot akan membuat file `.zip`
berisi seluruh folder `data/` + foto portofolio & testimoni, lalu
mengirimkannya langsung ke chat itu.

### Cara Restore

Kalau suatu saat perlu memulihkan data dari backup (misalnya abis
testing dan datanya kebentur, atau pindah environment):

1. Cari file `.zip` hasil `/backup` di chat Telegram-mu dengan bot.
2. **Forward atau kirim ulang** file zip itu ke bot, dengan **caption**:
   ```
   /restore
   ```
3. Bot akan membalas konfirmasi setelah selesai memulihkan data + foto.

⚠️ Restore itu **menimpa** data yang sedang berjalan sekarang dengan isi
backup — pastikan kamu memang mau melakukan itu sebelum kirim.

---

## Ringkasan Perbedaan dari Setup Lama (InfinityFree)

| | InfinityFree (lama) | Railway (baru) |
|---|---|---|
| Mode bot | Polling (`bot_poll.php`) + cron-job.org | Webhook (`bot.php`), real-time |
| HTTPS | Sering bermasalah | Otomatis selalu aktif |
| Penyimpanan foto/data | Folder biasa di hosting | Railway Volume (persisten) |
| Backup | Tidak ada | Manual kapan saja lewat `/backup` ke bot |
| Setting kredensial | Edit `admin/config.php` langsung | Environment Variables (`.env`) |

## Troubleshooting Railway

- **Deploy sukses tapi website blank/500** → cek tab **Deployments →
  Logs** di Railway, biasanya ketahuan errornya (mis. ekstensi PHP
  belum aktif, atau folder belum writable).
- **Foto/data hilang setelah re-deploy** → berarti Volume belum
  ter-attach dengan benar, ulangi Langkah 2.
- **Webhook `getWebhookInfo` menunjukkan error** → pastikan domain
  Railway sudah aktif (`Generate Domain` di Langkah 4.1) dan
  `TELEGRAM_WEBHOOK_SECRET` di `.env` sama persis dengan yang dipakai
  di URL `setWebhook`.
- **`/backup` tidak balas apa-apa / gagal** → cek TELEGRAM_BACKUP_CHAT_ID
  (atau TELEGRAM_ADMIN_CHAT_ID) di `.env` sudah benar, dan pastikan
  ekstensi `zip` PHP aktif (sudah di-install lewat Dockerfile).

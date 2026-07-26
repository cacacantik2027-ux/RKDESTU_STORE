# Panduan Live Chat & Bot Telegram — RK Destu Store

Fitur baru di website ini:
- **Tombol produk**: "Pesan Sekarang", "+ Keranjang", "Tanyakan Produk" di setiap
  layanan (`layanan.php`) dan postingan portofolio (`portofolio.php`).
- **Harga & label** tampil langsung di atas foto produk/portofolio.
- **Link testimoni per postingan** di bawah tiap foto portofolio — diatur lewat
  perintah bot Telegram.
- **Live Chat** di pojok kanan bawah setiap halaman. Begitu dibuka, widget
  bertanya dulu **"Apakah kamu punya akun Telegram?"**:
  - **Ya** → pengunjung langsung diarahkan (redirect, tab baru) ke chat
    Telegram bot kamu. Sejak saat itu **seluruh percakapan** (pesan pengunjung
    maupun balasan admin, termasuk foto) berlangsung **langsung di dalam
    Telegram** — kamu cukup **Reply** pesan pengunjung itu di Telegram untuk
    membalas, TIDAK perlu buka Panel Admin sama sekali untuk pengunjung ini.
  - **Tidak** → lanjut seperti sebelumnya: pesan pengunjung (dan foto)
    otomatis diteruskan sebagai **notifikasi** ke Telegram kamu, dan kamu
    membalas lewat **Panel Admin** di website (bagian "Live Chat"). Balasanmu
    otomatis muncul lagi di widget chat pengunjung lewat polling berkala.
    Pengunjung **tidak perlu** membuka Telegram sama sekali di jalur ini.
- **Deteksi bukti transfer otomatis** — kalau pengunjung upload foto di live chat
  sambil mengetik nominal transfer + nama toko (`RKDESTU STORE`), foto itu
  otomatis diteruskan ke **Channel Telegram Testi** kamu, dan masuk antrian
  "Bukti Transfer Terdeteksi" di Panel Admin untuk kamu approve manual sebelum
  tayang sebagai testimoni di halaman Testimoni website.
  > **Catatan jujur soal "deteksi" ini**: sistem TIDAK membaca tulisan di dalam
  > foto (bukan OCR sungguhan — itu butuh API berbayar seperti Google Vision,
  > tidak tersedia gratis). Yang dicek adalah **teks yang diketik pengunjung**
  > bersamaan dengan foto, misalnya "sudah tf ke RKDESTU STORE Rp150.000".

Semua ini jalan di atas hosting PHP biasa (tanpa Node.js, tanpa database, tanpa
server yang perlu nyala terus) — caranya Telegram yang "menghubungi" websitemu
lewat **webhook**, bukan website yang nunggu terus-terusan.

---

## 1. Buat Bot Telegram

1. Buka Telegram, chat **@BotFather**.
2. Kirim `/newbot`, ikuti instruksinya (nama bot & username bot, harus akhiran `bot`).
3. BotFather akan kasih **token** seperti `123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`.
   Simpan ini — akan dipakai di `admin/config.php`.

## 2. Cari Chat ID kamu sendiri

Ini adalah ID akun Telegram-mu sendiri (bukan bot), tempat semua pesan live
chat dari pengunjung akan masuk, dan tempat kamu mengetik perintah bot.

1. Chat bot **@userinfobot** (atau **@getidsbot**) di Telegram.
2. Bot akan membalas dengan `Id: 123456789` — itulah Chat ID kamu.

## 3. Isi `admin/config.php`

```php
define('TELEGRAM_BOT_TOKEN', '123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TELEGRAM_ADMIN_CHAT_ID', '123456789');
define('TELEGRAM_WEBHOOK_SECRET', 'buat-string-acak-sendiri-di-sini');
define('TELEGRAM_BOT_USERNAME', 'gosahsoknal_bot'); // TANPA @, lihat catatan di bawah
define('TELEGRAM_TESTI_CHANNEL_ID', '@namachannelkamu'); // atau ID numerik channel privat
define('RK_STORE_NAME', 'RKDESTU STORE');
```

`TELEGRAM_WEBHOOK_SECRET` boleh diisi teks acak apapun (misalnya campuran huruf
angka sepanjang 20+ karakter) — ini cuma supaya webhook-mu tidak bisa dipanggil
sembarang orang selain Telegram.

`TELEGRAM_BOT_USERNAME` adalah username bot (dari langkah 1, akhiran `bot`),
**tanpa** tanda `@` dan tanpa `https://t.me/`. Ini dipakai untuk membuat tombol
"Ya, saya punya Telegram" di live chat website — begitu diklik, pengunjung
langsung dibawa ke `https://t.me/<username-ini>` dan chat dengan admin
berlangsung sepenuhnya di Telegram. Kalau field ini dikosongkan, tombol
konfirmasi Telegram tidak akan muncul dan live chat berjalan seperti versi
lama (semua lewat Panel Admin).

`TELEGRAM_TESTI_CHANNEL_ID` adalah channel Telegram tempat foto bukti transfer
otomatis diposting. **Bot HARUS dijadikan admin di channel itu dulu**, kalau
tidak, pengiriman foto ke channel akan gagal diam-diam (dicek lewat
`getWebhookInfo` atau log kalau ada masalah).

## 4. Soal HTTPS — Ada 2 Mode Jalanin Bot Ini

Telegram **mewajibkan** URL webhook pakai `https://` — ini aturan dari pihak
Telegram sendiri, tidak bisa "diakalin" dari kode. Jadi ada 2 pilihan
tergantung status SSL hosting-mu:

| | **Mode Webhook** (`bot.php`) | **Mode Polling** (`bot_poll.php`) |
|---|---|---|
| Syarat | Hosting **harus** `https://` | Hosting **boleh** `http://` biasa (cocok untuk InfinityFree yang SSL-nya belum/tidak aktif) |
| Kecepatan balas | Real-time (langsung) | Ada delay ±1-5 menit (tergantung interval cronjob) |
| Setup | 1x `setWebhook` lewat browser | Perlu cronjob eksternal gratis (cron-job.org) yang jalan terus |

**Cara kerja Mode Polling**: bukan Telegram yang "mengetuk" websitemu (itu
yang butuh https://), tapi SEBALIKNYA — `bot_poll.php` di websitemu yang aktif
"menjemput" pesan baru ke server Telegram tiap kali dipanggil. Yang memanggil
`bot_poll.php` itu bukan Telegram, tapi layanan cron eksternal — jadi URL
pemanggilnya boleh `http://` biasa.

Kalau SSL InfinityFree-mu **belum aktif atau gagal terus**, langsung skip ke
**Langkah 5b** di bawah (Mode Polling), tidak perlu memaksakan Langkah 5a.

## 5a. Mode Webhook (kalau hosting SUDAH https://)

Upload dulu semua file ke hosting (termasuk `bot.php`), lalu buka URL berikut
di browser (ganti `<TOKEN>`, `<DOMAIN>`, dan `<SECRET>` sesuai punyamu):

```
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://<DOMAIN>/bot.php&secret_token=<SECRET>
```

Kalau berhasil, akan muncul balasan JSON `{"ok":true,"result":true,...}`.

Untuk cek status webhook kapan saja:
```
https://api.telegram.org/bot<TOKEN>/getWebhookInfo
```

## 5b. Mode Polling (kalau hosting MASIH http:// / InfinityFree tanpa SSL)

**Langkah 1 — Hapus webhook lama (kalau pernah di-set)**

Telegram menolak mode polling selama webhook masih aktif. Buka di browser:
```
https://api.telegram.org/bot<TOKEN>/deleteWebhook
```
Harus muncul `{"ok":true,"result":true,...}`.

**Langkah 2 — Pastikan file `bot_poll.php` sudah ke-upload** ke hosting
(satu folder dengan `bot.php`, `index.php`, dst).

**Langkah 3 — Tes manual dulu di browser**, buka:
```
http://<DOMAIN>/bot_poll.php?secret=<SECRET>
```
(`<SECRET>` = isi `TELEGRAM_WEBHOOK_SECRET` di `admin/config.php`)

Harus muncul balasan JSON seperti `{"ok":true,"processed":0,"next_offset":0}`
(angka `processed` akan >0 kalau ada pesan baru yang belum diproses saat itu).
Kalau muncul `{"ok":false,...}` → cek lagi `TELEGRAM_WEBHOOK_SECRET` di
`admin/config.php` sudah diisi dan sama persis dengan yang di URL.

**Langkah 4 — Daftar cron eksternal gratis di [cron-job.org](https://cron-job.org)**
1. Daftar akun gratis, verifikasi email.
2. Klik **Create cronjob**.
3. **Title**: bebas, mis. "RK Destu Bot Polling".
4. **URL**: `http://<DOMAIN>/bot_poll.php?secret=<SECRET>`
5. **Schedule**: pilih interval tercepat yang tersedia di akun gratismu
   (biasanya bisa tiap 1 menit). Makin sering, makin cepat bot membalas.
6. Simpan & aktifkan cronjob-nya.

Sekarang bot akan otomatis mengecek pesan baru sesuai interval cronjob di
atas — tanpa perlu HTTPS sama sekali. Semua fitur (relay chat, `/testimoni`,
`/portofolio`) tetap jalan sama persis seperti mode webhook, cuma responnya
tidak instan (nunggu giliran cronjob berikutnya).

> **Kalau nanti SSL InfinityFree-mu berhasil aktif**, kamu bisa pindah ke
> Mode Webhook biar real-time: hapus/nonaktifkan cronjob di cron-job.org,
> lalu jalankan `setWebhook` di Langkah 5a. Tidak perlu ubah kode apa pun —
> `bot.php` dan `bot_poll.php` pakai logika pemrosesan pesan yang sama persis.

## 6. Set Izin Folder

Folder-folder berikut perlu bisa ditulis PHP (klik kanan di File Manager
hosting → Permissions → **755**, kalau masih gagal coba **777**):
- `data/`
- `data/chat/`
- `data/chat/uploads/` (dibuat otomatis oleh script saat foto pertama
  diupload — kalau gagal muncul otomatis, buat folder ini manual lalu set
  permission-nya)
- `assets/img/testimoni/` (dibuat otomatis saat admin approve foto bukti TF
  pertama kali — folder PUBLIK, tempat foto yang sudah disetujui disimpan)

Folder `data/chat/uploads/` sengaja TERPROTEKSI (mewarisi `.htaccess` dari
`data/chat/`) supaya foto bukti TF tidak bisa diakses siapa pun lewat URL
langsung sebelum disetujui admin.

## 7. Coba Live Chat

1. Buka website, klik ikon chat bulat di pojok kanan bawah.
2. Isi nama (wajib) dan username Telegram (opsional), kirim pesan test.
3. Notifikasi pesan itu akan muncul di Telegram-mu (chat pribadi dengan bot) —
   ini HANYA notifikasi, bukan tempat membalas.
4. Buka **Panel Admin** (`admin/index.php`) di website → bagian **"Live Chat"**
   → klik percakapan yang mau dibalas → ketik balasan di kotak yang ada di
   bawah percakapan → klik Kirim.
5. Balasanmu akan muncul otomatis di widget chat website (jeda beberapa detik,
   karena website mengecek balasan baru setiap ±4 detik).

### Coba Live Chat Langsung di Telegram (mode baru)

1. Pastikan `TELEGRAM_BOT_USERNAME` sudah diisi di `admin/config.php`.
2. Buka widget live chat di website, klik **"Ya, saya punya"** saat ditanya
   soal akun Telegram.
3. Tab baru Telegram akan terbuka, otomatis chat dengan bot kamu (mengirim
   `/start`). Kirim pesan apa saja dari sana.
4. Pesan itu akan masuk ke chat pribadimu di Telegram (chat dengan bot),
   diberi label nama pengunjungnya.
5. Untuk membalas: **tekan & tahan pesan itu di Telegram → pilih Reply →
   ketik balasanmu → kirim.** Bot akan otomatis meneruskan balasan itu ke
   Telegram pengunjung. Kamu bisa membalas dengan teks maupun foto.
6. **Penting**: balasan HARUS dikirim dengan fitur Reply Telegram (bukan
   pesan biasa) supaya bot tahu balasan itu untuk pengunjung yang mana.

### Coba Deteksi Bukti Transfer

1. Di widget live chat, klik ikon jepit (📎) di sebelah kotak pesan, pilih foto.
2. Ketik pesan yang menyebut nominal + nama toko, misal: `sudah tf ke RKDESTU
   STORE Rp150.000`, lalu kirim bareng foto.
3. Foto otomatis terkirim ke **Channel Testi** kamu di Telegram.
4. Buka Panel Admin → bagian **"🧾 Bukti Transfer Terdeteksi"** → cek fotonya →
   klik **"Setujui & Tayangkan"** kalau valid, atau **"Tolak"** kalau bukan.
5. Setelah disetujui, foto + keterangan otomatis muncul di halaman Testimoni
   website.

## 8. Upload Foto Portofolio Langsung Lewat Bot Telegram

Kamu bisa menambahkan postingan baru ke halaman **Portofolio** website
langsung dari chat Telegram, tanpa buka file manager hosting:

1. Di Telegram, kirim **FOTO** ke bot (chat pribadi yang sama dengan
   `TELEGRAM_ADMIN_CHAT_ID`) dengan **caption**:
   ```
   /portofolio Retouch Produk | Rp15.000 | Foto | Terlaris
   ```
   Urutannya: `Judul | Harga | Kategori | Label`, dipisah tanda `|`.
   Yang **wajib cuma Judul** — sisanya boleh dikosongkan, misalnya cukup:
   ```
   /portofolio Edit Foto Produk
   ```
2. Bot otomatis: download foto itu, simpan ke `assets/img/portofolio/`,
   dan menambahkan entry baru ke `data/portfolio.json`.
3. Foto langsung tayang di halaman **Portofolio** website — beserta ID
   barunya (dibalas oleh bot), yang bisa langsung kamu pakai untuk
   `/testimoni ID LINK` kalau mau pasang link testimoni juga.

> Pastikan folder `assets/img/portofolio/` bisa ditulis PHP (permission
> 755, kalau masih gagal coba 777) — folder ini dibuat otomatis oleh bot
> saat upload pertama, tapi kalau gagal, buat manual dulu lewat file manager.

## 9. Atur Link Testimoni Portofolio Lewat Bot

Tiap postingan di halaman **Portofolio** punya ID (lihat di admin panel, bagian
"Portofolio & Link Testimoni", atau di file `data/portfolio.json`).

Untuk pasang/ganti link testimoni suatu postingan, ketik langsung ke bot di
Telegram:
```
/testimoni 1 https://t.me/gosahsoknal/123
```
Bot akan balas konfirmasi, dan link itu langsung tayang di bawah foto
portofolio #1 di website.

Ketik `/help` ke bot kapan saja untuk lihat daftar perintah.

## 10. Kelola Produk, Harga, Label, dan Slide Iklan

- **Layanan/produk** (harga, label, foto): edit `data/products.json`.
- **Portofolio** (harga, label, foto): edit `data/portfolio.json`
  (link testimoni tetap lewat bot seperti di atas, tapi field lain boleh
  diedit manual di file ini).
- **Slide iklan di halaman utama** (5 slide auto-geser): edit `data/slides.json`.
  Setiap slide punya `image` (path foto), `title`, `subtitle`, dan `fallback`
  (warna gradient kalau foto belum diupload).
- Semua path foto merujuk ke folder `assets/img/` — upload foto dengan nama
  yang sama seperti di file JSON.

## Troubleshooting

- **Pesan live chat tidak sampai ke Telegram** → cek `TELEGRAM_BOT_TOKEN` &
  `TELEGRAM_ADMIN_CHAT_ID` di `admin/config.php` sudah benar, dan kamu sudah
  pernah `/start` bot itu minimal sekali di Telegram.
- **Balasan dari Panel Admin tidak muncul di website** → pastikan folder
  `data/chat/` bisa ditulis (permission 755/777), dan pengunjung masih
  membuka tab widget chat-nya (polling berjalan tiap ±4 detik).
- **Foto tidak masuk ke Channel Testi** → pastikan bot sudah dijadikan
  **admin** di channel tersebut, `TELEGRAM_TESTI_CHANNEL_ID` di
  `admin/config.php` sudah benar (pakai format `@namachannel` atau ID `-100...`),
  dan folder `data/chat/uploads/` bisa ditulis PHP.
- **Foto bukti TF tidak terdeteksi otomatis** → ingat, sistem membaca TEKS yang
  diketik pengunjung (bukan tulisan di dalam foto). Pastikan teksnya menyebut
  nominal (mis. `Rp150.000` atau `150.000`) DAN nama toko `RKDESTU STORE`
  persis seperti di `RK_STORE_NAME` pada `admin/config.php`.
- **Bot tidak merespon perintah `/testimoni`** → kalau pakai Mode Webhook,
  cek webhook aktif lewat `getWebhookInfo` (langkah 5a) dan pastikan hosting
  sudah HTTPS. Kalau pakai Mode Polling, cek cronjob di cron-job.org statusnya
  aktif & baru saja jalan (lihat log eksekusinya di dashboard cron-job.org).
- **Mode Polling: `bot_poll.php` selalu balas `{"ok":false}`** → cocokkan lagi
  `?secret=` di URL cronjob dengan isi `TELEGRAM_WEBHOOK_SECRET` di
  `admin/config.php`, harus SAMA PERSIS (huruf besar/kecil berpengaruh).
- **Mode Polling: pesan lama ke-proses ulang / duplikat** → cek folder
  `data/chat/` bisa ditulis PHP (permission 755/777) — offset penanda pesan
  terakhir disimpan di `data/chat/tg_update_offset.json`, kalau folder tidak
  bisa ditulis, offset ini gagal tersimpan dan pesan yang sama diproses lagi
  tiap cronjob jalan.
- **Tombol "Ya, saya punya Telegram" tidak muncul di widget** → isi
  `TELEGRAM_BOT_USERNAME` di `admin/config.php` (tanpa `@`, tanpa `https://t.me/`).
- **Balasan admin (mode Telegram langsung) tidak sampai ke pengunjung** →
  pastikan kamu membalas dengan fitur **Reply** Telegram ke pesan pengunjung
  tersebut (bukan mengetik pesan biasa), dan folder `data/chat/` masih bisa
  ditulis PHP (untuk menyimpan pemetaan pesan → pengunjung).
- **Upload foto portofolio lewat bot gagal / tidak muncul di website** →
  pastikan caption fotonya diawali persis `/portofolio` (bukan `/Portofolio`
  atau ada spasi ekstra), dan folder `assets/img/portofolio/` bisa ditulis
  PHP (permission 755/777).

# Panduan Fitur Testimoni — RK Destu Store

## 1. Ganti password admin
Buka `admin/config.php`, ganti nilai `ADMIN_PASSWORD` dengan password baru
(bukan password yang dipakai di akun lain). Jangan lupa upload ulang file
ini setelah diganti.

## 2. Upload ke InfinityFree
Upload SEMUA folder & file ke `htdocs`, termasuk yang biasanya "tersembunyi":
```
htdocs/
├── index.php
├── layanan.php
├── proses.php
├── portofolio.php
├── harga.php
├── testimoni.php
├── kontak.php
├── functions.php
├── bot.php
├── chat_send.php
├── chat_poll.php
├── style.css
├── partials/
│   ├── head.php
│   ├── nav.php
│   ├── footer.php
│   ├── chat-widget.php
│   └── cart-drawer.php
├── assets/
│   ├── js/
│   │   ├── main.js
│   │   ├── slider.js
│   │   ├── cart.js
│   │   └── chat.js
│   └── img/   (taruh foto produk & portofolio di sini)
├── data/
│   ├── testimonials.json
│   ├── pending.json
│   ├── products.json
│   ├── portfolio.json
│   ├── slides.json
│   ├── .htaccess
│   └── chat/
│       ├── map.json
│       └── .htaccess
├── admin/
│   ├── index.php
│   └── config.php
```

Untuk panduan fitur Live Chat & bot Telegram (testimoni via bot, chat langsung
ke Telegram), lihat **README-TELEGRAM.md**.

## 3. Set izin folder `data/`
InfinityFree perlu izin tulis di folder `data/` supaya testimoni bisa
tersimpan. Di File Manager InfinityFree:
1. Klik kanan folder `data` → **Change Permissions** (atau ikon gerigi).
2. Set ke **755**. Kalau submit testimoni masih gagal, coba **777**
   (InfinityFree kadang butuh ini karena shared hosting).

## 4. Akses halaman
- Publik: `namadomainmu.com/testimoni.php`
- Admin: `namadomainmu.com/admin/` → login pakai password dari langkah 1

## 5. Alur kerja
- Pelanggan isi form di `testimoni.php` → masuk status **pending**, belum tayang.
- Kamu buka `admin/` → tab "Menunggu Persetujuan" → klik **Setujui** atau **Tolak**.
- Testimoni yang disetujui otomatis tayang di `testimoni.php`.
- Bisa juga tambah testimoni manual langsung dari panel admin (misalnya dari
  chat WhatsApp/Telegram pelanggan yang kamu copy sendiri).

> Catatan: ini adalah testimoni umum yang tampil di halaman **Testimoni**.
> Untuk link testimoni khusus yang tampil di bawah tiap postingan di halaman
> **Portofolio**, itu diatur terpisah lewat bot Telegram — lihat README-TELEGRAM.md.

## Catatan keamanan
- Folder `data/` sudah diblok dari akses langsung browser lewat `.htaccess`,
  tapi tetap jangan simpan data sensitif di situ.
- Ganti password admin secara berkala, dan jangan bagikan link `admin/` ke
  orang lain.

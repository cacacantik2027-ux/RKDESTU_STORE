#!/bin/bash
set -e

# Railway menyuntikkan PORT secara dinamis (bukan selalu 8080), jadi
# Apache harus listen di port itu, bukan port yang di-hardcode.
PORT="${PORT:-8080}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# ---------------------------------------------------------------------
# PERSISTENT VOLUME: Railway hanya menyediakan SATU mount path per
# volume di service ini -> kita mount ke /var/www/html/data (lihat
# README-RAILWAY.md). Supaya foto upload (portofolio & testimoni) IKUT
# persisten juga (tidak cuma folder data/), kita simpan foto-foto itu
# di DALAM volume (data/uploads/...), lalu buat SYMLINK dari lokasi
# publik assets/img/portofolio & assets/img/testimoni menuju ke situ.
# Apache tetap bisa menyajikan file lewat symlink ini seperti biasa.
# ---------------------------------------------------------------------
mkdir -p /var/www/html/data/chat/uploads
mkdir -p /var/www/html/data/uploads/portofolio
mkdir -p /var/www/html/data/uploads/testimoni
mkdir -p /var/www/html/data/uploads/slides

# Kalau assets/img/portofolio BUKAN symlink (pertama kali jalan / masih
# folder biasa dari image), pindahkan isinya ke volume lalu jadikan symlink.
if [ ! -L /var/www/html/assets/img/portofolio ]; then
  if [ -d /var/www/html/assets/img/portofolio ]; then
    cp -rn /var/www/html/assets/img/portofolio/. /var/www/html/data/uploads/portofolio/ 2>/dev/null || true
    rm -rf /var/www/html/assets/img/portofolio
  fi
  ln -s /var/www/html/data/uploads/portofolio /var/www/html/assets/img/portofolio
fi

if [ ! -L /var/www/html/assets/img/testimoni ]; then
  if [ -d /var/www/html/assets/img/testimoni ]; then
    cp -rn /var/www/html/assets/img/testimoni/. /var/www/html/data/uploads/testimoni/ 2>/dev/null || true
    rm -rf /var/www/html/assets/img/testimoni
  fi
  ln -s /var/www/html/data/uploads/testimoni /var/www/html/assets/img/testimoni
fi

if [ ! -L /var/www/html/assets/img/slides ]; then
  if [ -d /var/www/html/assets/img/slides ]; then
    cp -rn /var/www/html/assets/img/slides/. /var/www/html/data/uploads/slides/ 2>/dev/null || true
    rm -rf /var/www/html/assets/img/slides
  fi
  ln -s /var/www/html/data/uploads/slides /var/www/html/assets/img/slides
fi

chown -R www-data:www-data /var/www/html/data /var/www/html/assets/img || true

# ---------------------------------------------------------------------
# FIX RUNTIME: paksa ulang HANYA mpm_prefork yang aktif tepat sebelum
# Apache start. Ini WAJIB dilakukan lagi di sini (bukan cuma sekali di
# Dockerfile saat build) karena platform Railway kadang me-reset/
# menambahkan module MPM lain saat container start, menyebabkan error
# "AH00534: More than one MPM loaded" walau sudah difix di build time.
# ---------------------------------------------------------------------
a2dismod mpm_event mpm_worker 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
      2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true
apache2ctl -t || true

exec apache2-foreground

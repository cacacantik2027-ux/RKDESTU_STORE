FROM php:8.2-apache

# Ekstensi PHP yang dipakai project ini:
# - zip: dibutuhkan fitur backup/restore (backup.php, ZipArchive)
# - gd: jaga-jaga untuk pemrosesan gambar di masa depan
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
    && docker-php-ext-install zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache: aktifkan mod_rewrite (jaga-jaga untuk pretty URL di masa depan).
# PENTING: mod_php WAJIB jalan dengan MPM "prefork" (non-thread), bukan
# "event"/"worker". Kalau dua MPM ke-enable bersamaan, Apache akan
# menolak start sama sekali (error "More than one MPM loaded" -> bikin
# Railway selalu menampilkan "Application failed to respond"/502).
# Baris di bawah ini memaksa HANYA mpm_prefork yang aktif.
RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork; \
    a2enmod rewrite

# Railway memberi PORT lewat environment variable, bukan selalu 80.
# Kita ubah Apache supaya listen di $PORT saat container start (lihat
# entrypoint.sh), bukan di-hardcode ke 80.
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# AllowOverride All supaya .htaccess di data/ dan data/chat/ (yang
# mem-blokir akses publik ke foto bukti TF yang belum di-approve)
# benar-benar berlaku, bukan diabaikan Apache.
RUN printf '<Directory /var/www/html>\n\tAllowOverride All\n\tRequire all granted\n\tOptions +FollowSymLinks\n</Directory>\n' \
    > /etc/apache2/conf-available/rk-allowoverride.conf \
    && a2enconf rk-allowoverride

WORKDIR /var/www/html
COPY . /var/www/html

# Folder-folder ini HARUS bisa ditulis PHP (upload foto, simpan JSON, dll).
# Kalau kamu pakai Railway Volume (sangat disarankan, lihat README-RAILWAY.md),
# volume itu akan di-mount menimpa folder /var/www/html/data saat runtime —
# baris di bawah ini cuma jaga-jaga permission awal sebelum volume ter-mount.
RUN mkdir -p data/chat/uploads assets/img/portofolio assets/img/testimoni assets/img/slides assets/img/logo \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 data assets/img

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]

# Panduan Production Deployment — Sampurasun

> Sistem Pengadaan Barang & Jasa Pemerintah  
> Stack: Laravel 12 · Vue 3 · Inertia.js · MySQL  
> Repository: https://github.com/mo4lbebeja/sampurasun_v2.git

---

## Daftar Isi

1. [Kebutuhan Server](#1-kebutuhan-server)
2. [Fase 1 — Persiapan Server](#fase-1--persiapan-server)
3. [Fase 2 — Konfigurasi .env Production](#fase-2--konfigurasi-env-production)
4. [Fase 3 — Install Dependensi & Build Asset](#fase-3--install-dependensi--build-asset)
5. [Fase 4 — Setup Database & Storage](#fase-4--setup-database--storage)
6. [Fase 5 — Optimasi Laravel](#fase-5--optimasi-laravel)
7. [Fase 6 — Konfigurasi Nginx & SSL](#fase-6--konfigurasi-nginx--ssl)
8. [Fase 7 — Konfigurasi PHP](#fase-7--konfigurasi-php)
9. [Fase 8 — Verifikasi Akhir](#fase-8--verifikasi-akhir)
10. [Script Deploy Ulang](#script-deploy-ulang)
11. [Catatan Khusus Project Ini](#catatan-khusus-project-ini)
12. [Troubleshooting Umum](#troubleshooting-umum)

---

## 1. Kebutuhan Server

| Komponen | Minimum | Rekomendasi |
|---|---|---|
| OS | Ubuntu 20.04 | Ubuntu 22.04 LTS |
| PHP | 8.2 | 8.4 |
| MySQL | 8.0 | 8.0+ |
| Node.js | 18 | 20 LTS |
| Composer | 2.x | 2.x |
| Web Server | Apache/Nginx | Nginx |
| RAM | 1 GB | 2 GB+ |
| Storage | 10 GB | 20 GB+ |

---

## Fase 1 — Persiapan Server

### 1.1 Install dependensi server (Ubuntu)

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.4 dan ekstensi yang diperlukan
sudo apt install -y php8.4 php8.4-fpm php8.4-mysql php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl \
  php8.4-bcmath php8.4-tokenizer

# Nginx, MySQL, Node.js, Composer
sudo apt install -y nginx mysql-server
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 1.2 Buat database MySQL

```sql
CREATE DATABASE sampurasun_prod
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'sampurasun'@'localhost' IDENTIFIED BY 'password_sangat_kuat';
GRANT ALL PRIVILEGES ON sampurasun_prod.* TO 'sampurasun'@'localhost';
FLUSH PRIVILEGES;
```

### 1.3 Clone repository ke server

```bash
cd /var/www
git clone https://github.com/mo4lbebeja/sampurasun_v2.git sampurasun
cd sampurasun
```

---

## Fase 2 — Konfigurasi .env Production

### 2.1 Buat file .env

```bash
cp .env.example .env
```

### 2.2 Isi nilai .env

Buka `.env` dan sesuaikan nilai berikut:

```env
# ── Aplikasi ───────────────────────────────────────────────────────
APP_NAME="Sampurasun"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.go.id
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

# ── Database ───────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sampurasun_prod
DB_USERNAME=sampurasun
DB_PASSWORD=password_sangat_kuat

# ── Cache & Session ────────────────────────────────────────────────
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_SECURE_COOKIE=true

# ── Mail (sesuaikan dengan SMTP instansi) ──────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.instansi.go.id
MAIL_PORT=587
MAIL_USERNAME=noreply@instansi.go.id
MAIL_PASSWORD=password_mail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@instansi.go.id
MAIL_FROM_NAME="Sampurasun"

# ── Storage ────────────────────────────────────────────────────────
FILESYSTEM_DISK=public
```

### 2.3 Generate APP_KEY

> **Penting:** Lakukan hanya sekali. Jangan pernah mengganti APP_KEY setelah data production tersimpan — semua data terenkripsi akan tidak bisa dibaca.

```bash
php artisan key:generate
```

---

## Fase 3 — Install Dependensi & Build Asset

### 3.1 Install Composer (tanpa package development)

```bash
composer install --optimize-autoloader --no-dev
```

### 3.2 Install NPM dan build asset

```bash
npm install
npm run build
```

Hasil build akan tersimpan di folder `public/build/`. Pastikan folder ini ter-upload ke server.

---

## Fase 4 — Setup Database & Storage

### 4.1 Jalankan migration

```bash
php artisan migrate --force
```

### 4.2 Jalankan seeder (data awal)

> Hanya jika diperlukan untuk mengisi data master awal (roles, kategori, DPA).

```bash
php artisan db:seed --force
```

### 4.3 Buat symlink storage

Wajib dijalankan agar file upload (kop surat, dokumen kontrak, BAST, dll) bisa diakses publik.

```bash
php artisan storage:link
```

### 4.4 Set permission folder

```bash
sudo chown -R www-data:www-data /var/www/sampurasun
sudo chmod -R 755 /var/www/sampurasun
sudo chmod -R 775 /var/www/sampurasun/storage
sudo chmod -R 775 /var/www/sampurasun/bootstrap/cache
```

---

## Fase 5 — Optimasi Laravel

Jalankan semua perintah berikut setiap kali melakukan deployment ulang.

```bash
# Cache konfigurasi
php artisan config:cache

# Cache route
php artisan route:cache

# Cache view Blade
php artisan view:cache

# Cache event listener
php artisan event:cache

# Optimasi autoloader
composer dump-autoload --optimize --no-dev
```

Untuk memverifikasi semua optimasi aktif:

```bash
php artisan about
```

Pastikan output menunjukkan:
- `Environment: production`
- `Debug Mode: OFF`
- `Config: Cached`
- `Routes: Cached`

---

## Fase 6 — Konfigurasi Nginx & SSL

### 6.1 Buat file konfigurasi Nginx

```bash
sudo nano /etc/nginx/sites-available/sampurasun
```

Isi dengan konfigurasi berikut:

```nginx
server {
    listen 80;
    server_name domain-anda.go.id;
    root /var/www/sampurasun/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    charset utf-8;

    # Ukuran upload maksimal (sesuaikan dengan kebutuhan dokumen)
    client_max_body_size 25M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    # Blokir akses ke file tersembunyi
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache asset statis
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6.2 Aktifkan site dan reload Nginx

```bash
sudo ln -s /etc/nginx/sites-available/sampurasun /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 6.3 Pasang SSL dengan Let's Encrypt (HTTPS)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d domain-anda.go.id
```

Certbot akan otomatis memperbarui sertifikat SSL setiap 90 hari.

---

## Fase 7 — Konfigurasi PHP

Edit file `/etc/php/8.4/fpm/php.ini`:

```bash
sudo nano /etc/php/8.4/fpm/php.ini
```

Sesuaikan nilai berikut:

```ini
; Upload file — untuk dokumen kontrak, BAST, HPS, kop surat
upload_max_filesize = 25M
post_max_size = 30M

; Waktu eksekusi — untuk generate PDF dokumen panjang
max_execution_time = 120
max_input_time = 120

; Memory — untuk proses PDF dan query besar
memory_limit = 256M

; Timezone
date.timezone = Asia/Jakarta
```

Restart PHP-FPM setelah perubahan:

```bash
sudo systemctl restart php8.4-fpm
```

---

## Fase 8 — Verifikasi Akhir

### 8.1 Checklist fungsional

- [ ] Login berhasil untuk semua role (admin, sarana_umum, pptk, pejabat_pengadaan, upbj, keuangan, perencanaan)
- [ ] Buat usulan pengadaan baru
- [ ] Approval usulan oleh PPTK
- [ ] Buat paket pengadaan (multi-paket)
- [ ] Input kontrak dengan harga per item
- [ ] Generate nomor dokumen (BAST, BAPMHP, BAPRHP, BAPP)
- [ ] Cetak semua dokumen PDF (Surat Pesanan, BAST, Ringkasan Kontrak, SPP-GU)
- [ ] Upload kop surat via Settings
- [ ] Selesaikan dokumen UPBJ
- [ ] Input dan selesaikan pembayaran
- [ ] Dashboard menampilkan data yang benar
- [ ] Notification bell berfungsi

### 8.2 Setup backup otomatis database

Tambahkan ke crontab server (`sudo crontab -e`):

```bash
# Backup database setiap hari pukul 02.00
0 2 * * * mysqldump -u sampurasun -p'password_sangat_kuat' sampurasun_prod \
  | gzip > /var/backups/sampurasun/db-$(date +\%Y\%m\%d).sql.gz

# Hapus backup lebih dari 30 hari
0 3 * * * find /var/backups/sampurasun -name "*.sql.gz" -mtime +30 -delete
```

Buat folder backup:

```bash
sudo mkdir -p /var/backups/sampurasun
sudo chown www-data:www-data /var/backups/sampurasun
```

### 8.3 Isi data master awal

Setelah aplikasi berjalan, isi data berikut melalui UI admin:

1. **Settings** → Upload kop surat instansi
2. **Master Data → DPA Anggaran** → Tambah DPA tahun berjalan
3. **Master Data → Sub Kegiatan** → Hubungkan ke DPA
4. **Master Data → Anggaran** → Isi pagu per rekening
5. **Master Data → Kategori Barang** → Sesuaikan dengan kebutuhan
6. **Master Data → User** → Buat akun untuk setiap pegawai sesuai role

---

## Script Deploy Ulang

Simpan sebagai `/var/www/sampurasun/deploy.sh` untuk digunakan saat ada update:

```bash
#!/bin/bash
set -e

echo "▶ Memulai deployment — $(date)"

cd /var/www/sampurasun

echo "▶ Pull perubahan dari Git..."
git pull origin main

echo "▶ Install dependensi PHP..."
composer install --optimize-autoloader --no-dev

echo "▶ Build asset frontend..."
npm ci
npm run build

echo "▶ Jalankan migration..."
php artisan migrate --force

echo "▶ Clear dan rebuild cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "▶ Set permission..."
chown -R www-data:www-data storage bootstrap/cache

echo "✅ Deployment selesai — $(date)"
```

Beri izin eksekusi:

```bash
chmod +x /var/www/sampurasun/deploy.sh
```

Cara pakai untuk update berikutnya:

```bash
sudo bash /var/www/sampurasun/deploy.sh
```

---

## Catatan Khusus Project Ini

### Timezone dokumen

Project ini digunakan untuk instansi pemerintah Indonesia. Pastikan timezone **WIB (Asia/Jakarta)** sudah dikonfigurasi dengan benar di `.env` agar tanggal pada dokumen cetak (BAST, Surat Pesanan, dll) sesuai.

### File yang wajib ada setelah deployment

| File/Folder | Keterangan |
|---|---|
| `public/storage` | Symlink ke `storage/app/public` — wajib ada |
| `storage/app/public/images/` | Folder untuk kop surat |
| `storage/app/public/pengadaan/` | Folder untuk file kontrak dan HPS |
| `storage/app/public/dokumen-upbj/` | Folder untuk file BAST, invoice, dll |
| `storage/app/public/settings/` | Folder untuk file kop surat yang diupload |

Semua folder di atas dibuat otomatis saat pertama kali file diupload, selama permission sudah benar.

### Limit upload file

Project ini memiliki upload file hingga 20MB (file kontrak). Pastikan konfigurasi Nginx (`client_max_body_size`) dan PHP (`upload_max_filesize`, `post_max_size`) sudah disesuaikan.

### Tabel `app_settings`

Digunakan untuk menyimpan path kop surat. Pastikan migration sudah dijalankan (`php artisan migrate`) dan tabel ini sudah ada sebelum menggunakan fitur Settings.

---

## Troubleshooting Umum

| Gejala | Kemungkinan Penyebab | Solusi |
|---|---|---|
| File upload gagal | Permission storage | `chmod -R 775 storage` |
| PDF tidak bisa dicetak | Extension GD/mbstring belum install | `apt install php8.4-gd php8.4-mbstring` |
| Halaman 500 setelah deploy | Cache lama | `php artisan cache:clear && php artisan config:cache` |
| File tidak bisa diakses publik | Symlink storage belum ada | `php artisan storage:link` |
| Login redirect terus | Session tidak tersimpan | Cek permission `storage/framework/sessions` |
| Tanggal dokumen salah | Timezone belum diset | Tambah `APP_TIMEZONE=Asia/Jakarta` di `.env` |
| Kop surat tidak muncul di PDF | `app_settings` tabel kosong | Upload kop surat via menu Settings |
| `route()` tidak dikenali | Ziggy tidak dikonfigurasi | Gunakan URL string langsung, bukan `route()` |

---

*Dokumen ini dibuat untuk Sampurasun v2 — Mei 2026*

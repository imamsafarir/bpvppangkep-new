# BPVP Pangkep - Super APP

### Sistem Informasi Terpadu Balai Pelatihan Vokasi dan Produktivitas Pangkajene dan Kepulauan

**Kementerian Ketenagakerjaan Republik Indonesia**

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue 3](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.x-9553E9?style=for-the-badge&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Vite](https://img.shields.io/badge/Vite-6.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PWA Ready](https://img.shields.io/badge/PWA-Super_App_Ready-0A2E50?style=for-the-badge&logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)

---

## 📌 Daftar Isi

1. [Tentang Aplikasi](#-tentang-aplikasi)
2. [Fitur & Modul Utama](#-fitur--modul-utama)
3. [Persyaratan Sistem (Prerequisites)](#-persyaratan-sistem-prerequisites)
4. [Panduan Instalasi Lokal via GitHub](#-panduan-instalasi-lokal-via-github)
5. [Panduan Deployment di cPanel (Shared Hosting / VPS)](#-panduan-deployment-di-cpanel-shared-hosting--vps)
    - [Metode 1: Menggunakan cPanel Git Version Control](#metode-1-menggunakan-cpanel-git-version-control-direkomendasikan)
    - [Metode 2: Upload Manual File ZIP](#metode-2-upload-manual-file-zip)
    - [Konfigurasi Web Root & Document Root di cPanel](#konfigurasi-web-root--document-root-di-cpanel)
    - [Konfigurasi Storage Symlink di cPanel](#konfigurasi-storage-symlink-di-cpanel)
6. [Daftar Perintah Penting (Command Reference)](#-daftar-perintah-penting-command-reference)
7. [Panduan Troubleshooting & Solusi Error](#-panduan-troubleshooting--solusi-error)
8. [Struktur Direktori Proyek](#-struktur-direktori-proyek)
9. [Kontak & Lisensi](#-kontak--lisensi)

---

## 📖 Tentang Aplikasi

**BPVP Pangkep - Super APP** adalah platform digital terpadu yang dirancang dan dikembangkan untuk **Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan**, salah satu Unit Pelaksana Teknis Pusat (UPTP) di bawah naungan **Kementerian Ketenagakerjaan Republik Indonesia (Kemnaker RI)**.

Aplikasi ini menyatukan portal informasi publik, manajemen kejuruan dan pelatihan vokasi, keterbukaan informasi publik (PPID), pelaporan layanan publik, manajemen terpadu tautan promosi dan penangkapan prospek (Shortlink & Leads Capture), agregator media sosial (SosmedHub), serta teknologi **Progressive Web App (PWA)** sehingga dapat dipasang langsung pada perangkat Android, iPhone, Windows, dan macOS seperti aplikasi native.

---

## 🚀 Fitur & Modul Utama

### 1. Website Resmi & Portal Publik (`Modules/Website`)

- **Profil Lembaga**: Sambutan Kepala Balai, Sejarah & Tentang Kami, Visi & Misi, Tugas & Fungsi, Struktur Organisasi, dan Profil Pejabat Struktural.
- **Layanan Publik & PPID**: Maklumat Pelayanan, Standar Pelayanan, Alur Pelayanan, Dokumen Informasi Publik Berkala, Setiap Saat, dan Serta Merta, serta Statistik Survei Kepuasan Masyarakat (IKM).
- **Kejuruan & Fasilitas**: Katalog jurusan pelatihan vokasi, ruang kelas, workshop kelautan, perikanan, otomotif, pengelasan, dan fasilitas pendukung.
- **Pemberitaan & Galeri**: Berita terupdate, pengumuman resmi, artikel, testimoni alumni pelatihan, dan dokumentasi kegiatan.
- **Pemberitahuan Pop-up & Banner Slider**: Banner dinamis dan pop-up informasi penting yang dapat diaktifkan/dinonaktifkan dari panel admin.

### 2. Modul Shortlink & Leads Capture (`Modules/Shortlink`)

- **Generator Tautan Pendek**: Membuat tautan pendek resmi dengan kode custom (`/s/{code}`) untuk pegawai dan instansi.
- **Formulir Buku Tamu / Capture Lead**: Halaman antara sebelum pengalihan URL untuk mengumpulkan identitas pengunjung (Nama, WhatsApp, Email).
- **Integrasi Google Spreadsheet Real-Time**:
    - Rumus `=IMPORTDATA()` otomatis untuk sinkronisasi live data daftar shortlink dan leads ke Google Sheets.
    - **Webhook Google Apps Script**: Setiap lead baru otomatis terkirim seketika ke Google Sheets dengan tombol pengujian ping (_Test Webhook_) langsung dari dashboard.
- **Ekspor & Impor Data**: Fitur ekspor data leads dan tautan ke format CSV/Excel dengan dukungan UTF-8 BOM, serta template impor massal.
- **Generator QR Code**: Otomatis menghasilkan kode QR siap cetak/unduh dalam format PNG resolusi tinggi.

### 3. Modul SosmedHub (`Modules/Sosmedhub`)

- **Kalender Konten Media Sosial**: Perencanaan publikasi konten lintas platform dengan status jadwal terstruktur.
- **Kelola Platform Resmi**: Tautan terverifikasi untuk akun Instagram, Facebook, YouTube, dan TikTok BPVP Pangkep.
- **Manajemen Kredensial & Graph API Token**: Form meta terpusat untuk konfigurasi App ID, App Secret, dan Access Token media sosial.

### 4. BPVP Pangkep - Super APP (Progressive Web App / PWA)

- **Standalone Mode**: Berjalan mandiri tanpa bilah alamat browser, memberikan pengalaman seluler yang mulus.
- **Offline Shell Caching**: Halaman offline yang menarik saat koneksi internet terputus dengan deteksi otomatis pemulihan sinyal.
- **In-App Install Prompt**: Banner instalasi cerdas untuk browser Chromium/Android dan instruksi khusus untuk Safari iOS.
- **Shortcuts & Icons Adaptif**: Ikon maskable standar modern dan menu jalan pintas langsung dari ikon aplikasi di beranda HP.

### 5. Manajemen Pengguna & Keamanan

- Autentikasi aman berbasis sesi Laravel dengan proteksi CSRF.
- Role-based Access Control (Super Admin, Admin Shortlink, Admin Website, dll.).

---

## 💻 Persyaratan Sistem (Prerequisites)

Sebelum melakukan instalasi, pastikan lingkungan server atau komputer Anda memenuhi spesifikasi berikut:

| Komponen       | Persyaratan Minimum      | Rekomendasi                 |
| -------------- | ------------------------ | --------------------------- |
| **PHP**        | 8.2.0                    | **8.4.x**                   |
| **Node.js**    | 18.x LTS                 | **20.x LTS / 22.x**         |
| **NPM**        | 9.x                      | **10.x**                    |
| **Database**   | MySQL 8.0 / MariaDB 10.4 | MySQL 8.0+ / MariaDB 10.6+  |
| **Composer**   | 2.5+                     | Versi terbaru               |
| **Web Server** | Apache 2.4 / Nginx 1.20  | Apache dengan `mod_rewrite` |

### Ekstensi PHP yang Wajib Aktif:

- `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd` (wajib untuk ikon PWA & gambar), `hash`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `xmlwriter`.

---

## 🛠️ Panduan Instalasi Lokal via GitHub

### Langkah 1: Kloning Repositori

Buka terminal / command prompt, lalu jalankan perintah:

```bash
git clone https://github.com/username-repo/bpvppangkep-new.git
cd bpvppangkep-new
```

### Langkah 2: Instal Dependensi PHP (Composer)

```bash
composer install
```

### Langkah 3: Instal Dependensi Frontend (NPM)

```bash
npm install
```

### Langkah 4: Konfigurasi File Lingkungan (`.env`)

Salin file `.env.example` menjadi `.env`:

```bash
# Windows PowerShell
copy .env.example .env

# Linux / macOS / Git Bash
cp .env.example .env
```

Buka file `.env` menggunakan text editor Anda dan sesuaikan konfigurasi database:

```env
APP_NAME="BPVP Pangkep - Super APP"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE="Asia/Makassar"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_bpvppangkep
DB_USERNAME=root
DB_PASSWORD=
```

### Langkah 5: Generate Application Key

```bash
php artisan key:generate
```

### Langkah 6: Jalankan Migrasi & Database Seeder

Pastikan database MySQL dengan nama yang sesuai di `.env` sudah dibuat di phpMyAdmin / MySQL CLI, lalu jalankan:

```bash
php artisan migrate --seed
```

### Langkah 7: Buat Tautan Simbolik Storage

```bash
php artisan storage:link
```

### Langkah 8: Kompilasi Aset Frontend

Untuk mode pengembangan (_development_) dengan Hot Module Replacement (HMR):

```bash
npm run dev
```

Atau jika ingin membuat build produksi (_production assets_):

```bash
npm run build
```

### Langkah 9: Jalankan Server Lokal

Di jendela terminal baru, jalankan server pengembangan Laravel:

```bash
php artisan serve
```

Aplikasi kini dapat diakses melalui browser di alamat: `http://localhost:8000` (atau domain Herd Anda jika menggunakan Laravel Herd).

---

## 🌐 Panduan Deployment di cPanel (Shared Hosting / VPS)

Ada dua metode yang dapat digunakan untuk mengunggah proyek ini ke hosting cPanel:

---

### Metode 1: Menggunakan cPanel Git Version Control (Direkomendasikan)

Jika hosting cPanel Anda memiliki fitur **Terminal SSH** dan menu **Git Version Control**:

1. **Buat Database MySQL**:
    - Masuk ke cPanel &rarr; pilih menu **MySQL Database Wizard**.
    - Buat nama database (misal: `cpaneluser_bpvp`), nama pengguna, dan kata sandi yang kuat.
    - Centang **ALL PRIVILEGES** &rarr; klik **Make Changes**.

2. **Kloning Repo via cPanel Git**:
    - Di cPanel, buka menu **Git™ Version Control**.
    - Klik tombol **Create**.
    - Masukkan _Clone URL_ repositori GitHub Anda.
    - Atur _Repository Path_ ke luar folder `public_html`, misalnya: `/home/cpaneluser/bpvppangkep-new`.
    - Klik **Create**.

3. **Buka Menu Terminal di cPanel**:
   Masuk ke folder proyek Anda:

    ```bash
    cd ~/bpvppangkep-new
    ```

4. **Instal Dependensi & Atur Lingkungan**:

    ```bash
    composer install --no-dev --optimize-autoloader
    cp .env.example .env
    nano .env
    ```

    _(Sesuaikan kredensial database cPanel, `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL=https://namadomainanda.go.id`)_.

5. **Generate Key & Migrasi**:

    ```bash
    php artisan key:generate
    php artisan migrate --force --seed
    php artisan storage:link
    ```

6. **Build Aset Frontend**:
   Jika server memiliki Node.js (via menu _Setup Node.js App_ atau Terminal cPanel):

    ```bash
    npm install
    npm run build
    ```

    _(Alternatif jika server tidak memiliki Node.js: jalankan `npm run build` di komputer lokal Anda, lalu unggah folder `public/build` ke hosting)._

7. **Optimasi Cache Produksi**:
    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

---

### Metode 2: Upload Manual File ZIP

Jika server tidak memiliki akses Git atau Terminal:

1. **Kompilasi di Komputer Lokal Terlebih Dahulu**:
   Di komputer lokal Anda, jalankan:

    ```bash
    composer install --no-dev --optimize-autoloader
    npm run build
    ```

2. **Kompres Folder Menjadi ZIP**:
   Pilih seluruh file proyek Anda, **KECUALI**:
    - Folder `.git`
    - Folder `node_modules`
    - File `.env` lokal Anda
      Kompres file-file tersebut menjadi `project-bpvp.zip`.

3. **Unggah ke cPanel**:
    - Buka **File Manager** cPanel.
    - Masuk ke direktori _home_ (satu tingkat di atas `public_html`), misalnya: `/home/cpaneluser/`.
    - Buat folder baru bernama `bpvppangkep-source`.
    - Upload file `project-bpvp.zip` ke folder tersebut dan lakukan **Extract**.

4. **Pindahkan Folder Public ke `public_html`**:
    - Buka folder `/home/cpaneluser/bpvppangkep-source/public/`.
    - Pindahkan seluruh isinya (termasuk folder `build`, `icons`, `storage`, file `index.php`, `.htaccess`, `manifest.json`, `sw.js`, `offline.html`) ke dalam folder `public_html/`.

5. **Sesuaikan Path pada `public_html/index.php`**:
   Buka file `public_html/index.php` menggunakan fitur edit cPanel, ubah baris path berikut:

    ```php
    // Ubah dari:
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';

    // Menjadi:
    require __DIR__.'/../bpvppangkep-source/vendor/autoload.php';
    $app = require_once __DIR__.'/../bpvppangkep-source/bootstrap/app.php';
    ```

6. **Konfigurasi Database & File `.env`**:
    - Buka folder `bpvppangkep-source`.
    - Buat / edit file `.env` sesuai data database cPanel Anda.
    - Impor struktur database melalui **phpMyAdmin** jika tidak bisa menjalankan migrasi lewat terminal.

---

### Konfigurasi Web Root & Document Root di cPanel

Jika Anda menggunakan Subdomain atau Addon Domain di cPanel, Anda dapat langsung mengatur **Document Root** domain mengarah ke folder public aplikasi, contohnya:
`/home/cpaneluser/bpvppangkep-new/public`

Jika menggunakan domain utama (`public_html`) dan ingin tetap mempertahankan struktur rapi tanpa memecah folder, buat file `.htaccess` di dalam `public_html/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

### Konfigurasi Storage Symlink di cPanel

Laravel menggunakan symlink dari `storage/app/public` ke `public/storage` untuk menampilkan berkas upload (logo, dokumen pdf, gambar slider, dan favicon).

Jika perintah `php artisan storage:link` tidak dapat dijalankan di cPanel karena tidak ada terminal SSH, gunakan salah satu solusi berikut:

#### Opsi A: Menggunakan Menu Cron Jobs di cPanel

1. Buka cPanel &rarr; pilih menu **Cron Jobs**.
2. Masukkan jadwal: `Once Per Minute` (\* \* \* \* \*).
3. Masukkan perintah:
    ```bash
    ln -s /home/usernamecpanel/bpvppangkep-new/storage/app/public /home/usernamecpanel/public_html/storage
    ```
4. Klik **Add New Cron Job**. Tunggu 1 menit hingga symlink terbentuk, lalu hapus cron job tersebut.

#### Opsi B: Membuat Route PHP Sementara

Tambahkan route sementara di `routes/web.php`:

```php
Route::get('/buat-symlink-storage', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return 'Storage symlink berhasil dibuat!';
});
```

Buka URL `https://domainanda.com/buat-symlink-storage` di browser sekali saja, lalu hapus kembali baris route tersebut demi keamanan.

---

## ⌨️ Daftar Perintah Penting (Command Reference)

Berikut ringkasan perintah yang sering digunakan dalam pengelolaan proyek:

### 1. Manajemen Cache & Optimasi

```bash
# Membersihkan seluruh cache (config, route, view, application)
php artisan optimize:clear

# Membuat cache konfigurasi, rute, dan view untuk produksi (kecepatan maksimal)
php artisan optimize

# Membersihkan log aplikasi jika terlalu besar
php artisan log:clear
```

### 2. Database & Migrasi

```bash
# Menjalankan migrasi database
php artisan migrate

# Menjalankan migrasi di server production dengan konfirmasi paksa
php artisan migrate --force

# Reset database dan jalankan seeder ulang (PERINGATAN: menghapus seluruh data!)
php artisan migrate:fresh --seed

# Cek status migrasi yang telah dieksekusi
php artisan migrate:status
```

### 3. Modul Laravel Modules (`nwidart/laravel-modules`)

```bash
# Menampilkan daftar seluruh modul dan status aktifnya
php artisan module:list

# Menjalankan migrasi hanya untuk modul tertentu
php artisan module:migrate Website
php artisan module:migrate Shortlink
php artisan module:migrate Sosmedhub
```

### 4. Frontend & PWA

```bash
# Mode pengembangan dengan HMR
npm run dev

# Kompilasi aset produksi (menghasilkan file bundle di public/build)
npm run build

# Menghasilkan ulang seluruh ukuran icon PWA
php scratch/generate_icons.php
```

### 5. Tautan & Debugging

```bash
# Memeriksa daftar rute yang terdaftar
php artisan route:list

# Memeriksa rute khusus modul shortlink
php artisan route:list --path=admin/shortlinks

# Mengakses REPL Tinker
php artisan tinker
```

---

## ❓ Panduan Troubleshooting & Solusi Error

### 1. Gambar & File PDF Upload Tidak Muncul (Error 404 pada `/storage/...`)

- **Penyebab**: Tautan simbolik (_symlink_) antara `public/storage` dan `storage/app/public` belum terpasang atau terputus saat dipindahkan ke server baru.
- **Solusi**:
    1. Hapus folder/shortcut `public/storage` lama jika rusak:
        ```bash
        rm public/storage
        ```
    2. Buat ulang tautan simbolik:
        ```bash
        php artisan storage:link
        ```
    3. Pastikan izin direktori (_file permissions_) untuk folder `storage/` dan `bootstrap/cache/` bernilai `775` atau `755`.

---

### 2. Error `ViteException: Unable to locate file in Vite manifest`

- **Penyebab**: File aset frontend belum dibuild atau file `public/build/manifest.json` belum terbuat.
- **Solusi**:
  Jalankan perintah kompilasi produksi di terminal:
    ```bash
    npm run build
    ```
    Kemudian bersihkan cache tampilan Laravel:
    ```bash
    php artisan view:clear
    ```

---

### 3. Error `502 Bad Gateway` di Localhost / Server

- **Penyebab**: PHP-FPM mengalami timeout atau proses Vite dev server tidak aktif saat aset dideklarasikan lewat HMR.
- **Solusi**:
    - Hentikan mode `npm run dev` jika sedang tidak diperlukan, lalu jalankan `npm run build`.
    - Hapus file `public/hot` jika tertinggal:

        ```bash
        # Windows
        del public\hot

        # Linux / macOS
        rm -f public/hot
        ```

    - Restart layanan PHP pada Laravel Herd atau Nginx/Apache.

---

### 4. Integrasi Webhook Google Sheets Tidak Mengirim Data

- **Penyebab**: Google Apps Script belum diatur ke perizinan publik atau URL webhook yang dimasukkan salah.
- **Solusi**:
    1. Buka spreadsheet Google Anda &rarr; **Ekstensi** &rarr; **Apps Script**.
    2. Klik tombol **Deploy** &rarr; **Manage Deployments**.
    3. Pastikan kolom **Who has access** dipilih **Anyone** (Siapa saja). Jika dipilih _"Only myself"_, server web tidak akan diberi izin mengirim data POST.
    4. Salin URL yang berakhiran `/exec`.
    5. Masuk ke panel Admin BPVP Pangkep &rarr; Tab **Integrasi Google Sheets** &rarr; Masukkan URL & klik tombol **Uji Kirim (Test Ping)** untuk memastikan status HTTP 200.

---

### 5. Tombol Pasang PWA Tidak Muncul di Browser

- **Penyebab**: Aplikasi tidak diakses menggunakan protokol aman HTTPS (PWA mewajibkan HTTPS kecuali di `localhost`), atau aplikasi sudah terpasang di perangkat Anda.
- **Solusi**:
    - Pastikan domain menggunakan sertifikat SSL aktif (`https://`).
    - Jika menggunakan Safari di iOS, Apple tidak mendukung pop-up otomatis; gunakan menu _Bagikan (Share)_ &rarr; _Tambahkan ke Layar Utama (Add to Home Screen)_.

---

### 6. Error Permission Denied (`storage/logs/laravel.log`) di cPanel

- **Penyebab**: Web server tidak memiliki hak akses tulis (_write permission_) pada folder penyimpanan Laravel.
- **Solusi**:
  Jalankan perintah izin akses di Terminal cPanel:
    ```bash
    chmod -R 775 storage bootstrap/cache
    ```

---

## 📂 Struktur Direktori Proyek

```plaintext
bpvppangkep-new/
├── app/                        # Inti aplikasi Laravel (Models, Controllers, Middleware)
├── bootstrap/                  # Inisialisasi framework & caching
├── config/                     # File konfigurasi Laravel
├── database/                   # Migrasi, factories, dan database seeders
├── Modules/                    # Arsitektur Modular (Laravel Modules)
│   ├── Shortlink/              # Modul Manajemen Shortlink & Leads Capture
│   │   ├── app/                # Controllers, Models Shortlink
│   │   ├── database/           # Migrasi tabel shortlink & leads
│   │   ├── resources/          # Komponen Vue 3 & Halaman Admin Shortlink
│   │   └── routes/             # Rute khusus shortlink (/s/{code}, feeds, admin)
│   ├── Sosmedhub/              # Modul Agregator & Kalender Media Sosial
│   └── Website/                # Modul Profil, Pelayanan Publik, Berita, & PPID
├── public/                     # Dokumen Web Root Publik
│   ├── build/                  # Aset hasil kompilasi Vite (CSS, JS)
│   ├── icons/                  # Seluruh paket ikon resmi PWA Super APP
│   ├── manifest.json           # Konfigurasi PWA Web App Manifest
│   ├── sw.js                   # Service Worker PWA (Offline cache & sync)
│   ├── offline.html            # Halaman cadangan saat koneksi offline
│   └── storage/                # Tautan simbolik berkas unggahan
├── resources/                  # Frontend Utama (Inertia.js, Vue 3, CSS Tailwind)
│   ├── css/                    # Desain Tailwind CSS 4
│   ├── js/                     # Vue App bootstrapping, layout admin, modul PWA
│   └── views/                  # app.blade.php (Template root HTML)
├── routes/                     # Rute utama aplikasi (web.php, api.php, console.php)
├── storage/                    # Berkas unggahan, cache framework, dan file logs
├── .env.example                # Template konfigurasi environment
├── .gitignore                  # Aturan file yang diabaikan git
├── composer.json               # Dependensi PHP
├── package.json                # Dependensi JavaScript & Vite
└── vite.config.js              # Konfigurasi bundler Vite
```

---

## 🏛️ Kontak & Dukungan Resmi

Jika Anda memerlukan bantuan teknis atau informasi lebih lanjut terkait pengembangan sistem ini:

- **Instansi**: Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan
- **Lembaga Induk**: Direktorat Jenderal Pembinaan Pelatihan Vokasi dan Produktivitas, Kementerian Ketenagakerjaan Republik Indonesia
- **Alamat**: Jl. Poros Makassar - Parepare KM. 68, Pangkajene dan Kepulauan, Sulawesi Selatan
- **Website Resmi**: [https://bpvppangkep.kemnaker.go.id](https://bpvppangkep.kemnaker.go.id)

---

_Dikembangkan dengan dedikasi untuk peningkatan mutu pelatihan vokasi dan keterbukaan pelayanan publik di Indonesia._

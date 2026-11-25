# LogiPro — Sistem Manajemen Agen Logistik

Aplikasi web berbasis **PHP Native (PHP 8.0+)**, **MySQL/MariaDB**, dan **Bootstrap 5** untuk mengelola operasional agen pengiriman logistik secara menyeluruh—mulai dari pencatatan transaksi, kalkulasi tarif berversi, cetak resi, pelacakan publik, keuangan, notifikasi WhatsApp/Telegram, hingga rekomendasi penanganan keluhan berbasis AI.

---

## ✨ Fitur Utama

### 1. Modul Inti & Operasional (Tahap 1 & 5)

- **Autentikasi & Hak Akses Ketat:** Login berbasis _role_ (`Admin` & `Staff`) dengan perlindungan CSRF, validasi sesi aktif setiap permintaan, dan penguncian akun otomatis selama 15 menit setelah 5 kali gagal login.
- **Transaksi & Kalkulasi Otomatis:** Hitung berat aktual vs berat volumetrik (`P × L × T ÷ faktor volumetrik`), pembulatan berat ditagihkan, ongkir dasar, diskon, serta perhitungan komisi agen.
- **Cetak Resi Multi-Ukuran:** Mendukung pencetakan struk/nota pengiriman ukuran **58mm**, **80mm**, dan **A4** melalui `cetak-resi.php`.
- **Pelacakan Paket (Publik & Internal):** Halaman `tracking.php` untuk pelanggan melacak status resi tanpa login (tanpa menampilkan data sensitif), serta `tracking-internal.php` bagi petugas untuk memperbarui riwayat status.

### 2. Keuangan, Laporan & Pengguna (Tahap 2, 3 & 6)

- **Pembayaran & Cicilan:** Pencatatan pembayaran tunai, transfer, atau QRIS dengan status lunas otomatis dan fitur pembatalan transaksi berizin admin.
- **Pengeluaran & Laporan Keuangan:** Rekap omzet, kas masuk/keluar, komisi bersih agen, piutang, grafik harian/ekspedisi, cetak A4, dan ekspor ke format **CSV**.
- **Manajemen Pengguna & Audit:** Pengelolaan akun staf/admin (`users.php`), _audit log_ aktivitas penting, serta fitur unduh salinan _backup_ database (`.sql`).

### 3. Notifikasi Otomatis & AI Keluhan (Tahap 4 & 7)

- **Kirim Struk WhatsApp & Telegram:** Integrasi cURL dengan API **Fonnte** (WhatsApp) dan **Telegram Bot** langsung dari halaman detail transaksi, dilengkapi pencegah kirim ganda (_rate limit_ & konfirmasi ulang).
- **Rekomendasi Keluhan Pelanggan (AI Gateway):** Penyusunan saran tindakan dan draf balasan keluhan pelanggan secara aman menggunakan penyamaran data pribadi (_redaction_) dan batas permintaan per jam.

---

## 🛠️ Kebutuhan Sistem

- **Web Server:** Apache (XAMPP / Laragon / Hosting Linux) dengan `mod_rewrite` / `.htaccess` aktif.
- **PHP:** Versi 8.0 atau lebih baru dengan ekstensi `mysqli`, `mbstring`, dan `curl` aktif.
- **Database:** MySQL 5.7+ atau MariaDB 10.3+ (mendukung _generated virtual columns_).

---

## 🚀 Cara Instalasi di Lokal (XAMPP)

### 1. Persiapan Folder

1. Kloning atau salin folder proyek ke direktori `C:\xampp\htdocs\agen-logistik\`.
2. Jalankan **Apache** dan **MySQL** melalui XAMPP Control Panel.
3. Pastikan ekstensi `curl` aktif di `C:\xampp\php\php.ini` (hapus tanda `;` pada `;extension=curl`, lalu restart Apache).

### 2. Setup Database

1. Buka `http://localhost/phpmyadmin` dan buat database baru bernama **`db_logistik`**.
2. Masuk ke database `db_logistik`, lalu impor file SQL berikut **secara berurutan**:
   1. `database/db_logistik.sql` _(struktur tabel utama)_
   2. `database/seed.sql` _(data awal ekspedisi, kota, layanan, dan tarif contoh)_
   3. `database/notifikasi_log.sql` _(tabel log WhatsApp & Telegram)_
   4. `database/tahap5.sql` _(kolom virtual `status_pengiriman`)_
   5. `database/tahap7.sql` _(tabel riwayat AI keluhan pelanggan)_
3. Sesuaikan konfigurasi koneksi database di `config/koneksi.php` jika password MySQL Anda tidak kosong.

### 3. Konfigurasi Rahasia (API WhatsApp, Telegram & AI)

Salin file `config/rahasia.contoh.php` menjadi **`config/rahasia.php`**, lalu isi token Anda:

```php
<?php
// Konfigurasi Fonnte (WhatsApp) & Telegram
define('FONNTE_TOKEN', 'ISI_TOKEN_FONNTE_ANDA');
define('TELEGRAM_BOT_TOKEN', 'ISI_TOKEN_BOT_TELEGRAM');
define('TELEGRAM_CHAT_ID', 'ISI_CHAT_ID_TUJUAN');

// Konfigurasi AI Relay (Opsional - Tahap 7)
define('LOGIPRO_AI_RELAY_URL', 'https://DOMAIN-PUBLIK-ANDA/api/public/complaint-recommendation');
define('LOGIPRO_AI_RELAY_SECRET', 'ISI_KUNCI_RAHASIA_MINIMAL_32_KARAKTER');
```

_(Catatan: File `config/rahasia.php` sudah masuk dalam `.gitignore` agar kredensial tidak ikut terunggah ke repositori publik)._

### 4. Pembuatan Akun Awal

1. Buka `http://localhost/agen-logistik/setup.php` satu kali di browser untuk membuat akun bawaan:
   - **Admin:** `admin` / `admin123`
   - **Staff:** `staff` / `staff123`
2. **Hapus file `setup.php`** segera setelah akun berhasil dibuat.
3. Login melalui `http://localhost/agen-logistik/` dan segera ganti kata sandi bawaan.

---

## 🧪 Pengujian Aturan (CLI)

Proyek ini menyertakan skrip pengujian unit untuk memeriksa aturan bisnis, format resi, notifikasi, hak akses, dan keluhan melalui terminal/Command Prompt:

```bash
php tests/uji-struk.php
php tests/uji-resi.php
php tests/uji-tahap6.php
php tests/uji-keluhan.php
```

---

## 🔒 Checklist Keamanan Sebelum Hosting Produksi

- [ ] Hapus file `setup.php`, `test-telegram.php`, dan seluruh folder `tests/` sebelum mengunggah ke server publik.
- [ ] Ganti seluruh password akun bawaan dan ubah nama gerai DUMMY di tabel `pengaturan` (`nama_usaha`).
- [ ] Matikan `display_errors` di konfigurasi PHP server produksi dan wajib gunakan **HTTPS**.
- [ ] Pastikan folder `config/`, `includes/`, dan `database/` terlindungi dari akses langsung browser.

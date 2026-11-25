# Tahap 6 — Akses, Pengguna/Staff, Laporan

Kode lengkap PHP, HTML dan CSS Bootstrap 5 berada di paket ini; melanjutkan Tahap 5 tanpa membuat ulang aplikasi. PHP 8.0+ dan MySQL/MariaDB `db_logistik`.

## Pemasangan XAMPP dari Tahap 5
1. Backup folder `C:\xampp\htdocs\agen-logistik\` dan database melalui phpMyAdmin.
2. Ekstrak ZIP, lalu salin isi folder `agen-logistik/` ke `C:\xampp\htdocs\agen-logistik\`.
3. Pertahankan `config/koneksi.php`, `config/rahasia.php` dan konfigurasi provider lokal Anda; jangan timpa penyesuaian pribadi. Token tidak disertakan.
4. Tidak ada perubahan skema database Tahap 6. **Jangan impor ulang db_logistik.sql, seed.sql, atau tahap5.sql pada database lama.**
5. Jalankan Apache/MySQL, keluar lalu masuk lagi melalui `http://localhost/agen-logistik/`.
6. Admin membuka menu Pengguna (`users.php`) dan Laporan (`laporan.php`). URL lama `pengguna.php` tetap berfungsi.
7. Hapus `setup.php` setelah instalasi. Hapus `test-telegram.php` dan folder `tests/` sebelum hosting nyata; ganti password contoh dan gunakan HTTPS.

Instalasi pertama pada database kosong saja: impor db_logistik.sql, seed.sql (data dummy, bukan tarif nyata), notifikasi_log.sql, tahap5.sql; jalankan setup.php sekali lokal, lalu ganti password contoh dan hapus setup. Lihat README sebelumnya. Jangan lakukan langkah database kosong pada data usaha.

## Penempatan file utama
- `config/auth.php`: middleware terpusat, memvalidasi akun aktif dan role terbaru dari database.
- `config/app.php`: bootstrap sesi, cookie HttpOnly/SameSite, Secure bila HTTPS.
- `includes/auth.php`: kompatibilitas pemanggilan lama.
- `includes/access-policy.php`: allowlist staff, endpoint baru default admin-only.
- `includes/csrf.php`: menolak token kosong, salah, hilang atau bukan string.
- `includes/sidebar.php`: menu sesuai izin; bukan pengganti middleware.
- `includes/user-management.php`: transaksi database, prepared statements, password_hash, perlindungan admin, penghapusan aman.
- `actions/simpan-pengguna.php`: POST khusus admin dan CSRF.
- `users.php`: daftar ID/Nama/Username/Role/Status dan form tambah/edit/hapus.
- `pengguna.php`: URL kompatibilitas lama.
- `includes/report-range.php`: validasi tanggal ketat dan rentang inklusif.
- `laporan.php`: rekap, CSV dan cetak khusus admin.
- `assets/css/laporan.css`: tampilan cetak A4 tanpa menu/grafik.
- `api/hitung-ongkir.php`: staff tidak menerima tarif internal/modal/komisi.
- `detail-transaksi.php`: cetak tersedia untuk staff, status/notifikasi khusus admin.
- `index.php`: setelah login staff ke daftar transaksi; admin ke dashboard.
- `tests/uji-tahap6.php`: pemeriksaan aturan akses dan tanggal; jalankan dari CLI.

Salin semua file bersama-sama. Setiap halaman private, aksi dan API melewati config/app.php → config/auth.php. URL langsung tetap ditolak (403), tidak sekadar menyembunyikan menu.

## Hak akses
Staff hanya input resi, daftar/detail transaksi, cetak resi, API kalkulasi input, simpan pengiriman dan logout. Pembayaran awal dalam form input tetap tersedia. Admin penuh: tarif, ekspedisi, pengguna, laporan, dashboard, backup, pembayaran lanjutan, pengeluaran, status dan notifikasi.
Dengan batas staff ketat, admin kini menangani update status, WhatsApp/Telegram dan penggantian password staff; fitur lama tidak dihapus. Tracking publik tetap tanpa login dan tanpa data privat.
Role/akun aktif dibaca ulang setiap permintaan; sesi akun terhapus/nonaktif tidak tetap berizin.

## Kelola akun
Tambah: isi nama, username unik (3–50 huruf/angka/titik/underscore), role admin/staff, password 8–72 byte. Hash memakai password_hash(PASSWORD_DEFAULT), login password_verify.
Edit via pensil; password kosong mempertahankan hash lama. Ikon daya mengubah aktif/nonaktif. Ikon tempat sampah menghapus akun yang tidak punya riwayat.
**Akun dengan riwayat transaksi/pembayaran/status/pengeluaran/log notifikasi dinonaktifkan, bukan dihapus fisik**, supaya foreign key dan resi lama tetap utuh. Pesan hasil menjelaskan tindakan sebenarnya.
Tidak boleh hapus/nonaktif/turunkan role akun sendiri; minimal satu admin aktif dilindungi. Semua perubahan menggunakan POST, CSRF, prepared statements dan audit.

## Laporan
Pilih Dari Tanggal sampai Sampai Tanggal: termasuk kedua hari, berdasarkan tanggal transaksi, bukan tanggal perubahan status.
- Total Paket Terkirim: jumlah transaksi dengan status `terkirim`, bukan jumlah koli.
- Total Omset: SUM(total_tagihan) setelah diskon dari seluruh transaksi non-batal dalam periode, termasuk yang belum terkirim.
- Total Komisi Bersih untuk Agen: SUM(komisi) tersimpan dari transaksi non-batal; tidak dihitung ulang dari tarif terbaru, **bukan laba setelah pengeluaran atau komisi yang pasti diterima tunai**.
- Kas masuk/pengeluaran/piutang lama tetap tersedia (piutang semua periode diberi label).
- Cetak membuka dialog browser, dapat disimpan PDF. CSV berisi ringkasan dan detail non-batal, dengan perlindungan formula Excel.
- Tanggal tidak valid/terbalik ditolak (422).

## Verifikasi dan batasnya
Implementasi sebelum pemulihan terakhir lulus 44 pemeriksaan akses/akun/tanggal/keuangan pada PHP 8.4 + MariaDB 11.4 lokal. Browser membuktikan tambah/edit/hapus staff, penolakan URL admin, CSRF hilang, pencabutan sesi, input staff hingga detail/struk, dan laporan/CSV 1 terkirim / Rp100.000 / Rp10.000. Tampilan cetak dan struk diperiksa.
Folder sementara kemudian terhapus oleh sesi platform; kode direkonstruksi dengan perbaikan yang sama. Paket rekonstruksi diperiksa sintaks dan aturan akses/tanggal/struk/resi kembali; **pengujian browser/database belum diulang pada rekonstruksi terakhir**. Jangan menganggapnya sudah diuji di XAMPP/printer/hosting Anda.
Audit Tahap 4 terpisah masih belum lengkap; pengiriman asli Fonnte/Telegram tidak diuji, paket bukan pernyataan siap produksi.
Jalankan `php tests/uji-tahap6.php`, `php tests/uji-resi.php`, `php tests/uji-struk.php` untuk pemeriksaan CLI. Folder tests tidak boleh tersedia secara publik pada hosting nyata.

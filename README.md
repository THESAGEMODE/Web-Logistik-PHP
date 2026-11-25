# LogiPro — Tahap 1 (modul inti)

## Cara menjalankan di XAMPP
1. Salin folder `agen-logistik` ke `C:\xampp\htdocs\`.
2. Buka XAMPP Control Panel, nyalakan **Apache** dan **MySQL**.
3. Buka http://localhost/phpmyadmin → tab **Import** → pilih `database/db_logistik.sql` → Go. Lalu impor `database/seed.sql`.
4. Buka http://localhost/agen-logistik/setup.php sekali (membuat akun awal), lalu **hapus file setup.php**.
5. Login di http://localhost/agen-logistik — `admin / admin123` atau `staff / staff123` (hanya untuk lokal, segera ganti).

Jika password MySQL Anda bukan kosong, ubah di `config/koneksi.php`.

## Rumus
- Berat volumetrik = P × L × T ÷ faktor volumetrik (diatur per tarif, default 6000)
- Berat ditagihkan = pembulatan ke atas dari nilai terbesar (berat aktual, volumetrik, berat minimum)
- Ongkir dasar = berat ditagihkan × tarif per kg; Total = ongkir dasar + biaya tambahan − diskon
- Komisi: persen × ongkir dasar | nominal tetap | ongkir dasar − (berat ditagihkan × tarif modal)
- Total tagihan = omzet, BUKAN keuntungan. Keuntungan = komisi − pengeluaran operasional (dihitung di modul laporan tahap 2).

## Sudah ada (tahap 1)
Login + role (dicek di server), dashboard + grafik, ekspedisi & layanan, tarif berversi, input transaksi dengan hitung otomatis (dihitung ulang server, snapshot tarif), daftar transaksi + pencarian + pagination, detail & cetak bukti, tracking + riwayat status dengan validasi alur, audit log, CSRF, prepared statement.

## Belum ada (tahap 2)
Halaman pembayaran (cicilan), pengeluaran, laporan + ekspor CSV, manajemen pengguna, pengaturan.

## Tahap 2
- pembayaran.php — catat bayar tunai/transfer/QRIS, cicilan, status lunas otomatis
- pengeluaran.php — catat biaya operasional (hapus khusus admin)
- laporan.php — omzet, kas masuk/keluar, komisi, piutang, per ekspedisi, ekspor CSV (admin)
- pengguna.php — tambah/edit/nonaktifkan staf + log aktivitas (admin)
- profil.php — ganti password sendiri
Tidak perlu impor SQL baru: tabelnya sudah ada sejak tahap 1.

## Tahap 3
- Admin bisa membatalkan pembayaran yang salah (wajib isi alasan, tercatat di log)
- Grafik omzet harian dan per ekspedisi di Laporan
- Login dikunci 15 menit setelah 5 kali gagal dari komputer yang sama
- Menu Backup: unduh salinan database (.sql), dengan peringatan jika lebih dari 7 hari

## Tahap 4 — Kirim struk WhatsApp (Fonnte) dan Telegram

### File baru / berubah
- `config/whatsapp.php`, `config/telegram.php` — pengaturan (tanpa token asli)
- `config/rahasia.contoh.php` — contoh tempat token. Salin menjadi `config/rahasia.php`
- `includes/struk.php` — normalisasi nomor, format struk, cek kirim ganda
- `includes/whatsapp.php`, `includes/telegram.php` — pengirim pesan (cURL)
- `actions/kirim-struk-whatsapp.php`, `actions/kirim-struk-telegram.php`
- `detail-transaksi.php` — tombol hijau WhatsApp, tombol Telegram, riwayat kirim
- `database/notifikasi_log.sql` — tabel `whatsapp_logs` dan `telegram_logs`
- `test-telegram.php` — halaman uji sementara (admin). HAPUS setelah selesai
- `tests/uji-struk.php` — uji aturan nomor, format struk, kirim ganda

### Langkah pemasangan
1. Impor `database/notifikasi_log.sql` di phpMyAdmin (tabel lama tidak berubah).
2. Pastikan cURL aktif: buka `C:\xampp\php\php.ini`, cari `;extension=curl`, hapus titik koma, simpan, restart Apache.
3. Salin `config/rahasia.contoh.php` menjadi `config/rahasia.php`, lalu isi:
   - **FONNTE_TOKEN**: daftar di fonnte.com → hubungkan nomor WhatsApp (scan QR) → menu Device → salin Token.
   - **TELEGRAM_BOT_TOKEN**: chat @BotFather di Telegram → `/newbot` → salin token.
   - **TELEGRAM_CHAT_ID**: kirim pesan apa saja ke bot Anda, lalu buka
     `https://api.telegram.org/bot<TOKEN>/getUpdates` di browser dan salin angka `chat":{"id":...`.
     Untuk grup: masukkan bot ke grup dulu; ID grup diawali tanda minus.
4. Jangan pernah mengirim `rahasia.php` ke orang lain atau mengunggahnya ke GitHub.

### Cara menguji
1. Login sebagai admin, buka `http://localhost/agen-logistik/test-telegram.php`, klik **Kirim Pesan Uji**.
2. Buka detail transaksi yang nomor pengirimnya adalah nomor WhatsApp Anda sendiri, klik **Kirim Struk WhatsApp**.
3. Lihat tabel **Riwayat kirim struk** di bawah bukti pengiriman.
4. Uji aturan (opsional, lewat Command Prompt): `C:\xampp\php\php.exe tests\uji-struk.php`

### Arti status
- **Diterima provider** (WhatsApp): Fonnte menerima permintaan. BELUM bukti pesan sampai/dibaca.
- **Terkirim ke chat** (Telegram): Telegram sudah memasukkan pesan ke chat tujuan.
- **Gagal**: pasti tidak terkirim. Aman dicoba lagi setelah masalah diperbaiki.
- **Belum pasti**: waktu habis atau jawaban tidak terbaca. Pesan mungkin sudah terkirim.
  Cek dashboard Fonnte / chat Telegram dulu sebelum menekan Kirim Ulang.

### Pencegah kirim ganda
- Tombol terkunci saat mengirim.
- Dalam 10 menit setelah berhasil, atau setelah status "Belum pasti", wajib centang/konfirmasi kirim ulang.
- Maksimal 5 permintaan per transaksi per jam.

### Jika gagal
| Pesan | Penyebab | Solusi |
|---|---|---|
| Token belum diatur | `rahasia.php` belum ada/kosong | Ulangi langkah 3 |
| cURL belum aktif | php.ini | Langkah 2 |
| Nomor tidak valid | Nomor di transaksi salah | Harus nomor HP Indonesia (08…/62…) |
| Ditolak provider: … | Perangkat Fonnte terputus, kuota habis, token salah | Cek dashboard Fonnte, scan ulang QR |
| Ditolak Telegram: chat not found | Chat ID salah atau belum chat bot | Kirim pesan ke bot dulu, cek Chat ID |
| Tidak dapat tersambung | Internet mati | Periksa koneksi |
| Gagal menyiapkan pengiriman | Tabel belum diimpor | Langkah 1 |
Detail teknis tercatat di `C:\xampp\apache\logs\error.log` (tanpa token).

### Belum tersedia
- Status "sudah dibaca" WhatsApp butuh webhook Fonnte dan website yang online (bukan localhost).
  Dokumentasi Fonnte tidak menyebut cara memverifikasi bahwa webhook benar dari Fonnte, jadi perlu pengaman tambahan.


## Tahap 5
Lihat README-tahap5.md: cetak 58/80mm/A4, tracking publik, SQL kompatibel, pemasangan dan batas pengujian.

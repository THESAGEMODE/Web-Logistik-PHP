# Tahap 7 — Keluhan Pelanggan & Rekomendasi AI Gateway

Melanjutkan aplikasi PHP tahap 6, bukan menggantinya dengan aplikasi online. Staf/admin membuka detail transaksi → Keluhan Pelanggan. AI hanya menyusun rekomendasi dan draf balasan, tidak memperbarui status, memproses refund, atau mengirim WhatsApp/Telegram. Catatan resi adalah status internal agen, bukan pemeriksaan langsung API ekspedisi.

## Pasang di XAMPP
1. Backup folder dan database lama. Salin isi `agen-logistik/` dari paket ini ke `C:\xampp\htdocs\agen-logistik\`. Pertahankan `config/koneksi.php` dan `config/rahasia.php` lokal.
2. Impor **hanya** `database/tahap7.sql` ke database lama `db_logistik` melalui phpMyAdmin. Jangan impor ulang skema/seed lama.
3. Aktifkan ekstensi PHP mysqli, mbstring, curl serta CA certificate yang valid. Jangan mematikan verifikasi TLS. PHP harus dapat mengakses internet HTTPS; panggilan dapat berlangsung hingga 120 detik.
4. Pemilik mempublikasikan aplikasi pendamping Lovable ini. URL berakhir `/api/public/complaint-recommendation` adalah sambungan privat server-ke-server, bukan halaman pelanggan.
5. Tambahkan rahasia Cloud bernama `LOGIPRO_AI_RELAY_SECRET` lewat formulir aman Lovable. Gunakan Generate, salin nilainya sebelum menyimpan, lalu masukkan nilai yang sama di `config/rahasia.php` XAMPP (minimal 32 karakter). Jangan membagikan nilai di chat, JavaScript, screenshot atau ZIP.
6. Tambahkan di file rahasia lokal (jangan menimpa Fonnte/Telegram):
   ```php
   define('LOGIPRO_AI_RELAY_URL', 'https://DOMAIN-PUBLIK-ANDA/api/public/complaint-recommendation');
   define('LOGIPRO_AI_RELAY_SECRET', 'ISI-KUNCI-DARI-FORMULIR-AMAN');
   ```
   Domain contoh bukan sambungan aktif. Dua nilai juga dapat diberikan melalui environment server. Kunci ini berbeda dari kunci AI Gateway; `LOVABLE_API_KEY` tetap dikelola Cloud dan tidak disalin ke PHP.
7. Login staf, buka transaksi yang sudah ada, pilih Keluhan Pelanggan. Tulis keluhan tanpa data pribadi, centang persetujuan, klik Susun rekomendasi. Tinjau rekomendasi/draf sebelum menggunakannya. Riwayat tetap tersimpan ketika halaman dimuat ulang.
8. Hapus folder tests dan setup/provider test sebelum hosting; gunakan HTTPS dan lindungi config, database, includes.

## Penempatan dan keamanan
- `keluhan.php`: halaman GET/POST berizin, CSRF, membaca transaksi dengan ID numerik dan prepared statements.
- `config/ai.php`: konfigurasi URL HTTPS dan kunci sambungan lokal.
- `includes/ai-complaint.php`: snapshot minimal, penyamaran data, validasi hasil dan satu panggilan cURL tanpa retry/redirect.
- `database/tahap7.sql`: riwayat keluhan, snapshot status pada waktu permintaan, hasil atau kegagalan. Backup lama otomatis mencakup tabel ini.
- `includes/access-policy.php`: hanya menambah keluhan ke allowlist staf, izin admin/keuangan tidak diperluas.
- `includes/user-management.php`: akun yang memiliki riwayat keluhan dinonaktifkan saat dihapus, bukan dihapus fisik.
- `detail-transaksi.php`: tombol Keluhan Pelanggan.
- Sambungan Cloud menggunakan SDK `@lovable.dev/ai-gateway`, model bawaan `openai/gpt-6-astra` Responses, maksimal 3500 token keluaran. Model tidak diganti otomatis jika izin/kredit ditolak.
- Batas 10 permintaan/staf/jam di PHP; 20 permintaan/jam untuk seluruh sambungan di Cloud. ID unik dicatat sebelum panggilan, ID sama tidak dapat dipanggil dua kali. Metadata Cloud hanya ID dan waktu, bukan data pelanggan. Kunci sambungan dibatasi ke endpoint ini; rotasi dengan memperbarui nilai Cloud dan PHP bersama.
- Snapshot tidak membawa nama/telepon/alamat pelanggan atau komisi. Teks keluhan menyamarkan nilai pribadi yang dikenal, telepon dan email; redaksi otomatis tidak menjamin semua data pribadi tertangkap. Staf wajib menghapus data pribadi/rahasia sebelum mengirim. Keluhan yang sudah disamarkan tetap disimpan lokal.
- UI menampilkan data dasar pada saat permintaan agar rekomendasi lama tidak terlihat sebagai status terbaru. Riwayat processing/unknown tidak dianggap sukses dan tidak dikirim ulang otomatis. Timeout dapat terjadi setelah AI mulai diproses; hubungi pemilik untuk memeriksa log Gateway sebelum permintaan baru.
- Rekomendasi bisa salah. Staf bertanggung jawab memeriksa fakta, kontak ekspedisi dan izin admin; jangan menjanjikan waktu tiba/refund dari hasil AI.

## Verifikasi
Jalankan `php tests/uji-keluhan.php` dan uji tahap 6/resi/struk. Pengujian kontrak sambungan AI juga disimpan di proyek Lovable. Pengujian lokal tidak membuktikan sambungan XAMPP ke hosting, model berizin, printer atau provider pesan nyata. Pemasangan rahasia dan publikasi diperlukan sebelum uji AI dengan transaksi nyata.

### Hasil pemeriksaan paket ini
18 uji aturan keluhan, 30 uji tahap 6, 32 uji resi dan 15 uji struk lulus; seluruh 60 file PHP lulus pemeriksaan sintaks PHP 8.4.27. Enam uji kontrak/routing Lovable lulus. Sambungan browser menghasilkan 503 karena kunci yang tersimpan belum memenuhi minimum 32 karakter; belum ada panggilan model berhasil, uji simpan/baca ulang keluhan melalui UI staf atau pengujian database tahap 7. Pemilik perlu mengganti kunci dengan Generate, mempublikasikan sambungan, lalu memasang paket dan menguji alur di XAMPP.

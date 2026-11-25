# LogiPro Tahap 5 — PHP Native

Paket melanjutkan versi 4, tidak mengganti aplikasi dengan preview Lovable. Cetak membutuhkan login admin/staf; tracking pelanggan tidak memerlukan login. WA/Telegram tidak diubah. Status paket berasal dari pencatatan petugas, bukan API ekspedisi.

## SQL disesuaikan dengan database sebenarnya
Versi 4 memiliki `total_tagihan`, `resi_ekspedisi`, dan `status`, bukan `total_biaya` atau `no_resi`. Jangan menjalankan SQL brief dengan `AFTER total_biaya` karena kolom itu tidak ada.

`database/tahap5.sql` menambahkan `status_pengiriman VARCHAR(50)` setelah `total_tagihan` sebagai generated virtual column. Label mengikuti `status` otomatis; default Manifest statis tidak digunakan karena akan salah untuk transaksi lama yang sudah terkirim. Jangan mengisi kolom turunan secara manual. MySQL 5.7+ atau MariaDB dengan dukungan generated columns diperlukan. Bila kolom sudah ada, SQL melewatkannya tanpa mengubah data/struktur lama: periksa definisinya jika pernah dibuat manual.

Manifest: dibuat/menunggu_bayar/menunggu_serah. On Process: diserahkan/perjalanan/diantar. Delivered: terkirim. Bermasalah dan Dibatalkan tidak dianggap Delivered. Label nota/publik mengikuti status utama yang sama.

## Pemasangan pada versi 4
1. Hentikan input sementara, backup folder aplikasi dan database. Jangan impor ulang `db_logistik.sql` atau `seed.sql` pada data nyata.
2. Ekstrak ZIP sementara; salin hanya file baru/berubah di bawah ke `C:\xampp\htdocs\agen-logistik\` sesuai folder. Pertahankan `config/koneksi.php` dan `config/rahasia.php` Anda.
3. Di phpMyAdmin pilih `db_logistik`, impor hanya `database/tahap5.sql`. SQL tidak menghapus tabel atau data.
4. Nama agen dibaca dari tabel `pengaturan`, kunci `nama_usaha`. Ganti nama DUMMY dengan nama gerai sebenarnya melalui phpMyAdmin.
5. Setelah login, detail transaksi → Cetak Struk. Pilih 58mm, 80mm atau A4; klik Cetak Struk. Atur ukuran kertas printer sama, skala 100%, tanpa header/footer browser; sesuaikan margin dengan perangkat.
6. Link langsung: `cetak-resi.php?id=123&ukuran=58` atau `cetak-resi.php?resi=RESI-ASLI`. Parameter resi memakai `resi_ekspedisi`. Resi duplikat ditolak sebagai ambigu; gunakan ID untuk cetak.
7. Pelanggan: `tracking.php?resi=RESI-ASLI`, tanpa login. Nomor transaksi internal tidak bisa dicari. Tracking tidak menampilkan nama/HP/alamat lengkap, tagihan, komisi, catatan atau petugas.
8. Menu Tracking staf/tombol Perbarui Status tetap bekerja melalui `tracking-internal.php`.
9. Backup baru tidak mengekspor nilai generated column sehingga restore tidak mencoba mengisinya. Uji restore pada database terpisah; restore menghapus/mengganti tabel tujuan. Hentikan input transaksi saat backup.

## File baru
`cetak-resi.php`, `tracking-internal.php`, `includes/resi.php`, `includes/backup-data.php`, `assets/css/resi.css`, `database/tahap5.sql`, `tests/uji-resi.php`, `README-tahap5.md`.

## File berubah
`tracking.php` (publik), `detail-transaksi.php` (tautan cetak/status), `includes/sidebar.php`, `actions/update-status.php` (tautan internal), `backup.php` (kolom eksplisit tanpa generated), `README.md`.

## Pengujian dan batas klaim
Sebelum sesi dimuat ulang: 32 uji aturan dan 10 pemeriksaan database tambahan lulus pada PHP 8.4.27/MariaDB 11.4.12 menggunakan fixture lokal; SQL diimpor dua kali; alur browser publik, login cetak, status staf yang terbaca kembali publik, nota 58/80/A4, lookup ID/resi, kembali, ID invalid, unduhan dan restore backup ke database terpisah berhasil. Tampilan tiga nota dan pelacakan ponsel diperiksa. Data pelanggan dan provider asli tidak digunakan.

Lingkungan kemudian menghapus semua staging sementara sebelum penyerahan. Paket ini direkonstruksi dari kode yang sama, dengan pengujian aturan 32 kasus disertakan; hasil pemeriksaan ulang dicatat pada akhir panduan. Pengujian database/browser sebelumnya bukan bukti ulang untuk arsip rekonstruksi. Tidak ada uji printer fisik, provider asli atau hosting produksi. Jangan menyatakan siap produksi.

## Checklist sebelum digunakan nyata
- [ ] Cocokkan nota, alamat, berat, biaya dan nama agen dengan transaksi nyata.
- [ ] Uji printer fisik masing-masing ukuran dan cek teks tidak terpotong.
- [ ] Uji resi valid, tidak ada, duplikat, dan seluruh status termasuk Bermasalah/Dibatalkan.
- [ ] Uji jendela privat: tracking tanpa login; cetak dan halaman staf harus login.
- [ ] Backup dan restore ke database terpisah; jangan menguji restore di produksi.
- [ ] Hosting HTTPS, password akun/database khusus, display_errors mati; cek pembatasan akses config/includes/database. `.htaccess` membutuhkan Apache/AllowOverride; Nginx perlu aturan setara.
- [ ] Hapus `setup.php`, `test-telegram.php` dan `tests/` pada salinan publik; ZIP adalah paket pengembangan, bukan unggahan produksi mentah.
- [ ] Ganti password bawaan. Jangan membagikan token atau memasukkannya dalam ZIP.
- [ ] Selesaikan audit tahap 4 tertunda: status processing lebih dari dua menit masih berisiko duplikasi. Tahap 5 tidak memperbaiki alur notifikasi.
- [ ] Uji Fonnte/Telegram asli ke nomor/chat sendiri dengan transaksi tersimpan. Isi token hanya pada server pribadi; cek isi pesan, dashboard provider/chat dan riwayat terpisah. Accepted WA bukan bukti tiba/dibaca. Timeout/belum pasti bukan gagal pasti dan tidak boleh retry otomatis.

Uji aturan: `C:\xampp\php\php.exe tests\uji-resi.php` dan `C:\xampp\php\php.exe tests\uji-struk.php`.

Pemeriksaan ulang setelah pemulihan: 32 uji resi + 15 uji struk lulus; 50 file PHP lulus sintaks. Browser/database belum diulang setelah rekonstruksi.

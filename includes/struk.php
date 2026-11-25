<?php
// Fungsi bersama untuk struk WhatsApp dan Telegram. Semua data berasal dari database.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }

const STRUK_BATAS_KARAKTER = 4000;    // Batas aman (Telegram maksimal 4096 karakter)
const STRUK_JEDA_DUPLIKAT_MENIT = 10; // Kirim ulang dalam 10 menit butuh konfirmasi
const STRUK_MAKS_PER_JAM = 5;         // Maksimal permintaan per transaksi per jam

/** Ubah nomor Indonesia ke format 62xxxxxxxxxx. Mengembalikan null jika tidak masuk akal. */
function normalisasi_wa($nomor) {
    $n = preg_replace('/[\s\-\.\(\)]/', '', (string)$nomor);
    if (str_starts_with($n, '+')) $n = substr($n, 1);
    if ($n === '' || !ctype_digit($n)) return null;
    if (str_starts_with($n, '0')) $n = '62' . substr($n, 1);
    elseif (str_starts_with($n, '8')) $n = '62' . $n;
    // Nomor seluler Indonesia: 62 8xx, total 10-15 digit
    if (!preg_match('/^628[1-9][0-9]{6,11}$/', $n)) return null;
    return $n;
}

/** Samarkan untuk ditampilkan/dicatat: 6281234567890 -> 62812*****890 */
function samarkan_nomor($n) {
    $n = (string)$n; $len = strlen($n);
    if ($len <= 7) return str_repeat('*', $len);
    return substr($n, 0, 5) . str_repeat('*', $len - 8) . substr($n, -3);
}

function tgl_indo($d) {
    if (!$d) return '-';
    $b = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y, H:i', $t) . ' WIB';
}

function teks_status_bayar($s) {
    return ['belum' => 'Belum dibayar', 'sebagian' => 'Dibayar sebagian', 'lunas' => 'Lunas'][$s] ?? '-';
}

function nilai_atau_strip($v) { $v = trim((string)$v); return $v === '' ? '-' : $v; }

function angka_berat($v) { return rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ','); }

/** Ambil transaksi lengkap berdasarkan ID (prepared statement). */
function ambil_transaksi_id($db, $id) {
    $st = $db->prepare("SELECT p.*, e.nama eksp, l.nama_layanan, ka.nama asal, kt.nama tujuan,
      (SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE pengiriman_id=p.id) terbayar,
      (SELECT GROUP_CONCAT(DISTINCT metode ORDER BY metode SEPARATOR ', ') FROM pembayaran WHERE pengiriman_id=p.id) metode_bayar
      FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id
      JOIN kota ka ON ka.id=p.kota_asal_id JOIN kota kt ON kt.id=p.kota_tujuan_id WHERE p.id=? LIMIT 1");
    $st->bind_param('i', $id); $st->execute();
    return $st->get_result()->fetch_assoc() ?: null;
}

/** Pelanggan penerima struk: 'pengirim' (pembayar, bawaan) atau 'penerima'. Nomor selalu dari database. */
function pelanggan_struk($t, $pihak) {
    return $pihak === 'penerima'
        ? ['nama' => $t['nama_penerima'], 'telp' => $t['telp_penerima']]
        : ['nama' => $t['nama_pengirim'], 'telp' => $t['telp_pengirim']];
}

function rincian_bayar($t) {
    $sisa = max(0, (float)$t['total_tagihan'] - (float)$t['terbayar']);
    return [rupiah($t['ongkir_dasar']), rupiah($t['biaya_tambahan']), rupiah($t['diskon']), rupiah($t['total_tagihan']),
            rupiah($t['terbayar']), rupiah($sisa)];
}

function format_struk_wa($t, $namaPelanggan) {
    [$ongkir, $tambahan, $diskon, $total, $dibayar, $sisa] = rincian_bayar($t);
    $resi = trim((string)$t['resi_ekspedisi']) !== '' ? $t['resi_ekspedisi'] : 'Belum tersedia (resi ekspedisi belum diterbitkan)';
    return implode("\n", [
        'Halo, ' . nilai_atau_strip($namaPelanggan) . '!', '',
        'Terima kasih telah menggunakan layanan pengiriman kami.', '',
        '*BUKTI TRANSAKSI PENGIRIMAN*', '',
        'No. Transaksi: ' . $t['no_transaksi'],
        'Tanggal: ' . tgl_indo($t['tanggal']),
        'Ekspedisi: ' . nilai_atau_strip($t['eksp']),
        'Layanan: ' . nilai_atau_strip($t['nama_layanan']),
        'No. Resi: ' . $resi, '',
        'Pengirim: ' . nilai_atau_strip($t['nama_pengirim']),
        'Penerima: ' . nilai_atau_strip($t['nama_penerima']),
        'Tujuan: ' . nilai_atau_strip($t['tujuan']),
        'Berat: ' . angka_berat($t['berat_tagih']) . ' kg', '',
        'Ongkir: ' . $ongkir, 'Biaya tambahan: ' . $tambahan, 'Diskon: ' . $diskon,
        'Total tagihan: ' . $total, 'Total dibayar: ' . $dibayar, 'Sisa pembayaran: ' . $sisa,
        'Status pembayaran: ' . teks_status_bayar($t['status_bayar']), '',
        'Terima kasih telah mempercayakan pengiriman kepada kami.',
    ]);
}

function format_struk_telegram($t) {
    [$ongkir, $tambahan, $diskon, $total, $dibayar, $sisa] = rincian_bayar($t);
    return implode("\n", [
        '🧾 STRUK TRANSAKSI LOGISTIK', '',
        'Nomor Transaksi: ' . $t['no_transaksi'],
        'Tanggal: ' . tgl_indo($t['tanggal']),
        'Nama Pelanggan: ' . nilai_atau_strip($t['nama_pengirim']),
        'No. Telepon: ' . nilai_atau_strip($t['telp_pengirim']), '',
        'DETAIL PENGIRIMAN',
        'Ekspedisi: ' . nilai_atau_strip($t['eksp']) . ' - ' . nilai_atau_strip($t['nama_layanan']),
        'Nomor Resi: ' . (trim((string)$t['resi_ekspedisi']) !== '' ? $t['resi_ekspedisi'] : 'Belum tersedia'),
        'Kota Asal: ' . nilai_atau_strip($t['asal']),
        'Kota Tujuan: ' . nilai_atau_strip($t['tujuan']),
        'Penerima: ' . nilai_atau_strip($t['nama_penerima']),
        'Berat: ' . angka_berat($t['berat_tagih']) . ' kg',
        'Tarif (ongkir): ' . $ongkir, '',
        'PEMBAYARAN',
        'Subtotal: ' . $ongkir, 'Biaya Tambahan: ' . $tambahan, 'Diskon: ' . $diskon, 'Total: ' . $total,
        'Dibayar: ' . $dibayar, 'Sisa: ' . $sisa,
        'Metode Pembayaran: ' . nilai_atau_strip($t['metode_bayar'] ?? ''),
        'Status Pembayaran: ' . teks_status_bayar($t['status_bayar']), '',
        'Terima kasih telah menggunakan layanan kami.',
    ]);
}

/**
 * Cek duplikasi. Panggil di dalam transaksi database setelah baris pengiriman dikunci.
 * Mengembalikan null jika boleh kirim, atau teks alasan penolakan.
 */
function cek_duplikat_struk($db, $tabel, $kolomTransaksi, $id, $kirimUlang) {
    $izin = ['whatsapp_logs' => 'pengiriman_id', 'telegram_logs' => 'transaction_id'];
    if (($izin[$tabel] ?? null) !== $kolomTransaksi) throw new InvalidArgumentException('Tabel tidak dikenal');
    $st = $db->prepare("SELECT COUNT(*) n FROM `$tabel` WHERE `$kolomTransaksi`=? AND created_at > NOW() - INTERVAL 1 HOUR");
    $st->bind_param('i', $id); $st->execute();
    if ((int)$st->get_result()->fetch_assoc()['n'] >= STRUK_MAKS_PER_JAM) {
        return 'Batas ' . STRUK_MAKS_PER_JAM . ' permintaan per jam untuk transaksi ini tercapai. Coba lagi nanti.';
    }
    $st = $db->prepare("SELECT status, created_at FROM `$tabel` WHERE `$kolomTransaksi`=? ORDER BY id DESC LIMIT 1");
    $st->bind_param('i', $id); $st->execute();
    $akhir = $st->get_result()->fetch_assoc();
    if (!$akhir) return null;
    if ($akhir['status'] === 'processing' && strtotime($akhir['created_at']) > strtotime('-2 minutes')) {
        return 'Permintaan sebelumnya masih diproses. Tunggu sebentar lalu muat ulang halaman.';
    }
    $baru = strtotime($akhir['created_at']) > strtotime('-' . STRUK_JEDA_DUPLIKAT_MENIT . ' minutes');
    if (!$kirimUlang && ($akhir['status'] === 'unknown' || ($baru && in_array($akhir['status'], ['accepted', 'success'], true)))) {
        return 'Struk ini baru saja dikirim atau hasilnya belum pasti. Periksa riwayat, lalu pakai tombol Kirim Ulang jika memang perlu.';
    }
    return null;
}

/** Gagal tersambung = pasti belum terkirim. Timeout/terputus = hasil belum pasti. */
function hasil_error_curl($errno) {
    return in_array($errno, [6, 7], true)
        ? ['status' => 'failed', 'pesan' => 'Tidak dapat tersambung ke layanan. Periksa koneksi internet.']
        : ['status' => 'unknown', 'pesan' => $errno === 28
            ? 'Waktu tunggu habis. Hasil pengiriman belum pasti, jadi jangan langsung kirim ulang.'
            : 'Koneksi terputus. Hasil pengiriman belum pasti.'];
}

function ringkas($s, $max = 250) { return mb_substr(preg_replace('/\s+/', ' ', (string)$s), 0, $max); }

/** Riwayat untuk halaman detail. Mengembalikan null jika tabel belum diimpor. */
function riwayat_struk($db, $tabel, $id) {
    $sql = $tabel === 'whatsapp_logs'
        ? 'SELECT w.created_at, w.nomor_tujuan tujuan, w.pihak, w.status, w.response_summary ket, u.nama FROM whatsapp_logs w JOIN users u ON u.id=w.user_id WHERE w.pengiriman_id=? ORDER BY w.id DESC LIMIT 10'
        : 'SELECT t.created_at, t.chat_id tujuan, NULL pihak, t.status, t.response_message ket, u.nama FROM telegram_logs t JOIN users u ON u.id=t.sent_by WHERE t.transaction_id=? ORDER BY t.id DESC LIMIT 10';
    try { $st = $db->prepare($sql); $st->bind_param('i', $id); $st->execute(); return $st->get_result()->fetch_all(MYSQLI_ASSOC); }
    catch (mysqli_sql_exception $e) { return null; }
}

<?php require '../config/app.php'; require_login();
require '../config/whatsapp.php'; require '../includes/struk.php'; require '../includes/whatsapp.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../transaksi.php');
csrf_check();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$pihak = $_POST['pihak'] ?? 'pengirim';
$kirimUlang = ($_POST['kirim_ulang'] ?? '') === '1';
$uid = (int)$_SESSION['user']['id'];
if (!$id || !in_array($pihak, ['pengirim', 'penerima'], true)) { flash('err', 'Permintaan tidak valid.'); redirect('../transaksi.php'); }

$back = '../transaksi.php';
try {
    $db->begin_transaction();
    // Kunci baris transaksi agar dua klik bersamaan tidak lolos pemeriksaan duplikasi
    $st = $db->prepare('SELECT id, no_transaksi, status FROM pengiriman WHERE id=? FOR UPDATE');
    $st->bind_param('i', $id); $st->execute();
    $kunci = $st->get_result()->fetch_assoc();
    if (!$kunci) throw new RuntimeException('Transaksi tidak ditemukan.');
    $back = '../detail-transaksi.php?no=' . urlencode($kunci['no_transaksi']);
    if ($kunci['status'] === 'dibatalkan') throw new RuntimeException('Transaksi sudah dibatalkan, struk tidak dikirim.');
    if (!whatsapp_siap()) throw new RuntimeException('Token WhatsApp (Fonnte) belum diatur. Hubungi admin.');

    $t = ambil_transaksi_id($db, $id);
    $pel = pelanggan_struk($t, $pihak);
    $nomor = normalisasi_wa($pel['telp']);
    if (!$nomor) throw new RuntimeException('Nomor WhatsApp ' . $pihak . ' kosong atau tidak valid. Perbaiki data transaksi dahulu.');
    $pesan = format_struk_wa($t, $pel['nama']);
    if (mb_strlen($pesan) > STRUK_BATAS_KARAKTER) throw new RuntimeException('Isi struk terlalu panjang untuk dikirim.');
    if ($tolak = cek_duplikat_struk($db, 'whatsapp_logs', 'pengiriman_id', $id, $kirimUlang)) throw new RuntimeException($tolak);

    $samar = samarkan_nomor($nomor);
    $st = $db->prepare("INSERT INTO whatsapp_logs (pengiriman_id, user_id, nomor_tujuan, pihak, jenis_pesan, status) VALUES (?,?,?,?,'struk','processing')");
    $st->bind_param('iiss', $id, $uid, $samar, $pihak); $st->execute();
    $logId = $db->insert_id;
    $db->commit();
} catch (RuntimeException $e) {
    $db->rollback(); flash('err', $e->getMessage()); redirect($back);
} catch (Throwable $e) {
    $db->rollback(); error_log('[kirim-struk-whatsapp] ' . $e->getMessage());
    flash('err', 'Gagal menyiapkan pengiriman. Pastikan database/notifikasi_log.sql sudah diimpor.'); redirect($back);
}

// Kirim di luar transaksi database agar kunci tidak tertahan selama menunggu provider
$hasil = kirim_fonnte($nomor, $pesan);

try {
    $ringkasan = ringkas($hasil['pesan']);
    $st = $db->prepare('UPDATE whatsapp_logs SET status=?, http_code=?, provider_message_id=?, response_summary=? WHERE id=?');
    $st->bind_param('sissi', $hasil['status'], $hasil['http_code'], $hasil['provider_id'], $ringkasan, $logId); $st->execute();
    audit($db, 'kirim_wa', "pengiriman_id=$id log=$logId status={$hasil['status']}" . ($kirimUlang ? ' ulang' : ''));
} catch (Throwable $e) {
    error_log('[kirim-struk-whatsapp] gagal menyimpan log: ' . $e->getMessage());
    flash('warn', 'Hasil: ' . $hasil['pesan'] . ' Namun riwayat gagal disimpan, catat secara manual.'); redirect($back);
}

match ($hasil['status']) {
    'accepted' => flash('ok', 'Permintaan pengiriman diterima oleh provider (ke ' . $samar . '). Ini belum memastikan pesan sudah diterima pelanggan.'),
    'unknown' => flash('warn', $hasil['pesan'] . ' Cek dashboard Fonnte atau tanyakan pelanggan sebelum kirim ulang.'),
    default => flash('err', 'Struk gagal dikirim. ' . $hasil['pesan']),
};
redirect($back);

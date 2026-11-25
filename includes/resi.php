<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }
function parameter_resi($nilai) {
    if (!is_string($nilai)) return null;
    $nilai = trim($nilai);
    return $nilai !== '' && mb_strlen($nilai) <= 50 && !preg_match('/[\x00-\x1F\x7F]/', $nilai) ? $nilai : null;
}
function parameter_id_resi($nilai) {
    if (!is_string($nilai) || !preg_match('/^[1-9][0-9]{0,9}$/D', $nilai)) return null;
    return (float)$nilai <= 2147483647 ? (int)$nilai : null;
}
function status_resi($status) {
    return match ($status) {
        'dibuat', 'menunggu_bayar', 'menunggu_serah' => 'Manifest (Diproses di Agen)',
        'diserahkan', 'perjalanan', 'diantar' => 'On Process (Dalam Pengiriman)',
        'terkirim' => 'Delivered (Terkirim)',
        'dibatalkan' => 'Dibatalkan', 'bermasalah' => 'Bermasalah', default => 'Status belum tersedia',
    };
}
function progres_resi($status) {
    return match ($status) {
        'dibuat', 'menunggu_bayar', 'menunggu_serah' => 1,
        'diserahkan', 'perjalanan', 'diantar' => 2,
        'terkirim' => 3, default => 0,
    };
}
function ukuran_resi($nilai) { return in_array($nilai, ['58', '80', 'a4'], true) ? $nilai : '80'; }
function nama_agen_resi($db) {
    $st = $db->prepare("SELECT nilai FROM pengaturan WHERE kunci='nama_usaha' LIMIT 1");
    $st->execute(); $row = $st->get_result()->fetch_assoc();
    return trim((string)($row['nilai'] ?? '')) ?: APP_NAME;
}
function ambil_resi_cetak($db, $id, $resi) {
    $sql = 'SELECT p.*, e.nama eksp, l.nama_layanan, ka.nama asal, kt.nama tujuan
      FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id
      JOIN ekspedisi e ON e.id=l.ekspedisi_id JOIN kota ka ON ka.id=p.kota_asal_id
      JOIN kota kt ON kt.id=p.kota_tujuan_id WHERE ';
    if ($id !== null) { $st = $db->prepare($sql . 'p.id=? LIMIT 1'); $st->bind_param('i', $id); }
    else { $st = $db->prepare($sql . "p.resi_ekspedisi<>'' AND p.resi_ekspedisi=? LIMIT 2"); $st->bind_param('s', $resi); }
    $st->execute(); $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    return count($rows) === 1 ? $rows[0] : null;
}
function cari_resi_publik($db, $resi) {
    // Tidak mengambil data pribadi, keuangan, atau pengguna.
    $st = $db->prepare("SELECT p.id, p.resi_ekspedisi, p.tanggal, p.status, e.nama eksp, l.nama_layanan, kt.nama tujuan
      FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id
      JOIN kota kt ON kt.id=p.kota_tujuan_id WHERE p.resi_ekspedisi<>'' AND p.resi_ekspedisi=? LIMIT 2");
    $st->bind_param('s', $resi); $st->execute(); $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    return count($rows) === 1 ? $rows[0] : null;
}
function riwayat_resi_publik($db, $id) {
    $st = $db->prepare('SELECT status, created_at FROM riwayat_status WHERE pengiriman_id=? ORDER BY id DESC LIMIT 50');
    $st->bind_param('i', $id); $st->execute(); return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

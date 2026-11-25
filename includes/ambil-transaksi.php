<?php
function ambil_transaksi($db, $kode) {
    $st = $db->prepare("SELECT p.*, e.nama eksp, l.nama_layanan, ka.nama asal, kt.nama tujuan, u.nama staf,
      (SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE pengiriman_id=p.id) terbayar
      FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id
      JOIN kota ka ON ka.id=p.kota_asal_id JOIN kota kt ON kt.id=p.kota_tujuan_id JOIN users u ON u.id=p.user_id
      WHERE p.no_transaksi=? OR (p.resi_ekspedisi<>'' AND p.resi_ekspedisi=?) LIMIT 1");
    $st->bind_param('ss', $kode, $kode); $st->execute(); return $st->get_result()->fetch_assoc();
}
function riwayat($db, $id) {
    $st = $db->prepare('SELECT r.*, u.nama FROM riwayat_status r JOIN users u ON u.id=r.user_id WHERE pengiriman_id=? ORDER BY r.id DESC');
    $st->bind_param('i', $id); $st->execute(); return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

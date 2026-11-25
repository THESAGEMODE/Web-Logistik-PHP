<?php
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($d) {
    if (!$d) return '-';
    $b = ['', 'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($d);
    return date('d', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y H:i', $t);
}
function flash($type = null, $msg = null) {
    if ($type) { $_SESSION['flash'] = [$type, $msg]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function redirect($url) { header("Location: $url"); exit; }
function audit($db, $aksi, $detail = '') {
    $uid = $_SESSION['user']['id'] ?? null;
    $st = $db->prepare('INSERT INTO audit_log (user_id, aksi, detail, ip) VALUES (?,?,?,?)');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $st->bind_param('isss', $uid, $aksi, $detail, $ip); $st->execute();
}
const STATUS_LIST = [
  'dibuat' => 'Transaksi dibuat', 'menunggu_bayar' => 'Menunggu pembayaran',
  'menunggu_serah' => 'Menunggu penyerahan ke ekspedisi', 'diserahkan' => 'Dikirim ke ekspedisi',
  'perjalanan' => 'Dalam perjalanan', 'diantar' => 'Sedang diantar', 'terkirim' => 'Terkirim',
  'dibatalkan' => 'Dibatalkan', 'bermasalah' => 'Bermasalah',
];
// Perubahan status yang diizinkan (mencegah lompatan tidak masuk akal)
const STATUS_NEXT = [
  'dibuat' => ['menunggu_bayar','menunggu_serah','dibatalkan'],
  'menunggu_bayar' => ['menunggu_serah','dibatalkan'],
  'menunggu_serah' => ['diserahkan','dibatalkan','bermasalah'],
  'diserahkan' => ['perjalanan','bermasalah'],
  'perjalanan' => ['diantar','bermasalah'],
  'diantar' => ['terkirim','bermasalah'],
  'bermasalah' => ['perjalanan','diantar','terkirim','dibatalkan'],
  'terkirim' => [], 'dibatalkan' => [],
];
function badge($s) {
  $c = ['terkirim'=>'success','dibatalkan'=>'secondary','bermasalah'=>'danger','dibuat'=>'info','menunggu_bayar'=>'warning'][$s] ?? 'primary';
  return '<span class="badge text-bg-'.$c.'">'.e(STATUS_LIST[$s] ?? $s).'</span>';
}
/**
 * Hitung ongkir di SERVER. Mengembalikan null jika tarif tidak ditemukan.
 * berat_tagih = ceil(max(berat aktual, berat volumetrik, berat minimum))
 * volumetrik  = P x L x T / faktor_volumetrik (faktor diatur per tarif)
 */
function hitung_ongkir($db, $layanan_id, $asal, $tujuan, $berat, $p = 0, $l = 0, $t = 0) {
    $st = $db->prepare('SELECT * FROM tarif WHERE layanan_id=? AND kota_asal_id=? AND kota_tujuan_id=? AND aktif=1 AND berlaku_mulai<=CURDATE() ORDER BY berlaku_mulai DESC LIMIT 1');
    $st->bind_param('iii', $layanan_id, $asal, $tujuan); $st->execute();
    $tr = $st->get_result()->fetch_assoc();
    if (!$tr) return null;
    $vol = ($p > 0 && $l > 0 && $t > 0 && $tr['faktor_volumetrik'] > 0) ? round($p * $l * $t / $tr['faktor_volumetrik'], 2) : 0;
    $tagih = ceil(max((float)$berat, $vol, (float)$tr['berat_minimum']));
    $dasar = $tagih * $tr['tarif_per_kg'];
    $total = $dasar + $tr['biaya_tambahan'];
    $komisi = match ($tr['komisi_tipe']) {
        'persen' => round($dasar * $tr['komisi_nilai'] / 100, 2),
        'nominal' => (float)$tr['komisi_nilai'],
        'selisih' => max(0, $dasar - $tagih * $tr['tarif_modal_per_kg']),
        default => 0,
    };
    return ['tarif' => $tr, 'berat_volumetrik' => $vol, 'berat_tagih' => $tagih, 'ongkir_dasar' => $dasar,
            'biaya_tambahan' => (float)$tr['biaya_tambahan'], 'total' => $total, 'komisi' => $komisi, 'estimasi' => $tr['estimasi']];
}

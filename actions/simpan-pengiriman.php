<?php require '../config/app.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../input-resi.php'); csrf_check();
$s = fn($k, $max = 255) => mb_substr(trim($_POST[$k] ?? ''), 0, $max);
$num = fn($k) => max(0, (float)($_POST[$k] ?? 0));
foreach (['nama_pengirim','telp_pengirim','alamat_pengirim','nama_penerima','telp_penerima','alamat_penerima','jenis_barang'] as $k)
    if ($s($k) === '') { flash('err', 'Data pengirim, penerima, dan barang wajib diisi.'); redirect('../input-resi.php'); }
$lay = (int)$_POST['layanan_id']; $asal = (int)$_POST['asal']; $tuj = (int)$_POST['tujuan'];
// HITUNG ULANG DI SERVER — harga dari browser tidak dipercaya
$h = hitung_ongkir($db, $lay, $asal, $tuj, $num('berat'), $num('panjang'), $num('lebar'), $num('tinggi'));
if (!$h || $num('berat') <= 0) { flash('err', 'Tarif tidak ditemukan atau berat tidak valid.'); redirect('../input-resi.php'); }
$diskon = min($num('diskon'), $h['total']);
$total = $h['total'] - $diskon;
$dibayar = min($num('dibayar'), $total);
$metode = in_array($_POST['metode'] ?? '', ['tunai','transfer','qris']) ? $_POST['metode'] : 'tunai';
$status_bayar = $dibayar >= $total ? 'lunas' : ($dibayar > 0 ? 'sebagian' : 'belum');
$status = $status_bayar === 'belum' ? 'menunggu_bayar' : 'menunggu_serah';
$snapshot = json_encode($h['tarif']); $uid = $_SESSION['user']['id'];
$db->begin_transaction();
try {
    // Nomor transaksi: TRX-YYYYMMDD-0001 (dikunci FOR UPDATE agar tidak bentrok)
    $pref = 'TRX-' . date('Ymd') . '-';
    $r = $db->query("SELECT no_transaksi FROM pengiriman WHERE no_transaksi LIKE '$pref%' ORDER BY id DESC LIMIT 1 FOR UPDATE")->fetch_assoc();
    $no = $pref . str_pad(($r ? (int)substr($r['no_transaksi'], -4) : 0) + 1, 4, '0', STR_PAD_LEFT);
    $st = $db->prepare('INSERT INTO pengiriman (no_transaksi,user_id,layanan_id,kota_asal_id,kota_tujuan_id,resi_ekspedisi,nama_pengirim,telp_pengirim,alamat_pengirim,nama_penerima,telp_penerima,alamat_penerima,jenis_barang,koli,berat_aktual,panjang,lebar,tinggi,berat_volumetrik,berat_tagih,ongkir_dasar,biaya_tambahan,diskon,total_tagihan,komisi,catatan,tarif_snapshot,status,status_bayar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $v = [$no,$uid,$lay,$asal,$tuj,$s('resi_ekspedisi',50),$s('nama_pengirim',100),$s('telp_pengirim',20),$s('alamat_pengirim'),$s('nama_penerima',100),$s('telp_penerima',20),$s('alamat_penerima'),$s('jenis_barang',100),max(1,(int)$_POST['koli']),
          $num('berat'),$num('panjang'),$num('lebar'),$num('tinggi'),$h['berat_volumetrik'],$h['berat_tagih'],$h['ongkir_dasar'],$h['biaya_tambahan'],$diskon,$total,$h['komisi'],$s('catatan'),$snapshot,$status,$status_bayar];
    $st->bind_param('siiiissssssssidddddddddddssss', ...$v); $st->execute(); $pid = $db->insert_id;
    $rs = $db->prepare('INSERT INTO riwayat_status (pengiriman_id,status,keterangan,user_id) VALUES (?,?,?,?)');
    $k = 'Transaksi dibuat'; $d = 'dibuat'; $rs->bind_param('issi', $pid, $d, $k, $uid); $rs->execute();
    if ($status !== 'dibuat') { $k = ''; $rs->bind_param('issi', $pid, $status, $k, $uid); $rs->execute(); }
    if ($dibayar > 0) { $pb = $db->prepare('INSERT INTO pembayaran (pengiriman_id,jumlah,metode,user_id) VALUES (?,?,?,?)'); $pb->bind_param('idsi', $pid, $dibayar, $metode, $uid); $pb->execute(); }
    audit($db, 'buat_transaksi', "$no total=$total"); $db->commit();
    flash('ok', "Transaksi $no berhasil disimpan."); redirect('../detail-transaksi.php?no=' . urlencode($no));
} catch (Throwable $e) { $db->rollback(); error_log($e); flash('err', 'Gagal menyimpan transaksi. Coba lagi.'); redirect('../input-resi.php'); }

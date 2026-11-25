<?php require '../config/app.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../pembayaran.php'); csrf_check();
$id = (int)($_POST['id'] ?? 0); $jumlah = round((float)($_POST['jumlah'] ?? 0), 2);
$metode = $_POST['metode'] ?? ''; $ref = trim($_POST['no_referensi'] ?? ''); $uid = $_SESSION['user']['id'];
$back = '../pembayaran.php';
if (!in_array($metode, ['tunai','transfer','qris'], true) || $jumlah <= 0) { flash('err', 'Data pembayaran tidak valid.'); redirect($back); }
if ($metode !== 'tunai' && $ref === '') { flash('err', 'No. referensi wajib untuk transfer/QRIS.'); redirect($back); }
$db->begin_transaction();
try {
    // Kunci baris transaksi agar dua kasir tidak mencatat bersamaan
    $st = $db->prepare('SELECT no_transaksi, total_tagihan, status FROM pengiriman WHERE id=? FOR UPDATE'); $st->bind_param('i', $id); $st->execute();
    $p = $st->get_result()->fetch_assoc(); if (!$p || $p['status'] === 'dibatalkan') throw new RuntimeException('Transaksi tidak valid.');
    $back = '../pembayaran.php?no=' . urlencode($p['no_transaksi']);
    $st = $db->prepare('SELECT COALESCE(SUM(jumlah),0) s FROM pembayaran WHERE pengiriman_id=?'); $st->bind_param('i', $id); $st->execute();
    $sudah = (float)$st->get_result()->fetch_assoc()['s']; $sisa = $p['total_tagihan'] - $sudah;
    if ($jumlah > $sisa + 0.001) throw new RuntimeException('Jumlah melebihi sisa tagihan (' . rupiah($sisa) . ').');
    $st = $db->prepare('INSERT INTO pembayaran (pengiriman_id, jumlah, metode, no_referensi, user_id) VALUES (?,?,?,?,?)');
    $st->bind_param('idssi', $id, $jumlah, $metode, $ref, $uid); $st->execute();
    $status = ($sudah + $jumlah >= $p['total_tagihan'] - 0.001) ? 'lunas' : 'sebagian';
    $st = $db->prepare('UPDATE pengiriman SET status_bayar=? WHERE id=?'); $st->bind_param('si', $status, $id); $st->execute();
    audit($db, 'pembayaran', "id=$id jumlah=$jumlah metode=$metode"); $db->commit(); flash('ok', 'Pembayaran dicatat. Status: ' . $status);
} catch (RuntimeException $e) { $db->rollback(); flash('err', $e->getMessage()); }
  catch (Throwable $e) { $db->rollback(); error_log($e); flash('err', 'Gagal mencatat pembayaran.'); }
redirect($back);

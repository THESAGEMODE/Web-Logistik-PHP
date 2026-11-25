<?php require '../config/app.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../pembayaran.php'); csrf_check();
$id = (int)($_POST['id'] ?? 0); $alasan = trim($_POST['alasan'] ?? ''); $back = '../pembayaran.php';
if ($alasan === '') { flash('err', 'Alasan pembatalan wajib diisi.'); redirect($back); }
$db->begin_transaction();
try {
    $st = $db->prepare('SELECT b.*, p.no_transaksi, p.total_tagihan FROM pembayaran b JOIN pengiriman p ON p.id=b.pengiriman_id WHERE b.id=? FOR UPDATE');
    $st->bind_param('i', $id); $st->execute(); $b = $st->get_result()->fetch_assoc();
    if (!$b) throw new RuntimeException('Pembayaran tidak ditemukan.');
    $back = '../pembayaran.php?no=' . urlencode($b['no_transaksi']); $pid = (int)$b['pengiriman_id'];
    $st = $db->prepare('DELETE FROM pembayaran WHERE id=?'); $st->bind_param('i', $id); $st->execute();
    $st = $db->prepare('SELECT COALESCE(SUM(jumlah),0) s FROM pembayaran WHERE pengiriman_id=?'); $st->bind_param('i', $pid); $st->execute();
    $sisa = (float)$st->get_result()->fetch_assoc()['s'];
    $status = $sisa <= 0 ? 'belum' : ($sisa >= $b['total_tagihan'] - 0.001 ? 'lunas' : 'sebagian');
    $st = $db->prepare('UPDATE pengiriman SET status_bayar=? WHERE id=?'); $st->bind_param('si', $status, $pid); $st->execute();
    // Jejak lengkap tetap tersimpan di log
    audit($db, 'batal_pembayaran', "trx={$b['no_transaksi']} jumlah={$b['jumlah']} metode={$b['metode']} ref={$b['no_referensi']} alasan=$alasan");
    $db->commit(); flash('ok', 'Pembayaran dibatalkan. Status bayar: ' . $status);
} catch (RuntimeException $e) { $db->rollback(); flash('err', $e->getMessage()); }
  catch (Throwable $e) { $db->rollback(); error_log($e); flash('err', 'Gagal membatalkan pembayaran.'); }
redirect($back);

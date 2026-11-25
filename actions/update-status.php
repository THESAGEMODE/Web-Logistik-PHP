<?php require '../config/app.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../tracking-internal.php'); csrf_check();
$id = (int)$_POST['id']; $baru = $_POST['status'] ?? ''; $uid = $_SESSION['user']['id'];
$db->begin_transaction();
try {
    $st = $db->prepare('SELECT no_transaksi, status FROM pengiriman WHERE id=? FOR UPDATE'); $st->bind_param('i', $id); $st->execute();
    $p = $st->get_result()->fetch_assoc();
    if (!$p || !in_array($baru, STATUS_NEXT[$p['status']] ?? [], true)) throw new RuntimeException('transisi');
    if ($baru === 'dibatalkan' && !is_admin()) throw new RuntimeException('izin');
    $resi = mb_substr(trim($_POST['resi'] ?? ''), 0, 50); $ket = mb_substr(trim($_POST['ket'] ?? ''), 0, 255);
    $u = $db->prepare('UPDATE pengiriman SET status=?, resi_ekspedisi=? WHERE id=?'); $u->bind_param('ssi', $baru, $resi, $id); $u->execute();
    $r = $db->prepare('INSERT INTO riwayat_status (pengiriman_id,status,keterangan,user_id) VALUES (?,?,?,?)'); $r->bind_param('issi', $id, $baru, $ket, $uid); $r->execute();
    audit($db, 'update_status', "{$p['no_transaksi']}: {$p['status']} -> $baru"); $db->commit(); flash('ok', 'Status diperbarui.');
} catch (Throwable $e) { $db->rollback(); flash('err', $e->getMessage() === 'izin' ? 'Hanya admin yang boleh membatalkan.' : 'Perubahan status tidak valid.'); $p = $p ?? null; }
redirect('../tracking-internal.php?q=' . urlencode($p['no_transaksi'] ?? ''));

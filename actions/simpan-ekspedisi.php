<?php require '../config/app.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../ekspedisi.php'); csrf_check();
if (!empty($_POST['toggle'])) {
    $id = (int)$_POST['toggle']; $st = $db->prepare('UPDATE ekspedisi SET aktif = 1 - aktif WHERE id=?'); $st->bind_param('i', $id); $st->execute();
    audit($db, 'toggle_ekspedisi', "id=$id"); flash('ok', 'Status ekspedisi diperbarui.'); redirect('../ekspedisi.php');
}
$id = (int)($_POST['id'] ?? 0); $nama = trim($_POST['nama'] ?? ''); $kode = strtoupper(trim($_POST['kode'] ?? '')); $kontak = trim($_POST['kontak'] ?? '');
if ($nama === '' || $kode === '') { flash('err', 'Nama dan kode wajib diisi.'); redirect('../ekspedisi.php'); }
$st = $db->prepare('SELECT id FROM ekspedisi WHERE (nama=? OR kode=?) AND id<>?'); $st->bind_param('ssi', $nama, $kode, $id); $st->execute();
if ($st->get_result()->num_rows) { flash('err', 'Nama atau kode ekspedisi sudah dipakai.'); redirect('../ekspedisi.php'); }
$db->begin_transaction();
try {
    if ($id) { $st = $db->prepare('UPDATE ekspedisi SET nama=?, kode=?, kontak=? WHERE id=?'); $st->bind_param('sssi', $nama, $kode, $kontak, $id); }
    else { $st = $db->prepare('INSERT INTO ekspedisi (nama, kode, kontak) VALUES (?,?,?)'); $st->bind_param('sss', $nama, $kode, $kontak); }
    $st->execute(); if (!$id) $id = $db->insert_id;
    $ins = $db->prepare('INSERT IGNORE INTO layanan_ekspedisi (ekspedisi_id, nama_layanan) VALUES (?,?)');
    foreach (array_filter(array_map('trim', explode(',', $_POST['layanan'] ?? ''))) as $l) { $ins->bind_param('is', $id, $l); $ins->execute(); }
    audit($db, 'simpan_ekspedisi', "id=$id nama=$nama"); $db->commit(); flash('ok', 'Ekspedisi disimpan.');
} catch (Throwable $e) { $db->rollback(); error_log($e); flash('err', 'Gagal menyimpan ekspedisi.'); }
redirect('../ekspedisi.php');

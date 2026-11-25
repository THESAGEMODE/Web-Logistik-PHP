<?php require '../config/app.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../pengeluaran.php'); csrf_check();
if (!empty($_POST['hapus'])) {
    if (!is_admin()) { http_response_code(403); exit('Khusus admin.'); }
    $id = (int)$_POST['hapus']; $st = $db->prepare('DELETE FROM pengeluaran WHERE id=?'); $st->bind_param('i', $id); $st->execute();
    audit($db, 'hapus_pengeluaran', "id=$id"); flash('ok', 'Pengeluaran dihapus.'); redirect('../pengeluaran.php');
}
$tgl = $_POST['tanggal'] ?? ''; $kat = trim($_POST['kategori'] ?? ''); $desk = trim($_POST['deskripsi'] ?? '');
$nom = round((float)($_POST['nominal'] ?? 0), 2); $met = in_array($_POST['metode'] ?? '', ['tunai','transfer'], true) ? $_POST['metode'] : 'tunai';
$ref = trim($_POST['no_referensi'] ?? ''); $uid = $_SESSION['user']['id'];
$d = DateTime::createFromFormat('Y-m-d', $tgl);
if (!$d || $tgl > date('Y-m-d') || $kat === '' || $desk === '' || $nom <= 0) { flash('err', 'Data pengeluaran tidak valid.'); redirect('../pengeluaran.php'); }
$st = $db->prepare('INSERT INTO pengeluaran (tanggal, kategori, deskripsi, nominal, metode, no_referensi, user_id) VALUES (?,?,?,?,?,?,?)');
$st->bind_param('sssdssi', $tgl, $kat, $desk, $nom, $met, $ref, $uid); $st->execute();
audit($db, 'pengeluaran', "nominal=$nom kategori=$kat"); flash('ok', 'Pengeluaran dicatat.'); redirect('../pengeluaran.php');

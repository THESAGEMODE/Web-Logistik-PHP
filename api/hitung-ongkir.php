<?php require '../config/app.php'; require_login();
header('Content-Type: application/json');
$h = hitung_ongkir($db, (int)($_GET['layanan_id'] ?? 0), (int)($_GET['asal'] ?? 0), (int)($_GET['tujuan'] ?? 0),
    (float)($_GET['berat'] ?? 0), (float)($_GET['p'] ?? 0), (float)($_GET['l'] ?? 0), (float)($_GET['t'] ?? 0));
if (!$h) { echo json_encode(['ok' => false, 'pesan' => 'Tarif untuk rute & layanan ini belum tersedia.']); exit; }
if(!is_admin()) unset($h['tarif'],$h['komisi']); else unset($h['tarif']['tarif_modal_per_kg']);
echo json_encode(['ok' => true] + $h);

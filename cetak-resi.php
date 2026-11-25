<?php
require 'config/app.php';
require_login();
require 'includes/resi.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$id = array_key_exists('id', $_GET) ? parameter_id_resi($_GET['id']) : null;
$resi = parameter_resi($_GET['resi'] ?? null);
if ((array_key_exists('id', $_GET) && $id === null) || ($id === null && $resi === null)) {
    http_response_code(400);
    exit('ID transaksi atau nomor resi tidak valid.');
}
$t = ambil_resi_cetak($db, $id, $resi);
if (!$t) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan atau nomor resi digunakan lebih dari satu transaksi. Gunakan ID transaksi.');
}
$ukuran = ukuran_resi($_GET['ukuran'] ?? null);
$namaAgen = nama_agen_resi($db);
?>
<!doctype html>\n<html lang="id">\n

<head>\n
    <meta charset="utf-8">\n
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cetak Resi — <?= e(APP_NAME) ?>\n</title>\n
    <meta name="description" content="Cetak bukti pengiriman transaksi LogiPro.">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/resi.css">\n
</head>\n

<body class="receipt-page paper-<?= e($ukuran) ?>">
    <nav class="receipt-tools no-print" aria-label="Pilihan cetak">\n<form method="get" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">\n<label for="ukuran">Kertas</label>
            <select name="ukuran" id="ukuran" class="form-select w-auto" onchange="this.form.submit()">
                <?php foreach (['58' => 'Thermal 58 mm', '80' => 'Thermal 80 mm', 'a4' => 'Printer biasa (A4)'] as $k => $v): ?>
                    <option value="<?= e($k) ?>" <?= $ukuran === (string)$k ? 'selected' : '' ?>>\n<?= e($v) ?>\n</option>\n<?php endforeach; ?>\n
            </select>
            <button type="button" class="btn btn-primary" onclick="window.print()">Cetak Struk</button>\n<a href="transaksi.php" class="btn btn-outline-secondary">Kembali ke Transaksi</a>\n
        </form>\n</nav>
    <main id="nota" class="receipt">\n<header class="receipt-heading">\n<h1>\n<?= e($namaAgen) ?>\n</h1>\n<p>BUKTI PENGIRIMAN</p>\n</header>\n<div class="receipt-divider">\n</div>
        <dl class="receipt-details">\n<dt>Tanggal</dt>\n<dd>\n<?= e(tgl($t['tanggal'])) ?> WIB</dd>\n<dt>No. Transaksi</dt>\n<dd>\n<?= e($t['no_transaksi']) ?>\n</dd>
            <dt>No. Resi</dt>\n<dd>\n<?= e($t['resi_ekspedisi'] ?: 'Belum tersedia') ?>\n</dd>\n<dt>Ekspedisi</dt>\n<dd>\n<?= e($t['eksp']) ?>\n</dd>\n<dt>Layanan</dt>\n<dd>\n<?= e($t['nama_layanan']) ?>\n</dd>\n
        </dl>
        <div class="receipt-divider">\n</div>\n<section>\n<h2>Pengirim</h2>\n<p>\n<?= e($t['nama_pengirim']) ?>\n<br>\n<?= e($t['telp_pengirim']) ?>\n</p>\n</section>
        <section>\n<h2>Penerima</h2>\n<p>\n<?= e($t['nama_penerima']) ?>\n<br>\n<?= e($t['telp_penerima']) ?>\n<br>\n<?= e($t['alamat_penerima']) ?>\n<br>\n<?= e($t['tujuan']) ?>\n</p>\n</section>
        <div class="receipt-divider">\n</div>\n<dl class="receipt-details">\n<dt>Berat aktual</dt>\n<dd>\n<?= e(number_format((float)$t['berat_aktual'], 2, ',', '.')) ?> kg</dd>
            <dt>Berat tagihan</dt>\n<dd>\n<?= e(number_format((float)$t['berat_tagih'], 2, ',', '.')) ?> kg</dd>\n<dt>Ongkir</dt>\n<dd>\n<?= e(rupiah($t['ongkir_dasar'])) ?>\n</dd>
            <dt>Tambahan</dt>\n<dd>\n<?= e(rupiah($t['biaya_tambahan'])) ?>\n</dd>\n<dt>Diskon</dt>\n<dd>\n<?= e(rupiah($t['diskon'])) ?>\n</dd>
            <dt class="receipt-total">Total Biaya</dt>\n<dd class="receipt-total">\n<?= e(rupiah($t['total_tagihan'])) ?>\n</dd>
            <dt>Status</dt>\n<dd>\n<?= e(status_resi($t['status'])) ?>\n<br>\n<?= e(STATUS_LIST[$t['status']] ?? 'Status belum tersedia') ?>\n</dd>\n
        </dl>
        <div class="receipt-divider">\n</div>\n<footer class="receipt-heading">\n<p>Terima kasih atas kepercayaan Anda.</p>\n</footer>\n
    </main>\n
</body>\n

</html>
<?php require 'config/app.php';
require_admin();
$title = 'Laporan';
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
require 'includes/report-range.php';
try {
    [$d1, $d2] = report_range($dari, $sampai);
} catch (InvalidArgumentException $ex) {
    http_response_code(422);
    include 'includes/header.php';
    echo '<div class="alert alert-danger">' . e($ex->getMessage()) . '</div>\n<a href="laporan.php" class="btn btn-light">Kembali</a>';
    include 'includes/footer.php';
    exit;
}
function q1($db, $sql, $types, ...$p)
{
    $s = $db->prepare($sql);
    $s->bind_param($types, ...$p);
    $s->execute();
    return $s->get_result();
}
$ring = q1($db, "SELECT COUNT(*) jml, COALESCE(SUM(status='terkirim'),0) terkirim, COALESCE(SUM(total_tagihan),0) omzet, COALESCE(SUM(komisi),0) komisi FROM pengiriman WHERE status<>'dibatalkan' AND tanggal >= ? AND tanggal < ?", 'ss', $d1, $d2)->fetch_assoc();
$masuk = (float)q1($db, 'SELECT COALESCE(SUM(jumlah),0) s FROM pembayaran WHERE tanggal >= ? AND tanggal < ?', 'ss', $d1, $d2)->fetch_assoc()['s'];
$keluar = (float)q1($db, 'SELECT COALESCE(SUM(nominal),0) s FROM pengeluaran WHERE tanggal BETWEEN ? AND ?', 'ss', $dari, $sampai)->fetch_assoc()['s'];
$piutang = (float)$db->query("SELECT COALESCE(SUM(p.total_tagihan - COALESCE(b.s,0)),0) s FROM pengiriman p LEFT JOIN (SELECT pengiriman_id, SUM(jumlah) s FROM pembayaran GROUP BY pengiriman_id) b ON b.pengiriman_id=p.id WHERE p.status<>'dibatalkan' AND p.status_bayar<>'lunas'")->fetch_assoc()['s'];
$per_eksp = q1($db, "SELECT e.nama, COUNT(*) jml, SUM(p.berat_tagih) berat, SUM(p.total_tagihan) omzet, SUM(p.komisi) komisi FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id WHERE p.status<>'dibatalkan' AND p.tanggal >= ? AND tanggal < ? GROUP BY e.id ORDER BY omzet DESC", 'ss', $d1, $d2)->fetch_all(MYSQLI_ASSOC);
$per_metode = q1($db, 'SELECT metode, SUM(jumlah) s FROM pembayaran WHERE tanggal >= ? AND tanggal < ? GROUP BY metode', 'ss', $d1, $d2)->fetch_all(MYSQLI_ASSOC);
$per_kat = q1($db, 'SELECT kategori, SUM(nominal) s FROM pengeluaran WHERE tanggal BETWEEN ? AND ? GROUP BY kategori ORDER BY s DESC', 'ss', $dari, $sampai)->fetch_all(MYSQLI_ASSOC);
$harian = q1($db, "SELECT DATE(tanggal) d, SUM(total_tagihan) s FROM pengiriman WHERE status<>'dibatalkan' AND tanggal >= ? AND tanggal < ? GROUP BY DATE(tanggal) ORDER BY d", 'ss', $d1, $d2)->fetch_all(MYSQLI_ASSOC);
if (($_GET['export'] ?? '') === 'csv') {
    $r = q1($db, "SELECT p.no_transaksi, p.tanggal, e.nama eksp, l.nama_layanan, p.nama_pengirim, p.nama_penerima, p.berat_tagih, p.total_tagihan, p.komisi, p.status, p.status_bayar FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id WHERE p.tanggal >= ? AND tanggal < ? ORDER BY p.tanggal", 'ss', $d1, $d2);
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=laporan_{$dari}_{$sampai}.csv");
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Periode', $dari, $sampai], ';');
    fputcsv($o, ['Total Paket Terkirim', $ring['terkirim']], ';');
    fputcsv($o, ['Total Omset', $ring['omzet']], ';');
    fputcsv($o, ['Total Komisi Bersih untuk Agen', $ring['komisi']], ';');
    fputcsv($o, [], ';');
    fputcsv($o, ['No Transaksi', 'Tanggal', 'Ekspedisi', 'Layanan', 'Pengirim', 'Penerima', 'Berat (kg)', 'Total', 'Komisi', 'Status', 'Bayar'], ';');
    while ($row = $r->fetch_row()) { // cegah formula injection di Excel
        fputcsv($o, array_map(fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'$v" : $v, $row), ';');
    }
    audit($db, 'export_laporan', "$dari s/d $sampai");
    exit;
}
include 'includes/header.php'; ?>
<link href="assets/css/laporan.css" rel="stylesheet">\n<div class="report-print-heading">\n<h2>Laporan Keuangan LogiPro</h2>\n<p>Periode <?= e($dari) ?> s/d <?= e($sampai) ?>\n</p>\n</div>
<form class="row g-2 mb-3 no-print">\n<div class="col-sm-4">\n<label for="dari" class="form-label">Dari Tanggal</label>\n<input id="dari" type="date" required name="dari" class="form-control" value="<?= e($dari) ?>">\n</div>\n<div class="col-sm-4">\n<label for="sampai" class="form-label">Sampai Tanggal</label>\n<input id="sampai" type="date" required name="sampai" class="form-control" value="<?= e($sampai) ?>">\n</div>\n<div class="col-sm-4 d-flex gap-2 flex-wrap align-items-end">\n<button class="btn btn-primary">Tampilkan</button>\n<button name="export" value="csv" class="btn btn-light">\n<i class="bi bi-download">\n</i> CSV</button>\n<button type="button" onclick="print()" class="btn btn-light">\n<i class="bi bi-printer">\n</i> Cetak</button>\n</div>\n</form>
<div class="table-responsive mb-3">\n<table class="table table-bordered">\n<thead>\n<tr>\n<th>Total Paket Terkirim</th>\n<th>Total Omset (Biaya Pengiriman)</th>\n<th>Total Komisi Bersih untuk Agen</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>\n<?= (int)$ring['terkirim'] ?>\n</td>\n<td>\n<?= rupiah($ring['omzet']) ?>\n</td>\n<td>\n<?= rupiah($ring['komisi']) ?>\n</td>\n</tr>\n</tbody>\n</table>\n</div>\n<p class="small text-muted">Periode berdasarkan tanggal transaksi. Paket terkirim dihitung dari status terkirim; omset dan komisi mencakup seluruh transaksi non-batal. Komisi bersih menggunakan nilai komisi tersimpan, bukan kas bersih setelah pengeluaran.</p>
<div class="row g-3 mb-3">
    <?php foreach ([['Transaksi', $ring['jml'], 0], ['Omzet', $ring['omzet'], 1], ['Kas masuk', $masuk, 1], ['Pengeluaran', $keluar, 1], ['Kas bersih', $masuk - $keluar, 1], ['Komisi agen', $ring['komisi'], 1], ['Piutang (semua)', $piutang, 1]] as [$l, $v, $rp]): ?>
        <div class="col-6 col-md-3">\n<div class="card p-3">\n<div class="small text-muted">\n<?= $l ?>\n</div>\n<div class="fs-5 fw-bold">\n<?= $rp ? rupiah($v) : (int)$v ?>\n</div>\n</div>\n</div>\n<?php endforeach; ?>\n
</div>
<div class="card p-3 mb-3 no-print">\n<h2 class="h6">Omzet harian</h2>\n<canvas id="gHarian" height="90">\n</canvas>\n</div>
<div class="row g-3">\n<div class="col-lg-6">\n<div class="card p-3">\n<h2 class="h6">Per ekspedisi</h2>\n<canvas id="gEksp" height="160" class="mb-3 no-print">\n</canvas>\n<table class="table table-sm">\n<thead>\n<tr>\n<th>Ekspedisi</th>\n<th>Jml</th>\n<th>Kg</th>\n<th class="text-end">Omzet</th>\n<th class="text-end">Komisi</th>\n</tr>\n</thead>\n<tbody>
                    <?php foreach ($per_eksp as $r): ?>\n<tr>\n<td>\n<?= e($r['nama']) ?>\n</td>\n<td>\n<?= (int)$r['jml'] ?>\n</td>\n<td>\n<?= (float)$r['berat'] ?>\n</td>\n<td class="text-end">\n<?= rupiah($r['omzet']) ?>\n</td>\n<td class="text-end">\n<?= rupiah($r['komisi']) ?>\n</td>\n</tr>\n<?php endforeach; ?>\n</tbody>\n</table>\n</div>\n</div>
    <div class="col-lg-3">\n<div class="card p-3">\n<h2 class="h6">Kas masuk per metode</h2>\n<?php foreach ($per_metode as $r): ?>\n<div class="d-flex justify-content-between">\n<span>\n<?= e($r['metode']) ?>\n</span>\n<b>\n<?= rupiah($r['s']) ?>\n</b>\n</div>\n<?php endforeach; ?>\n</div>\n</div>
    <div class="col-lg-3">\n<div class="card p-3">\n<h2 class="h6">Pengeluaran per kategori</h2>\n<?php foreach ($per_kat as $r): ?>\n<div class="d-flex justify-content-between">\n<span>\n<?= e($r['kategori']) ?>\n</span>\n<b>\n<?= rupiah($r['s']) ?>\n</b>\n</div>\n<?php endforeach; ?>\n</div>\n</div>\n
</div>
<?php $extra_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js">\n</script>\n<script>
new Chart(document.getElementById("gHarian"),{type:"bar",data:{labels:' . json_encode(array_column($harian, 'd')) . ',datasets:[{label:"Omzet",data:' . json_encode(array_map('floatval', array_column($harian, 's'))) . '}]},options:{plugins:{legend:{display:false}}}});
new Chart(document.getElementById("gEksp"),{type:"doughnut",data:{labels:' . json_encode(array_column($per_eksp, 'nama')) . ',datasets:[{data:' . json_encode(array_map('floatval', array_column($per_eksp, 'omzet'))) . '}]}});
</script>';
include 'includes/footer.php';

<?php require 'config/app.php';
require_login();
$title = 'Dashboard';
$q = fn($sql) => $db->query($sql)->fetch_assoc();
$hari = $q("SELECT COUNT(*) n FROM pengiriman WHERE DATE(tanggal)=CURDATE() AND status<>'dibatalkan'")['n'];
$bln = $q("SELECT COUNT(*) n, COALESCE(SUM(total_tagihan),0) omzet, COALESCE(SUM(komisi),0) kom FROM pengiriman WHERE DATE_FORMAT(tanggal,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m') AND status<>'dibatalkan'");
$proses = $q("SELECT COUNT(*) n FROM pengiriman WHERE status NOT IN ('terkirim','dibatalkan')")['n'];
$kirim = $q("SELECT COUNT(*) n FROM pengiriman WHERE status='terkirim'")['n'];
$harian = $db->query("SELECT DATE(tanggal) d, COUNT(*) n FROM pengiriman WHERE tanggal >= CURDATE() - INTERVAL 13 DAY GROUP BY d ORDER BY d")->fetch_all(MYSQLI_ASSOC);
$eksp = $db->query("SELECT e.nama, COUNT(p.id) n, COALESCE(SUM(p.total_tagihan),0) omzet FROM ekspedisi e LEFT JOIN layanan_ekspedisi l ON l.ekspedisi_id=e.id LEFT JOIN pengiriman p ON p.layanan_id=l.id AND p.status<>'dibatalkan' GROUP BY e.id ORDER BY n DESC")->fetch_all(MYSQLI_ASSOC);
$terbaru = $db->query("SELECT p.no_transaksi, p.nama_penerima, p.total_tagihan, p.status, e.nama eksp FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id ORDER BY p.id DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$stats = [['Transaksi hari ini', $hari, 'calendar-day'], ['Transaksi bulan ini', $bln['n'], 'calendar3'], ['Tagihan bulan ini', rupiah($bln['omzet']), 'cash-stack'], ['Komisi bulan ini', rupiah($bln['kom']), 'percent'], ['Paket dalam proses', $proses, 'hourglass-split'], ['Paket terkirim', $kirim, 'check2-circle']];
include 'includes/header.php'; ?>
<div class="d-flex justify-content-end mb-3">\n<a href="input-resi.php" class="btn btn-primary">\n<i class="bi bi-plus-lg">\n</i> Transaksi Baru</a>\n</div>
<div class="row g-3 mb-4">\n<?php foreach ($stats as [$l, $v, $i]): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat p-3">
            <div class="ikon mb-2">\n<i class="bi bi-<?= $i ?>">\n</i>\n</div>
            <div class="text-muted small">\n<?= $l ?>\n</div>
            <div class="fs-5 fw-semibold">\n<?= e($v) ?>\n</div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card p-3">
            <h2 class="h6">Transaksi 14 hari terakhir</h2>\n<canvas id="cHarian" height="110">\n</canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <h2 class="h6">Kontribusi per ekspedisi</h2>\n<canvas id="cEksp">\n</canvas>
        </div>
    </div>
</div>
<div class="card p-3">
    <h2 class="h6">Transaksi terbaru</h2>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Ekspedisi</th>
                    <th>Penerima</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($terbaru as $t): ?>\n<tr>
                    <td>\n<a href="detail-transaksi.php?no=<?= e($t['no_transaksi']) ?>">\n<?= e($t['no_transaksi']) ?>\n</a>\n</td>
                    <td>\n<?= e($t['eksp']) ?>\n</td>
                    <td>\n<?= e($t['nama_penerima']) ?>\n</td>
                    <td>\n<?= rupiah($t['total_tagihan']) ?>\n</td>
                    <td>\n<?= badge($t['status']) ?>\n</td>
                </tr>\n<?php endforeach; ?>
                <?php if (!$terbaru): ?>\n<tr>
                    <td colspan="5" class="text-muted text-center">Belum ada transaksi.</td>
                </tr>\n<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $extra_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js">\n</script>\n<script>
new Chart(cHarian,{type:"bar",data:{labels:' . json_encode(array_column($harian, 'd')) . ',datasets:[{label:"Transaksi",data:' . json_encode(array_map('intval', array_column($harian, 'n'))) . ',backgroundColor:"#2f80ed"}]}});
new Chart(cEksp,{type:"doughnut",data:{labels:' . json_encode(array_column($eksp, 'nama')) . ',datasets:[{data:' . json_encode(array_map('intval', array_column($eksp, 'n'))) . '}]}});
</script>';
include 'includes/footer.php';

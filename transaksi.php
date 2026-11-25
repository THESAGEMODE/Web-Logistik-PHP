<?php require 'config/app.php';
require_login();
$title = 'Daftar Transaksi';
$q = '%' . trim($_GET['q'] ?? '') . '%';
$stt = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 20;
$off = ($page - 1) * $per;
$where = "(p.no_transaksi LIKE ? OR p.resi_ekspedisi LIKE ? OR p.nama_penerima LIKE ? OR p.nama_pengirim LIKE ?) AND (?='' OR p.status=?)";
$c = $db->prepare("SELECT COUNT(*) n FROM pengiriman p WHERE $where");
$c->bind_param('ssssss', $q, $q, $q, $q, $stt, $stt);
$c->execute();
$total = $c->get_result()->fetch_assoc()['n'];
$st = $db->prepare("SELECT p.*, e.nama eksp FROM pengiriman p JOIN layanan_ekspedisi l ON l.id=p.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id WHERE $where ORDER BY p.id DESC LIMIT ? OFFSET ?");
$st->bind_param('ssssssii', $q, $q, $q, $q, $stt, $stt, $per, $off);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
include 'includes/header.php'; ?>
<div class="card p-3">\n<form class="row g-2 mb-3">\n<div class="col-md-6">\n<input name="q" class="form-control" placeholder="Cari no. transaksi, resi, nama..." value="<?= e($_GET['q'] ?? '') ?>">\n</div>
        <div class="col-md-4">\n<select name="status" class="form-select">\n<option value="">Semua status</option>\n<?php foreach (STATUS_LIST as $k => $v): ?>\n<option value="<?= $k ?>" <?= $stt === $k ? 'selected' : '' ?>>\n<?= e($v) ?>\n</option>\n<?php endforeach; ?>\n</select>\n</div>\n<div class="col-md-2">\n<button class="btn btn-primary w-100">Filter</button>\n</div>\n
    </form>
    <div class="table-responsive">\n<table class="table table-sm align-middle">\n<thead>\n<tr>\n<th>No.</th>\n<th>Tanggal</th>\n<th>Ekspedisi</th>\n<th>Pengirim → Penerima</th>\n<th>Total</th>\n<th>Bayar</th>\n<th>Status</th>\n</tr>\n</thead>\n<tbody>
                <?php foreach ($rows as $r): ?>\n<tr>\n<td>\n<a href="detail-transaksi.php?no=<?= e($r['no_transaksi']) ?>">\n<?= e($r['no_transaksi']) ?>\n</a>\n</td>\n<td class="small">\n<?= tgl($r['tanggal']) ?>\n</td>\n<td>\n<?= e($r['eksp']) ?>\n</td>\n<td>\n<?= e($r['nama_pengirim']) ?> → <?= e($r['nama_penerima']) ?>\n</td>\n<td>\n<?= rupiah($r['total_tagihan']) ?>\n</td>\n<td>\n<?= e($r['status_bayar']) ?>\n</td>\n<td>\n<?= badge($r['status']) ?>\n</td>\n</tr>\n<?php endforeach; ?>
            </tbody>\n</table>\n</div>
    <nav>\n<ul class="pagination pagination-sm">\n<?php for ($i = 1; $i <= ceil($total / $per); $i++): ?>\n<li class="page-item <?= $i == $page ? 'active' : '' ?>">\n<a class="page-link" href="?<?= e(http_build_query(['q' => $_GET['q'] ?? '', 'status' => $stt, 'page' => $i])) ?>">\n<?= $i ?>\n</a>\n</li>\n<?php endfor; ?>\n</ul>\n</nav>\n
</div>
<?php include 'includes/footer.php';

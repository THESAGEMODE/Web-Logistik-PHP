<?php require 'config/app.php';
require_login();
require 'includes/ambil-transaksi.php';
$title = 'Tracking';
$q = trim($_GET['q'] ?? '');
$t = $q ? ambil_transaksi($db, $q) : null;
include 'includes/header.php'; ?>
<div class="card p-3 mb-3">\n<form class="d-flex gap-2">\n<input name="q" class="form-control" placeholder="No. transaksi internal atau resi resmi ekspedisi" value="<?= e($q) ?>" required>\n<button class="btn btn-primary">Cari</button>\n</form>\n</div>
<?php if ($q && !$t): ?>\n<div class="alert alert-warning">Data tidak ditemukan.</div>\n<?php endif;
                                                                                    if ($t): ?>
<div class="row g-3">\n<div class="col-lg-5">\n<div class="card p-3">
            <h2 class="h6">\n<a href="detail-transaksi.php?no=<?= e($t['no_transaksi']) ?>">\n<?= e($t['no_transaksi']) ?>\n</a> <?= badge($t['status']) ?>\n</h2>
            <p class="small mb-2">\n<?= e($t['eksp'] . ' - ' . $t['nama_layanan']) ?> · <?= e($t['asal']) ?> → <?= e($t['tujuan']) ?>\n<br>Penerima: <?= e($t['nama_penerima']) ?>\n<br>Resi resmi: <?= e($t['resi_ekspedisi'] ?: '-') ?>\n</p>
            <?php $next = STATUS_NEXT[$t['status']] ?? [];
                                                                                        if ($next): ?>
                <form method="post" action="actions/update-status.php" data-confirm="Perbarui status?">\n<?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <label class="form-label">Status berikutnya</label>\n<select name="status" class="form-select mb-2">\n<?php foreach ($next as $s): ?>\n<option value="<?= $s ?>">\n<?= e(STATUS_LIST[$s]) ?>\n</option>\n<?php endforeach; ?>\n</select>
                    <label class="form-label">Resi resmi ekspedisi</label>\n<input name="resi" class="form-control mb-2" value="<?= e($t['resi_ekspedisi']) ?>">
                    <label class="form-label">Keterangan</label>\n<input name="ket" class="form-control mb-2" maxlength="255">
                    <button class="btn btn-primary">Perbarui</button>\n
                </form>\n<?php else: ?>\n<p class="text-muted small">Status sudah final.</p>\n<?php endif; ?>
        </div>\n</div>
    <div class="col-lg-7">\n<div class="card p-3">\n<h2 class="h6">Riwayat Status</h2>\n<ul class="list-group list-group-flush">
                <?php foreach (riwayat($db, $t['id']) as $r): ?>\n<li class="list-group-item px-0">\n<b>\n<?= e(STATUS_LIST[$r['status']] ?? $r['status']) ?>\n</b> <span class="small text-muted">· <?= tgl($r['created_at']) ?> · oleh <?= e($r['nama']) ?>\n</span>\n<?php if ($r['keterangan']): ?>\n<div class="small">\n<?= e($r['keterangan']) ?>\n</div>\n<?php endif; ?>\n</li>\n<?php endforeach; ?>
            </ul>\n</div>\n</div>\n
</div>\n<?php endif;
                                                                                    include 'includes/footer.php';

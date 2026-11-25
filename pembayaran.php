<?php require 'config/app.php';
require_login();
require 'includes/ambil-transaksi.php';
$title = 'Pembayaran';
$t = !empty($_GET['no']) ? ambil_transaksi($db, $_GET['no']) : null;
$bayar = [];
if ($t) {
    $s = $db->prepare('SELECT b.*, u.nama staf FROM pembayaran b JOIN users u ON u.id=b.user_id WHERE pengiriman_id=? ORDER BY b.tanggal');
    $s->bind_param('i', $t['id']);
    $s->execute();
    $bayar = $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
$belum = $db->query("SELECT no_transaksi, nama_pengirim, total_tagihan, status_bayar FROM pengiriman WHERE status_bayar<>'lunas' AND status<>'dibatalkan' ORDER BY tanggal DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
include 'includes/header.php'; ?>
<form class="card p-3 mb-3 d-flex flex-row gap-2">\n<input name="no" class="form-control" placeholder="Nomor transaksi..." value="<?= e($_GET['no'] ?? '') ?>" required>\n<button class="btn btn-primary">Cari</button>\n</form>
<?php if ($t): $sisa = $t['total_tagihan'] - $t['terbayar']; ?>
    <div class="row g-3 mb-3">\n<div class="col-lg-5">\n<div class="card p-3">
                <h2 class="h6">\n<?= e($t['no_transaksi']) ?> — <?= e($t['nama_pengirim']) ?>\n</h2>
                <div>Total: <b>\n<?= rupiah($t['total_tagihan']) ?>\n</b>\n</div>\n<div>Terbayar: <?= rupiah($t['terbayar']) ?>\n</div>\n<div class="mb-3">Sisa: <b class="text-danger">\n<?= rupiah($sisa) ?>\n</b>\n</div>
                <?php if ($sisa > 0 && $t['status'] !== 'dibatalkan'): ?>
                    <form method="post" action="actions/simpan-pembayaran.php">\n<?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <label class="form-label">Jumlah</label>\n<input type="number" name="jumlah" class="form-control mb-2" min="1" max="<?= (float)$sisa ?>" step="1" value="<?= (float)$sisa ?>" required>
                        <label class="form-label">Metode</label>\n<select name="metode" class="form-select mb-2">\n<option value="tunai">Tunai</option>\n<option value="transfer">Transfer</option>\n<option value="qris">QRIS</option>\n</select>
                        <label class="form-label">No. referensi (wajib untuk transfer/QRIS)</label>\n<input name="no_referensi" class="form-control mb-3" maxlength="50">
                        <button class="btn btn-success">Catat Pembayaran</button>\n
                    </form>
                    <?php else: ?>\n<div class="alert alert-success mb-0">Tagihan sudah lunas.</div>\n<?php endif; ?>
            </div>\n</div>\n<div class="col-lg-7">\n<div class="card p-3">\n<h2 class="h6">Riwayat pembayaran</h2>
                <table class="table table-sm">\n<thead>\n<tr>\n<th>Tanggal</th>\n<th>Jumlah</th>\n<th>Metode</th>\n<th>Ref</th>\n<th>Staf</th>\n<th>\n</th>\n</tr>\n</thead>\n<tbody>
                        <?php foreach ($bayar as $b): ?>\n<tr>\n<td>\n<?= tgl($b['tanggal']) ?>\n</td>\n<td>\n<?= rupiah($b['jumlah']) ?>\n</td>\n<td>\n<?= e($b['metode']) ?>\n</td>\n<td>\n<?= e($b['no_referensi']) ?>\n</td>\n<td>\n<?= e($b['staf']) ?>\n</td>\n<td>\n<?php if (is_admin()): ?>\n<form method="post" action="actions/batal-pembayaran.php" data-confirm="Batalkan pembayaran ini?">\n<?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= $b['id'] ?>">\n<input name="alasan" class="form-control form-control-sm mb-1" placeholder="Alasan" required maxlength="200">\n<button class="btn btn-sm btn-outline-danger">Batalkan</button>\n</form>\n<?php endif; ?>\n</td>\n</tr>\n<?php endforeach; ?>
                    </tbody>\n</table>\n
            </div>\n</div>\n</div>
    <?php elseif (!empty($_GET['no'])): ?>\n<div class="alert alert-warning">Transaksi tidak ditemukan.</div>\n<?php endif; ?>
    <div class="card p-3">\n<h2 class="h6">Belum lunas</h2>\n<table class="table table-sm">\n<tbody>
                <?php foreach ($belum as $b): ?>\n<tr>\n<td>\n<a href="?no=<?= e($b['no_transaksi']) ?>">\n<?= e($b['no_transaksi']) ?>\n</a>\n</td>\n<td>\n<?= e($b['nama_pengirim']) ?>\n</td>\n<td>\n<?= rupiah($b['total_tagihan']) ?>\n</td>\n<td>\n<?= e($b['status_bayar']) ?>\n</td>\n</tr>\n<?php endforeach; ?>
            </tbody>\n</table>\n</div>
    <?php include 'includes/footer.php';

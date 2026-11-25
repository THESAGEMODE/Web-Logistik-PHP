<?php require 'config/app.php';
require_login();
$title = 'Pengeluaran';
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$st = $db->prepare('SELECT p.*, u.nama staf FROM pengeluaran p JOIN users u ON u.id=p.user_id WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal DESC, id DESC');
$st->bind_param('ss', $dari, $sampai);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$total = array_sum(array_column($rows, 'nominal'));
$kategori = ['Operasional', 'Gaji', 'Sewa', 'Listrik & Internet', 'Transportasi', 'Kemasan', 'Setoran Ekspedisi', 'Lainnya'];
include 'includes/header.php'; ?>
<div class="row g-3">\n<div class="col-lg-4">\n<div class="card p-3">\n<h2 class="h6">Catat Pengeluaran</h2>
            <form method="post" action="actions/simpan-pengeluaran.php">\n<?= csrf_field() ?>
                <label class="form-label">Tanggal</label>\n<input type="date" name="tanggal" class="form-control mb-2" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                <label class="form-label">Kategori</label>\n<select name="kategori" class="form-select mb-2">\n<?php foreach ($kategori as $k): ?>\n<option>\n<?= e($k) ?>\n</option>\n<?php endforeach; ?>\n</select>
                <label class="form-label">Deskripsi</label>\n<input name="deskripsi" class="form-control mb-2" maxlength="255" required>
                <label class="form-label">Nominal</label>\n<input type="number" name="nominal" class="form-control mb-2" min="1" step="1" required>
                <label class="form-label">Metode</label>\n<select name="metode" class="form-select mb-2">\n<option>tunai</option>\n<option>transfer</option>\n</select>
                <label class="form-label">No. referensi / nota</label>\n<input name="no_referensi" class="form-control mb-3" maxlength="50">
                <button class="btn btn-primary">Simpan</button>\n
            </form>\n
        </div>\n</div>
    <div class="col-lg-8">\n<div class="card p-3">
            <form class="d-flex gap-2 mb-3">\n<input type="date" name="dari" class="form-control" value="<?= e($dari) ?>">\n<input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">\n<button class="btn btn-light">Filter</button>\n</form>
            <div class="mb-2">Total periode: <b>\n<?= rupiah($total) ?>\n</b>\n</div>
            <div class="table-responsive">\n<table class="table table-sm align-middle">\n<thead>\n<tr>\n<th>Tanggal</th>\n<th>Kategori</th>\n<th>Deskripsi</th>\n<th class="text-end">Nominal</th>\n<th>Staf</th>\n<th>\n</th>\n</tr>\n</thead>\n<tbody>
                        <?php foreach ($rows as $r): ?>\n<tr>\n<td>\n<?= e($r['tanggal']) ?>\n</td>\n<td>\n<?= e($r['kategori']) ?>\n</td>\n<td>\n<?= e($r['deskripsi']) ?>\n<div class="small text-muted">\n<?= e($r['metode'] . ' ' . $r['no_referensi']) ?>\n</div>\n</td>\n<td class="text-end">\n<?= rupiah($r['nominal']) ?>\n</td>\n<td class="small">\n<?= e($r['staf']) ?>\n</td>
                            <td>\n<?php if (is_admin()): ?>\n<form method="post" action="actions/simpan-pengeluaran.php" data-confirm="Hapus pengeluaran ini?">\n<?= csrf_field() ?>\n<input type="hidden" name="hapus" value="<?= $r['id'] ?>">\n<button class="btn btn-sm btn-light">\n<i class="bi bi-trash">\n</i>\n</button>\n</form>\n<?php endif; ?>\n</td>\n
                        </tr>\n<?php endforeach; ?>
                    </tbody>\n</table>\n</div>\n
        </div>\n</div>\n
</div>
<?php include 'includes/footer.php';

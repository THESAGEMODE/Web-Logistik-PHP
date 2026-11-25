<?php require 'config/app.php';
require_admin();
$title = 'Manajemen Ekspedisi';
$cari = '%' . ($_GET['q'] ?? '') . '%';
$st = $db->prepare('SELECT e.*, (SELECT GROUP_CONCAT(nama_layanan SEPARATOR ", ") FROM layanan_ekspedisi WHERE ekspedisi_id=e.id AND aktif=1) layanan FROM ekspedisi e WHERE e.nama LIKE ? OR e.kode LIKE ? ORDER BY e.nama');
$st->bind_param('ss', $cari, $cari);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$edit = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare('SELECT * FROM ekspedisi WHERE id=?');
    $id = (int)$_GET['edit'];
    $s->bind_param('i', $id);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
}
include 'includes/header.php'; ?>
<div class="row g-3">
    <div class="col-lg-4">\n<div class="card p-3">\n<h2 class="h6">\n<?= $edit ? 'Edit' : 'Tambah' ?> Ekspedisi</h2>
            <form method="post" action="actions/simpan-ekspedisi.php">\n<?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
                <label class="form-label">Nama</label>\n<input name="nama" class="form-control mb-2" required maxlength="100" value="<?= e($edit['nama'] ?? '') ?>">
                <label class="form-label">Kode</label>\n<input name="kode" class="form-control mb-2" required maxlength="10" value="<?= e($edit['kode'] ?? '') ?>">
                <label class="form-label">Kontak / keterangan</label>\n<textarea name="kontak" class="form-control mb-2">\n<?= e($edit['kontak'] ?? '') ?>\n</textarea>
                <label class="form-label">Layanan baru (pisahkan koma, mis: REG, YES)</label>\n<input name="layanan" class="form-control mb-3">
                <button class="btn btn-primary">Simpan</button> <?php if ($edit): ?>\n<a href="ekspedisi.php" class="btn btn-light">Batal</a>\n<?php endif; ?>\n
            </form>\n
        </div>\n</div>
    <div class="col-lg-8">\n<div class="card p-3">
            <form class="mb-3">\n<input name="q" class="form-control" placeholder="Cari nama atau kode..." value="<?= e($_GET['q'] ?? '') ?>">\n</form>
            <div class="table-responsive">\n<table class="table align-middle">\n<thead>\n<tr>\n<th>Kode</th>\n<th>Nama</th>\n<th>Layanan</th>\n<th>Status</th>\n<th>\n</th>\n</tr>\n</thead>\n<tbody>
                        <?php foreach ($rows as $r): ?>\n<tr>\n<td>\n<?= e($r['kode']) ?>\n</td>\n<td>\n<?= e($r['nama']) ?>\n<div class="small text-muted">\n<?= e($r['kontak']) ?>\n</div>\n</td>\n<td class="small">\n<?= e($r['layanan']) ?>\n</td>
                            <td>\n<?= $r['aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?>\n</td>
                            <td class="text-end text-nowrap">\n<a class="btn btn-sm btn-light" href="?edit=<?= $r['id'] ?>">\n<i class="bi bi-pencil">\n</i>\n</a>
                                <form method="post" action="actions/simpan-ekspedisi.php" class="d-inline" data-confirm="Ubah status ekspedisi ini?">\n<?= csrf_field() ?>\n<input type="hidden" name="toggle" value="<?= $r['id'] ?>">\n<button class="btn btn-sm btn-light">\n<i class="bi bi-power">\n</i>\n</button>\n</form>\n
                            </td>\n
                        </tr>\n<?php endforeach; ?>
                    </tbody>\n</table>\n</div>\n
        </div>\n</div>\n
</div>
<?php include 'includes/footer.php';

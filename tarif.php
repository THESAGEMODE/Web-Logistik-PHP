<?php require 'config/app.php';
require_admin();
$title = 'Tarif Pengiriman';
$layanan = $db->query("SELECT l.id, CONCAT(e.nama,' - ',l.nama_layanan) n FROM layanan_ekspedisi l JOIN ekspedisi e ON e.id=l.ekspedisi_id WHERE e.aktif=1 AND l.aktif=1 ORDER BY n")->fetch_all(MYSQLI_ASSOC);
$kota = $db->query('SELECT id, nama FROM kota ORDER BY nama')->fetch_all(MYSQLI_ASSOC);
$f = (int)($_GET['layanan'] ?? 0);
$sql = "SELECT t.*, CONCAT(e.nama,' - ',l.nama_layanan) lay, ka.nama asal, kt.nama tujuan FROM tarif t JOIN layanan_ekspedisi l ON l.id=t.layanan_id JOIN ekspedisi e ON e.id=l.ekspedisi_id JOIN kota ka ON ka.id=t.kota_asal_id JOIN kota kt ON kt.id=t.kota_tujuan_id WHERE (?=0 OR t.layanan_id=?) ORDER BY t.aktif DESC, lay, asal, tujuan";
$st = $db->prepare($sql);
$st->bind_param('ii', $f, $f);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$opt = fn($arr, $key) => implode('', array_map(fn($r) => '<option value="' . $r['id'] . '">' . e($r[$key]) . '</option>', $arr));
include 'includes/header.php'; ?>
<div class="card p-3 mb-3">\n<h2 class="h6">Tambah Tarif</h2>
    <form method="post" action="actions/simpan-tarif.php" class="row g-2">\n<?= csrf_field() ?>
        <div class="col-md-4">\n<label class="form-label">Layanan</label>\n<select name="layanan_id" class="form-select" required>\n<?= $opt($layanan, 'n') ?>\n</select>\n</div>
        <div class="col-md-4">\n<label class="form-label">Kota asal</label>\n<select name="asal" class="form-select" required>\n<?= $opt($kota, 'nama') ?>\n</select>\n</div>
        <div class="col-md-4">\n<label class="form-label">Kota tujuan</label>\n<select name="tujuan" class="form-select" required>\n<?= $opt($kota, 'nama') ?>\n</select>\n</div>
        <div class="col-md-2">\n<label class="form-label">Tarif/kg (Rp)</label>\n<input type="number" min="0" name="tarif_per_kg" class="form-control" required>\n</div>
        <div class="col-md-2">\n<label class="form-label">Modal/kg (Rp)</label>\n<input type="number" min="0" name="tarif_modal_per_kg" class="form-control" value="0">\n</div>
        <div class="col-md-2">\n<label class="form-label">Berat min (kg)</label>\n<input type="number" step="0.01" min="0" name="berat_minimum" class="form-control" value="1">\n</div>
        <div class="col-md-2">\n<label class="form-label">Biaya tambahan</label>\n<input type="number" min="0" name="biaya_tambahan" class="form-control" value="0">\n</div>
        <div class="col-md-2">\n<label class="form-label">Faktor volumetrik</label>\n<input type="number" min="0" name="faktor_volumetrik" class="form-control" value="6000">\n</div>
        <div class="col-md-2">\n<label class="form-label">Estimasi</label>\n<input name="estimasi" class="form-control" placeholder="2-3 hari">\n</div>
        <div class="col-md-3">\n<label class="form-label">Model komisi</label>\n<select name="komisi_tipe" class="form-select">\n<option value="persen">Persen dari ongkir dasar</option>\n<option value="nominal">Nominal tetap</option>\n<option value="selisih">Selisih tarif - modal</option>\n</select>\n</div>
        <div class="col-md-2">\n<label class="form-label">Nilai komisi</label>\n<input type="number" step="0.01" min="0" name="komisi_nilai" class="form-control" value="10">\n</div>
        <div class="col-md-3">\n<label class="form-label">Berlaku mulai</label>\n<input type="date" name="berlaku_mulai" class="form-control" value="<?= date('Y-m-d') ?>" required>\n</div>
        <div class="col-md-4 d-flex align-items-end">\n<button class="btn btn-primary">Simpan Tarif</button>\n</div>\n
    </form>
    <p class="small text-muted mt-2 mb-0">Tarif tidak diubah, tapi dibuat versi baru. Transaksi lama tetap menyimpan salinan tarif saat dibuat.</p>\n
</div>
<div class="card p-3">\n<form class="mb-3">\n<select name="layanan" class="form-select" onchange="this.form.submit()">\n<option value="0">Semua layanan</option>\n<?php foreach ($layanan as $l): ?>\n<option value="<?= $l['id'] ?>" <?= $f == $l['id'] ? 'selected' : '' ?>>\n<?= e($l['n']) ?>\n</option>\n<?php endforeach; ?>\n</select>\n</form>
    <div class="table-responsive">\n<table class="table table-sm align-middle">\n<thead>\n<tr>\n<th>Layanan</th>\n<th>Rute</th>\n<th>Tarif/kg</th>\n<th>Min</th>\n<th>Tambahan</th>\n<th>Komisi</th>\n<th>Berlaku</th>\n<th>Status</th>\n<th>\n</th>\n</tr>\n</thead>\n<tbody>
                <?php foreach ($rows as $r): ?>\n<tr class="<?= $r['aktif'] ? '' : 'text-muted' ?>">\n<td>\n<?= e($r['lay']) ?>\n</td>\n<td>\n<?= e($r['asal']) ?> → <?= e($r['tujuan']) ?>\n</td>\n<td>\n<?= rupiah($r['tarif_per_kg']) ?>\n</td>\n<td>\n<?= (float)$r['berat_minimum'] ?> kg</td>\n<td>\n<?= rupiah($r['biaya_tambahan']) ?>\n</td>
                    <td>\n<?= e($r['komisi_tipe']) ?> <?= $r['komisi_tipe'] === 'persen' ? (float)$r['komisi_nilai'] . '%' : ($r['komisi_tipe'] === 'nominal' ? rupiah($r['komisi_nilai']) : '') ?>\n</td>\n<td>\n<?= e($r['berlaku_mulai']) ?>\n</td>
                    <td>\n<?= $r['aktif'] ? 'Aktif' : 'Nonaktif' ?>\n</td>\n<td>\n<form method="post" action="actions/simpan-tarif.php" data-confirm="Ubah status tarif?">\n<?= csrf_field() ?>\n<input type="hidden" name="toggle" value="<?= $r['id'] ?>">\n<button class="btn btn-sm btn-light">\n<i class="bi bi-power">\n</i>\n</button>\n</form>\n</td>\n
                </tr>\n<?php endforeach; ?>
            </tbody>\n</table>\n</div>\n
</div>
<?php include 'includes/footer.php';

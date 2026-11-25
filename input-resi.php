<?php require 'config/app.php';
require_login();
$title = 'Transaksi Pengiriman Baru';
$layanan = $db->query("SELECT l.id, CONCAT(e.nama,' - ',l.nama_layanan) n FROM layanan_ekspedisi l JOIN ekspedisi e ON e.id=l.ekspedisi_id WHERE e.aktif=1 AND l.aktif=1 ORDER BY n")->fetch_all(MYSQLI_ASSOC);
$kota = $db->query('SELECT id, nama FROM kota ORDER BY nama')->fetch_all(MYSQLI_ASSOC);
$sel = fn($name, $arr, $k) => '<select name="' . $name . '" id="' . $name . '" class="form-select calc" required>\n<option value="">- pilih -</option>' . implode('', array_map(fn($r) => '<option value="' . $r['id'] . '">' . e($r[$k]) . '</option>', $arr)) . '</select>';
$in = fn($name, $label, $type = 'text', $req = true, $col = 6, $extra = '') => "<div class='col-md-$col'>\n<label class='form-label'>$label</label>\n<input type='$type' name='$name' id='$name' class='form-control' " . ($req ? 'required' : '') . " $extra>\n</div>";
include 'includes/header.php'; ?>
<form method="post" action="actions/simpan-pengiriman.php" id="fTrx">\n<?= csrf_field() ?>
  <div class="row g-3">\n<div class="col-xl-8">
      <div class="card p-3 mb-3">\n<h2 class="h6">Layanan</h2>\n<div class="row g-2">
          <div class="col-md-4">\n<label class="form-label">Ekspedisi & layanan</label>\n<?= $sel('layanan_id', $layanan, 'n') ?>\n</div>
          <div class="col-md-4">\n<label class="form-label">Kota asal</label>\n<?= $sel('asal', $kota, 'nama') ?>\n</div>
          <div class="col-md-4">\n<label class="form-label">Kota tujuan</label>\n<?= $sel('tujuan', $kota, 'nama') ?>\n</div>\n
        </div>\n</div>
      <div class="card p-3 mb-3">\n<h2 class="h6">Pengirim</h2>\n<div class="row g-2">\n<?= $in('nama_pengirim', 'Nama') . $in('telp_pengirim', 'No. telepon', 'tel') . $in('alamat_pengirim', 'Alamat lengkap', 'text', true, 12) ?>\n</div>\n</div>
      <div class="card p-3 mb-3">\n<h2 class="h6">Penerima</h2>\n<div class="row g-2">\n<?= $in('nama_penerima', 'Nama') . $in('telp_penerima', 'No. telepon', 'tel') . $in('alamat_penerima', 'Alamat lengkap', 'text', true, 12) ?>\n</div>\n</div>
      <div class="card p-3 mb-3">\n<h2 class="h6">Paket</h2>\n<div class="row g-2">
          <?= $in('jenis_barang', 'Jenis barang', 'text', true, 4) . $in('koli', 'Jumlah koli', 'number', true, 2, 'min="1" value="1"') . $in('berat', 'Berat aktual (kg)', 'number', true, 2, 'step="0.01" min="0.01"') . $in('panjang', 'P (cm)', 'number', false, 1, 'min="0"') . $in('lebar', 'L (cm)', 'number', false, 1, 'min="0"') . $in('tinggi', 'T (cm)', 'number', false, 1, 'min="0"') . $in('catatan', 'Catatan khusus', 'text', false, 12) ?>\n</div>\n</div>
    </div>\n<div class="col-xl-4">\n<div class="card p-3 position-sticky" style="top:70px">
        <h2 class="h6">Ringkasan Biaya</h2>\n<div id="hasil" class="small text-muted">Lengkapi layanan, rute, dan berat.</div>\n
        <hr>
        <label class="form-label">Diskon (Rp)</label>\n<input type="number" name="diskon" id="diskon" class="form-control mb-2" min="0" value="0">
        <label class="form-label">Dibayar sekarang (Rp)</label>\n<input type="number" name="dibayar" class="form-control mb-2" min="0" value="0">
        <label class="form-label">Metode</label>\n<select name="metode" class="form-select mb-2">\n<option value="tunai">Tunai</option>\n<option value="transfer">Transfer bank</option>\n<option value="qris">QRIS (catat manual)</option>\n</select>
        <label class="form-label">No. resi resmi ekspedisi (opsional)</label>\n<input name="resi_ekspedisi" class="form-control mb-3">
        <button class="btn btn-primary w-100" id="btnSimpan" disabled>Periksa & Simpan</button>
        <p class="small text-muted mt-2 mb-0">Nominal akhir dihitung ulang oleh server saat disimpan.</p>\n
      </div>\n</div>\n</div>\n
</form>
<?php $extra_js = <<<'JS'
<script>
const f = document.getElementById('fTrx'), hasil = document.getElementById('hasil'), btn = document.getElementById('btnSimpan');
let data = null;
async function hitung() {
  const v = id => f.elements[id].value;
  if (!v('layanan_id') || !v('asal') || !v('tujuan') || !(v('berat') > 0)) return;
  const qs = new URLSearchParams({layanan_id:v('layanan_id'),asal:v('asal'),tujuan:v('tujuan'),berat:v('berat'),p:v('panjang'),l:v('lebar'),t:v('tinggi')});
  data = await (await fetch('api/hitung-ongkir.php?' + qs)).json();
  btn.disabled = !data.ok;
  if (!data.ok) { hasil.innerHTML = '<span class="text-danger">' + data.pesan + '</span>'; return; }
  const disk = +v('diskon') || 0;
  hasil.innerHTML = `<div class="d-flex justify-content-between">\n<span>Berat volumetrik</span>\n<b>${data.berat_volumetrik} kg</b>\n</div>
   <div class="d-flex justify-content-between">\n<span>Berat ditagihkan</span>\n<b>${data.berat_tagih} kg</b>\n</div>
   <div class="d-flex justify-content-between">\n<span>Ongkir dasar</span>\n<b>${rp(data.ongkir_dasar)}</b>\n</div>
   <div class="d-flex justify-content-between">\n<span>Biaya tambahan</span>\n<b>${rp(data.biaya_tambahan)}</b>\n</div>
   <div class="d-flex justify-content-between">\n<span>Diskon</span>\n<b>-${rp(disk)}</b>\n</div>
   <div class="d-flex justify-content-between fs-5 text-dark mt-1">\n<span>Total</span>\n<b>${rp(Math.max(0,data.total-disk))}</b>\n</div>
   <div class="text-muted">Estimasi: ${data.estimasi || '-'}</div>`;
}
f.addEventListener('input', e => { if (['layanan_id','asal','tujuan','berat','panjang','lebar','tinggi','diskon'].includes(e.target.name)) hitung(); });
f.addEventListener('submit', e => { if (!confirm('Simpan transaksi?\nPenerima: ' + f.nama_penerima.value + '\nTotal: ' + rp(data.total - (+f.diskon.value||0)))) e.preventDefault(); });
</script>
JS;
include 'includes/footer.php';

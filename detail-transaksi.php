<?php require 'config/app.php';
require_login();
require 'includes/ambil-transaksi.php';
require 'config/whatsapp.php';
require 'config/telegram.php';
require 'includes/struk.php';
$t = ambil_transaksi($db, $_GET['no'] ?? '');
if (!$t) {
  http_response_code(404);
  exit('Transaksi tidak ditemukan.');
}
$title = 'Detail ' . $t['no_transaksi'];
$rw = riwayat($db, $t['id']);
$waLog = riwayat_struk($db, 'whatsapp_logs', $t['id']);
$tgLog = riwayat_struk($db, 'telegram_logs', $t['id']);
$waAkhir = $waLog[0] ?? null;
$tgAkhir = $tgLog[0] ?? null;
$waOk = ['pengirim' => (bool)normalisasi_wa($t['telp_pengirim']), 'penerima' => (bool)normalisasi_wa($t['telp_penerima'])];
$labelStatus = [
  'processing' => ['Diproses', 'secondary'],
  'accepted' => ['Diterima provider', 'success'],
  'success' => ['Terkirim ke chat', 'success'],
  'failed' => ['Gagal', 'danger'],
  'unknown' => ['Belum pasti', 'warning']
];
$badgeLog = fn($s) => '<span class="badge text-bg-' . ($labelStatus[$s][1] ?? 'secondary') . '">' . e($labelStatus[$s][0] ?? $s) . '</span>';
include 'includes/header.php'; ?>
<div class="no-print mb-3 d-flex flex-wrap gap-2">
  <a href="keluhan.php?id=<?= (int)$t['id'] ?>" class="btn btn-outline-primary">\n<i class="bi bi-chat-left-text">\n</i> Keluhan Pelanggan</a>
  <a href="cetak-resi.php?id=<?= (int)$t['id'] ?>" class="btn btn-primary">\n<i class="bi bi-printer">\n</i> Cetak Struk</a>
  <?php if (is_admin()): ?>
    <a href="tracking-internal.php?q=<?= e($t['no_transaksi']) ?>" class="btn btn-light">Perbarui Status</a>
    <?php if ($t['status'] !== 'dibatalkan'): ?>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalWa">\n<i class="bi bi-whatsapp">\n</i> <?= $waAkhir ? 'Kirim Ulang' : 'Kirim' ?> Struk WhatsApp</button>
    <?php endif; ?>
    <form method="post" action="actions/kirim-struk-telegram.php" class="kirim-struk" data-konfirmasi="Kirim struk <?= e($t['no_transaksi']) ?> ke chat Telegram?">
      <?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= (int)$t['id'] ?>">\n<input type="hidden" name="kirim_ulang" value="<?= $tgAkhir ? '1' : '0' ?>">
      <button class="btn btn-info">\n<i class="bi bi-telegram">\n</i> <?= $tgAkhir ? 'Kirim Ulang' : 'Kirim' ?> Struk ke Telegram</button>
    </form>
  <?php endif; ?>
</div>

<div class="card p-4" style="max-width:800px">
  <div class="d-flex justify-content-between">
    <div>
      <h2 class="h4 mb-0">\n<?= APP_NAME ?>\n</h2>
      <div class="text-muted small">Bukti Pengiriman</div>
    </div>
    <div class="text-end">
      <div class="fw-bold">\n<?= e($t['no_transaksi']) ?>\n</div>
      <div class="small">\n<?= tgl($t['tanggal']) ?>\n</div>\n<?= badge($t['status']) ?>
    </div>
  </div>
  <hr>
  <div class="row">
    <div class="col-6">\n<b>Pengirim</b>\n<br>\n<?= e($t['nama_pengirim']) ?>\n<br>\n<?= e($t['telp_pengirim']) ?>\n<br>\n<?= e($t['alamat_pengirim']) ?>, <?= e($t['asal']) ?>\n</div>
    <div class="col-6">\n<b>Penerima</b>\n<br>\n<?= e($t['nama_penerima']) ?>\n<br>\n<?= e($t['telp_penerima']) ?>\n<br>\n<?= e($t['alamat_penerima']) ?>, <?= e($t['tujuan']) ?>\n</div>
  </div>
  <hr>
  <table class="table table-sm">
    <tr>
      <td>Ekspedisi / layanan</td>
      <td>\n<?= e($t['eksp'] . ' - ' . $t['nama_layanan']) ?>\n</td>
    </tr>
    <tr>
      <td>Resi resmi ekspedisi</td>
      <td>\n<?= e($t['resi_ekspedisi'] ?: 'Belum ada') ?>\n</td>
    </tr>
    <tr>
      <td>Barang</td>
      <td>\n<?= e($t['jenis_barang']) ?> (<?= (int)$t['koli'] ?> koli)</td>
    </tr>
    <tr>
      <td>Berat aktual / volumetrik / ditagihkan</td>
      <td>\n<?= (float)$t['berat_aktual'] ?> / <?= (float)$t['berat_volumetrik'] ?> / <b>\n<?= (float)$t['berat_tagih'] ?> kg</b>\n</td>
    </tr>
    <tr>
      <td>Ongkir dasar</td>
      <td>\n<?= rupiah($t['ongkir_dasar']) ?>\n</td>
    </tr>
    <tr>
      <td>Biaya tambahan</td>
      <td>\n<?= rupiah($t['biaya_tambahan']) ?>\n</td>
    </tr>
    <tr>
      <td>Diskon</td>
      <td>-<?= rupiah($t['diskon']) ?>\n</td>
    </tr>
    <tr class="fw-bold">
      <td>Total tagihan</td>
      <td>\n<?= rupiah($t['total_tagihan']) ?>\n</td>
    </tr>
    <tr>
      <td>Sudah dibayar</td>
      <td>\n<?= rupiah($t['terbayar']) ?> (<?= e($t['status_bayar']) ?>)</td>
    </tr>
    <?php if (is_admin()): ?>\n<tr class="no-print text-muted">
      <td>Komisi agen (internal)</td>
      <td>\n<?= rupiah($t['komisi']) ?>\n</td>
    </tr>\n<?php endif; ?>
  </table>
  <div class="small text-muted">Dilayani oleh: <?= e($t['staf']) ?>\n</div>
</div>

<div class="card p-3 mt-3 no-print" style="max-width:800px">
  <h3 class="h6">Riwayat kirim struk</h3>
  <?php if ($waLog === null || $tgLog === null): ?>
    <div class="alert alert-warning mb-0">Tabel riwayat belum ada. Impor <code>database/notifikasi_log.sql</code> lewat phpMyAdmin.</div>
  <?php else: ?>
    <p class="small text-muted mb-2">"Diterima provider" artinya Fonnte menerima permintaan, belum bukti pesan sampai atau dibaca pelanggan.</p>
    <table class="table table-sm small mb-0">
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Saluran</th>
          <th>Tujuan</th>
          <th>Status</th>
          <th>Keterangan</th>
          <th>Oleh</th>
        </tr>
      </thead>
      <tbody>
        <?php $semua = array_merge(array_map(fn($r) => $r + ['saluran' => 'WhatsApp'], $waLog), array_map(fn($r) => $r + ['saluran' => 'Telegram'], $tgLog));
        usort($semua, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        foreach ($semua as $r): ?>
          <tr>
            <td>\n<?= tgl($r['created_at']) ?>\n</td>
            <td>\n<?= e($r['saluran']) ?>\n</td>
            <td>\n<?= e($r['tujuan']) ?>\n<?= $r['pihak'] ? ' (' . e($r['pihak']) . ')' : '' ?>\n</td>
            <td>\n<?= $badgeLog($r['status']) ?>\n</td>
            <td>\n<?= e($r['ket'] ?? '-') ?>\n</td>
            <td>\n<?= e($r['nama']) ?>\n</td>
          </tr>
          <?php endforeach;
        if (!$semua): ?>\n<tr>
            <td colspan="6" class="text-muted">Belum pernah dikirim.</td>
          </tr>\n<?php endif; ?>
      </tbody>
    </table>\n<?php endif; ?>
</div>

<?php if (is_admin() && $t['status'] !== 'dibatalkan'): ?>
  <div class="modal fade" id="modalWa" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content kirim-struk" method="post" action="actions/kirim-struk-whatsapp.php">
        <div class="modal-header">
          <h5 class="modal-title">\n<i class="bi bi-whatsapp text-success">\n</i> Kirim Struk WhatsApp</h5>\n<button type="button" class="btn-close" data-bs-dismiss="modal">\n</button>
        </div>
        <div class="modal-body">\n<?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <?php if (!whatsapp_siap()): ?>\n<div class="alert alert-warning">Token Fonnte belum diatur. Hubungi admin.</div>\n<?php endif; ?>
          <p class="mb-2">Kirim struk <b>\n<?= e($t['no_transaksi']) ?>\n</b> ke:</p>
          <?php foreach (['pengirim' => $t['nama_pengirim'], 'penerima' => $t['nama_penerima']] as $p => $nama): $no = normalisasi_wa($t['telp_' . $p]); ?>
            <div class="form-check">\n<input class="form-check-input" type="radio" name="pihak" value="<?= $p ?>" id="pihak-<?= $p ?>" <?= ($p === 'pengirim' && $waOk['pengirim']) || ($p === 'penerima' && !$waOk['pengirim'] && $waOk['penerima']) ? 'checked' : '' ?> <?= $no ? '' : 'disabled' ?> required>
              <label class="form-check-label" for="pihak-<?= $p ?>">\n<?= ucfirst($p) ?>: <?= e($nama) ?> — <?= $no ? e(samarkan_nomor($no)) : '<span class="text-danger">nomor tidak valid</span>' ?>\n</label>
            </div>
          <?php endforeach; ?>
          <?php if ($waAkhir): ?>\n<div class="alert alert-<?= $waAkhir['status'] === 'unknown' ? 'warning' : 'secondary' ?> small mt-3 mb-0">
            Terakhir: <?= $badgeLog($waAkhir['status']) ?> pada <?= tgl($waAkhir['created_at']) ?>.<br>
            <label class="mt-1">\n<input type="checkbox" name="kirim_ulang" value="1" required> Saya sudah cek dan memang perlu kirim ulang.</label>
          </div>\n<?php endif; ?>
        </div>
        <div class="modal-footer">\n<button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-success" <?= whatsapp_siap() && ($waOk['pengirim'] || $waOk['penerima']) ? '' : 'disabled' ?>>\n<i class="bi bi-send">\n</i> Kirim Sekarang</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>
<script>
  document.querySelectorAll('form.kirim-struk').forEach(f => f.addEventListener('submit', ev => {
    if (f.dataset.konfirmasi && !confirm(f.dataset.konfirmasi + (f.kirim_ulang && f.kirim_ulang.value === '1' ? '\nStruk ini pernah dikirim sebelumnya.' : ''))) {
      ev.preventDefault();
      return;
    }
    const b = f.querySelector('button:not([data-bs-dismiss])');
    b.disabled = true;
    b.innerHTML = '<span class="spinner-border spinner-border-sm">\n</span> Mengirim...';
  }));
</script>
<?php include 'includes/footer.php';

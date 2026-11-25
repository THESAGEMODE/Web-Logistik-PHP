<?php
require 'config/app.php';
require_login();
require 'config/ai.php';
require 'includes/ai-complaint.php';
require 'includes/resi.php';
header('Cache-Control: no-store');
$id = parameter_id_resi($_GET['id'] ?? null);
if (!$id) {
    http_response_code(400);
    exit('ID transaksi tidak valid.');
}
$t = ambil_resi_cetak($db, $id, null);
if (!$t) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan.');
}
$s = $db->prepare('SELECT COALESCE(SUM(jumlah),0) n FROM pembayaran WHERE pengiriman_id=?');
$s->bind_param('i', $id);
$s->execute();
$t['terbayar'] = (float)$s->get_result()->fetch_assoc()['n'];
$question = '';
$error = '';
$tableReady = true;
try {
    $s = $db->prepare('SELECT * FROM ai_keluhan WHERE pengiriman_id=? ORDER BY id DESC LIMIT 20');
    $s->bind_param('i', $id);
    $s->execute();
    $logs = $s->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $ex) {
    $logs = [];
    $tableReady = false;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (!$tableReady) throw new InvalidArgumentException('Tabel keluhan belum tersedia. Hubungi admin.');
        $question = ai_question($_POST['question'] ?? null);
        if (!ai_siap()) throw new InvalidArgumentException('Sambungan AI belum diatur oleh admin.');
        if (($_POST['consent'] ?? '') !== '1') throw new InvalidArgumentException('Persetujuan pengiriman data ke AI diperlukan.');
        if (!is_string($_POST['request_id'] ?? null) || !hash_equals((string)($_SESSION['ai_form'][$id] ?? ''), $_POST['request_id'])) throw new InvalidArgumentException('Formulir telah dipakai atau kedaluwarsa. Muat ulang halaman.');
        $requestId = $_POST['request_id'];
        unset($_SESSION['ai_form'][$id]);
        $snapshot = ai_snapshot($t, riwayat_resi_publik($db, $id));
        $safeQuestion = ai_redact($question, $t);
        $snapshotJson = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $uid = (int)$_SESSION['user']['id'];
        $s = $db->prepare('SELECT COUNT(*) n FROM ai_keluhan WHERE user_id=? AND created_at>DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $s->bind_param('i', $uid);
        $s->execute();
        if ((int)$s->get_result()->fetch_assoc()['n'] >= 10) throw new InvalidArgumentException('Batas staf 10 permintaan per jam tercapai.');
        $s = $db->prepare("INSERT INTO ai_keluhan(request_id,pengiriman_id,user_id,question,snapshot,status) VALUES(?,?,?,?,?,'processing')");
        $s->bind_param('siiss', $requestId, $id, $uid, $safeQuestion, $snapshotJson);
        $s->execute();
        $logId = (int)$db->insert_id;
        $result = ai_send(['request_id' => $requestId, 'question' => $safeQuestion, 'transaction' => $snapshot]);
        $state = $result['state'];
        $output = isset($result['recommendation']) ? json_encode($result['recommendation'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null;
        $message = $result['error'] ?? '';
        $model = $result['model'] ?? '';
        $s = $db->prepare('UPDATE ai_keluhan SET status=?,recommendation=?,error_message=?,model=? WHERE id=?');
        $s->bind_param('ssssi', $state, $output, $message, $model, $logId);
        $s->execute();
        flash($state === 'completed' ? 'ok' : 'warn', $state === 'completed' ? 'Rekomendasi tersimpan. Tinjau sebelum digunakan.' : $message);
        redirect('keluhan.php?id=' . $id);
    } catch (InvalidArgumentException $ex) {
        $error = $ex->getMessage();
    } catch (Throwable $ex) {
        $error = 'Permintaan tidak dapat diselesaikan. Periksa riwayat sebelum mencoba lagi.';
    }
}
$_SESSION['ai_form'][$id] = ai_uuid();
$title = 'Keluhan Pelanggan';
include 'includes/header.php';
?>
<div class="mb-3">\n<a class="btn btn-outline-secondary" href="detail-transaksi.php?no=<?= e($t['no_transaksi']) ?>">\n<i class="bi bi-arrow-left">\n</i> Transaksi</a>\n</div>
<h1 class="h3">Keluhan pelanggan</h1>
<div class="mb-4">\n<b>\n<?= e($t['no_transaksi']) ?>\n</b> · <?= e($t['eksp'] . ' / ' . $t['nama_layanan']) ?> · <?= badge($t['status']) ?>\n<div class="small text-muted">Resi: <?= e($t['resi_ekspedisi'] ?: 'Belum tercatat') ?> · <?= e($t['asal'] . ' → ' . $t['tujuan']) ?>\n</div>\n</div>
<?php if (!$tableReady): ?>\n<div class="alert alert-warning">Riwayat keluhan belum tersedia. Admin perlu memasang pembaruan tahap 7.</div>\n<?php endif; ?>
<?php if (!ai_siap()): ?>\n<div class="alert alert-warning">Sambungan AI belum aktif. Hubungi admin.</div>\n<?php endif; ?>
<?php if ($error): ?>\n<div role="alert" class="alert alert-danger">\n<?= e($error) ?>\n</div>\n<?php endif; ?>
<form method="post" class="mb-4" id="form-keluhan" style="max-width:850px">
    <?= csrf_field() ?>\n<input type="hidden" name="request_id" value="<?= e($_SESSION['ai_form'][$id]) ?>">
    <label for="question" class="form-label">Pertanyaan atau keluhan pelanggan</label>
    <textarea id="question" name="question" class="form-control" rows="4" minlength="5" maxlength="2000" required>\n<?= e($question) ?>\n</textarea>
    <div class="form-check my-3">\n<input class="form-check-input" type="checkbox" name="consent" value="1" id="consent" required>\n<label class="form-check-label small" for="consent">Saya memastikan keluhan tidak berisi data pribadi atau rahasia; saya setuju keluhan dan ringkasan transaksi dikirim ke AI.</label>\n</div>
    <button class="btn btn-primary" <?= !$tableReady || !ai_siap() ? 'disabled' : '' ?>>\n<i class="bi bi-stars">\n</i> Susun rekomendasi</button>
</form>
<h2 class="h5">Riwayat rekomendasi</h2>
<?php if (!$logs): ?>\n<p class="text-muted">Belum ada keluhan.</p>\n<?php endif; ?>
<?php foreach ($logs as $log): $r = json_decode($log['recommendation'] ?? '', true);
    $snap = json_decode($log['snapshot'], true); ?>
    <article class="border-top py-4" style="max-width:850px">
        <div class="d-flex flex-wrap justify-content-between gap-2">\n<span>\n<?= tgl($log['created_at']) ?>\n</span>\n<span class="badge text-bg-<?= $log['status'] === 'completed' ? 'success' : 'secondary' ?>">\n<?php $labels = ['processing' => 'Diproses / hasil belum pasti', 'completed' => 'Saran tersedia', 'failed' => 'Gagal', 'unknown' => 'Hasil belum pasti'];
                                                                                                                                                                                                            echo e($labels[$log['status']] ?? 'Tidak tersedia'); ?>\n</span>\n</div>
        <p class="mt-3" style="white-space:pre-wrap">\n<?= e($log['question']) ?>\n</p>
        <p class="small text-muted">Dasar: status <?= e(STATUS_LIST[$snap['status'] ?? ''] ?? '-') ?>, resi <?= e(($snap['resi_ekspedisi'] ?? '') ?: 'belum tercatat') ?> · Data saat permintaan <?= tgl($log['created_at']) ?>. Status internal agen, bukan pelacakan langsung ekspedisi.</p>
        <?php if (ai_output($r)): ?>
            <h3 class="h6">Ringkasan</h3>\n<p>\n<?= e($r['ringkasan']) ?>\n</p>
            <h3 class="h6">Tindak lanjut staf</h3>\n<ol>\n<?php foreach ($r['tindak_lanjut'] as $step): ?>\n<li>\n<?= e($step) ?>\n</li>\n<?php endforeach; ?>\n</ol>
            <?php if ($r['perlu_dikonfirmasi']): ?>\n<h3 class="h6">Perlu dikonfirmasi</h3>\n<ul>\n<?php foreach ($r['perlu_dikonfirmasi'] as $item): ?>\n<li>\n<?= e($item) ?>\n</li>\n<?php endforeach; ?>\n</ul>\n<?php endif; ?>
            <label class="form-label fw-bold" for="draft-<?= $log['id'] ?>">Draf balasan · wajib ditinjau staf</label>\n<textarea readonly id="draft-<?= $log['id'] ?>" class="form-control" rows="4">\n<?= e($r['draf_balasan']) ?>\n</textarea>
            <?php else: ?>\n<p class="text-warning-emphasis">\n<?= e($log['error_message'] ?: 'Hasil belum pasti. Tidak ada pengiriman ulang otomatis; lakukan tindak lanjut manual.') ?>\n</p>\n<?php endif; ?>
    </article>\n<?php endforeach; ?>
    <script>
        document.getElementById('form-keluhan').addEventListener('submit', function() {
            const b = this.querySelector('button');
            b.disabled = true;
            b.textContent = 'Menyusun rekomendasi…';
        });
    </script>
    <?php include 'includes/footer.php'; ?>
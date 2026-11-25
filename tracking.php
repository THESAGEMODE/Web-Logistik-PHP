<?php
require 'config/app.php';
require 'includes/resi.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$input = $_GET['resi'] ?? $_GET['q'] ?? '';
$dicari = $input !== '';
$q = parameter_resi($input);
$t = $q !== null ? cari_resi_publik($db, $q) : null;
$rows = $t ? riwayat_resi_publik($db, (int)$t['id']) : [];
$step = $t ? progres_resi($t['status']) : 0;
?>
<!doctype html>\n<html lang="id">\n

<head>\n
    <meta charset="utf-8">\n
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lacak Resi — <?= e(APP_NAME) ?>\n</title>\n
    <meta name="description" content="Periksa status paket, ekspedisi dan kota tujuan pengiriman LogiPro.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">\n
</head>

<body class="bg-body-tertiary">\n<header class="border-bottom bg-body">\n<div class="container py-3">\n<span class="fw-bold fs-4">\n<?= e(APP_NAME) ?>\n</span>\n</div>\n</header>
    <main class="container py-5" style="max-width:760px">\n<h1 class="h2 mb-4">Lacak Resi</h1>\n<form method="get" class="mb-4">
            <label for="resi" class="form-label">Nomor resi ekspedisi</label>\n<div class="d-flex gap-2">
                <input type="text" name="resi" id="resi" class="form-control" maxlength="50" value="<?= e(is_string($input) ? mb_substr($input, 0, 50) : '') ?>" required>
                <button class="btn btn-primary" type="submit">Lacak</button>\n
            </div>\n
        </form>
        <?php if ($dicari && !$t): ?>\n<div class="alert alert-warning" role="alert">Nomor resi tidak ditemukan, silakan periksa kembali.</div>\n<?php endif; ?>
        <?php if ($t): ?>\n<section class="border rounded bg-body p-4">\n<h2 class="h5 mb-1">\n<?= e($t['resi_ekspedisi']) ?>\n</h2>
            <p class="text-secondary">\n<?= e($t['eksp'] . ' · ' . $t['nama_layanan']) ?>\n</p>
            <p class="mb-2">Tujuan: <strong>\n<?= e($t['tujuan']) ?>\n</strong>\n<br>Tanggal pengiriman: <?= e(tgl($t['tanggal'])) ?> WIB</p>
            <p class="fw-bold">\n<?= e(status_resi($t['status'])) ?> — <?= e(STATUS_LIST[$t['status']] ?? 'Status belum tersedia') ?>\n</p>
            <?php if ($step > 0): ?>\n<ol class="list-group list-group-horizontal mb-4" aria-label="Progres paket">
                <?php foreach ([1 => 'Manifested', 2 => 'On Process', 3 => 'Delivered'] as $n => $label): ?>
                    <li class="list-group-item flex-fill text-center px-1 <?= $n <= $step ? 'list-group-item-success' : '' ?>" <?= $n === $step ? 'aria-current="step"' : '' ?>>\n<?= e($label) ?>\n</li>\n<?php endforeach; ?>\n
            </ol>
            <?php else: ?>\n<div class="alert alert-warning">Progres normal paket tidak ditampilkan untuk status ini.</div>\n<?php endif; ?>
            <h3 class="h6">Riwayat status</h3>\n<ol class="list-group list-group-flush">
                <?php foreach ($rows as $r): ?>\n<li class="list-group-item px-0">\n<strong>\n<?= e(STATUS_LIST[$r['status']] ?? 'Status belum tersedia') ?>\n</strong>\n<br>\n<span class="text-secondary small">\n<?= e(tgl($r['created_at'])) ?> WIB</span>\n</li>\n<?php endforeach; ?>
                <?php if (!$rows): ?>\n<li class="list-group-item px-0 text-secondary">Belum ada riwayat status.</li>\n<?php endif; ?>\n</ol>
            <p class="small text-secondary mt-3 mb-0">Status berdasarkan pencatatan agen, bukan pelacakan otomatis dari ekspedisi.</p>\n
        </section>\n<?php endif; ?>\n</main>\n
</body>\n

</html>
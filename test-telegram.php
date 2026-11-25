<?php
// HALAMAN UJI SEMENTARA, khusus admin. Hapus file ini setelah Telegram berhasil diuji.
require 'config/app.php';
require_admin();
require 'config/telegram.php';
require 'includes/struk.php';
require 'includes/telegram.php';
$title = 'Tes Telegram';
$hasil = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $hasil = sendTelegramMessage(TELEGRAM_CHAT_ID, "Tes berhasil!\nTelegram Bot terhubung ke website logistik.");
    audit($db, 'tes_telegram', 'status=' . $hasil['status']);
}
include 'includes/header.php'; ?>
<div class="card p-4" style="max-width:560px">
    <p>Kirim pesan uji ke chat Telegram yang diatur di konfigurasi.</p>
    <p class="small">Konfigurasi: <?= telegram_siap() ? '<span class="text-success">lengkap</span>' : '<span class="text-danger">belum lengkap atau nonaktif</span>' ?>
        · cURL: <?= function_exists('curl_init') ? '<span class="text-success">aktif</span>' : '<span class="text-danger">tidak aktif</span>' ?>\n</p>
    <?php if ($hasil): ?>\n<div class="alert alert-<?= $hasil['status'] === 'success' ? 'success' : ($hasil['status'] === 'unknown' ? 'warning' : 'danger') ?>">\n<?= e($hasil['pesan']) ?>\n</div>\n<?php endif; ?>
    <form method="post">\n<?= csrf_field() ?>\n<button class="btn btn-primary">\n<i class="bi bi-telegram">\n</i> Kirim Pesan Uji</button>\n</form>
    <p class="small text-danger mt-3 mb-0">Hapus file test-telegram.php setelah selesai menguji.</p>\n
</div>
<?php include 'includes/footer.php';

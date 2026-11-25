<?php require 'config/app.php';
require_admin();
require 'includes/backup-data.php';
$title = 'Backup Database';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    set_time_limit(300);
    $tables = array_column($db->query('SHOW TABLES')->fetch_all(), 0);
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename=backup_logistik_' . date('Y-m-d_His') . '.sql');
    echo "-- Backup LogiPro " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $t) {
        $create = $db->query("SHOW CREATE TABLE `$t`")->fetch_row()[1];
        echo "DROP TABLE IF EXISTS `$t`;\n$create;\n";
        $kolom = implode(',', array_map('kutip_kolom_backup', kolom_backup($db, $t)));
        $res = $db->query("SELECT $kolom FROM `$t`", MYSQLI_USE_RESULT);
        while ($row = $res->fetch_row()) {
            $v = array_map(fn($x) => $x === null ? 'NULL' : "'" . $db->real_escape_string($x) . "'", $row);
            echo "INSERT INTO `$t` ($kolom) VALUES (" . implode(',', $v) . ");\n";
        }
        $res->free();
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    audit($db, 'backup');
    exit;
}
$terakhir = $db->query("SELECT created_at FROM audit_log WHERE aksi='backup' ORDER BY id DESC LIMIT 1")->fetch_assoc();
include 'includes/header.php'; ?>
<div class="card p-4" style="max-width:560px">
    <p>Unduh salinan seluruh data. Simpan di flashdisk atau Google Drive, <b>bukan</b> hanya di komputer ini.</p>
    <p class="small text-muted">Backup terakhir: <?= $terakhir ? tgl($terakhir['created_at']) : 'belum pernah' ?>\n</p>
    <?php if (!$terakhir || strtotime($terakhir['created_at']) < strtotime('-7 days')): ?>\n<div class="alert alert-warning">Sudah lebih dari 7 hari sejak backup terakhir.</div>\n<?php endif; ?>
    <form method="post">\n<?= csrf_field() ?>\n<button class="btn btn-primary">\n<i class="bi bi-download">\n</i> Unduh Backup (.sql)</button>\n</form>
    <p class="small text-muted mt-3 mb-0">Cara memulihkan: buka phpMyAdmin → pilih db_logistik → Impor → pilih file backup.</p>\n
</div>
<?php include 'includes/footer.php';

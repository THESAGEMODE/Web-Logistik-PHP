<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }
function kolom_backup($db, $tabel) {
    $kolom = $db->query('SHOW FULL COLUMNS FROM `' . str_replace('`', '``', $tabel) . '`')->fetch_all(MYSQLI_ASSOC);
    return array_column(array_filter($kolom, fn($c) => stripos($c['Extra'], 'GENERATED') === false), 'Field');
}
function kutip_kolom_backup($nama) { return '`' . str_replace('`', '``', $nama) . '`'; }

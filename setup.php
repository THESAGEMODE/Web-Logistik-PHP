<?php
// Jalankan SEKALI setelah impor database untuk membuat akun awal, lalu HAPUS file ini.
require 'config/app.php';
if ($db->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'] > 0) exit('Akun sudah ada. Hapus file setup.php.');
$st = $db->prepare('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)');
foreach ([['Administrator','admin','admin123','admin'],['Staf Gerai','staff','staff123','staff']] as [$n,$u,$p,$r]) {
    $h = password_hash($p, PASSWORD_DEFAULT); $st->bind_param('ssss', $n, $u, $h, $r); $st->execute();
}
echo 'Selesai. Login: admin / admin123 dan staff / staff123 (HANYA untuk lokal). Segera hapus setup.php.';

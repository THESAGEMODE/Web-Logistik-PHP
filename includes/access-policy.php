<?php
function role_can_access($role,$path) { return $role==='admin' || ($role==='staff' && in_array($path,['input-resi.php','transaksi.php','detail-transaksi.php','keluhan.php','cetak-resi.php','api/hitung-ongkir.php','actions/simpan-pengiriman.php','logout.php'],true)); }
function auth_endpoint() { $root=realpath(__DIR__.'/..');$file=realpath($_SERVER['SCRIPT_FILENAME']??'');return $root && $file && str_starts_with($file,$root.DIRECTORY_SEPARATOR)?str_replace(DIRECTORY_SEPARATOR,'/',substr($file,strlen($root)+1)):''; }
function auth_base_url() { $p=auth_endpoint();$s=$_SERVER['SCRIPT_NAME']??'/index.php';return $p && str_ends_with($s,$p)?substr($s,0,-strlen($p)):'/'; }
function home_url() { return auth_base_url().(is_admin()?'dashboard.php':'transaksi.php'); }

<?php
require '../config/app.php';require_admin();if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Gunakan POST.');}csrf_check();require '../includes/user-management.php';
try{$d=$_POST;if(isset($d['toggle'])){$d['id']=$d['toggle'];$d['action']='toggle';}flash('ok',save_managed_user($db,$d,(int)$_SESSION['user']['id']));}
catch(InvalidArgumentException $ex){flash('err',$ex->getMessage());}catch(Throwable $ex){error_log($ex->getMessage());flash('err','Akun gagal disimpan. Periksa data atau coba kembali.');}redirect('../users.php');

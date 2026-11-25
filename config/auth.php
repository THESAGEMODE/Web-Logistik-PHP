<?php
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(403);exit;}
require_once __DIR__.'/../includes/access-policy.php';
function login_url(){return auth_base_url().'index.php';}
function is_admin(){return ($_SESSION['user']['role']??'')==='admin';}
function require_login(){
 global $db;$id=filter_var($_SESSION['user']['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 if(!$id){$_SESSION=[];redirect(login_url());}
 $s=$db->prepare('SELECT id,nama,username,role,aktif FROM users WHERE id=?');$s->bind_param('i',$id);$s->execute();$u=$s->get_result()->fetch_assoc();
 if(!$u || !$u['aktif'] || !in_array($u['role'],['admin','staff'],true)){$_SESSION=[];session_regenerate_id(true);redirect(login_url());}
 $_SESSION['user']=$u;if(!role_can_access($u['role'],auth_endpoint())){http_response_code(403);exit('Akses ditolak: khusus admin.');}
}
function require_admin(){require_login();if(!is_admin()){http_response_code(403);exit('Akses ditolak: khusus admin.');}}
$p=auth_endpoint();if(!in_array($p,['index.php','tracking.php','setup.php'],true))require_login();
if($p==='index.php'&&!empty($_SESSION['user']))require_login();
if($p==='setup.php'&&(int)$db->query('SELECT COUNT(*) n FROM users')->fetch_assoc()['n']>0){http_response_code(403);exit('Setup ditutup. Hapus setup.php.');}

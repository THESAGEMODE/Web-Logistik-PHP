<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) { http_response_code(403); exit; }
date_default_timezone_set('Asia/Jakarta');
define('APP_NAME', 'LogiPro');
ini_set('display_errors', '0');
ini_set('session.use_strict_mode','1');
ini_set('session.use_only_cookies','1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/koneksi.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

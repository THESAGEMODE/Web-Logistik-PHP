<?php
function csrf_token() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    $a=$_SESSION['csrf']??null;$b=$_POST['csrf']??$_SERVER['HTTP_X_CSRF']??null;
    if (!is_string($a)||$a===''||!is_string($b)||!hash_equals($a,$b)) {
        http_response_code(419); exit('Sesi formulir kedaluwarsa. Muat ulang halaman.');
    }
}

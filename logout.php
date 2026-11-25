<?php require 'config/app.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); audit($db, 'logout'); }
$_SESSION = []; session_destroy(); redirect('index.php');

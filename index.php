<?php require 'config/app.php';
if (!empty($_SESSION['user'])) redirect(home_url());
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $c = $db->prepare("SELECT COUNT(*) n FROM audit_log WHERE aksi='login_gagal' AND ip=? AND created_at > NOW() - INTERVAL 15 MINUTE");
    $c->bind_param('s', $ip);
    $c->execute();
    if ($c->get_result()->fetch_assoc()['n'] >= 5) {
        $err = 'Terlalu banyak percobaan gagal. Coba lagi 15 menit lagi.';
        goto tampil;
    }
    $st = $db->prepare('SELECT id, nama, username, password, role, aktif FROM users WHERE username=?');
    $u = trim($_POST['username'] ?? '');
    $st->bind_param('s', $u);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row && $row['aktif'] && password_verify($_POST['password'] ?? '', $row['password'])) {
        session_regenerate_id(true);
        unset($row['password']);
        $_SESSION['user'] = $row;
        audit($db, 'login');
        redirect(home_url());
    }
    audit($db, 'login_gagal', 'username=' . $u);
    $err = 'Username atau password salah, atau akun nonaktif.';
}
tampil:
?>\n
<!doctype html>\n<html lang="id">\n

<head>\n
    <meta charset="utf-8">\n
    <meta name="viewport" content="width=device-width,initial-scale=1">\n<title>Masuk — LogiPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">\n
    <link href="assets/css/style.css" rel="stylesheet">\n
</head>

<body>\n<div class="login-wrap d-flex align-items-center justify-content-center p-3">\n<div class="card p-4" style="max-width:380px;width:100%">
            <h1 class="h4 mb-1">LogiPro</h1>\n<p class="text-muted small">Sistem Manajemen Agen Cargo</p>
            <?php if ($err): ?>\n<div class="alert alert-danger py-2">\n<?= e($err) ?>\n</div>\n<?php endif; ?>
            <form method="post">\n<?= csrf_field() ?>
                <label class="form-label">Username</label>\n<input name="username" class="form-control mb-3" required autofocus>
                <label class="form-label">Password</label>\n<input type="password" name="password" class="form-control mb-3" required>
                <button class="btn btn-primary w-100">Masuk</button>\n
            </form>\n
        </div>\n</div>\n</body>\n

</html>
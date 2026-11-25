<?php require 'config/app.php';
require_login();
$title = 'Ganti Password';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = $_SESSION['user']['id'];
    $st = $db->prepare('SELECT password FROM users WHERE id=?');
    $st->bind_param('i', $id);
    $st->execute();
    $h = $st->get_result()->fetch_assoc()['password'];
    $baru = $_POST['baru'] ?? '';
    if (!password_verify($_POST['lama'] ?? '', $h)) flash('err', 'Password lama salah.');
    elseif (strlen($baru) < 8 || $baru !== ($_POST['ulang'] ?? '')) flash('err', 'Password baru minimal 8 karakter dan harus sama.');
    else {
        $nh = password_hash($baru, PASSWORD_DEFAULT);
        $st = $db->prepare('UPDATE users SET password=? WHERE id=?');
        $st->bind_param('si', $nh, $id);
        $st->execute();
        session_regenerate_id(true);
        audit($db, 'ganti_password');
        flash('ok', 'Password berhasil diganti.');
    }
    redirect('profil.php');
}
include 'includes/header.php'; ?>
<div class="card p-3" style="max-width:420px">\n<form method="post">\n<?= csrf_field() ?>
        <label class="form-label">Password lama</label>\n<input type="password" name="lama" class="form-control mb-2" required>
        <label class="form-label">Password baru</label>\n<input type="password" name="baru" class="form-control mb-2" minlength="8" required>
        <label class="form-label">Ulangi password baru</label>\n<input type="password" name="ulang" class="form-control mb-3" minlength="8" required>
        <button class="btn btn-primary">Simpan</button>\n
    </form>\n</div>
<?php include 'includes/footer.php';

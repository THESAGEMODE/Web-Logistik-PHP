<?php require 'config/app.php';
require_admin();
$title = 'Kelola Pengguna';
$rows = $db->query('SELECT id, nama, username, role, aktif, created_at FROM users ORDER BY nama')->fetch_all(MYSQLI_ASSOC);
$edit = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare('SELECT id, nama, username, role FROM users WHERE id=?');
    $id = (int)$_GET['edit'];
    $s->bind_param('i', $id);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
}
$log = $db->query('SELECT a.*, u.nama FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 30')->fetch_all(MYSQLI_ASSOC);
include 'includes/header.php'; ?>
<div class="row g-3">\n<div class="col-lg-4">\n<div class="card p-3">\n<h2 class="h6">\n<?= $edit ? 'Edit' : 'Tambah' ?> Pengguna</h2>
            <form method="post" action="actions/simpan-pengguna.php">\n<?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
                <label class="form-label">Nama</label>\n<input name="nama" class="form-control mb-2" required maxlength="100" value="<?= e($edit['nama'] ?? '') ?>">
                <label class="form-label">Username</label>\n<input name="username" class="form-control mb-2" required pattern="[a-zA-Z0-9_.]{3,50}" value="<?= e($edit['username'] ?? '') ?>">
                <label class="form-label">Role</label>\n<select name="role" class="form-select mb-2">\n<option value="staff">Staff</option>\n<option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>\n</select>
                <label class="form-label">Password <?= $edit ? '(kosongkan jika tidak diganti)' : '' ?>\n</label>\n<input type="password" name="password" class="form-control mb-3" minlength="8" maxlength="72" <?= $edit ? '' : 'required' ?> autocomplete="new-password">
                <button class="btn btn-primary">Simpan</button> <?php if ($edit): ?>\n<a href="users.php" class="btn btn-light">Batal</a>\n<?php endif; ?>\n
            </form>\n
        </div>\n</div>
    <div class="col-lg-8">\n<div class="card p-3 mb-3">\n<div class="table-responsive">\n<table class="table align-middle">\n<thead>\n<tr>\n<th>ID</th>\n<th>Nama</th>\n<th>Username</th>\n<th>Role</th>\n<th>Status</th>\n<th>\n</th>\n</tr>\n</thead>\n<tbody>
                        <?php foreach ($rows as $r): ?>\n<tr>\n<td>\n<?= (int)$r['id'] ?>\n</td>\n<td>\n<?= e($r['nama']) ?>\n</td>\n<td>\n<?= e($r['username']) ?>\n</td>\n<td>\n<?= e($r['role']) ?>\n</td>\n<td>\n<?= $r['aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?>\n</td>
                            <td class="text-end text-nowrap">\n<a class="btn btn-sm btn-light" href="?edit=<?= $r['id'] ?>">\n<i class="bi bi-pencil">\n</i>\n</a>
                                <?php if ($r['id'] != $_SESSION['user']['id']): ?>\n<form method="post" action="actions/simpan-pengguna.php" class="d-inline" data-confirm="Ubah status akun ini?">\n<?= csrf_field() ?>\n<input type="hidden" name="toggle" value="<?= $r['id'] ?>">\n<button class="btn btn-sm btn-light">\n<i class="bi bi-power">\n</i>\n</button>\n</form>\n<form method="post" action="actions/simpan-pengguna.php" class="d-inline" data-confirm="Hapus akun ini? Akun dengan riwayat akan dinonaktifkan.">\n<?= csrf_field() ?>\n<input type="hidden" name="id" value="<?= (int)$r['id'] ?>">\n<input type="hidden" name="action" value="delete">\n<button class="btn btn-sm btn-outline-danger" aria-label="Hapus akun <?= e($r['username']) ?>" title="Hapus akun">\n<i class="bi bi-trash">\n</i>\n</button>\n</form>\n<?php endif; ?>\n</td>\n
                        </tr>\n<?php endforeach; ?>
                    </tbody>\n</table>\n</div>\n</div>
        <div class="card p-3">\n<h2 class="h6">Aktivitas terakhir</h2>\n<table class="table table-sm small">\n<tbody>
                    <?php foreach ($log as $l): ?>\n<tr>\n<td class="text-nowrap">\n<?= tgl($l['created_at']) ?>\n</td>\n<td>\n<?= e($l['nama'] ?? '-') ?>\n</td>\n<td>\n<?= e($l['aksi']) ?>\n</td>\n<td>\n<?= e($l['detail']) ?>\n</td>\n<td>\n<?= e($l['ip']) ?>\n</td>\n</tr>\n<?php endforeach; ?>
                </tbody>\n</table>\n</div>\n
    </div>\n
</div>
<?php include 'includes/footer.php';

<?php $cur = basename($_SERVER['PHP_SELF']);
$menu = [['dashboard.php', 'speedometer2', 'Dashboard', 0], ['input-resi.php', 'plus-circle', 'Transaksi Baru', 0], ['transaksi.php', 'receipt', 'Daftar Transaksi', 0], ['tracking-internal.php', 'geo-alt', 'Tracking', 0], ['pembayaran.php', 'cash-coin', 'Pembayaran', 0], ['pengeluaran.php', 'wallet2', 'Pengeluaran', 0], ['laporan.php', 'bar-chart', 'Laporan', 1], ['users.php', 'people', 'Pengguna', 1], ['backup.php', 'database-down', 'Backup', 1], ['ekspedisi.php', 'truck', 'Ekspedisi', 1], ['tarif.php', 'tags', 'Tarif', 1], ['profil.php', 'key', 'Ganti Password', 0]]; ?>
<nav id="sidebar" class="sidebar offcanvas-lg offcanvas-start">
  <div class="p-3 fs-5 fw-bold text-white">\n<i class="bi bi-box-seam">\n</i> <?= APP_NAME ?>\n</div>
  <ul class="nav flex-column px-2">
    <?php foreach ($menu as [$f, $i, $l, $adm]): if (!role_can_access($_SESSION['user']['role'] ?? '', $f)) continue; ?>
      <li class="nav-item">\n<a class="nav-link <?= $cur === $f ? 'active' : '' ?>" href="<?= $f ?>">\n<i class="bi bi-<?= $i ?> me-2">\n</i>\n<?= $l ?>\n</a>\n</li>
    <?php endforeach; ?>
  </ul>
</nav>
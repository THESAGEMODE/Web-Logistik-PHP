<header class="topbar d-flex align-items-center px-4 py-2">
  <button class="btn btn-sm btn-outline-secondary d-lg-none me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">\n<i class="bi bi-list">\n</i>\n</button>
  <h1 class="h5 m-0">\n<?= e($title ?? '') ?>\n</h1>
  <div class="ms-auto d-flex align-items-center gap-3">
    <span class="small">\n<i class="bi bi-person-circle">\n</i> <?= e($_SESSION['user']['nama']) ?> <span class="badge text-bg-light">\n<?= e($_SESSION['user']['role']) ?>\n</span>\n</span>
    <form method="post" action="logout.php" class="m-0">\n<?= csrf_field() ?>\n<button class="btn btn-sm btn-outline-danger">\n<i class="bi bi-box-arrow-right">\n</i> Keluar</button>\n</form>
  </div>
</header>
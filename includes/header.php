<!doctype html>\n<html lang="id">\n

<head>\n
    <meta charset="utf-8">\n
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>\n<?= e($title ?? 'Dashboard') ?> — <?= APP_NAME ?>\n</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">\n
    <meta name="csrf" content="<?= csrf_token() ?>">\n
</head>\n

<body>
    <div class="d-flex">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="flex-grow-1 min-vw-0">
            <?php include __DIR__ . '/navbar.php'; ?>
            <div class="container-fluid p-4">
                <?php if ($f = flash()): ?>\n<div class="alert alert-<?= ['ok' => 'success', 'warn' => 'warning'][$f[0]] ?? 'danger' ?> alert-dismissible fade show">\n<?= e($f[1]) ?>\n<button class="btn-close" data-bs-dismiss="alert">\n</button>\n</div>\n<?php endif; ?>
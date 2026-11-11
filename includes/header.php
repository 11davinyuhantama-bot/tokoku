<?php

require_once __DIR__ . '/../config/helpers.php';

$pageTitle = $pageTitle ?? 'Dashboard';

$currentPath = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$dashboardPath = url('index.php');

$isDashboard = ($currentPath === $dashboardPath);

?>

<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title><?= e($pageTitle) ?> | Tokoku</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        href="<?= url('assets/css/style.css') ?>"
        rel="stylesheet"
    >

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark topbar sticky-top">

    <div class="container-fluid">

        <button
            class="btn btn-outline-light d-lg-none me-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileMenu"
        >
            <i class="bi bi-list"></i>
        </button>

        <a
            class="navbar-brand fw-bold"
            href="<?= url('index.php') ?>"
        >
            <i class="bi bi-shop-window me-2"></i>
            TOKOKU
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="navbar-text text-white-50 small d-none d-sm-inline">
                <?= e($_SESSION['user']['nama'] ?? 'Pengguna') ?> · <?= e(ucfirst($_SESSION['user']['role'] ?? '')) ?>
            </span>
            <a class="btn btn-sm btn-outline-light" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
        </div>

    </div>

</nav>

<div class="d-flex">

    <aside class="sidebar d-none d-lg-block">

        <?php include __DIR__ . '/sidebar.php'; ?>

    </aside>

    <div
        class="offcanvas offcanvas-start"
        tabindex="-1"
        id="mobileMenu"
    >

        <div class="offcanvas-header">

            <h5 class="offcanvas-title">
                Menu Tokoku
            </h5>

            <button
                class="btn-close"
                data-bs-dismiss="offcanvas"
            ></button>

        </div>

        <div class="offcanvas-body p-0">

            <?php include __DIR__ . '/sidebar.php'; ?>

        </div>

    </div>

    <main class="main-content flex-grow-1">

        <div class="container-fluid py-4">

            <div class="mb-4">

                <h3 class="fw-bold mb-1">
                    <?= e($pageTitle) ?>
                </h3>

                <div class="text-secondary small">
                    Beranda / <?= e($pageTitle) ?>
                </div>

            </div>

            <?php show_flash(); ?>

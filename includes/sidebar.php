<?php

$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$isDashboard = ($path === url('index.php'));

$isPelanggan = str_contains($path, '/pelanggan/');

$isTransaksi = str_contains($path, '/transaksi/');

$isCabang = str_contains($path, '/cabang/');

$isLaporan = str_contains($path, '/laporan/');

$isAktivitas = str_contains($path, '/aktivitas/');

$isProduk = str_contains($path, '/produk/');

?>

<div class="p-3">

    <div class="text-uppercase text-white-50 small fw-bold mb-2">
        Menu Utama
    </div>

    <a
        class="side-link <?= $isDashboard ? 'active' : '' ?>"
        href="<?= url('index.php') ?>"
    >
        <i class="bi bi-grid-1x2"></i>
        Dashboard
    </a>

    <a
        class="side-link <?= $isPelanggan ? 'active' : '' ?>"
        href="<?= url('pelanggan/index.php') ?>"
    >
        <i class="bi bi-people"></i>
        Data Pelanggan
    </a>

    <a
        class="side-link <?= $isTransaksi ? 'active' : '' ?>"
        href="<?= url('transaksi/index.php') ?>"
    >
        <i class="bi bi-receipt"></i>
        Transaksi
    </a>

    <a
    class="side-link <?= $isProduk ? 'active' : '' ?>"
    href="<?= url('produk/index.php') ?>"
>
    <i class="bi bi-box-seam"></i>
    Data Produk
    </a>
    
    <a
        class="side-link <?= $isCabang ? 'active' : '' ?>"
        href="<?= url('cabang/index.php') ?>"
    >
        <i class="bi bi-geo-alt"></i>
        Data Cabang
    </a>

    <div class="text-uppercase text-white-50 small fw-bold mt-4 mb-2">
        Laporan & Aktivitas
    </div>

    <a
        class="side-link <?= $isLaporan ? 'active' : '' ?>"
        href="<?= url('laporan/index.php') ?>"
    >
        <i class="bi bi-bar-chart"></i>
        Laporan Penjualan
    </a>

    <a
        class="side-link <?= $isAktivitas ? 'active' : '' ?>"
        href="<?= url('aktivitas/index.php') ?>"
    >
        <i class="bi bi-clock-history"></i>
        Log Aktivitas
    </a>

    <div class="sidebar-note mt-4">

        <i class="bi bi-database-check me-1"></i>
        Database: db_tokoku

    </div>

</div>
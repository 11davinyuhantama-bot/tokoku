
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$halamanAktif = basename($_SERVER['PHP_SELF']);
$scriptPathKasir = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$diFolderTransaksiKasir = str_contains($scriptPathKasir, '/kasir/transaksi/');
$diHalamanProduk = str_contains($scriptPathKasir, '/produk/');
$urlDashboardKasir = $diFolderTransaksiKasir ? '../index.php' : ($diHalamanProduk ? '../kasir/index.php' : 'index.php');
$urlTransaksiKasir = $diFolderTransaksiKasir ? 'index.php' : ($diHalamanProduk ? '../kasir/transaksi/index.php' : 'transaksi/index.php');
$urlRiwayatKasir = $diFolderTransaksiKasir ? 'riwayat.php' : ($diHalamanProduk ? '../kasir/transaksi/riwayat.php' : 'transaksi/riwayat.php');
$urlProdukKasir = $diFolderTransaksiKasir ? '../../produk/index.php' : ($diHalamanProduk ? 'index.php' : '../produk/index.php');
$urlLogoutKasir = $diFolderTransaksiKasir ? '../../logout.php' : '../logout.php';
$transaksiAktifKasir = $diFolderTransaksiKasir && in_array($halamanAktif, ['index.php', 'detail.php'], true);
$namaKasir = $_SESSION['user']['nama'] ?? 'Kasir';
?>

<style>
    .sidebar-kasir {
        width: 250px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: #172554;
        color: white;
        padding: 20px 15px;
        display: flex;
        flex-direction: column;
        z-index: 1000;
    }

    .sidebar-kasir .brand {
        font-size: 23px;
        font-weight: bold;
        padding: 10px;
        border-bottom: 1px solid #334155;
        margin-bottom: 25px;
    }

    .sidebar-kasir .brand small {
        display: block;
        font-size: 12px;
        font-weight: normal;
        color: #94a3b8;
        margin-top: 5px;
    }

    .sidebar-kasir .judul-menu {
        font-size: 11px;
        color: #94a3b8;
        margin: 15px 10px 10px;
        text-transform: uppercase;
    }

    .sidebar-kasir .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        color: #cbd5e1;
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 5px;
        transition: .2s;
    }

    .sidebar-kasir .menu-item:hover,
    .sidebar-kasir .menu-item.active {
        background: #2563eb;
        color: white;
    }

    .sidebar-kasir .menu-item i {
        font-size: 19px;
        width: 22px;
    }

    .sidebar-kasir .profil-kasir {
        margin-top: auto;
        padding: 15px 10px;
        border-top: 1px solid #334155;
        color: #e2e8f0;
    }

    .sidebar-kasir .profil-kasir small {
        color: #94a3b8;
    }

    .konten-kasir {
        margin-left: 250px;
        padding: 25px;
    }

    @media (max-width: 768px) {
        .sidebar-kasir {
            width: 210px;
        }

        .konten-kasir {
            margin-left: 210px;
        }
    }
</style>

<aside class="sidebar-kasir">

    <div class="brand">
        <i class="bi bi-shop"></i> TOKOKU
        <small>Sistem Kasir</small>
    </div>

    <div class="judul-menu">Menu Kasir</div>

    <a href="<?= htmlspecialchars($urlDashboardKasir); ?>"
       class="menu-item <?= $halamanAktif === 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i>
        Dashboard
    </a>

    <a href="<?= htmlspecialchars($urlTransaksiKasir); ?>"
       class="menu-item <?= $transaksiAktifKasir ? 'active' : '' ?>">
        <i class="bi bi-cart-plus"></i>
        Transaksi Penjualan
    </a>

    <a href="<?= htmlspecialchars($urlRiwayatKasir); ?>"
       class="menu-item <?= $halamanAktif === 'riwayat.php' ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i>
        Riwayat Transaksi
    </a>

    <a href="<?= htmlspecialchars($urlProdukKasir); ?>"
       class="menu-item <?= strpos($_SERVER['PHP_SELF'], '/produk/') !== false ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i>
        Data Produk
    </a>

    <div class="judul-menu">Akun</div>

    <a href="<?= htmlspecialchars($urlLogoutKasir); ?>"
       class="menu-item"
       onclick="return confirm('Apakah Anda yakin ingin logout?')">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

    <div class="profil-kasir">
        <i class="bi bi-person-circle"></i>
        <?= htmlspecialchars($namaKasir); ?>
        <br>
        <small>Login sebagai Kasir</small>
    </div>

</aside>

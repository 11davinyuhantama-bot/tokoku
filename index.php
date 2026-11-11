
<?php
require_once "config/database.php";

$pageTitle = "Dashboard";

// =============================
// STATISTIK UTAMA
// =============================

$totalProduk = (int) $conn->query("
    SELECT COUNT(*) AS total FROM tbl_produk
")->fetch_assoc()['total'];

$totalPelanggan = (int) $conn->query("
    SELECT COUNT(*) AS total FROM tbl_pelanggan
")->fetch_assoc()['total'];

$totalTransaksi = (int) $conn->query("
    SELECT COUNT(*) AS total FROM tbl_transaksi
")->fetch_assoc()['total'];

$totalOmzet = (float) $conn->query("
    SELECT COALESCE(SUM(total_belanja), 0) AS total
    FROM tbl_transaksi
")->fetch_assoc()['total'];

// =============================
// GRAFIK PENJUALAN 6 BULAN
// =============================

$penjualanBulanan = [];

$result = $conn->query("
    SELECT
        DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
        SUM(total_belanja) AS omzet,
        COUNT(*) AS jumlah_transaksi
    FROM tbl_transaksi
    WHERE tanggal >= DATE_FORMAT(
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
        '%Y-%m-01'
    )
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan ASC
");

while ($row = $result->fetch_assoc()) {
    $penjualanBulanan[$row['bulan']] = [
        'omzet' => (float) $row['omzet'],
        'transaksi' => (int) $row['jumlah_transaksi']
    ];
}

$labelBulan = [];
$dataOmzet = [];
$dataJumlahTransaksi = [];

for ($i = 5; $i >= 0; $i--) {
    $tanggal = new DateTime("first day of -$i month");
    $key = $tanggal->format('Y-m');

    $labelBulan[] = $tanggal->format('M Y');

    $dataOmzet[] = $penjualanBulanan[$key]['omzet'] ?? 0;

    $dataJumlahTransaksi[] =
        $penjualanBulanan[$key]['transaksi'] ?? 0;
}

// =============================
// GRAFIK KATEGORI PRODUK
// =============================

$kategoriLabel = [];
$kategoriJumlah = [];

$resultKategori = $conn->query("
    SELECT
        COALESCE(NULLIF(kategori, ''), 'Tanpa Kategori') AS kategori,
        COUNT(*) AS jumlah
    FROM tbl_produk
    GROUP BY kategori
    ORDER BY jumlah DESC
");

while ($row = $resultKategori->fetch_assoc()) {
    $kategoriLabel[] = $row['kategori'];
    $kategoriJumlah[] = (int) $row['jumlah'];
}

// =============================
// TRANSAKSI TERBARU
// =============================

$transaksiTerbaru = $conn->query("
    SELECT
        t.id_transaksi,
        t.tanggal,
        t.total_belanja,
        p.nama_pelanggan
    FROM tbl_transaksi t
    LEFT JOIN tbl_pelanggan p
        ON t.id_pelanggan = p.id_pelanggan
    ORDER BY t.tanggal DESC, t.id_transaksi DESC
    LIMIT 5
");

// =============================
// PRODUK STOK MENIPIS
// =============================

$stokMenipis = $conn->query("
    SELECT
        id_produk,
        kode_produk,
        nama_produk,
        stok
    FROM tbl_produk
    WHERE stok <= 5
    ORDER BY stok ASC
    LIMIT 5
");

include "includes/header.php";
?>

<style>
.dashboard-welcome {
    background: linear-gradient(120deg, #1d4ed8, #4338ca);
    color: white;
    border-radius: 18px;
    padding: 26px;
    margin-bottom: 25px;
}

.dashboard-welcome h3 {
    font-weight: 750;
}

.stat-card {
    background: white;
    border: 1px solid #edf0f4;
    border-radius: 15px;
    padding: 20px;
    height: 100%;
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
}

.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    font-size: 23px;
}

.stat-label {
    color: #84909e;
    font-size: 13px;
    margin-bottom: 8px;
}

.stat-value {
    color: #202938;
    font-size: 24px;
    font-weight: 750;
}

.dashboard-card {
    background: white;
    border: 1px solid #edf0f4;
    border-radius: 15px;
    padding: 20px;
    height: 100%;
}

.dashboard-card-title {
    font-weight: 700;
    color: #202938;
}

.dashboard-card-subtitle {
    font-size: 12px;
    color: #84909e;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px;
    border: 1px solid #edf0f4;
    border-radius: 10px;
    color: #202938;
    text-decoration: none;
    transition: .2s;
}

.quick-action:hover {
    background: #f8faff;
    border-color: #2563eb;
    color: #2563eb;
}

.quick-action i {
    font-size: 20px;
}

@media(max-width:576px) {
    .stat-value {
        font-size: 20px;
    }

    .dashboard-welcome {
        padding: 20px;
    }

    .chart-container {
        height: 250px;
    }
}
</style>

<!-- WELCOME -->
<div class="dashboard-welcome">

    <div class="row align-items-center">

        <div class="col-md-8">

            <div class="small opacity-75 mb-2">
                SISTEM INFORMASI PENJUALAN TOKO
            </div>

            <h3>Selamat Datang di Tokoku!</h3>

            <p class="mb-0 opacity-75">
                Pantau penjualan, produk, pelanggan, dan aktivitas
                toko melalui dashboard ini.
            </p>

        </div>

        <div class="col-md-4 text-md-end mt-3 mt-md-0">

            <div class="small opacity-75">
                Tanggal Hari Ini
            </div>

            <div class="fw-bold fs-5">
                <?= date('d F Y') ?>
            </div>

        </div>

    </div>

</div>

<!-- STATISTIK -->
<div class="row g-3 mb-4">

    <div class="col-6 col-xl-3">
        <div class="stat-card">

            <div class="d-flex justify-content-between mb-3">

                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-cash-stack"></i>
                </div>

                <span class="badge bg-primary-subtle text-primary">
                    Omzet
                </span>

            </div>

            <div class="stat-label">Total Penjualan</div>

            <div class="stat-value">
                <?= rupiah($totalOmzet) ?>
            </div>

        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">

            <div class="d-flex justify-content-between mb-3">

                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-receipt"></i>
                </div>

                <span class="badge bg-success-subtle text-success">
                    Transaksi
                </span>

            </div>

            <div class="stat-label">Total Transaksi</div>

            <div class="stat-value">
                <?= number_format($totalTransaksi) ?>
            </div>

        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">

            <div class="d-flex justify-content-between mb-3">

                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-people"></i>
                </div>

                <span class="badge bg-warning-subtle text-warning">
                    Pelanggan
                </span>

            </div>

            <div class="stat-label">Total Pelanggan</div>

            <div class="stat-value">
                <?= number_format($totalPelanggan) ?>
            </div>

        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">

            <div class="d-flex justify-content-between mb-3">

                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-box-seam"></i>
                </div>

                <span class="badge bg-info-subtle text-info">
                    Produk
                </span>

            </div>

            <div class="stat-label">Total Produk</div>

            <div class="stat-value">
                <?= number_format($totalProduk) ?>
            </div>

        </div>
    </div>

</div>

<!-- GRAFIK -->
<div class="row g-3 mb-4">

    <div class="col-lg-8">

        <div class="dashboard-card">

            <div class="d-flex justify-content-between mb-4">

                <div>
                    <div class="dashboard-card-title">
                        Grafik Penjualan
                    </div>

                    <div class="dashboard-card-subtitle">
                        Ringkasan omzet enam bulan terakhir
                    </div>
                </div>

                <span class="badge bg-primary-subtle text-primary">
                    6 Bulan
                </span>

            </div>

            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>

        </div>

    </div>

    <div class="col-lg-4">

        <div class="dashboard-card">

            <div class="mb-4">

                <div class="dashboard-card-title">
                    Kategori Produk
                </div>

                <div class="dashboard-card-subtitle">
                    Jumlah barang berdasarkan kategori
                </div>

            </div>

            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>

        </div>

    </div>

</div>

<!-- TABEL DAN QUICK ACTION -->
<div class="row g-3">

    <div class="col-lg-8">

        <div class="dashboard-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>
                    <div class="dashboard-card-title">
                        Transaksi Terbaru
                    </div>

                    <div class="dashboard-card-subtitle">
                        Lima transaksi terakhir
                    </div>
                </div>

                <a href="transaksi/index.php"
                   class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>

            </div>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($transaksiTerbaru->num_rows > 0): ?>

                        <?php while ($row = $transaksiTerbaru->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    #<?= (int)$row['id_transaksi'] ?>
                                </td>

                                <td>
                                    <?= e($row['nama_pelanggan'] ?? 'Pelanggan umum') ?>
                                </td>

                                <td>
                                    <?= e(date('d-m-Y', strtotime($row['tanggal']))) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= rupiah($row['total_belanja']) ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">
                                Belum ada transaksi.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div class="col-lg-4">

        <div class="dashboard-card mb-3">

            <div class="dashboard-card-title mb-3">
                Akses Cepat
            </div>

            <div class="d-grid gap-2">

                <a href="produk/tambah.php" class="quick-action">
                    <i class="bi bi-plus-circle text-primary"></i>
                    <span>Tambah Barang</span>
                </a>

                <a href="produk/index.php" class="quick-action">
                    <i class="bi bi-box-seam text-success"></i>
                    <span>Kelola Produk</span>
                </a>

                <a href="pelanggan/index.php" class="quick-action">
                    <i class="bi bi-person-plus text-warning"></i>
                    <span>Data Pelanggan</span>
                </a>

                <a href="transaksi/index.php" class="quick-action">
                    <i class="bi bi-receipt text-info"></i>
                    <span>Data Transaksi</span>
                </a>

            </div>

        </div>

        <div class="dashboard-card">

            <div class="dashboard-card-title mb-3">
                <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                Stok Menipis
            </div>

            <?php if ($stokMenipis->num_rows > 0): ?>

                <?php while ($barang = $stokMenipis->fetch_assoc()): ?>

                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">

                        <div>
                            <div class="fw-semibold small">
                                <?= e($barang['nama_produk']) ?>
                            </div>

                            <div class="text-secondary small">
                                <?= e($barang['kode_produk']) ?>
                            </div>
                        </div>

                        <span class="badge bg-danger">
                            <?= (int)$barang['stok'] ?> tersisa
                        </span>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="text-secondary small">
                    Tidak ada produk dengan stok 5 atau kurang.
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const salesLabels = <?= json_encode($labelBulan) ?>;
const salesData = <?= json_encode($dataOmzet) ?>;

const categoryLabels = <?= json_encode($kategoriLabel) ?>;
const categoryData = <?= json_encode($kategoriJumlah) ?>;

// Grafik penjualan
new Chart(document.getElementById('salesChart'), {

    type: 'line',

    data: {
        labels: salesLabels,

        datasets: [{
            label: 'Omzet Penjualan',
            data: salesData,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 4,
            pointBackgroundColor: '#2563eb'
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                display: false
            },

            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(
                            context.raw
                        );
                    }
                }
            }
        },

        scales: {
            y: {
                beginAtZero: true,

                ticks: {
                    callback: function(value) {
                        return 'Rp ' +
                            new Intl.NumberFormat('id-ID', {
                                notation: 'compact'
                            }).format(value);
                    }
                },

                grid: {
                    color: '#f0f2f5'
                }
            },

            x: {
                grid: {
                    display: false
                }
            }
        }
    }

});

// Grafik kategori produk
new Chart(document.getElementById('categoryChart'), {

    type: 'doughnut',

    data: {
        labels: categoryLabels,

        datasets: [{
            data: categoryData,
            backgroundColor: [
                '#2563eb',
                '#10b981',
                '#f59e0b',
                '#8b5cf6',
                '#ec4899',
                '#06b6d4',
                '#64748b'
            ],
            borderWidth: 3,
            borderColor: '#ffffff'
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                position: 'bottom'
            }
        },

        cutout: '65%'
    }

});
</script>

<?php include "includes/footer.php"; ?>

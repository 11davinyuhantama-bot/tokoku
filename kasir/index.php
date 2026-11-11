
<?php
require_once '../config/database.php';
require_once '../config/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'kasir') {
    header('Location: ../login.php');
    exit;
}

$namaKasir = $_SESSION['user']['nama'] ?? 'Kasir';

// Fungsi aman untuk mengambil angka statistik
function ambilAngka($conn, $sql, $kolom = 'total') {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $data = mysqli_fetch_assoc($result);
    return $data[$kolom] ?? 0;
}

// Statistik
$totalTransaksi = ambilAngka(
    $conn,
    "SELECT COUNT(*) AS total FROM tbl_transaksi"
);

$totalPendapatan = ambilAngka(
    $conn,
    "SELECT COALESCE(SUM(total_belanja),0) AS total FROM tbl_transaksi"
);

$transaksiHariIni = ambilAngka(
    $conn,
    "SELECT COUNT(*) AS total FROM tbl_transaksi WHERE DATE(tanggal) = CURDATE()"
);

$rataTransaksi = $totalTransaksi > 0
    ? $totalPendapatan / $totalTransaksi
    : 0;

// Grafik penjualan 7 hari terakhir
$labelGrafik = [];
$dataGrafik = [];

for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));

    $labelGrafik[] = date('d M', strtotime($tanggal));

    $stmtGrafik = mysqli_prepare(
        $conn,
        "SELECT COALESCE(SUM(total_belanja),0) AS total
         FROM tbl_transaksi
         WHERE DATE(tanggal) = ?"
    );

    mysqli_stmt_bind_param($stmtGrafik, "s", $tanggal);
    mysqli_stmt_execute($stmtGrafik);

    $hasilGrafik = mysqli_stmt_get_result($stmtGrafik);
    $rowGrafik = mysqli_fetch_assoc($hasilGrafik);

    $dataGrafik[] = (float) ($rowGrafik['total'] ?? 0);

    mysqli_stmt_close($stmtGrafik);
}

// Data transaksi terbaru
$queryTerbaru = mysqli_query($conn, "
    SELECT
        t.id_transaksi,
        t.tanggal,
        t.total_belanja,
        p.nama_pelanggan
    FROM tbl_transaksi t
    LEFT JOIN tbl_pelanggan p
        ON t.id_pelanggan = p.id_pelanggan
    ORDER BY t.id_transaksi DESC
    LIMIT 5
");

if (!$queryTerbaru) {
    die('Gagal mengambil transaksi: ' . mysqli_error($conn));
}

// Produk terlaris
$queryProduk = mysqli_query($conn, "
    SELECT
        nama_produk,
        SUM(jumlah) AS total_terjual
    FROM tbl_detail_transaksi
    GROUP BY nama_produk
    ORDER BY total_terjual DESC
    LIMIT 5
");

if (!$queryProduk) {
    die('Gagal mengambil produk: ' . mysqli_error($conn));
}

// Komposisi pendapatan
$queryKomposisi = mysqli_query($conn, "
    SELECT
        nama_produk,
        SUM(jumlah * harga) AS total
    FROM tbl_detail_transaksi
    GROUP BY nama_produk
    ORDER BY total DESC
    LIMIT 5
");

$labelKomposisi = [];
$dataKomposisi = [];

if ($queryKomposisi) {
    while ($row = mysqli_fetch_assoc($queryKomposisi)) {
        $labelKomposisi[] = $row['nama_produk'];
        $dataKomposisi[] = (float) $row['total'];
    }
}

function formatRupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function tanggalIndonesia($tanggal) {
    return date('d/m/Y H:i', strtotime($tanggal));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Kasir | Tokoku</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- Chart JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #6366f1;
            --dark: #111827;
            --muted: #94a3b8;
            --background: #f5f7fc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .konten-kasir {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        /* HEADER */

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .top-header h3 {
            font-size: 25px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 7px;
        }

        .top-header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
        }

        .tanggal-header {
            background: white;
            border: 1px solid #e9edf5;
            border-radius: 12px;
            padding: 12px 16px;
            color: #475569;
            font-size: 13px;
        }

        /* WELCOME */

        .welcome-card {
            background: linear-gradient(120deg, #312e81, #4f46e5 55%, #7c3aed);
            border-radius: 20px;
            padding: 30px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 12px 30px rgba(79,70,229,.18);
        }

        .welcome-card::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border: 40px solid rgba(255,255,255,.08);
            border-radius: 50%;
            right: -55px;
            top: -100px;
        }

        .welcome-card h2 {
            font-size: 25px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: #ddd6fe;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .welcome-btn {
            display: inline-block;
            text-decoration: none;
            background: white;
            color: #4338ca;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            transition: .2s;
        }

        .welcome-btn:hover {
            background: #e0e7ff;
            color: #312e81;
            transform: translateY(-2px);
        }

        .welcome-icon {
            position: absolute;
            right: 45px;
            bottom: 15px;
            font-size: 100px;
            opacity: .15;
        }

        /* STATISTIK */

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 22px;
            border: 1px solid #edf0f6;
            height: 100%;
            transition: .25s;
            box-shadow: 0 4px 15px rgba(15,23,42,.025);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(15,23,42,.07);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 22px;
        }

        .stat-card h3 {
            font-size: 25px;
            font-weight: 800;
            margin: 20px 0 5px;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .stat-card p {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
        }

        .stat-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        /* CARD */

        .dashboard-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #edf0f6;
            box-shadow: 0 4px 15px rgba(15,23,42,.025);
            height: 100%;
        }

        .card-heading {
            padding: 20px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .card-heading h5 {
            font-size: 15px;
            font-weight: 800;
            margin: 0;
            color: #1e293b;
        }

        .card-heading small {
            font-size: 11px;
            color: #94a3b8;
        }

        .card-body-custom {
            padding: 22px;
        }

        .chart-container {
            height: 290px;
            position: relative;
        }

        .chart-container-donut {
            height: 270px;
            position: relative;
        }

        /* TABLE */

        .table {
            font-size: 12px;
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            padding: 14px;
            border: none;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 15px 14px;
            border-color: #f1f5f9;
            vertical-align: middle;
            color: #475569;
        }

        .table tbody tr:hover {
            background: #fafbff;
        }

        .badge-id {
            background: #eef2ff;
            color: #4f46e5;
            padding: 7px 10px;
            border-radius: 7px;
            font-weight: 700;
        }

        .badge-selesai {
            background: #dcfce7;
            color: #15803d;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
        }

        /* PRODUK TERLARIS */

        .produk-item {
            display: flex;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 13px;
        }

        .produk-item:last-child {
            border-bottom: none;
        }

        .produk-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #6366f1;
            font-size: 19px;
        }

        .produk-info {
            flex: 1;
            min-width: 0;
        }

        .produk-info h6 {
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 5px;
            color: #334155;
        }

        .produk-info small {
            color: #94a3b8;
            font-size: 11px;
        }

        .produk-jumlah {
            background: #f1f5f9;
            color: #475569;
            padding: 6px 9px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
        }

        /* FOOTER */

        .footer-dashboard {
            text-align: center;
            padding: 25px 0 5px;
            color: #94a3b8;
            font-size: 11px;
        }

        .link-semua {
            color: #6366f1;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }

        .link-semua:hover {
            color: #4338ca;
        }

        @media(max-width: 992px) {
            .konten-kasir {
                padding: 20px;
            }
        }

        @media(max-width: 768px) {
            .konten-kasir {
                margin-left: 210px;
                padding: 15px;
            }

            .top-header h3 {
                font-size: 21px;
            }

            .welcome-card {
                padding: 22px;
            }

            .welcome-icon {
                right: 15px;
                font-size: 65px;
            }
        }

        @media(max-width: 576px) {
            .konten-kasir {
                margin-left: 0;
                padding: 15px;
            }

            .tanggal-header {
                width: 100%;
            }

            .stat-card h3 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<?php require_once '../includes/sidebar_kasir.php'; ?>

<main class="konten-kasir">

    <!-- HEADER -->
    <div class="top-header">
        <div>
            <h3>Dashboard Kasir</h3>
            <p>Ringkasan aktivitas dan performa penjualan Tokoku.</p>
        </div>

        <div class="tanggal-header">
            <i class="bi bi-calendar3 me-2"></i>
            <?= date('d F Y'); ?>
        </div>
    </div>

    <!-- WELCOME -->
    <section class="welcome-card">
        <div class="position-relative" style="z-index:2;">
            <h2>Halo, <?= htmlspecialchars($namaKasir); ?>! 👋</h2>

            <p>
                Selamat bekerja. Pantau penjualan dan kelola transaksi
                dengan mudah melalui dashboard kasir.
            </p>

            <a href="../transaksi/index.php" class="welcome-btn">
                <i class="bi bi-plus-circle me-2"></i>
                Buat Transaksi Baru
            </a>
        </div>

        <i class="bi bi-shop-window welcome-icon"></i>
    </section>

    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">TOTAL TRANSAKSI</span>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>

                <h3><?= number_format($totalTransaksi); ?></h3>
                <p>Seluruh transaksi tersimpan</p>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">TOTAL PENDAPATAN</span>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>

                <h3 style="font-size:21px;">
                    <?= formatRupiah($totalPendapatan); ?>
                </h3>
                <p>Akumulasi seluruh penjualan</p>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">HARI INI</span>
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>

                <h3><?= number_format($transaksiHariIni); ?></h3>
                <p>Transaksi pada hari ini</p>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">RATA-RATA</span>
                    <div class="stat-icon bg-info-subtle text-info">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>

                <h3 style="font-size:21px;">
                    <?= formatRupiah($rataTransaksi); ?>
                </h3>
                <p>Rata-rata nilai transaksi</p>
            </div>
        </div>

    </div>

    <!-- GRAFIK -->
    <div class="row g-3 mb-4">

        <div class="col-lg-8">
            <div class="dashboard-card">

                <div class="card-heading">
                    <div>
                        <h5>Grafik Penjualan</h5>
                        <small>Performa penjualan 7 hari terakhir</small>
                    </div>

                    <span class="badge text-bg-primary">7 Hari</span>
                </div>

                <div class="card-body-custom">
                    <div class="chart-container">
                        <canvas id="grafikPenjualan"></canvas>
                    </div>
                </div>

            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-card">

                <div class="card-heading">
                    <div>
                        <h5>Produk Teratas</h5>
                        <small>Komposisi pendapatan produk</small>
                    </div>
                </div>

                <div class="card-body-custom">
                    <div class="chart-container-donut">
                        <canvas id="grafikProduk"></canvas>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- TABEL DAN PRODUK -->
    <div class="row g-3">

        <div class="col-lg-8">
            <div class="dashboard-card">

                <div class="card-heading">
                    <div>
                        <h5>Transaksi Terbaru</h5>
                        <small>5 transaksi terakhir</small>
                    </div>

                    <a href="transaksi/riwayat.php" class="link-semua">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table">

                        <thead>
                            <tr>
                                <th>ID Transaksi</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (mysqli_num_rows($queryTerbaru) > 0): ?>

                            <?php while ($trx = mysqli_fetch_assoc($queryTerbaru)): ?>

                                <tr>
                                    <td>
                                        <span class="badge-id">
                                            #TRX-<?= (int)$trx['id_transaksi']; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= tanggalIndonesia($trx['tanggal']); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $trx['nama_pelanggan'] ?? 'Pelanggan Umum'
                                        ); ?>
                                    </td>

                                    <td class="fw-bold text-dark">
                                        <?= formatRupiah($trx['total_belanja']); ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" class="text-center py-4 text-secondary">
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
            <div class="dashboard-card">

                <div class="card-heading">
                    <div>
                        <h5>Produk Terlaris</h5>
                        <small>Berdasarkan jumlah terjual</small>
                    </div>

                    <i class="bi bi-trophy text-warning fs-5"></i>
                </div>

                <div class="card-body-custom">

                    <?php if (mysqli_num_rows($queryProduk) > 0): ?>

                        <?php $nomorProduk = 1; ?>

                        <?php while ($produk = mysqli_fetch_assoc($queryProduk)): ?>

                            <div class="produk-item">

                                <div class="produk-icon">
                                    <i class="bi bi-box-seam"></i>
                                </div>

                                <div class="produk-info">
                                    <h6>
                                        <?= htmlspecialchars($produk['nama_produk']); ?>
                                    </h6>

                                    <small>
                                        Peringkat <?= $nomorProduk; ?>
                                    </small>
                                </div>

                                <span class="produk-jumlah">
                                    <?= number_format($produk['total_terjual']); ?> Terjual
                                </span>

                            </div>

                            <?php $nomorProduk++; ?>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <div class="text-center text-secondary py-4">
                            <i class="bi bi-box fs-1"></i>
                            <p class="small mt-2">Belum ada data produk terjual.</p>
                        </div>

                    <?php endif; ?>

                </div>

            </div>
        </div>

    </div>

    <div class="footer-dashboard">
        © <?= date('Y'); ?> Tokoku. Dashboard Kasir.
    </div>

</main>

<script>
const labelGrafik = <?= json_encode(
    $labelGrafik,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

const dataGrafik = <?= json_encode($dataGrafik); ?>;

const labelProduk = <?= json_encode(
    $labelKomposisi,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

const dataProduk = <?= json_encode($dataKomposisi); ?>;

// GRAFIK PENJUALAN
const ctxPenjualan = document
    .getElementById('grafikPenjualan')
    .getContext('2d');

const gradient = ctxPenjualan.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(99, 102, 241, 0.30)');
gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

new Chart(ctxPenjualan, {
    type: 'line',

    data: {
        labels: labelGrafik,

        datasets: [{
            label: 'Pendapatan',
            data: dataGrafik,
            borderColor: '#6366f1',
            backgroundColor: gradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#6366f1',
            pointBorderWidth: 3
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
                backgroundColor: '#111827',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return ' Pendapatan: Rp ' +
                            Number(context.raw).toLocaleString('id-ID');
                    }
                }
            }
        },

        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9'
                },
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + Number(value).toLocaleString('id-ID');
                    },
                    font: {
                        size: 10
                    }
                }
            },

            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 10
                    }
                }
            }
        }
    }
});

// GRAFIK PRODUK
const ctxProduk = document
    .getElementById('grafikProduk')
    .getContext('2d');

new Chart(ctxProduk, {
    type: 'doughnut',

    data: {
        labels: labelProduk,

        datasets: [{
            data: dataProduk,
            backgroundColor: [
                '#6366f1',
                '#8b5cf6',
                '#06b6d4',
                '#10b981',
                '#f59e0b'
            ],
            borderWidth: 3,
            borderColor: '#ffffff',
            hoverOffset: 8
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',

        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 14,
                    font: {
                        size: 10
                    }
                }
            },

            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': Rp ' +
                            Number(context.raw).toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>

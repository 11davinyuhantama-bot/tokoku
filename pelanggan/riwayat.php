<?php
require_once __DIR__ . '/../config/database.php';
if (($_SESSION['user']['role'] ?? '') !== 'pelanggan') {
    redirect('login.php');
}

$idPelanggan = (int) ($_SESSION['user']['id_pelanggan'] ?? 0);
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$perHalaman = 15;
$offset = ($halaman - 1) * $perHalaman;
$totalTransaksi = 0;
$totalBelanja = 0.0;
$riwayat = [];
$recentOrders = [];

if ($idPelanggan > 0) {
    $summaryStmt = $conn->prepare('SELECT COUNT(*) AS jumlah, COALESCE(SUM(total_belanja), 0) AS total FROM tbl_transaksi WHERE id_pelanggan = ?');
    $summaryStmt->bind_param('i', $idPelanggan);
    $summaryStmt->execute();
    $summary = $summaryStmt->get_result()->fetch_assoc();
    $summaryStmt->close();
    $totalTransaksi = (int) $summary['jumlah'];
    $totalBelanja = (float) $summary['total'];

    $totalHalaman = max(1, (int) ceil($totalTransaksi / $perHalaman));
    $stmt = $conn->prepare('SELECT id_transaksi, tanggal, total_belanja FROM tbl_transaksi WHERE id_pelanggan = ? ORDER BY tanggal DESC, id_transaksi DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('iii', $idPelanggan, $perHalaman, $offset);
    $stmt->execute();
    $riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $recentStmt = $conn->prepare('SELECT id_transaksi, tanggal, total_belanja FROM tbl_transaksi WHERE id_pelanggan = ? ORDER BY tanggal DESC, id_transaksi DESC LIMIT 10');
    $recentStmt->bind_param('i', $idPelanggan);
    $recentStmt->execute();
    $recentOrders = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recentStmt->close();
} else {
    $totalHalaman = 1;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Belanja | Tokoku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>body{background:#f3f5fb;color:#172033}.customer-main{min-height:100vh;padding:1rem}@media(min-width:992px){.customer-main{padding:2rem}}</style>
</head>
<body>
<?php $menuPelangganAktif = 'riwayat'; require __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>
<main class="customer-main"><div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"><div><div class="text-primary fw-semibold small text-uppercase">Akun pelanggan</div><h1 class="h3 fw-bold mb-1">Riwayat Belanja</h1><p class="text-secondary mb-0">Lihat semua pesanan yang tercatat pada akun Anda.</p></div><a class="btn btn-outline-primary" href="beranda.php"><i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda</a></div>
    <?php if ($idPelanggan < 1): ?><div class="alert alert-warning">Akun Anda belum terhubung ke profil pelanggan. Jalankan <code>setup_login.sql</code> kembali.</div>
    <?php else: ?>
        <div class="row g-3 mb-4"><div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-secondary">Jumlah transaksi</div><div class="fs-4 fw-bold"><?= number_format($totalTransaksi, 0, ',', '.') ?></div></div></div></div><div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body"><div class="small text-secondary">Total belanja</div><div class="fs-4 fw-bold text-primary"><?= rupiah($totalBelanja) ?></div></div></div></div></div>
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">No. transaksi</th><th>Tanggal</th><th>Total belanja</th><th class="text-end pe-3">Rincian</th></tr></thead><tbody>
            <?php if (!$riwayat): ?><tr><td colspan="4" class="text-center text-secondary py-5"><i class="bi bi-receipt fs-2 d-block mb-2"></i>Belum ada transaksi.</td></tr>
            <?php else: foreach ($riwayat as $item): ?><tr><td class="ps-3 fw-semibold">#<?= (int) $item['id_transaksi'] ?></td><td><?= e(date('d/m/Y', strtotime($item['tanggal']))) ?></td><td><?= rupiah($item['total_belanja']) ?></td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="detail_transaksi.php?id=<?= (int) $item['id_transaksi'] ?>"><i class="bi bi-eye me-1"></i>Lihat detail</a></td></tr><?php endforeach; endif; ?>
        </tbody></table></div>
        <?php if ($totalHalaman > 1): ?><nav class="p-3" aria-label="Halaman riwayat"><ul class="pagination justify-content-end mb-0"><li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?halaman=<?= max(1, $halaman - 1) ?>">Sebelumnya</a></li><li class="page-item disabled"><span class="page-link">Halaman <?= $halaman ?> / <?= $totalHalaman ?></span></li><li class="page-item <?= $halaman >= $totalHalaman ? 'disabled' : '' ?>"><a class="page-link" href="?halaman=<?= min($totalHalaman, $halaman + 1) ?>">Berikutnya</a></li></ul></nav><?php endif; ?>
        </div>
    <?php endif; ?>
</div></main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

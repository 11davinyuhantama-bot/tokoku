<?php
require_once __DIR__ . '/../../config/database.php';

if (($_SESSION['user']['role'] ?? '') !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    set_flash('danger', 'ID transaksi tidak valid.');
    header('Location: riwayat.php');
    exit;
}

$stmt = $conn->prepare('SELECT t.id_transaksi, t.tanggal, t.total_belanja, p.nama_pelanggan, p.kota FROM tbl_transaksi t LEFT JOIN tbl_pelanggan p ON p.id_pelanggan = t.id_pelanggan WHERE t.id_transaksi = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaksi) {
    set_flash('warning', 'Transaksi tidak ditemukan.');
    header('Location: riwayat.php');
    exit;
}

$stmtDetail = $conn->prepare('SELECT nama_produk, jumlah, harga FROM tbl_detail_transaksi WHERE id_transaksi = ? ORDER BY id_detail');
$stmtDetail->bind_param('i', $id);
$stmtDetail->execute();
$detail = $stmtDetail->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtDetail->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Transaksi #<?= (int) $transaksi['id_transaksi'] ?> | Tokoku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f1f5f9}.konten-kasir{margin-left:250px;padding:28px}.panel{border:0;border-radius:16px;box-shadow:0 8px 28px #0f172a0a}.total-card{background:#eef2ff;border-radius:12px;padding:18px}
        @media(max-width:768px){.konten-kasir{margin-left:0;padding:16px}.sidebar-kasir{position:relative;width:100%;height:auto}.sidebar-kasir .profil-kasir{margin-top:12px}}
    </style>
</head>
<body>
<?php require __DIR__ . '/../../includes/sidebar_kasir.php'; ?>
<main class="konten-kasir">
    <?php show_flash(); ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">Detail Transaksi</h1><p class="text-secondary mb-0">Transaksi #<?= (int) $transaksi['id_transaksi'] ?></p></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="riwayat.php"><i class="bi bi-clock-history me-1"></i>Riwayat</a>
            <a class="btn btn-primary" href="../../transaksi/cetak_struk.php?id=<?= (int) $transaksi['id_transaksi'] ?>" target="_blank"><i class="bi bi-printer me-1"></i>Cetak Struk</a>
        </div>
    </div>

    <section class="card panel mb-4"><div class="card-body p-4">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Nomor transaksi</div><div class="fw-bold">#<?= (int) $transaksi['id_transaksi'] ?></div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Tanggal</div><div class="fw-semibold"><?= e(date('d/m/Y H:i', strtotime($transaksi['tanggal']))) ?></div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Pelanggan</div><div class="fw-semibold"><?= e($transaksi['nama_pelanggan'] ?: 'Pelanggan umum') ?></div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-secondary">Kota</div><div class="fw-semibold"><?= e($transaksi['kota'] ?: '-') ?></div></div>
        </div>
    </div></section>

    <section class="card panel"><div class="card-body p-0">
        <div class="p-3 border-bottom"><h2 class="h6 fw-bold mb-0">Barang yang dibeli</h2></div>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th class="ps-3">Produk</th><th>Harga</th><th>Jumlah</th><th class="text-end pe-3">Subtotal</th></tr></thead>
            <tbody>
            <?php if (!$detail): ?><tr><td colspan="4" class="text-center text-secondary py-4">Rincian produk tidak tersedia.</td></tr>
            <?php else: foreach ($detail as $item): ?><tr><td class="ps-3 fw-semibold"><?= e($item['nama_produk']) ?></td><td><?= rupiah($item['harga']) ?></td><td><?= (int) $item['jumlah'] ?></td><td class="text-end pe-3"><?= rupiah((float) $item['harga'] * (int) $item['jumlah']) ?></td></tr><?php endforeach; endif; ?>
            </tbody>
            <tfoot><tr><th colspan="3" class="text-end">Total pembayaran</th><th class="text-end pe-3"><div class="total-card d-inline-block"><?= rupiah($transaksi['total_belanja']) ?></div></th></tr></tfoot>
        </table></div>
    </div></section>
    <a class="btn btn-outline-primary mt-3" href="index.php"><i class="bi bi-plus-lg me-1"></i>Buat transaksi berikutnya</a>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

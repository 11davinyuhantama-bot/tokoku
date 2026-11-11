<?php
require_once __DIR__ . '/../config/database.php';
if (($_SESSION['user']['role'] ?? '') !== 'pelanggan') {
    redirect('login.php');
}
$idPelanggan = (int) ($_SESSION['user']['id_pelanggan'] ?? 0);
$idTransaksi = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT id_transaksi, tanggal, total_belanja FROM tbl_transaksi WHERE id_transaksi = ? AND id_pelanggan = ? LIMIT 1');
$stmt->bind_param('ii', $idTransaksi, $idPelanggan);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$transaksi) {
    set_flash('warning', 'Transaksi tidak ditemukan di akun Anda.');
    redirect('pelanggan/beranda.php');
}
$stmt = $conn->prepare('SELECT nama_produk, jumlah, harga FROM tbl_detail_transaksi WHERE id_transaksi = ? ORDER BY id_detail');
$stmt->bind_param('i', $idTransaksi);
$stmt->execute();
$detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$recentStmt = $conn->prepare('SELECT id_transaksi, tanggal, total_belanja FROM tbl_transaksi WHERE id_pelanggan = ? ORDER BY tanggal DESC, id_transaksi DESC LIMIT 10');
$recentStmt->bind_param('i', $idPelanggan);
$recentStmt->execute();
$recentOrders = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStmt->close();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Detail Belanja | Tokoku</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><style>body{background:#f5f7fb}.customer-main{min-height:100vh;padding:1rem}@media(min-width:992px){.customer-main{margin-left:320px;padding:2rem}}</style></head><body class="bg-light">
<?php $menuPelangganAktif = 'riwayat'; require __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>
<main class="customer-main"><div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3"><div><h1 class="h3 fw-bold mb-1">Detail Belanja #<?= (int) $transaksi['id_transaksi'] ?></h1><p class="text-secondary mb-0">Tanggal <?= e(date('d/m/Y', strtotime($transaksi['tanggal']))) ?></p></div><a class="btn btn-outline-primary" href="<?= url('pelanggan/beranda.php') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda</a></div><div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Produk</th><th>Harga</th><th>Jumlah</th><th class="text-end pe-3">Subtotal</th></tr></thead><tbody><?php foreach ($detail as $item): ?><tr><td class="ps-3"><?= e($item['nama_produk']) ?></td><td><?= rupiah($item['harga']) ?></td><td><?= (int) $item['jumlah'] ?></td><td class="text-end pe-3"><?= rupiah((float) $item['harga'] * (int) $item['jumlah']) ?></td></tr><?php endforeach; ?></tbody><tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end pe-3"><?= rupiah($transaksi['total_belanja']) ?></th></tr></tfoot></table></div></div></main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>

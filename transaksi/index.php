<?php
require_once '../config/database.php';
$pageTitle='Data Transaksi';
$rows=$conn->query("SELECT * FROM view_transaksi_pelanggan ORDER BY tanggal DESC,id_transaksi DESC");
include '../includes/header.php';
?>
<div class="card content-card"><div class="card-body">
 <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0">Daftar Transaksi</h5><a href="../laporan/index.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-bar-graph me-1"></i>Laporan</a></div>
 <div class="table-responsive"><table class="table table-hover"><thead><tr><th>ID Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th>Kota</th><th class="text-end">Total Belanja</th><th>Aksi</th></tr></thead><tbody>
 <?php if($rows->num_rows):while($r=$rows->fetch_assoc()): ?>
 <tr><td>#<?= e($r['id_transaksi']) ?></td><td><?= e($r['tanggal']) ?></td><td><?= e($r['nama_pelanggan']) ?></td><td><?= e($r['kota']) ?></td><td class="text-end fw-semibold"><?= rupiah($r['total_belanja']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="detail.php?id=<?= (int)$r['id_transaksi'] ?>">Detail</a></td></tr>
 <?php endwhile;else: ?><tr><td colspan="6" class="text-center text-secondary py-4">Belum ada transaksi.</td></tr><?php endif; ?>
 </tbody></table></div>
</div></div>
<?php include '../includes/footer.php'; ?>

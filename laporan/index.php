<?php
require_once '../config/database.php';
$pageTitle='Laporan Penjualan';
$rows=$conn->query("SELECT * FROM view_total_belanja ORDER BY total_belanja DESC");
$summary=$conn->query("SELECT COUNT(*) jumlah, COALESCE(SUM(total_belanja),0) total FROM tbl_transaksi")->fetch_assoc();
include '../includes/header.php';
?>
<div class="row g-3 mb-3"><div class="col-md-6"><div class="card stat-card"><div class="card-body"><div class="text-secondary">Jumlah Transaksi</div><div class="fs-3 fw-bold"><?= (int)$summary['jumlah'] ?></div></div></div></div><div class="col-md-6"><div class="card stat-card"><div class="card-body"><div class="text-secondary">Total Penjualan</div><div class="fs-3 fw-bold"><?= rupiah($summary['total']) ?></div></div></div></div></div>
<div class="card content-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0">Rekap Belanja Pelanggan</h5><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Cetak</button></div>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>ID</th><th>Pelanggan</th><th>Total Transaksi</th><th class="text-end">Total Belanja</th><th>Kategori</th></tr></thead><tbody>
<?php if($rows->num_rows):while($r=$rows->fetch_assoc()):
 $s=$conn->prepare("SELECT KategoriBelanja(?) kategori");$s->bind_param("d",$r['total_belanja']);$s->execute();$kategori=$s->get_result()->fetch_assoc()['kategori']; ?>
<tr><td><?=e($r['id_pelanggan'])?></td><td><?=e($r['nama_pelanggan'])?></td><td><?= (int)$r['total_transaksi'] ?></td><td class="text-end fw-semibold"><?=rupiah($r['total_belanja'])?></td><td><span class="badge text-bg-<?= $kategori==='Pelanggan Gold'?'warning':($kategori==='Pelanggan Silver'?'secondary':'success') ?>"><?=e($kategori)?></span></td></tr>
<?php endwhile;else:?><tr><td colspan="5" class="text-center">Belum ada data.</td></tr><?php endif;?>
</tbody></table></div></div></div>
<?php include '../includes/footer.php';?>

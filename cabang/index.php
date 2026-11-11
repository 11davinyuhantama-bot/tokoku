<?php
require_once '../config/database.php';
$pageTitle='Data Cabang';
$rows=$conn->query("SELECT * FROM tbl_cabang ORDER BY id_cabang DESC");
include '../includes/header.php';
?>
<div class="card content-card"><div class="card-body">
 <div class="d-flex justify-content-between mb-3"><h5 class="fw-bold">Daftar Cabang Toko</h5><a href="tambah.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Tambah Cabang</a></div>
 <div class="table-responsive"><table class="table table-hover"><thead><tr><th>ID</th><th>Nama Cabang</th><th>Kota</th><th class="text-end">Aksi</th></tr></thead><tbody>
 <?php if($rows->num_rows):while($r=$rows->fetch_assoc()): ?><tr><td><?= e($r['id_cabang']) ?></td><td><?= e($r['nama_cabang']) ?></td><td><?= e($r['kota']) ?></td><td class="text-end"><a href="edit.php?id=<?= (int)$r['id_cabang'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a> <a href="hapus.php?id=<?= (int)$r['id_cabang'] ?>" data-confirm="Hapus cabang ini?" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td></tr><?php endwhile;else: ?><tr><td colspan="4" class="text-center">Belum ada cabang.</td></tr><?php endif; ?>
 </tbody></table></div>
</div></div>
<?php include '../includes/footer.php'; ?>

<?php
require_once '../config/database.php';
$pageTitle='Data Pelanggan';
$q=trim($_GET['q'] ?? '');
if($q!==''){
  $stmt=$conn->prepare("SELECT * FROM view_pelanggan WHERE nama_pelanggan LIKE CONCAT('%',?,'%') OR kota LIKE CONCAT('%',?,'%') ORDER BY id_pelanggan DESC");
  $stmt->bind_param("ss",$q,$q); $stmt->execute(); $rows=$stmt->get_result();
}else{$rows=$conn->query("SELECT * FROM view_pelanggan ORDER BY id_pelanggan DESC");}
$saranPelanggan=$conn->query("SELECT nama_pelanggan,kota FROM tbl_pelanggan ORDER BY nama_pelanggan LIMIT 250");
include '../includes/header.php';
?>
<div class="card content-card"><div class="card-body">
 <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="d-flex gap-2" method="get" data-auto-search data-search-delay="1200"><input class="form-control" name="q" list="saranPelanggan" data-search-input autocomplete="off" value="<?= e($q) ?>" placeholder="Cari nama atau kota..." aria-label="Cari pelanggan"><datalist id="saranPelanggan"><?php while($saran=$saranPelanggan->fetch_assoc()): ?><option value="<?= e($saran['nama_pelanggan']) ?>" label="<?= e($saran['kota']) ?>"></option><option value="<?= e($saran['kota']) ?>" label="<?= e($saran['nama_pelanggan']) ?>"></option><?php endwhile; ?></datalist></form>
  <?php if($q!==''): ?><div class="small text-secondary mb-3">Hasil untuk <strong>&quot;<?= e($q) ?>&quot;</strong>.</div><?php endif; ?>
  <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Pelanggan</a>
 </div>
 <div class="table-responsive"><table class="table table-hover">
 <thead><tr><th>No.</th><th>ID</th><th>Nama Pelanggan</th><th>Kota</th><th class="text-end">Aksi</th></tr></thead><tbody>
 <?php $no=1; if($rows->num_rows): while($r=$rows->fetch_assoc()): ?>
 <tr><td><?= $no++ ?></td><td><?= e($r['id_pelanggan']) ?></td><td class="fw-semibold"><?= e($r['nama_pelanggan']) ?></td><td><?= e($r['kota']) ?></td><td class="text-end">
 <a class="btn btn-sm btn-outline-warning" href="edit.php?id=<?= (int)$r['id_pelanggan'] ?>"><i class="bi bi-pencil"></i></a>
 <a class="btn btn-sm btn-outline-danger" data-confirm="Hapus pelanggan ini? Penghapusan dapat ditolak jika pelanggan memiliki transaksi." href="hapus.php?id=<?= (int)$r['id_pelanggan'] ?>"><i class="bi bi-trash"></i></a>
 </td></tr>
 <?php endwhile; else: ?><tr><td colspan="5" class="text-center text-secondary py-4">Data pelanggan tidak ditemukan.</td></tr><?php endif; ?>
 </tbody></table></div>
</div></div>
<?php include '../includes/footer.php'; ?>

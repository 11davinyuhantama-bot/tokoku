<?php
require_once '../config/database.php';
$pageTitle='Tambah Pelanggan';
$nama=$kota='';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $nama=trim($_POST['nama_pelanggan']??''); $kota=trim($_POST['kota']??'');
 if($nama===''||$kota==='') $error='Nama pelanggan dan kota wajib diisi.';
 else{
  try{
   $stmt=$conn->prepare("CALL TambahPelanggan(?,?)"); $stmt->bind_param("ss",$nama,$kota); $stmt->execute(); $stmt->close();
   while($conn->more_results() && $conn->next_result()){ if($res=$conn->store_result()) $res->free(); }
   set_flash('success','Pelanggan berhasil ditambahkan. Trigger log pelanggan juga dijalankan.'); redirect('index.php');
  }catch(mysqli_sql_exception $e){$error='Gagal menyimpan data: '.$e->getMessage();}
 }
}
include '../includes/header.php';
?>
<div class="card content-card"><div class="card-body p-4">
 <h5 class="fw-bold mb-3">Form Tambah Pelanggan</h5>
 <?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
 <form method="post">
  <div class="mb-3"><label class="form-label">Nama Pelanggan</label><input class="form-control" name="nama_pelanggan" maxlength="100" value="<?= e($nama) ?>" required></div>
  <div class="mb-3"><label class="form-label">Kota</label><input class="form-control" name="kota" maxlength="50" value="<?= e($kota) ?>" required></div>
  <a href="index.php" class="btn btn-light">Batal</a> <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
 </form>
</div></div>
<?php include '../includes/footer.php'; ?>

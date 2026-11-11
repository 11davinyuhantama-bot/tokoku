<?php
require_once '../config/database.php';
$pageTitle='Edit Pelanggan';
$id=(int)($_GET['id']??0);
$stmt=$conn->prepare("SELECT * FROM tbl_pelanggan WHERE id_pelanggan=?"); $stmt->bind_param("i",$id); $stmt->execute(); $data=$stmt->get_result()->fetch_assoc();
if(!$data){set_flash('warning','Data pelanggan tidak ditemukan.'); redirect('index.php');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $nama=trim($_POST['nama_pelanggan']??''); $kota=trim($_POST['kota']??'');
 if($nama===''||$kota==='') $error='Semua kolom wajib diisi.';
 else{
  try{$up=$conn->prepare("UPDATE tbl_pelanggan SET nama_pelanggan=?, kota=? WHERE id_pelanggan=?");$up->bind_param("ssi",$nama,$kota,$id);$up->execute();
   set_flash('success','Data pelanggan berhasil diperbarui.'); redirect('index.php');
  }catch(mysqli_sql_exception $e){$error='Gagal memperbarui data: '.$e->getMessage();}
 }
}else{$nama=$data['nama_pelanggan'];$kota=$data['kota'];}
include '../includes/header.php';
?>
<div class="card content-card"><div class="card-body p-4">
 <h5 class="fw-bold mb-3">Form Edit Pelanggan #<?= $id ?></h5>
 <?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
 <form method="post">
  <div class="mb-3"><label class="form-label">Nama Pelanggan</label><input class="form-control" name="nama_pelanggan" maxlength="100" value="<?= e($nama) ?>" required></div>
  <div class="mb-3"><label class="form-label">Kota</label><input class="form-control" name="kota" maxlength="50" value="<?= e($kota) ?>" required></div>
  <a href="index.php" class="btn btn-light">Batal</a> <button class="btn btn-primary">Simpan Perubahan</button>
 </form>
</div></div>
<?php include '../includes/footer.php'; ?>

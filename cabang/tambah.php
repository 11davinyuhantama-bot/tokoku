<?php
require_once '../config/database.php';$pageTitle='Tambah Cabang';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){$nama=trim($_POST['nama_cabang']??'');$kota=trim($_POST['kota']??'');
 if($nama===''||$kota==='')$error='Semua kolom wajib diisi.';else{$s=$conn->prepare("INSERT INTO tbl_cabang(nama_cabang,kota) VALUES(?,?)");$s->bind_param("ss",$nama,$kota);$s->execute();set_flash('success','Cabang berhasil ditambahkan.');redirect('index.php');}}
include '../includes/header.php';?>
<div class="card content-card"><div class="card-body p-4"><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<form method="post"><div class="mb-3"><label class="form-label">Nama Cabang</label><input class="form-control" name="nama_cabang" required></div><div class="mb-3"><label class="form-label">Kota</label><input class="form-control" name="kota" required></div><a href="index.php" class="btn btn-light">Batal</a> <button class="btn btn-primary">Simpan</button></form></div></div>
<?php include '../includes/footer.php';?>

<?php
require_once '../config/database.php';
$id=(int)($_GET['id']??0);
try{$stmt=$conn->prepare("DELETE FROM tbl_pelanggan WHERE id_pelanggan=?");$stmt->bind_param("i",$id);$stmt->execute();
 set_flash('success','Data pelanggan berhasil dihapus.');
}catch(mysqli_sql_exception $e){set_flash('danger','Pelanggan tidak dapat dihapus. Pastikan tidak memiliki transaksi terkait.');}
redirect('index.php');

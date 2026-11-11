<?php
require_once '../config/database.php';$id=(int)($_GET['id']??0);$s=$conn->prepare("DELETE FROM tbl_cabang WHERE id_cabang=?");$s->bind_param("i",$id);$s->execute();set_flash('success','Data cabang dihapus.');redirect('index.php');

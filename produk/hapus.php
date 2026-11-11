
<?php

require_once "../config/database.php";

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect('produk/index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('produk/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if (!verify_csrf_token($_POST['csrf_produk'] ?? '', 'csrf_produk')) {
    set_flash('danger', 'Sesi formulir tidak valid. Silakan coba kembali.');
    redirect('produk/index.php');
}

if ($id <= 0) {
    set_flash('danger', 'ID produk tidak valid.');
    redirect('produk/index.php');
}

$stmt = $conn->prepare("
    DELETE FROM tbl_produk
    WHERE id_produk = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    set_flash('success', 'Produk berhasil dihapus.');
} else {
    set_flash('warning', 'Produk tidak ditemukan.');
}

redirect('produk/index.php');

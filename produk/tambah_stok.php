<?php
require_once __DIR__ . '/../config/database.php';

if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'kasir'], true)) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('produk/index.php');
}

if (!verify_csrf_token($_POST['csrf_produk'] ?? '', 'csrf_produk')) {
    set_flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
    redirect('produk/index.php');
}

$idProduk = (int) ($_POST['id_produk'] ?? 0);
$jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);

if ($idProduk < 1 || $jumlah === false || $jumlah < 1 || $jumlah > 100000) {
    set_flash('danger', 'Jumlah stok harus berupa angka antara 1 dan 100.000.');
    redirect('produk/index.php');
}

$stmt = $conn->prepare('UPDATE tbl_produk SET stok = stok + ? WHERE id_produk = ? AND stok <= 2147483647 - ?');
$stmt->bind_param('iii', $jumlah, $idProduk, $jumlah);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    set_flash('success', 'Stok produk berhasil ditambahkan.');
} else {
    $check = $conn->prepare('SELECT id_produk FROM tbl_produk WHERE id_produk = ?');
    $check->bind_param('i', $idProduk);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    set_flash('warning', $exists ? 'Stok tidak dapat ditambah karena melewati batas.' : 'Produk tidak ditemukan.');
}
$stmt->close();
redirect('produk/index.php');

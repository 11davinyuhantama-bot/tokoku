<?php
require_once __DIR__ . '/../config/database.php';

if (($_SESSION['user']['role'] ?? '') !== 'pelanggan') {
    redirect('login.php');
}

$idPelanggan = (int) ($_SESSION['user']['id_pelanggan'] ?? 0);
if (!isset($_SESSION['keranjang_pelanggan'])) {
    $_SESSION['keranjang_pelanggan'] = [];
}
$csrf = csrf_token('csrf_pelanggan');
$cart = &$_SESSION['keranjang_pelanggan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? '', 'csrf_pelanggan')) {
        set_flash('danger', 'Sesi formulir berakhir. Muat ulang halaman lalu coba kembali.');
        redirect('pelanggan/beranda.php');
    }

    $aksi = $_POST['aksi'] ?? '';
    $idProduk = (int) ($_POST['id_produk'] ?? 0);

    if ($aksi === 'tambah') {
        $stmt = $conn->prepare('SELECT id_produk, nama_produk, stok FROM tbl_produk WHERE id_produk = ? LIMIT 1');
        $stmt->bind_param('i', $idProduk);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$item || (int) $item['stok'] < 1) {
            set_flash('warning', 'Produk tidak tersedia atau stoknya habis.');
        } elseif (($cart[$idProduk] ?? 0) + 1 > (int) $item['stok']) {
            set_flash('warning', 'Jumlah di keranjang sudah mencapai stok yang tersedia.');
        } else {
            $cart[$idProduk] = ($cart[$idProduk] ?? 0) + 1;
            set_flash('success', $item['nama_produk'] . ' ditambahkan ke keranjang.');
        }
        redirect('pelanggan/beranda.php#katalog');
    }

    if ($aksi === 'ubah_jumlah') {
        $jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
        $stmt = $conn->prepare('SELECT stok FROM tbl_produk WHERE id_produk = ? LIMIT 1');
        $stmt->bind_param('i', $idProduk);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$item || $jumlah === false || $jumlah < 1 || $jumlah > (int) $item['stok']) {
            set_flash('warning', 'Jumlah tidak valid atau stok tidak mencukupi.');
        } else {
            $cart[$idProduk] = $jumlah;
            set_flash('success', 'Jumlah produk diperbarui.');
        }
        redirect('pelanggan/beranda.php#keranjang');
    }

    if ($aksi === 'hapus') {
        unset($cart[$idProduk]);
        set_flash('info', 'Produk dihapus dari keranjang.');
        redirect('pelanggan/beranda.php#keranjang');
    }

    if ($aksi === 'kosongkan') {
        $cart = [];
        set_flash('info', 'Keranjang dikosongkan.');
        redirect('pelanggan/beranda.php#keranjang');
    }

    if ($aksi === 'checkout') {
        if ($idPelanggan < 1) {
            set_flash('danger', 'Akun belum terhubung ke profil pelanggan. Hubungi admin toko.');
            redirect('pelanggan/beranda.php#keranjang');
        }
        if (!$cart) {
            set_flash('warning', 'Keranjang masih kosong.');
            redirect('pelanggan/beranda.php#keranjang');
        }

        $transaksiDimulai = false;
        try {
            $conn->begin_transaction();
            $transaksiDimulai = true;
            $detail = [];
            $total = 0.0;
            $stmtProduk = $conn->prepare('SELECT id_produk, nama_produk, harga, stok FROM tbl_produk WHERE id_produk = ? FOR UPDATE');

            foreach ($cart as $cartId => $jumlah) {
                $cartId = (int) $cartId;
                $jumlah = (int) $jumlah;
                $stmtProduk->bind_param('i', $cartId);
                $stmtProduk->execute();
                $produk = $stmtProduk->get_result()->fetch_assoc();
                if (!$produk || $jumlah < 1 || $jumlah > (int) $produk['stok']) {
                    throw new RuntimeException('Stok ' . ($produk['nama_produk'] ?? 'produk') . ' tidak mencukupi. Periksa keranjang Anda.');
                }
                $harga = (float) $produk['harga'];
                $total += $harga * $jumlah;
                $detail[] = ['id' => $cartId, 'nama' => $produk['nama_produk'], 'jumlah' => $jumlah, 'harga' => $harga];
            }
            $stmtProduk->close();

            $stmtTransaksi = $conn->prepare('INSERT INTO tbl_transaksi (tanggal, id_pelanggan, total_belanja) VALUES (NOW(), ?, ?)');
            $stmtTransaksi->bind_param('id', $idPelanggan, $total);
            $stmtTransaksi->execute();
            $idTransaksi = $conn->insert_id;
            $stmtTransaksi->close();

            $stmtDetail = $conn->prepare('INSERT INTO tbl_detail_transaksi (id_transaksi, nama_produk, jumlah, harga) VALUES (?, ?, ?, ?)');
            $stmtStok = $conn->prepare('UPDATE tbl_produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?');
            foreach ($detail as $baris) {
                $stmtDetail->bind_param('isid', $idTransaksi, $baris['nama'], $baris['jumlah'], $baris['harga']);
                $stmtDetail->execute();
                $stmtStok->bind_param('iii', $baris['jumlah'], $baris['id'], $baris['jumlah']);
                $stmtStok->execute();
                if ($stmtStok->affected_rows !== 1) {
                    throw new RuntimeException('Stok berubah saat checkout. Silakan coba kembali.');
                }
            }
            $stmtDetail->close();
            $stmtStok->close();
            $conn->commit();

            $cart = [];
            $_SESSION['csrf_pelanggan'] = bin2hex(random_bytes(32));
            set_flash('success', 'Pesanan berhasil dibuat.');
            header('Location: detail_transaksi.php?id=' . $idTransaksi);
            exit;
        } catch (Throwable $exception) {
            if ($transaksiDimulai) {
                $conn->rollback();
            }
            set_flash('danger', $exception->getMessage());
            redirect('pelanggan/beranda.php#keranjang');
        }
    }

    set_flash('warning', 'Aksi tidak dikenal.');
    redirect('pelanggan/beranda.php');
}

$cari = trim($_GET['q'] ?? '');
if ($cari !== '') {
    $kata = '%' . $cari . '%';
    $produkStmt = $conn->prepare('SELECT id_produk, kode_produk, nama_produk, kategori, harga, stok, gambar FROM tbl_produk WHERE nama_produk LIKE ? OR kode_produk LIKE ? ORDER BY nama_produk');
    $produkStmt->bind_param('ss', $kata, $kata);
} else {
    $produkStmt = $conn->prepare('SELECT id_produk, kode_produk, nama_produk, kategori, harga, stok, gambar FROM tbl_produk ORDER BY nama_produk');
}
$produkStmt->execute();
$produk = $produkStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$produkStmt->close();
$saranProduk = $conn->query('SELECT kode_produk, nama_produk FROM tbl_produk ORDER BY nama_produk LIMIT 250')->fetch_all(MYSQLI_ASSOC);

$keranjang = [];
$totalKeranjang = 0.0;
$jumlahItem = 0;
if ($cart) {
    $stmtKeranjang = $conn->prepare('SELECT id_produk, kode_produk, nama_produk, harga, stok, gambar FROM tbl_produk WHERE id_produk = ?');
    foreach ($cart as $id => $jumlah) {
        $id = (int) $id;
        $jumlah = (int) $jumlah;
        $stmtKeranjang->bind_param('i', $id);
        $stmtKeranjang->execute();
        $item = $stmtKeranjang->get_result()->fetch_assoc();
        if (!$item) {
            unset($cart[$id]);
            continue;
        }
        $item['jumlah'] = $jumlah;
        $item['subtotal'] = (float) $item['harga'] * $jumlah;
        $keranjang[] = $item;
        $totalKeranjang += $item['subtotal'];
        $jumlahItem += $jumlah;
    }
    $stmtKeranjang->close();
}

$riwayat = [];
if ($idPelanggan > 0) {
    $riwayatStmt = $conn->prepare('SELECT id_transaksi, tanggal, total_belanja FROM tbl_transaksi WHERE id_pelanggan = ? ORDER BY tanggal DESC, id_transaksi DESC LIMIT 10');
    $riwayatStmt->bind_param('i', $idPelanggan);
    $riwayatStmt->execute();
    $riwayat = $riwayatStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $riwayatStmt->close();
}
    $recentOrders = array_slice($riwayat, 0, 10);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beranda Pelanggan | Tokoku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f3f5fb;color:#172033}.customer-main{min-height:100vh;padding:1rem;min-width:0}.hero{background:linear-gradient(120deg,#312e81,#4f46e5);border-radius:22px;color:#fff;padding:clamp(22px,4vw,38px);box-shadow:0 16px 36px #312e8124}.product-card,.panel{height:100%;min-width:0;overflow:hidden;border:0;border-radius:16px;box-shadow:0 8px 28px #0f172a0c}.product-card .card-body{min-width:0}.product-image{position:relative;height:155px;min-height:155px;overflow:hidden;background:#eef2ff;display:flex;align-items:center;justify-content:center}.product-image img{position:absolute;inset:0;display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain;padding:14px}.placeholder{font-size:42px;color:#818cf8}.section-title{font-size:1.2rem;font-weight:800}.sticky-cart{position:sticky;top:1rem}.stat-card{border:0;border-radius:14px;box-shadow:0 8px 24px #0f172a0a}.cart-thumb{flex:0 0 48px;width:48px;height:48px;max-width:48px;object-fit:contain;background:#f1f5f9;border-radius:9px}#katalog,#keranjang,#riwayat{scroll-margin-top:1rem}@media(min-width:992px){.customer-main{padding:2rem}}
    </style>
</head>
<body>
<?php $menuPelangganAktif = 'beranda'; $customerCartCount = $jumlahItem; require __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>
<main class="customer-main"><div class="container-fluid p-0">
    <?php show_flash(); ?>
    <section class="hero mb-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><div class="text-white-50 small text-uppercase fw-semibold mb-2">Toko online</div><h1 class="h2 fw-bold mb-2">Halo, <?= e($_SESSION['user']['nama']) ?>!</h1><p class="mb-0 text-white-50">Temukan produk, pesan dengan mudah, dan pantau belanja Anda.</p></div><a class="btn btn-light text-primary fw-semibold" href="#katalog"><i class="bi bi-bag me-2"></i>Mulai belanja</a></div></section>

    <div class="row g-3 mb-4"><div class="col-6 col-xl-4"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3"><span class="rounded-3 bg-primary-subtle text-primary p-3"><i class="bi bi-box-seam fs-5"></i></span><div><div class="small text-secondary">Produk tersedia</div><strong><?= count($produk) ?></strong></div></div></div></div><div class="col-6 col-xl-4"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3"><span class="rounded-3 bg-success-subtle text-success p-3"><i class="bi bi-cart3 fs-5"></i></span><div><div class="small text-secondary">Item di keranjang</div><strong><?= $jumlahItem ?></strong></div></div></div></div><div class="col-12 col-xl-4"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3"><span class="rounded-3 bg-warning-subtle text-warning p-3"><i class="bi bi-receipt fs-5"></i></span><div><div class="small text-secondary">Riwayat belanja</div><strong><?= count($riwayat) ?> transaksi terakhir</strong></div></div></div></div></div>

    <?php if ($idPelanggan < 1): ?><div class="alert alert-warning"><i class="bi bi-exclamation-circle me-2"></i>Akun ini belum terhubung ke profil pelanggan, jadi checkout belum dapat dilakukan. Jalankan <code>setup_login.sql</code> kembali.</div><?php endif; ?>

    <section class="mb-4" id="katalog">
            <div class="d-flex justify-content-between align-items-end gap-3 mb-3"><div><h2 class="section-title mb-1">Katalog produk</h2><div class="text-secondary small">Pilih produk untuk memasukkannya ke keranjang.</div></div></div>
            <form method="get" class="input-group mb-3" data-auto-search data-search-delay="1200"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input class="form-control" name="q" list="saranProdukPelanggan" data-search-input autocomplete="off" value="<?= e($cari) ?>" placeholder="Cari nama atau kode produk" aria-label="Cari produk"><datalist id="saranProdukPelanggan"><?php foreach ($saranProduk as $saran): ?><option value="<?= e($saran['nama_produk']) ?>" label="<?= e($saran['kode_produk']) ?>"></option><option value="<?= e($saran['kode_produk']) ?>" label="<?= e($saran['nama_produk']) ?>"></option><?php endforeach; ?></datalist><?php if ($cari !== ''): ?><a class="btn btn-outline-secondary" href="beranda.php">Reset</a><?php endif; ?></form>
            <?php if (!$produk): ?><div class="card panel"><div class="card-body text-center text-secondary py-5">Produk tidak ditemukan.</div></div><?php else: ?><div class="row g-3">
                <?php foreach ($produk as $item): ?><div class="col-6 col-md-4"><article class="card product-card"><div class="product-image"><?php if (!empty($item['gambar'])): ?><img src="../assets/img/produk/<?= rawurlencode(basename($item['gambar'])) ?>" alt="<?= e($item['nama_produk']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='block'"><i class="bi bi-box-seam placeholder" style="display:none"></i><?php else: ?><i class="bi bi-box-seam placeholder"></i><?php endif; ?></div><div class="card-body d-flex flex-column"><div class="small text-secondary mb-1"><?= e($item['kategori'] ?: 'Produk') ?> · <?= e($item['kode_produk']) ?></div><h3 class="h6 fw-bold mb-2"><?= e($item['nama_produk']) ?></h3><div class="fw-bold text-primary mb-1"><?= rupiah($item['harga']) ?></div><div class="small text-secondary mb-3">Stok: <?= (int) $item['stok'] ?></div><form method="post" class="mt-auto"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="tambah"><input type="hidden" name="id_produk" value="<?= (int) $item['id_produk'] ?>"><button class="btn btn-sm btn-primary w-100" <?= (int) $item['stok'] < 1 ? 'disabled' : '' ?>><i class="bi bi-cart-plus me-1"></i><?= (int) $item['stok'] < 1 ? 'Stok habis' : 'Tambah ke keranjang' ?></button></form></div></article></div><?php endforeach; ?>
            </div><?php endif; ?>
    </section>

    <section class="mt-5" id="riwayat-belanja">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div><h2 class="section-title mb-1">Riwayat Belanja</h2><p class="text-secondary small mb-0">Transaksi terbaru yang tercatat pada akun Anda.</p></div>
            <?php if ($idPelanggan > 0): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('pelanggan/riwayat.php') ?>">Lihat semua riwayat <i class="bi bi-arrow-right ms-1"></i></a><?php endif; ?>
        </div>
        <?php if ($idPelanggan < 1): ?>
            <div class="alert alert-info">Akun ini belum terhubung ke profil pelanggan. Jalankan <code>setup_login.sql</code> atau hubungi admin toko.</div>
        <?php elseif (!$riwayat): ?>
            <div class="card panel"><div class="card-body text-center text-secondary py-4"><i class="bi bi-receipt fs-2 d-block mb-2"></i>Belum ada transaksi pada akun ini.</div></div>
        <?php else: ?>
            <div class="card panel"><div class="table-responsive"><table class="table align-middle mb-0">
                <thead class="table-light"><tr><th class="ps-3">No. pesanan</th><th>Tanggal</th><th>Total belanja</th><th class="text-end pe-3">Rincian</th></tr></thead>
                <tbody><?php foreach ($riwayat as $transaksi): ?><tr><td class="ps-3 fw-semibold">#<?= (int) $transaksi['id_transaksi'] ?></td><td><?= e(date('d/m/Y', strtotime($transaksi['tanggal']))) ?></td><td><?= rupiah($transaksi['total_belanja']) ?></td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="<?= url('pelanggan/detail_transaksi.php?id=' . (int) $transaksi['id_transaksi']) ?>"><i class="bi bi-eye me-1"></i>Lihat detail</a></td></tr><?php endforeach; ?></tbody>
            </table></div></div>
        <?php endif; ?>
    </section>
</div></main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>


<?php
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman kasir
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'kasir') {
    header('Location: ../login.php');
    exit;
}

$namaKasir = $_SESSION['user']['nama'] ?? 'Kasir';

if (!isset($_SESSION['cart_kasir'])) {
    $_SESSION['cart_kasir'] = [];
}

if (empty($_SESSION['csrf_kasir'])) {
    $_SESSION['csrf_kasir'] = bin2hex(random_bytes(32));
}

function kembali() {
    header('Location: index.php');
    exit;
}

function pesan($jenis, $isi) {
    $_SESSION['pesan_kasir'] = [
        'jenis' => $jenis,
        'isi' => $isi
    ];
}

// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf']) ||
        !hash_equals($_SESSION['csrf_kasir'], $_POST['csrf'])
    ) {
        pesan('danger', 'Permintaan tidak valid. Silakan coba kembali.');
        kembali();
    }

    $aksi = $_POST['aksi'] ?? '';

    // TAMBAH PRODUK
    if ($aksi === 'tambah') {
        $id = (int)($_POST['id_produk'] ?? 0);

        $stmt = $conn->prepare("
            SELECT id_produk, kode_produk, nama_produk, harga, stok, gambar
            FROM tbl_produk
            WHERE id_produk = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $produk = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$produk) {
            pesan('danger', 'Produk tidak ditemukan.');
            kembali();
        }

        if ((int)$produk['stok'] <= 0) {
            pesan('warning', 'Stok produk habis.');
            kembali();
        }

        $jumlahSekarang = $_SESSION['cart_kasir'][$id]['jumlah'] ?? 0;

        if ($jumlahSekarang + 1 > (int)$produk['stok']) {
            pesan('warning', 'Jumlah melebihi stok yang tersedia.');
            kembali();
        }

        $_SESSION['cart_kasir'][$id] = [
            'id_produk' => (int)$produk['id_produk'],
            'kode_produk' => $produk['kode_produk'],
            'nama_produk' => $produk['nama_produk'],
            'gambar' => $produk['gambar'],
            'harga' => (float)$produk['harga'],
            'stok' => (int)$produk['stok'],
            'jumlah' => $jumlahSekarang + 1
        ];

        pesan('success', 'Produk berhasil ditambahkan ke keranjang.');
        kembali();
    }

    // UPDATE JUMLAH
    if ($aksi === 'update') {
        $id = (int)($_POST['id_produk'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 1);

        if (!isset($_SESSION['cart_kasir'][$id])) {
            pesan('danger', 'Produk tidak ditemukan di keranjang.');
            kembali();
        }

        $stmt = $conn->prepare("
            SELECT stok, harga, nama_produk, kode_produk, gambar
            FROM tbl_produk
            WHERE id_produk = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $produk = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$produk || $jumlah < 1 || $jumlah > (int)$produk['stok']) {
            pesan('warning', 'Jumlah tidak valid atau stok tidak mencukupi.');
            kembali();
        }

        $_SESSION['cart_kasir'][$id]['jumlah'] = $jumlah;
        $_SESSION['cart_kasir'][$id]['stok'] = (int)$produk['stok'];
        $_SESSION['cart_kasir'][$id]['harga'] = (float)$produk['harga'];
        $_SESSION['cart_kasir'][$id]['nama_produk'] = $produk['nama_produk'];
        $_SESSION['cart_kasir'][$id]['kode_produk'] = $produk['kode_produk'];
        $_SESSION['cart_kasir'][$id]['gambar'] = $produk['gambar'];

        pesan('success', 'Jumlah produk berhasil diperbarui.');
        kembali();
    }

    // HAPUS PRODUK
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_produk'] ?? 0);

        unset($_SESSION['cart_kasir'][$id]);

        pesan('success', 'Produk dihapus dari keranjang.');
        kembali();
    }

    // KOSONGKAN KERANJANG
    if ($aksi === 'kosongkan') {
        $_SESSION['cart_kasir'] = [];

        pesan('success', 'Keranjang berhasil dikosongkan.');
        kembali();
    }

    // CHECKOUT
    if ($aksi === 'checkout') {

        $idPelanggan = (int)($_POST['id_pelanggan'] ?? 0);

        if (empty($_SESSION['cart_kasir'])) {
            pesan('warning', 'Keranjang masih kosong.');
            kembali();
        }

        if ($idPelanggan <= 0) {
            pesan('warning', 'Silakan pilih pelanggan.');
            kembali();
        }

        $stmtPelanggan = $conn->prepare("
            SELECT id_pelanggan
            FROM tbl_pelanggan
            WHERE id_pelanggan = ?
        ");

        $stmtPelanggan->bind_param("i", $idPelanggan);
        $stmtPelanggan->execute();
        $pelangganValid = $stmtPelanggan->get_result()->fetch_assoc();
        $stmtPelanggan->close();

        if (!$pelangganValid) {
            pesan('danger', 'Pelanggan tidak ditemukan.');
            kembali();
        }

        try {
            $conn->begin_transaction();

            $totalBelanja = 0;
            $detailProduk = [];

            // Kunci dan periksa stok terbaru
            $stmtProduk = $conn->prepare("
                SELECT id_produk, nama_produk, harga, stok
                FROM tbl_produk
                WHERE id_produk = ?
                FOR UPDATE
            ");

            foreach ($_SESSION['cart_kasir'] as $item) {
                $idProduk = (int)$item['id_produk'];
                $jumlah = (int)$item['jumlah'];

                $stmtProduk->bind_param("i", $idProduk);
                $stmtProduk->execute();
                $produk = $stmtProduk->get_result()->fetch_assoc();

                if (!$produk || $jumlah < 1 || $jumlah > (int)$produk['stok']) {
                    throw new Exception(
                        'Stok produk ' . ($produk['nama_produk'] ?? 'tidak diketahui')
                        . ' tidak mencukupi.'
                    );
                }

                $harga = (float)$produk['harga'];
                $subtotal = $harga * $jumlah;

                $totalBelanja += $subtotal;

                $detailProduk[] = [
                    'id_produk' => $idProduk,
                    'nama_produk' => $produk['nama_produk'],
                    'harga' => $harga,
                    'jumlah' => $jumlah
                ];
            }

            $stmtProduk->close();

            // Simpan transaksi
            $stmtTransaksi = $conn->prepare("
                INSERT INTO tbl_transaksi
                (tanggal, id_pelanggan, total_belanja)
                VALUES (NOW(), ?, ?)
            ");

            $stmtTransaksi->bind_param(
                "id",
                $idPelanggan,
                $totalBelanja
            );

            if (!$stmtTransaksi->execute()) {
                throw new Exception('Gagal menyimpan transaksi.');
            }

            $idTransaksi = $conn->insert_id;
            $stmtTransaksi->close();

            // Simpan detail dan kurangi stok
            $stmtDetail = $conn->prepare("
                INSERT INTO tbl_detail_transaksi
                (id_transaksi, nama_produk, jumlah, harga)
                VALUES (?, ?, ?, ?)
            ");

            $stmtStok = $conn->prepare("
                UPDATE tbl_produk
                SET stok = stok - ?
                WHERE id_produk = ? AND stok >= ?
            ");

            foreach ($detailProduk as $item) {
                $idProduk = $item['id_produk'];
                $namaProduk = $item['nama_produk'];
                $jumlah = $item['jumlah'];
                $harga = $item['harga'];

                $stmtDetail->bind_param(
                    "isid",
                    $idTransaksi,
                    $namaProduk,
                    $jumlah,
                    $harga
                );

                if (!$stmtDetail->execute()) {
                    throw new Exception('Gagal menyimpan detail transaksi.');
                }

                $stmtStok->bind_param(
                    "iii",
                    $jumlah,
                    $idProduk,
                    $jumlah
                );

                if (!$stmtStok->execute() || $stmtStok->affected_rows !== 1) {
                    throw new Exception('Gagal memperbarui stok produk.');
                }
            }

            $stmtDetail->close();
            $stmtStok->close();

            $conn->commit();

            $_SESSION['cart_kasir'] = [];
            $_SESSION['csrf_kasir'] = bin2hex(random_bytes(32));

            set_flash('success', 'Transaksi berhasil disimpan.');
            header('Location: detail.php?id=' . $idTransaksi);
            exit;

        } catch (Throwable $e) {
            $conn->rollback();

            pesan('danger', $e->getMessage());
            kembali();
        }
    }
}

// TOTAL KERANJANG
$totalBelanja = 0;
$totalItem = 0;

foreach ($_SESSION['cart_kasir'] as $item) {
    $totalBelanja += $item['harga'] * $item['jumlah'];
    $totalItem += $item['jumlah'];
}

// DATA PRODUK
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $keyword = '%' . $cari . '%';

    $stmt = $conn->prepare("
        SELECT *
        FROM tbl_produk
        WHERE nama_produk LIKE ?
           OR kode_produk LIKE ?
        ORDER BY nama_produk ASC
    ");

    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();
    $queryProduk = $stmt->get_result();

} else {
    $queryProduk = $conn->query("
        SELECT *
        FROM tbl_produk
        ORDER BY nama_produk ASC
    ");
}
$saranProduk = $conn->query("SELECT kode_produk, nama_produk FROM tbl_produk ORDER BY nama_produk LIMIT 250");

// DATA PELANGGAN
$queryPelanggan = $conn->query("
    SELECT id_pelanggan, nama_pelanggan
    FROM tbl_pelanggan
    ORDER BY nama_pelanggan ASC
");

$flash = $_SESSION['pesan_kasir'] ?? null;
unset($_SESSION['pesan_kasir']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Transaksi Penjualan | Tokoku</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>
        body {
            background: #f3f5fb;
            font-family: Inter, sans-serif;
            color: #1e293b;
        }

        .konten-kasir {
            margin-left: 250px;
            padding: 28px;
            min-height: 100vh;
        }

        .judul-halaman {
            font-weight: 800;
            color: #172554;
            font-size: 25px;
        }

        .subjudul {
            color: #94a3b8;
            font-size: 13px;
        }

        .panel {
            background: white;
            border: 1px solid #edf0f6;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(15,23,42,.035);
        }

        .panel-header {
            padding: 19px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 800;
        }

        .panel-body {
            padding: 20px;
        }

        .produk-card {
            background: white;
            border: 1px solid #e9edf5;
            border-radius: 14px;
            padding: 17px;
            height: 100%;
            transition: .2s;
        }

        .produk-card:hover {
            border-color: #818cf8;
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(99,102,241,.09);
        }

        .produk-icon {
            height: 95px;
            background: linear-gradient(135deg,#eef2ff,#f5f3ff);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6366f1;
            font-size: 42px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .produk-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 7px;
        }

        .produk-thumb-fallback {
            display: none;
        }

        .produk-card h6 {
            font-size: 13px;
            font-weight: 700;
            min-height: 34px;
            margin-bottom: 5px;
        }

        .produk-kode {
            font-size: 11px;
            color: #94a3b8;
        }

        .produk-harga {
            color: #4f46e5;
            font-weight: 800;
            font-size: 15px;
            margin: 12px 0;
        }

        .stok-label {
            font-size: 11px;
            color: #64748b;
        }

        .btn-tambah {
            width: 100%;
            background: #4f46e5;
            border: none;
            color: white;
            border-radius: 9px;
            padding: 9px;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-tambah:hover {
            background: #4338ca;
            color: white;
        }

        .btn-tambah:disabled {
            background: #cbd5e1;
        }

        .keranjang-header {
            background: linear-gradient(120deg,#312e81,#6366f1);
            color: white;
            padding: 20px;
            border-radius: 16px 16px 0 0;
        }

        .keranjang-header h5 {
            font-weight: 800;
            margin: 0;
        }

        .keranjang-body {
            padding: 18px;
        }

        .item-keranjang {
            border-bottom: 1px solid #f1f5f9;
            padding: 14px 0;
        }

        .item-keranjang:last-child {
            border-bottom: none;
        }

        .item-nama {
            font-size: 12px;
            font-weight: 700;
        }

        .item-harga {
            font-size: 11px;
            color: #64748b;
        }

        .input-jumlah {
            width: 65px;
            font-size: 12px;
            text-align: center;
        }

        .total-box {
            background: #f5f3ff;
            border: 1px solid #e0e7ff;
            border-radius: 12px;
            padding: 17px;
        }

        .total-box h3 {
            color: #4f46e5;
            font-weight: 800;
            margin: 8px 0 0;
        }

        .btn-checkout {
            width: 100%;
            padding: 14px;
            border: none;
            background: #16a34a;
            color: white;
            border-radius: 10px;
            font-weight: 800;
            transition: .2s;
        }

        .btn-checkout:hover {
            background: #15803d;
        }

        .btn-kosong {
            background: #fff1f2;
            color: #e11d48;
            border: 1px solid #ffe4e6;
            font-size: 12px;
        }

        .btn-kosong:hover {
            background: #ffe4e6;
            color: #be123c;
        }

        @media(max-width:768px) {
            .konten-kasir {
                margin-left: 210px;
                padding: 16px;
            }
        }

        @media(max-width:576px) {
            .konten-kasir {
                margin-left: 0;
                padding: 14px;
            }
        }
    </style>
</head>

<body>

<?php require_once '../../includes/sidebar_kasir.php'; ?>

<main class="konten-kasir">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="judul-halaman">Transaksi Penjualan</h3>
            <p class="subjudul mb-0">
                Kelola penjualan dan pembayaran pelanggan.
            </p>
        </div>

        <div class="text-secondary small">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars($namaKasir); ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['jenis']); ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['isi']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- DAFTAR PRODUK -->
        <div class="col-xl-8">

            <div class="panel">

                <div class="panel-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-grid me-2 text-primary"></i>
                        Pilih Produk
                    </span>

                    <span class="badge text-bg-primary">
                        <?= mysqli_num_rows($queryProduk); ?> Produk
                    </span>
                </div>

                <div class="panel-body">

                    <form method="GET" class="mb-4" data-auto-search>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>

                            <input type="text"
                                   name="cari"
                                   list="saranProdukKasir"
                                   data-search-input
                                   autocomplete="off"
                                   class="form-control"
                                   placeholder="Cari nama atau kode produk..."
                                   value="<?= htmlspecialchars($cari); ?>">

                            <datalist id="saranProdukKasir">
                                <?php while ($saran = $saranProduk->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($saran['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>" label="<?= htmlspecialchars($saran['kode_produk'], ENT_QUOTES, 'UTF-8'); ?>"></option>
                                    <option value="<?= htmlspecialchars($saran['kode_produk'], ENT_QUOTES, 'UTF-8'); ?>" label="<?= htmlspecialchars($saran['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>"></option>
                                <?php endwhile; ?>
                            </datalist>

                            <?php if ($cari !== ''): ?>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="row g-3">

                        <?php if (mysqli_num_rows($queryProduk) > 0): ?>

                            <?php while ($produk = mysqli_fetch_assoc($queryProduk)): ?>

                                <div class="col-6 col-md-4 col-xl-3">

                                    <div class="produk-card">

                                        <div class="produk-icon">
                                            <?php if (!empty($produk['gambar'])): ?>
                                                <img src="../../assets/img/produk/<?= rawurlencode(basename($produk['gambar'])); ?>"
                                                     alt="<?= htmlspecialchars($produk['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                <i class="bi bi-box-seam produk-thumb-fallback"></i>
                                            <?php else: ?>
                                                <i class="bi bi-box-seam"></i>
                                            <?php endif; ?>
                                        </div>

                                        <h6>
                                            <?= htmlspecialchars($produk['nama_produk']); ?>
                                        </h6>

                                        <div class="produk-kode">
                                            <?= htmlspecialchars($produk['kode_produk']); ?>
                                        </div>

                                        <div class="produk-harga">
                                            <?= rupiah($produk['harga']); ?>
                                        </div>

                                        <div class="stok-label mb-3">
                                            Stok:
                                            <strong><?= (int)$produk['stok']; ?></strong>
                                        </div>

                                        <form method="POST">
                                            <input type="hidden" name="csrf"
                                                   value="<?= $_SESSION['csrf_kasir']; ?>">
                                            <input type="hidden" name="aksi" value="tambah">
                                            <input type="hidden" name="id_produk"
                                                   value="<?= (int)$produk['id_produk']; ?>">

                                            <button class="btn-tambah"
                                                    <?= (int)$produk['stok'] <= 0 ? 'disabled' : ''; ?>>
                                                <i class="bi bi-plus-lg me-1"></i>
                                                <?= (int)$produk['stok'] <= 0 ? 'Stok Habis' : 'Tambah'; ?>
                                            </button>
                                        </form>

                                    </div>

                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="col-12 text-center py-5 text-secondary">
                                <i class="bi bi-search fs-1"></i>
                                <p class="mt-2">Produk tidak ditemukan.</p>
                            </div>

                        <?php endif; ?>

                    </div>

                </div>
            </div>

        </div>

        <!-- KERANJANG -->
        <div class="col-xl-4">

            <div class="panel">

                <div class="keranjang-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5>
                            <i class="bi bi-cart3 me-2"></i>
                            Keranjang
                        </h5>

                        <small><?= $totalItem; ?> item dipilih</small>
                    </div>

                    <span class="badge bg-white text-primary fs-6">
                        <?= count($_SESSION['cart_kasir']); ?>
                    </span>
                </div>

                <div class="keranjang-body">

                    <?php if (!empty($_SESSION['cart_kasir'])): ?>

                        <?php foreach ($_SESSION['cart_kasir'] as $item): ?>

                            <div class="item-keranjang">

                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <?php if (!empty($item['gambar'])): ?>
                                            <img src="../../assets/img/produk/<?= rawurlencode(basename($item['gambar'])); ?>"
                                                 alt="" width="44" height="44" class="rounded object-fit-contain bg-light float-start me-2"
                                                 onerror="this.style.display='none';">
                                        <?php endif; ?>
                                        <div class="item-nama">
                                            <?= htmlspecialchars($item['nama_produk']); ?>
                                        </div>

                                        <div class="item-harga">
                                            <?= rupiah($item['harga']); ?> / item
                                        </div>
                                    </div>

                                    <form method="POST">
                                        <input type="hidden" name="csrf"
                                               value="<?= $_SESSION['csrf_kasir']; ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_produk"
                                               value="<?= (int)$item['id_produk']; ?>">

                                        <button class="btn btn-sm text-danger"
                                                title="Hapus produk">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">

                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="csrf"
                                               value="<?= $_SESSION['csrf_kasir']; ?>">
                                        <input type="hidden" name="aksi" value="update">
                                        <input type="hidden" name="id_produk"
                                               value="<?= (int)$item['id_produk']; ?>">

                                        <input type="number"
                                               name="jumlah"
                                               class="form-control form-control-sm input-jumlah"
                                               min="1"
                                               max="<?= (int)$item['stok']; ?>"
                                               value="<?= (int)$item['jumlah']; ?>">

                                        <button class="btn btn-sm btn-outline-primary">
                                            Update
                                        </button>
                                    </form>

                                    <strong class="small">
                                        <?= rupiah($item['harga'] * $item['jumlah']); ?>
                                    </strong>

                                </div>

                            </div>

                        <?php endforeach; ?>

                        <form method="POST" class="mt-3">
                            <input type="hidden" name="csrf"
                                   value="<?= $_SESSION['csrf_kasir']; ?>">
                            <input type="hidden" name="aksi" value="kosongkan">

                            <button class="btn btn-kosong w-100"
                                    onclick="return confirm('Kosongkan semua keranjang?')">
                                <i class="bi bi-trash me-1"></i>
                                Kosongkan Keranjang
                            </button>
                        </form>

                    <?php else: ?>

                        <div class="text-center py-5">
                            <i class="bi bi-cart-x text-secondary" style="font-size:55px;"></i>
                            <h6 class="mt-3 fw-bold">Keranjang Kosong</h6>
                            <p class="text-secondary small">
                                Silakan pilih produk untuk memulai transaksi.
                            </p>
                        </div>

                    <?php endif; ?>

                    <hr class="my-4">

                    <div class="total-box mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary small">Total Item</span>
                            <strong><?= $totalItem; ?></strong>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-secondary small">Total Pembayaran</span>
                        </div>

                        <h3><?= rupiah($totalBelanja); ?></h3>
                    </div>

                    <!-- CHECKOUT -->
                    <form method="POST" id="formCheckout">

                        <input type="hidden" name="csrf"
                               value="<?= $_SESSION['csrf_kasir']; ?>">
                        <input type="hidden" name="aksi" value="checkout">

                        <label class="form-label fw-bold small">
                            Pilih Pelanggan
                        </label>

                        <select name="id_pelanggan" class="form-select mb-3" required>
                            <option value="">-- Pilih Pelanggan --</option>

                            <?php while ($pelanggan = mysqli_fetch_assoc($queryPelanggan)): ?>

                                <option value="<?= (int)$pelanggan['id_pelanggan']; ?>">
                                    <?= htmlspecialchars($pelanggan['nama_pelanggan']); ?>
                                </option>

                            <?php endwhile; ?>

                        </select>

                        <button type="submit"
                                class="btn-checkout"
                                <?= empty($_SESSION['cart_kasir']) ? 'disabled' : ''; ?>
                                onclick="return confirm('Proses transaksi sekarang?')">

                            <i class="bi bi-check-circle me-2"></i>
                            Proses Transaksi

                        </button>

                    </form>

                </div>
            </div>

        </div>

    </div>

    <div class="text-center text-secondary small mt-4">
        © <?= date('Y'); ?> Tokoku - Sistem Transaksi Kasir
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/app.js"></script>

</body>
</html>


<?php
require_once "../config/database.php";

$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'kasir'], true)) {
    redirect('login.php');
}
$canManage = $role === 'admin';
$csrfProduk = csrf_token('csrf_produk');

$pageTitle = "Data Barang";

$keyword = trim($_GET['keyword'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

$sql = "SELECT * FROM tbl_produk WHERE 1=1";
$params = [];
$types = "";

if ($keyword !== '') {
    $sql .= " AND (kode_produk LIKE ? OR nama_produk LIKE ?)";
    $search = "%{$keyword}%";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

if ($kategori !== '') {
    $sql .= " AND kategori = ?";
    $params[] = $kategori;
    $types .= "s";
}

$sql .= " ORDER BY id_produk DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$produk = $stmt->get_result();

$kategoriResult = $conn->query(
    "SELECT DISTINCT kategori
     FROM tbl_produk
     WHERE kategori IS NOT NULL AND kategori <> ''
     ORDER BY kategori"
);

$totalProduk = $conn->query(
    "SELECT COUNT(*) AS total FROM tbl_produk"
)->fetch_assoc()['total'];

$saranProduk = $conn->query(
    "SELECT kode_produk, nama_produk FROM tbl_produk ORDER BY nama_produk LIMIT 250"
);

if ($canManage) {
    include "../includes/header.php";
} else {
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Data Produk | Tokoku</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></head><body>';
    require_once "../includes/sidebar_kasir.php";
    echo '<main class="konten-kasir"><div class="container-fluid py-3">';
    show_flash();
}
?>

<style>
.product-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

.product-card {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 14px;
    overflow: hidden;
    transition: .25s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
}

.product-image {
    height: 210px;
    background: #f5f6f8;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 15px;
}

.product-placeholder {
    color: #adb5bd;
    font-size: 65px;
}

.product-discount {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #2563eb;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 9px;
    border-radius: 5px;
}

.product-info {
    padding: 16px;
}

.product-category {
    color: #84909e;
    font-size: 12px;
    margin-bottom: 7px;
}

.product-name {
    font-size: 15px;
    font-weight: 650;
    color: #202938;
    min-height: 42px;
    margin-bottom: 10px;
}

.product-price {
    font-size: 17px;
    font-weight: 750;
    color: #202938;
}

.product-stock {
    font-size: 12px;
    color: #6b7280;
    margin-top: 7px;
}

.product-actions {
    display: flex;
    gap: 7px;
    margin-top: 15px;
}

.product-actions .btn {
    flex: 1;
}

.product-rating {
    color: #f4b400;
    font-size: 13px;
    margin-top: 8px;
}

.load-more-wrap {
    text-align: center;
    margin: 30px 0;
}

.load-more-btn {
    min-width: 180px;
    background: #f1f3f5;
    border: none;
    padding: 11px 25px;
    border-radius: 8px;
    color: #343a40;
}

.load-more-btn:hover {
    background: #e2e6ea;
}

@media (max-width: 992px) {
    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .product-grid {
        grid-template-columns: 1fr;
    }

    .product-image {
        height: 230px;
    }
}
</style>

<div class="product-toolbar">

    <div>
        <h5 class="fw-bold mb-1">Katalog Barang</h5>
        <div class="text-secondary small">
            Kelola semua produk yang tersedia di toko.
            Total <?= (int)$totalProduk ?> produk.
        </div>
    </div>

    <?php if ($canManage): ?>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Tambah Barang
    </a>
    <?php endif; ?>

</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">

        <form method="GET" class="row g-2" data-auto-search data-search-delay="1200">

            <div class="col-md-6">
                <input
                    type="text"
                    name="keyword"
                    list="saranProduk"
                    data-search-input
                    autocomplete="off"
                    class="form-control"
                    placeholder="Cari nama atau kode barang..."
                    value="<?= e($keyword) ?>"
                >
                <datalist id="saranProduk">
                    <?php while ($saran = $saranProduk->fetch_assoc()): ?>
                        <option value="<?= e($saran['nama_produk']) ?>" label="<?= e($saran['kode_produk']) ?>"></option>
                        <option value="<?= e($saran['kode_produk']) ?>" label="<?= e($saran['nama_produk']) ?>"></option>
                    <?php endwhile; ?>
                </datalist>
            </div>

            <div class="col-md-3">
                <select name="kategori" class="form-select" data-search-filter>
                    <option value="">Semua Kategori</option>

                    <?php while ($kat = $kategoriResult->fetch_assoc()): ?>
                        <option
                            value="<?= e($kat['kategori']) ?>"
                            <?= $kategori === $kat['kategori'] ? 'selected' : '' ?>
                        >
                            <?= e($kat['kategori']) ?>
                        </option>
                    <?php endwhile; ?>

                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>

        </form>

    </div>
</div>

<?php if ($keyword !== ''): ?>
    <div class="small text-secondary mb-3">Hasil untuk <strong>&quot;<?= e($keyword) ?>&quot;</strong>: <?= (int) $produk->num_rows ?> barang.</div>
<?php endif; ?>

<div class="product-grid" id="productGrid">

    <?php if ($produk->num_rows > 0): ?>

        <?php while ($row = $produk->fetch_assoc()): ?>

            <div class="product-card">

                <div class="product-image">

                    <?php if (!empty($row['gambar'])): ?>

                        <img
                            src="<?= url('assets/img/produk/' . rawurlencode($row['gambar'])) ?>"
                            alt="<?= e($row['nama_produk']) ?>"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                        >

                        <i
                            class="bi bi-image product-placeholder"
                            style="display:none"
                        ></i>

                    <?php else: ?>

                        <i class="bi bi-image product-placeholder"></i>

                    <?php endif; ?>

                    <?php if ((int)$row['stok'] > 0): ?>
                        <span class="product-discount">
                            TERSEDIA
                        </span>
                    <?php else: ?>
                        <span class="product-discount bg-danger">
                            HABIS
                        </span>
                    <?php endif; ?>

                </div>

                <div class="product-info">

                    <div class="product-category">
                        <?= e($row['kategori'] ?: 'Tanpa kategori') ?>
                        · <?= e($row['kode_produk']) ?>
                    </div>

                    <div class="product-name">
                        <?= e($row['nama_produk']) ?>
                    </div>

                    <div class="product-price">
                        <?= rupiah($row['harga']) ?>
                    </div>

                    <div class="product-rating">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star"></i>
                        <i class="bi bi-star"></i>
                    </div>

                    <div class="product-stock">
                        <i class="bi bi-box-seam me-1"></i>
                        Stok: <?= (int)$row['stok'] ?> barang
                    </div>

                    <?php if (!$canManage): ?>
                    <form action="tambah_stok.php" method="post" class="d-flex gap-2 mt-3">
                        <input type="hidden" name="csrf_produk" value="<?= e($csrfProduk) ?>">
                        <input type="hidden" name="id_produk" value="<?= (int) $row['id_produk'] ?>">
                        <input type="number" name="jumlah" class="form-control form-control-sm" min="1" max="100000" placeholder="Jumlah" aria-label="Jumlah stok yang ditambahkan" required>
                        <button class="btn btn-success btn-sm text-nowrap" type="submit"><i class="bi bi-plus-lg me-1"></i>Tambah Stok</button>
                    </form>
                    <?php endif; ?>

                    <?php if ($canManage): ?>
                    <div class="product-actions">

                        <a
                            href="edit.php?id=<?= (int)$row['id_produk'] ?>"
                            class="btn btn-warning btn-sm"
                        >
                            <i class="bi bi-pencil"></i> Edit
                        </a>

                        <form
                            action="hapus.php"
                            method="POST"
                            class="flex-fill"
                            onsubmit="return confirm('Yakin ingin menghapus barang ini?')"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$row['id_produk'] ?>"
                            >
                            <input type="hidden" name="csrf_produk" value="<?= e($csrfProduk) ?>">

                            <button class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-trash"></i> Hapus
                            </button>

                        </form>

                    </div>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="text-center py-5">
            <i class="bi bi-box-seam fs-1 text-secondary"></i>
            <p class="text-secondary mt-2">
                Barang tidak ditemukan.
            </p>
        </div>

    <?php endif; ?>

</div>

<div class="load-more-wrap" id="loadMoreWrap">
    <button class="load-more-btn" id="loadMoreBtn">
        Load More
        <i class="bi bi-chevron-down ms-1"></i>
    </button>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const cards = document.querySelectorAll(".product-card");
    const button = document.getElementById("loadMoreBtn");
    const wrapper = document.getElementById("loadMoreWrap");

    let visible = 9;
    const step = 6;

    function updateCards() {
        cards.forEach((card, index) => {
            card.style.display = index < visible ? "block" : "none";
        });

        wrapper.style.display =
            visible >= cards.length ? "none" : "block";
    }

    if (button) {
        updateCards();

        button.addEventListener("click", function () {
            visible += step;
            updateCards();
        });
    }
});
</script>

<?php if ($canManage): ?>
    <?php include "../includes/footer.php"; ?>
<?php else: ?>
    </div></main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= url('assets/js/app.js') ?>"></script>
    </body></html>
<?php endif; ?>

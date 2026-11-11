
<?php

require_once "../config/database.php";
require_once "../config/upload_produk.php";

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect('produk/index.php');
}

$pageTitle = "Tambah Barang";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_produk'] ?? '', 'csrf_produk')) {
        set_flash('danger', 'Sesi formulir tidak valid. Silakan coba kembali.');
        redirect('produk/tambah.php');
    }

    $kode = trim($_POST['kode_produk'] ?? '');
    $nama = trim($_POST['nama_produk'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $harga = (float) ($_POST['harga'] ?? 0);
    $stok = (int) ($_POST['stok'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    $gambar = null;

    try {

        if ($kode === '' || $nama === '' || $harga < 0 || $stok < 0) {
            throw new RuntimeException('Lengkapi data barang dengan benar.');
        }

        // Upload foto jika dipilih.
        if (
            isset($_FILES['gambar']) &&
            $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $gambar = uploadGambarProduk($_FILES['gambar']);
        }

        $stmt = $conn->prepare("
            INSERT INTO tbl_produk
            (
                kode_produk,
                nama_produk,
                kategori,
                harga,
                stok,
                deskripsi,
                gambar
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssdiss",
            $kode,
            $nama,
            $kategori,
            $harga,
            $stok,
            $deskripsi,
            $gambar
        );

        $stmt->execute();

        set_flash('success', 'Barang berhasil ditambahkan.');
        redirect('produk/index.php');

    } catch (Throwable $e) {

        if ($gambar !== null) {
            hapusFotoProduk($gambar);
        }

        set_flash('danger', $e instanceof mysqli_sql_exception
            ? 'Gagal menyimpan barang. Periksa kode barang agar tidak duplikat.'
            : $e->getMessage()
        );
    }
}

include "../includes/header.php";
?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <h5 class="fw-bold mb-4">Form Tambah Barang</h5>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_produk" value="<?= e(csrf_token('csrf_produk')) ?>">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label">Kode Barang</label>
                    <input
                        type="text"
                        name="kode_produk"
                        class="form-control"
                        placeholder="Contoh: PRD011"
                        required
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Barang</label>
                    <input
                        type="text"
                        name="nama_produk"
                        class="form-control"
                        placeholder="Masukkan nama barang"
                        required
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Kategori</label>
                    <input
                        type="text"
                        name="kategori"
                        class="form-control"
                        placeholder="Contoh: Elektronik"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Harga</label>
                    <input
                        type="number"
                        name="harga"
                        class="form-control"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Stok</label>
                    <input
                        type="number"
                        name="stok"
                        class="form-control"
                        min="0"
                        required
                    >
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Foto Barang</label>

                    <input
                        type="file"
                        name="gambar"
                        id="gambar"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small class="text-secondary">
                        Format JPG, PNG, WEBP. Maksimal 2 MB.
                    </small>

                    <div class="mt-3">
                        <img
                            id="preview"
                            src=""
                            alt="Preview foto barang"
                            style="display:none; width:180px; height:150px; object-fit:contain; border:1px solid #ddd; border-radius:10px; padding:5px;"
                        >
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea
                        name="deskripsi"
                        class="form-control"
                        rows="3"
                        placeholder="Deskripsi barang..."
                    ></textarea>
                </div>

            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Barang
            </button>

            <a href="index.php" class="btn btn-secondary">
                Kembali
            </a>

        </form>

    </div>
</div>

<script>
document.getElementById('gambar').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('preview');

    if (!file) {
        preview.src = '';
        preview.style.display = 'none';
        return;
    }

    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
});
</script>

<?php include "../includes/footer.php"; ?>

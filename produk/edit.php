
<?php

require_once "../config/database.php";
require_once "../config/upload_produk.php";

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect('produk/index.php');
}

$pageTitle = "Edit Barang";

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT * FROM tbl_produk WHERE id_produk = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    set_flash('danger', 'Barang tidak ditemukan.');
    redirect('produk/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_produk'] ?? '', 'csrf_produk')) {
        set_flash('danger', 'Sesi formulir tidak valid. Silakan coba kembali.');
        redirect('produk/edit.php?id=' . $id);
    }

    $kode = trim($_POST['kode_produk'] ?? '');
    $nama = trim($_POST['nama_produk'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $harga = (float) ($_POST['harga'] ?? 0);
    $stok = (int) ($_POST['stok'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    $gambarBaru = null;
    $gambarLama = $data['gambar'];

    try {

        if ($kode === '' || $nama === '' || $harga < 0 || $stok < 0) {
            throw new RuntimeException('Data barang tidak valid.');
        }

        // Foto lama tetap dipakai jika tidak upload foto baru.
        $gambar = $gambarLama;

        if (
            isset($_FILES['gambar']) &&
            $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $gambarBaru = uploadGambarProduk($_FILES['gambar']);
            $gambar = $gambarBaru;
        }

        $stmt = $conn->prepare("
            UPDATE tbl_produk
            SET
                kode_produk = ?,
                nama_produk = ?,
                kategori = ?,
                harga = ?,
                stok = ?,
                deskripsi = ?,
                gambar = ?
            WHERE id_produk = ?
        ");

        $stmt->bind_param(
            "sssdissi",
            $kode,
            $nama,
            $kategori,
            $harga,
            $stok,
            $deskripsi,
            $gambar,
            $id
        );

        $stmt->execute();

        // Hapus foto lama setelah update berhasil.
        if ($gambarBaru !== null && $gambarLama !== null) {
            hapusFotoProduk($gambarLama);
        }

        set_flash('success', 'Data barang berhasil diperbarui.');
        redirect('produk/index.php');

    } catch (Throwable $e) {

        // Jika update gagal, hapus file baru yang sempat diunggah.
        if ($gambarBaru !== null) {
            hapusFotoProduk($gambarBaru);
        }

        set_flash(
            'danger',
            $e instanceof mysqli_sql_exception
                ? 'Gagal memperbarui barang. Periksa kode barang.'
                : $e->getMessage()
        );
    }
}

include "../includes/header.php";
?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <h5 class="fw-bold mb-4">Form Edit Barang</h5>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_produk" value="<?= e(csrf_token('csrf_produk')) ?>">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label">Kode Barang</label>
                    <input
                        type="text"
                        name="kode_produk"
                        class="form-control"
                        value="<?= e($data['kode_produk']) ?>"
                        required
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Barang</label>
                    <input
                        type="text"
                        name="nama_produk"
                        class="form-control"
                        value="<?= e($data['nama_produk']) ?>"
                        required
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Kategori</label>
                    <input
                        type="text"
                        name="kategori"
                        class="form-control"
                        value="<?= e($data['kategori']) ?>"
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
                        value="<?= e($data['harga']) ?>"
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
                        value="<?= e($data['stok']) ?>"
                        required
                    >
                </div>

                <div class="col-12 mb-3">

                    <label class="form-label d-block">
                        Foto Barang Saat Ini
                    </label>

                    <?php if (!empty($data['gambar'])): ?>

                        <img
                            src="<?= url('assets/img/produk/' . rawurlencode($data['gambar'])) ?>"
                            alt="<?= e($data['nama_produk']) ?>"
                            style="width:180px;height:150px;object-fit:contain;border:1px solid #ddd;border-radius:10px;padding:5px;"
                        >

                    <?php else: ?>

                        <div class="text-secondary mb-2">
                            Belum ada foto barang.
                        </div>

                    <?php endif; ?>

                </div>

                <div class="col-12 mb-3">

                    <label class="form-label">
                        Ganti Foto Barang
                    </label>

                    <input
                        type="file"
                        name="gambar"
                        id="gambar"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small class="text-secondary">
                        Kosongkan jika tidak ingin mengganti foto.
                        Format JPG, PNG, WEBP. Maksimal 2 MB.
                    </small>

                    <div class="mt-3">
                        <img
                            id="preview"
                            src=""
                            alt="Preview foto baru"
                            style="display:none;width:180px;height:150px;object-fit:contain;border:1px solid #ddd;border-radius:10px;padding:5px;"
                        >
                    </div>

                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea
                        name="deskripsi"
                        class="form-control"
                        rows="3"
                    ><?= e($data['deskripsi']) ?></textarea>
                </div>

            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Perubahan
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

<?php
require_once __DIR__ . '/../../config/database.php';

if (($_SESSION['user']['role'] ?? '') !== 'kasir') {
    redirect('login.php');
}

$kataKunci = trim($_GET['q'] ?? '');
$tanggalMulai = trim($_GET['mulai'] ?? '');
$tanggalSelesai = trim($_GET['sampai'] ?? '');
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$perHalaman = 15;
$offset = ($halaman - 1) * $perHalaman;

$where = [];
$types = '';
$params = [];
if ($kataKunci !== '') {
    $where[] = '(CAST(t.id_transaksi AS CHAR) LIKE ? OR p.nama_pelanggan LIKE ?)';
    $term = '%' . $kataKunci . '%';
    $types .= 'ss';
    $params[] = $term;
    $params[] = $term;
}
if ($tanggalMulai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulai)) {
    $where[] = 'DATE(t.tanggal) >= ?';
    $types .= 's';
    $params[] = $tanggalMulai;
}
if ($tanggalSelesai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSelesai)) {
    $where[] = 'DATE(t.tanggal) <= ?';
    $types .= 's';
    $params[] = $tanggalSelesai;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $conn->prepare("SELECT COUNT(*) AS jumlah FROM tbl_transaksi t LEFT JOIN tbl_pelanggan p ON p.id_pelanggan = t.id_pelanggan $whereSql");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalData = (int) $countStmt->get_result()->fetch_assoc()['jumlah'];
$totalHalaman = max(1, (int) ceil($totalData / $perHalaman));
$countStmt->close();

$stmt = $conn->prepare("SELECT t.id_transaksi, t.tanggal, t.total_belanja, p.nama_pelanggan FROM tbl_transaksi t LEFT JOIN tbl_pelanggan p ON p.id_pelanggan = t.id_pelanggan $whereSql ORDER BY t.tanggal DESC, t.id_transaksi DESC LIMIT ? OFFSET ?");
$listTypes = $types . 'ii';
$listParams = [...$params, $perHalaman, $offset];
$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function urlRiwayat(int $halaman, string $q, string $mulai, string $sampai): string
{
    return 'riwayat.php?' . http_build_query([
        'halaman' => $halaman,
        'q' => $q,
        'mulai' => $mulai,
        'sampai' => $sampai
    ]);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Transaksi | Tokoku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f1f5f9}.konten-kasir{margin-left:250px;padding:28px}.panel{border:0;border-radius:16px;box-shadow:0 8px 28px #0f172a0a}.transaksi-row td{vertical-align:middle}
        @media(max-width:768px){.konten-kasir{margin-left:0;padding:16px}.sidebar-kasir{position:relative;width:100%;height:auto}.sidebar-kasir .profil-kasir{margin-top:12px}}
    </style>
</head>
<body>
<?php require __DIR__ . '/../../includes/sidebar_kasir.php'; ?>
<main class="konten-kasir">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">Riwayat Transaksi</h1><div class="text-secondary">Daftar penjualan yang sudah disimpan.</div></div>
        <a href="index.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Transaksi</a>
    </div>

    <section class="card panel mb-4"><div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label" for="q">Cari transaksi / pelanggan</label><input class="form-control" id="q" name="q" value="<?= e($kataKunci) ?>" placeholder="ID transaksi atau nama pelanggan"></div>
            <div class="col-sm-6 col-md-3"><label class="form-label" for="mulai">Dari tanggal</label><input class="form-control" type="date" id="mulai" name="mulai" value="<?= e($tanggalMulai) ?>"></div>
            <div class="col-sm-6 col-md-3"><label class="form-label" for="sampai">Sampai tanggal</label><input class="form-control" type="date" id="sampai" name="sampai" value="<?= e($tanggalSelesai) ?>"></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-dark flex-grow-1" type="submit"><i class="bi bi-search"></i> Cari</button><a class="btn btn-outline-secondary" href="riwayat.php" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a></div>
        </form>
    </div></section>

    <section class="card panel"><div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom"><h2 class="h6 fw-bold mb-0">Transaksi tersimpan</h2><span class="badge text-bg-primary"><?= number_format($totalData, 0, ',', '.') ?> transaksi</span></div>
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th class="ps-3">No.</th><th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Rincian</th></tr></thead>
            <tbody>
            <?php if (!$transaksi): ?>
                <tr><td colspan="6" class="text-center text-secondary py-5"><i class="bi bi-receipt fs-3 d-block mb-2"></i>Belum ada transaksi yang cocok.</td></tr>
            <?php else: ?>
                <?php foreach ($transaksi as $i => $item): ?>
                    <tr class="transaksi-row">
                        <td class="ps-3 text-secondary"><?= $offset + $i + 1 ?></td>
                        <td><span class="fw-semibold">#<?= (int) $item['id_transaksi'] ?></span></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($item['tanggal']))) ?></td>
                        <td><?= e($item['nama_pelanggan'] ?: 'Pelanggan umum') ?></td>
                        <td class="fw-semibold"><?= rupiah($item['total_belanja']) ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="detail.php?id=<?= (int) $item['id_transaksi'] ?>"><i class="bi bi-list-ul me-1"></i>Lihat detail transaksi</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table></div>
        <?php if ($totalHalaman > 1): ?><nav class="p-3" aria-label="Navigasi halaman"><ul class="pagination justify-content-end mb-0">
            <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(urlRiwayat(max(1, $halaman - 1), $kataKunci, $tanggalMulai, $tanggalSelesai)) ?>">Sebelumnya</a></li>
            <li class="page-item disabled"><span class="page-link">Halaman <?= $halaman ?> dari <?= $totalHalaman ?></span></li>
            <li class="page-item <?= $halaman >= $totalHalaman ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(urlRiwayat(min($totalHalaman, $halaman + 1), $kataKunci, $tanggalMulai, $tanggalSelesai)) ?>">Berikutnya</a></li>
        </ul></nav><?php endif; ?>
    </div></section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

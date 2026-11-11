<?php
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    exit('ID transaksi tidak valid.');
}

$stmt = $conn->prepare('SELECT t.id_transaksi, t.tanggal, t.total_belanja, p.nama_pelanggan, p.kota FROM tbl_transaksi t LEFT JOIN tbl_pelanggan p ON p.id_pelanggan = t.id_pelanggan WHERE t.id_transaksi = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$transaksi) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan.');
}

$stmt = $conn->prepare('SELECT nama_produk, jumlah, harga FROM tbl_detail_transaksi WHERE id_transaksi = ? ORDER BY id_detail');
$stmt->bind_param('i', $id);
$stmt->execute();
$detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$tanggal = date('d M Y, H:i', strtotime($transaksi['tanggal']));
$jumlahItem = array_sum(array_map(static fn($item) => (int) $item['jumlah'], $detail));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk #<?= (int) $transaksi['id_transaksi'] ?> | Toko GRATIS HARAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{min-height:100vh;background:#eef2f7;color:#172033;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
        .page-wrap{max-width:760px;margin:38px auto;padding:0 18px}
        .receipt{overflow:hidden;background:#fff;border:1px solid #e7ebf2;border-radius:22px;box-shadow:0 22px 60px #17255416}
        .receipt-head{position:relative;overflow:hidden;background:linear-gradient(125deg,#172554,#3730a3 58%,#4f46e5);color:#fff;padding:32px 38px}
        .receipt-head:after{content:"";position:absolute;width:230px;height:230px;border:1px solid #ffffff24;border-radius:50%;right:-50px;top:-135px;box-shadow:0 0 0 24px #ffffff0d,0 0 0 48px #ffffff08}
        .brand-mark{width:46px;height:46px;display:grid;place-items:center;border-radius:14px;background:#ffffff20;font-size:1.35rem}
        .receipt-body{padding:30px 38px}
        .receipt-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;padding:18px;background:#f8fafc;border:1px solid #edf0f5;border-radius:14px;margin:22px 0}
        .meta-label{font-size:.76rem;color:#64748b;margin-bottom:4px}.meta-value{font-weight:700;font-size:.92rem;overflow-wrap:anywhere}
        .receipt-table th{font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e8edf4;padding:12px 8px}
        .receipt-table td{padding:15px 8px;border-bottom:1px solid #f0f2f6;vertical-align:middle}
        .receipt-table tr:last-child td{border-bottom:0}.item-name{font-weight:700}.item-price{font-size:.8rem;color:#64748b;margin-top:3px}
        .total-box{margin-top:8px;padding:19px 20px;background:#f1f5ff;border:1px solid #e0e7ff;border-radius:15px}
        .receipt-foot{border-top:1px dashed #d7deea;margin-top:24px;padding-top:20px;text-align:center;color:#64748b;font-size:.88rem}
        .actions{display:flex;justify-content:center;gap:10px;margin-top:18px}
        @media(max-width:575px){.page-wrap{margin:18px auto;padding:0 10px}.receipt-head{padding:25px 22px}.receipt-body{padding:22px 18px}.receipt-meta{grid-template-columns:1fr 1fr;padding:14px}.receipt-meta>div:last-child{grid-column:1/-1}.receipt-table th,.receipt-table td{padding:11px 5px}.receipt-table{font-size:.86rem}}
        @page{size:58mm auto;margin:2mm}
        @media print{
            body{min-height:0;background:#fff;color:#000;font:10px Arial,sans-serif}
            .page-wrap{width:54mm;max-width:none;margin:0;padding:0}
            .receipt{border:0;border-radius:0;box-shadow:none}
            .receipt-head{padding:4mm 2mm;background:#fff!important;color:#000!important;border-bottom:1px dashed #000}
            .receipt-head:after{display:none}.brand-mark{display:none}
            .receipt-head h1{font-size:15px!important;color:#000!important}
            .receipt-head p,.receipt-head .text-white-50{color:#000!important}
            .receipt-body{padding:3mm 2mm}
            .receipt-meta{display:block;padding:0;margin:3mm 0;background:#fff;border:0;border-bottom:1px dashed #000;border-radius:0}
            .receipt-meta>div{display:flex;justify-content:space-between;gap:4px;margin-bottom:2mm}
            .meta-label,.meta-value{font-size:9px;color:#000}
            .receipt-table{font-size:9px}.receipt-table th{font-size:8px;color:#000;padding:2mm 0;border-bottom:1px dashed #000}
            .receipt-table td{padding:2mm 0;border-bottom:0}.item-price{font-size:8px;color:#000}
            .total-box{margin-top:2mm;padding:3mm 0;background:#fff;border:0;border-top:1px dashed #000;border-radius:0}
            .receipt-foot{margin-top:3mm;padding-top:3mm;border-top:1px dashed #000;color:#000;font-size:9px}
            .actions{display:none!important}
        }
    </style>
</head>
<body>
<main class="page-wrap">
    <article class="receipt">
        <header class="receipt-head">
            <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1">
                <div class="brand-mark"><i class="bi bi-shop-window"></i></div>
                <div><div class="small text-white-50 text-uppercase fw-semibold">Bukti pembayaran</div><h1 class="h3 fw-bold mb-1">TOKO GRATIS HARAM</h1><p class="mb-0 text-white-50">Terima kasih telah berbelanja</p></div>
            </div>
        </header>
        <div class="receipt-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div><div class="text-secondary small">Nomor transaksi</div><div class="h5 fw-bold mb-0">#<?= (int) $transaksi['id_transaksi'] ?></div></div>
                <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
            </div>

            <div class="receipt-meta">
                <div><div class="meta-label">Tanggal</div><div class="meta-value"><?= e($tanggal) ?></div></div>
                <div><div class="meta-label">Pelanggan</div><div class="meta-value"><?= e($transaksi['nama_pelanggan'] ?: 'Pelanggan umum') ?></div></div>
                <div><div class="meta-label">Jumlah item</div><div class="meta-value"><?= $jumlahItem ?> barang</div></div>
            </div>

            <h2 class="h6 fw-bold mb-2">Rincian belanja</h2>
            <div class="table-responsive"><table class="table receipt-table mb-0">
                <thead><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($detail as $item): $subtotal = (int) $item['jumlah'] * (float) $item['harga']; ?>
                    <tr><td><div class="item-name"><?= e($item['nama_produk']) ?></div><div class="item-price"><?= rupiah($item['harga']) ?> / item</div></td><td class="text-center fw-semibold"><?= (int) $item['jumlah'] ?></td><td class="text-end fw-semibold"><?= rupiah($subtotal) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>

            <div class="total-box"><div class="d-flex justify-content-between align-items-center"><span class="fw-semibold">Total pembayaran</span><strong class="fs-4 text-primary"><?= rupiah($transaksi['total_belanja']) ?></strong></div></div>
            <footer class="receipt-foot"><div class="fw-bold text-dark mb-1">Terima kasih!</div><div>Semoga hari Anda menyenangkan.</div></footer>
        </div>
    </article>

    <div class="actions">
        <button type="button" onclick="window.print()" class="btn btn-primary px-4"><i class="bi bi-printer me-2"></i>Cetak struk</button>
        <button type="button" onclick="keluar()" class="btn btn-outline-secondary px-4"><i class="bi bi-x-lg me-2"></i>Tutup</button>
    </div>
</main>
<script>
function keluar(){
    if(window.opener){window.close();return;}
    if(window.history.length>1){window.history.back();return;}
    window.location.href='index.php';
}
</script>
</body>
</html>

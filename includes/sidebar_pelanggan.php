<?php $menuPelangganAktif = $menuPelangganAktif ?? 'beranda'; ?>
<style>
    .sidebar-pelanggan{--sidebar-width:320px;--bs-offcanvas-width:min(340px,92vw);--bs-offcanvas-bg:#172554;--bs-offcanvas-color:#fff;width:var(--sidebar-width);background:#172554!important;color:#fff!important;z-index:1045;box-shadow:8px 0 28px #0f172a18}
    .sidebar-pelanggan .offcanvas-header{border-bottom:1px solid #ffffff20;min-height:82px}
    .sidebar-pelanggan .offcanvas-body{background:#172554;overflow-y:auto;gap:.75rem}
    .sidebar-pelanggan .brand{font-size:1.25rem;font-weight:800;color:#fff;text-decoration:none}
    .sidebar-pelanggan .nav-link{color:#dbe4f3!important;border-radius:10px;padding:.75rem .9rem;margin:.15rem 0;transition:background-color .18s ease,color .18s ease}
    .sidebar-pelanggan .nav-link:hover{background:#ffffff16;color:#fff!important}
    .sidebar-pelanggan .nav-link.active{background:#4f46e5;color:#fff!important;box-shadow:inset 3px 0 #c7d2fe}
    .sidebar-pelanggan .customer-profile{margin-top:auto;padding:1rem .5rem .25rem;border-top:1px solid #ffffff20;color:#e2e8f0}
    .sidebar-pelanggan .cart-sidebar{border-top:1px solid #ffffff24;padding-top:1rem;min-height:0}
    .sidebar-pelanggan .cart-items{max-height:24vh;overflow-y:auto}
    .sidebar-pelanggan .cart-line{border-bottom:1px solid #ffffff20;padding:.7rem 0}
    .sidebar-pelanggan .cart-line:last-child{border-bottom:0}
    .sidebar-pelanggan .cart-meta{color:#cbd5e1;font-size:.78rem}
    .sidebar-pelanggan .cart-total{background:#ffffff12;border-radius:10px;padding:.75rem}
    @media(min-width:992px){.sidebar-pelanggan{position:fixed!important;inset:0 auto 0 0;display:flex!important;flex-direction:column;visibility:visible!important;transform:none!important}.sidebar-pelanggan .offcanvas-header{display:block}.sidebar-pelanggan .btn-close{display:none}.customer-main{margin-left:320px}}
</style>
<button class="btn btn-primary d-lg-none m-3 mb-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarPelanggan" aria-controls="sidebarPelanggan"><i class="bi bi-list me-1"></i>Menu</button>
<aside class="offcanvas-lg offcanvas-start sidebar-pelanggan" tabindex="-1" id="sidebarPelanggan" aria-labelledby="sidebarPelangganLabel">
    <div class="offcanvas-header p-4"><a class="brand" id="sidebarPelangganLabel" href="<?= url('pelanggan/beranda.php') ?>"><i class="bi bi-shop-window me-2"></i>TOKOKU</a><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarPelanggan" aria-label="Tutup"></button></div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <div class="text-uppercase text-white-50 small fw-bold px-3 mb-2">Menu Pelanggan</div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $menuPelangganAktif === 'beranda' ? 'active' : '' ?>" href="beranda.php"><i class="bi bi-grid me-2"></i>Beranda</a>
        </nav>
        <?php if (isset($keranjang, $csrf, $jumlahItem, $totalKeranjang)): ?>
        <section class="cart-sidebar" aria-labelledby="cartSidebarTitle">
            <div class="d-flex justify-content-between align-items-center mb-2 px-1"><div><h2 class="h6 fw-bold mb-0" id="cartSidebarTitle"><i class="bi bi-cart3 me-2"></i>Keranjang</h2><small class="text-white-50"><?= (int) $jumlahItem ?> item</small></div><span class="badge rounded-pill text-bg-light text-primary"><?= count($keranjang) ?></span></div>
            <?php if (!$keranjang): ?><div class="small text-white-50 px-1 py-3">Keranjang masih kosong. Tambahkan produk dari katalog.</div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($keranjang as $item): ?>
                    <div class="cart-line">
                        <div class="d-flex justify-content-between gap-2 align-items-start"><div class="fw-semibold small"><?= e($item['nama_produk']) ?></div><form action="<?= url('pelanggan/beranda.php') ?>" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_produk" value="<?= (int) $item['id_produk'] ?>"><button class="btn btn-sm btn-link text-danger p-0" title="Hapus produk"><i class="bi bi-trash"></i></button></form></div>
                        <div class="cart-meta mb-2"><?= rupiah($item['harga']) ?> × <?= (int) $item['jumlah'] ?> = <strong class="text-white"><?= rupiah($item['subtotal']) ?></strong></div>
                        <form action="<?= url('pelanggan/beranda.php') ?>" method="post" class="d-flex gap-2"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="ubah_jumlah"><input type="hidden" name="id_produk" value="<?= (int) $item['id_produk'] ?>"><input class="form-control form-control-sm" type="number" name="jumlah" min="1" max="<?= (int) $item['stok'] ?>" value="<?= (int) $item['jumlah'] ?>" aria-label="Jumlah <?= e($item['nama_produk']) ?>"><button class="btn btn-sm btn-outline-light">Ubah</button></form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-total mt-2"><div class="d-flex justify-content-between align-items-center mb-2"><span class="small text-white-50">Total belanja</span><strong><?= rupiah($totalKeranjang) ?></strong></div>
                    <?php if ((int) ($_SESSION['user']['id_pelanggan'] ?? 0) > 0): ?><form action="<?= url('pelanggan/beranda.php') ?>" method="post" onsubmit="return confirm('Lanjutkan checkout sebesar <?= e(rupiah($totalKeranjang)) ?>?')"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="checkout"><button class="btn btn-success btn-sm w-100"><i class="bi bi-check2-circle me-1"></i>Checkout</button></form><?php else: ?><div class="small text-warning">Akun belum terhubung ke profil pelanggan.</div><?php endif; ?>
                    <form action="<?= url('pelanggan/beranda.php') ?>" method="post" class="mt-1" onsubmit="return confirm('Kosongkan keranjang?')"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="aksi" value="kosongkan"><button class="btn btn-sm btn-link text-white-50 w-100">Kosongkan keranjang</button></form>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        <div class="customer-profile"><div class="fw-semibold"><i class="bi bi-person-circle me-2"></i><?= e($_SESSION['user']['nama'] ?? 'Pelanggan') ?></div><div class="small text-white-50">Akun pelanggan</div><a class="nav-link mt-2 px-2" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></div>
    </div>
</aside>

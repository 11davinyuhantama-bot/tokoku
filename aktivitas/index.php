<?php
require_once '../config/database.php';$pageTitle='Log Aktivitas';
$logs=$conn->query("SELECT * FROM log_pelanggan ORDER BY waktu DESC");
$changes=$conn->query("SELECT * FROM log_perubahan_pelanggan ORDER BY waktu DESC");
include '../includes/header.php';?>
<div class="row g-3"><div class="col-xl-6"><div class="card content-card"><div class="card-body"><h5 class="fw-bold mb-3">Penambahan Pelanggan</h5><div class="table-responsive"><table class="table"><thead><tr><th>Aktivitas</th><th>ID Pelanggan</th><th>Waktu</th></tr></thead><tbody><?php if($logs->num_rows):while($r=$logs->fetch_assoc()):?><tr><td><?=e($r['aktivitas'])?></td><td><?=e($r['id_pelanggan'])?></td><td><?=e($r['waktu'])?></td></tr><?php endwhile;else:?><tr><td colspan="3">Belum ada log.</td></tr><?php endif;?></tbody></table></div></div></div></div>
<div class="col-xl-6"><div class="card content-card"><div class="card-body"><h5 class="fw-bold mb-3">Perubahan Data Pelanggan</h5><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Nama Lama</th><th>Nama Baru</th><th>Waktu</th></tr></thead><tbody><?php if($changes->num_rows):while($r=$changes->fetch_assoc()):?><tr><td><?=e($r['id_pelanggan'])?></td><td><?=e($r['nama_lama'])?></td><td><?=e($r['nama_baru'])?></td><td><?=e($r['waktu'])?></td></tr><?php endwhile;else:?><tr><td colspan="4">Belum ada log.</td></tr><?php endif;?></tbody></table></div></div></div></div></div>
<?php include '../includes/footer.php';?>

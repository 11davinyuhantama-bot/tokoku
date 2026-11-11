<?php
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    redirect($role === 'pelanggan' ? 'pelanggan/beranda.php' : ($role === 'kasir' ? 'kasir/index.php' : 'index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_register'] ?? '', 'csrf_register')) {
        $error = 'Sesi formulir berakhir. Muat ulang halaman lalu coba kembali.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $kota = trim($_POST['kota'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        if ($nama === '' || mb_strlen($nama) > 100) {
            $error = 'Nama wajib diisi dan maksimal 100 karakter.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) {
            $error = 'Username harus 4–50 karakter, hanya huruf, angka, titik, garis bawah, atau tanda hubung.';
        } elseif (mb_strlen($kota) > 50) {
            $error = 'Nama kota maksimal 50 karakter.';
        } elseif (strlen($password) < 8) {
            $error = 'Kata sandi harus minimal 8 karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi kata sandi tidak sama.';
        } else {
            try {
                $conn->begin_transaction();
                $stmtPelanggan = $conn->prepare('INSERT INTO tbl_pelanggan (nama_pelanggan, kota) VALUES (?, ?)');
                $kotaPelanggan = $kota !== '' ? $kota : 'Belum diisi';
                $stmtPelanggan->bind_param('ss', $nama, $kotaPelanggan);
                $stmtPelanggan->execute();
                $idPelanggan = $conn->insert_id;
                $stmtPelanggan->close();

                $hashPassword = password_hash($password, PASSWORD_DEFAULT);
                $role = 'pelanggan';
                $stmtUser = $conn->prepare('INSERT INTO tbl_user (nama, username, password, role, id_pelanggan) VALUES (?, ?, ?, ?, ?)');
                $stmtUser->bind_param('ssssi', $nama, $username, $hashPassword, $role, $idPelanggan);
                $stmtUser->execute();
                $idUser = $conn->insert_id;
                $stmtUser->close();
                $conn->commit();

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int) $idUser,
                    'nama' => $nama,
                    'username' => $username,
                    'role' => $role,
                    'id_pelanggan' => (int) $idPelanggan
                ];
                unset($_SESSION['csrf_register']);
                set_flash('success', 'Akun berhasil dibuat. Selamat datang di Tokoku!');
                redirect('pelanggan/beranda.php');
            } catch (mysqli_sql_exception $exception) {
                if ($conn->errno === 1062 || $exception->getCode() === 1062) {
                    $error = 'Username sudah digunakan. Silakan pilih username lain.';
                } else {
                    $error = 'Registrasi belum bisa diproses. Pastikan tabel akun dan pelanggan sudah disiapkan.';
                }
                if ($conn->errno !== 0) {
                    $conn->rollback();
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pelanggan | Tokoku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>body{min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#eef2ff,#f8fafc 55%,#e0f2fe)}.register-card{width:min(100% - 2rem,520px);border:0;border-radius:22px;box-shadow:0 22px 65px #17255420}.brand-icon{width:52px;height:52px;display:grid;place-items:center;border-radius:16px;background:#4f46e5;color:#fff;font-size:1.45rem}</style>
</head>
<body>
<main class="card register-card p-4 p-md-5">
    <div class="brand-icon mb-4"><i class="bi bi-person-plus"></i></div>
    <h1 class="h3 fw-bold mb-1">Buat akun pelanggan</h1>
    <p class="text-secondary mb-4">Daftar untuk melihat katalog dan riwayat belanja Anda.</p>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
        <input type="hidden" name="csrf_register" value="<?= e(csrf_token('csrf_register')) ?>">
        <div class="mb-3"><label class="form-label" for="nama">Nama lengkap</label><input class="form-control" id="nama" name="nama" maxlength="100" autocomplete="name" required value="<?= e($_POST['nama'] ?? '') ?>"></div>
        <div class="row g-3 mb-3"><div class="col-sm-6"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" minlength="4" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="username" required value="<?= e($_POST['username'] ?? '') ?>"><div class="form-text">4–50 karakter.</div></div><div class="col-sm-6"><label class="form-label" for="kota">Kota <span class="text-secondary">(opsional)</span></label><input class="form-control" id="kota" name="kota" maxlength="50" autocomplete="address-level2" value="<?= e($_POST['kota'] ?? '') ?>"></div></div>
        <div class="mb-3"><label class="form-label" for="password">Kata sandi</label><input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required></div>
        <div class="mb-4"><label class="form-label" for="konfirmasi_password">Ulangi kata sandi</label><input class="form-control" type="password" id="konfirmasi_password" name="konfirmasi_password" minlength="8" autocomplete="new-password" required></div>
        <button class="btn btn-primary w-100 py-2" type="submit">Daftar <i class="bi bi-arrow-right ms-1"></i></button>
    </form>
    <div class="text-center text-secondary small mt-4">Sudah punya akun? <a href="<?= url('login.php') ?>" class="text-decoration-none fw-semibold">Masuk</a></div>
</main>
</body>
</html>


<?php
require_once __DIR__ . '/config/database.php';

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, arahkan sesuai role
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];

    if ($role === 'admin') {
        redirect('index.php');
    } elseif ($role === 'kasir') {
        redirect('kasir/index.php');
    } elseif ($role === 'pelanggan') {
        redirect('pelanggan/beranda.php');
    }

    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {

        try {
            $stmt = $conn->prepare("
                SELECT
                    id_user,
                    nama,
                    username,
                    password,
                    role,
                    id_pelanggan
                FROM tbl_user
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->bind_param('s', $username);
            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();

            $stmt->close();

        } catch (mysqli_sql_exception $exception) {
            http_response_code(503);
            $error = 'Tabel akun belum tersedia. Silakan periksa database db_tokoko.';
            $user = null;
        }

        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {

            // Pastikan role valid
            $role = $user['role'];

            if (!in_array($role, ['admin', 'kasir', 'pelanggan'], true)) {
                $error = 'Role akun tidak dikenali.';
            } else {

                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id'       => (int) $user['id_user'],
                    'nama'     => $user['nama'],
                    'username' => $user['username'],
                    'role'     => $role,
                    'id_pelanggan' => $user['id_pelanggan'] !== null ? (int) $user['id_pelanggan'] : null
                ];

                // Arahkan berdasarkan role
                if ($role === 'admin') {
                    redirect('index.php');
                } elseif ($role === 'kasir') {
                    redirect('kasir/index.php');
                } elseif ($role === 'pelanggan') {
                    redirect('pelanggan/beranda.php');
                }

                exit;
            }

        } elseif ($error === '') {
            $error = 'Username atau kata sandi tidak sesuai.';
        }

    } else {
        $error = 'Username dan kata sandi wajib diisi.';
    }
}
?>

<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Masuk | Tokoku</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        href="<?= url('assets/css/style.css') ?>"
        rel="stylesheet"
    >

    <style>

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(
                135deg,
                #eef2ff,
                #f8fafc 55%,
                #e0f2fe
            );
        }

        .login-card {
            width: min(100% - 2rem, 440px);
            border: 0;
            border-radius: 22px;
            box-shadow: 0 22px 65px #17255420;
        }

        .brand-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: #4f46e5;
            color: white;
            font-size: 1.5rem;
        }

        .login-title {
            color: #1e293b;
        }

    </style>

</head>

<body>

<main class="card login-card p-4 p-md-5">

    <div class="brand-icon mb-4">
        <i class="bi bi-shop-window"></i>
    </div>

    <h1 class="h3 fw-bold login-title mb-1">
        Selamat Datang
    </h1>

    <p class="text-secondary mb-4">
        Masuk ke sistem penjualan Tokoku.
    </p>

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger" role="alert">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <form method="post" autocomplete="on">

        <div class="mb-3">

            <label class="form-label" for="username">
                Username
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-person"></i>
                </span>

                <input
                    class="form-control"
                    type="text"
                    id="username"
                    name="username"
                    required
                    autofocus
                    autocomplete="username"
                    value="<?= e($_POST['username'] ?? '') ?>"
                >

            </div>

        </div>

        <div class="mb-4">

            <label class="form-label" for="password">
                Kata Sandi
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>

                <input
                    class="form-control"
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >

            </div>

        </div>

        <button
            class="btn btn-primary w-100 py-2"
            type="submit"
        >
            Masuk
            <i class="bi bi-arrow-right ms-1"></i>
        </button>

    </form>

    <div class="text-secondary small mt-4">

        <i class="bi bi-shield-lock me-1"></i>

        Admin, kasir, dan pelanggan menggunakan halaman login yang sama.

    </div>

    <div class="text-center small mt-3">
        Belum punya akun pelanggan? <a href="<?= url('register.php') ?>" class="text-decoration-none fw-semibold">Daftar sekarang</a>
    </div>

</main>

</body>
</html>

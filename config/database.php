<?php

require_once __DIR__ . '/helpers.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "db_tokoko";
$port = 3306;

try {

    $conn = new mysqli(
        $host,
        $user,
        $password,
        $database,
        $port
    );

    $conn->set_charset("utf8mb4");

    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($currentPage, ['login.php', 'logout.php', 'register.php'], true) && empty($_SESSION['user'])) {
        redirect('login.php');
    }

    if (($_SESSION['user']['role'] ?? '') === 'pelanggan') {
        if (empty($_SESSION['user']['id_pelanggan']) && !empty($_SESSION['user']['id'])) {
            $profileStmt = $conn->prepare('SELECT id_pelanggan FROM tbl_user WHERE id_user = ? LIMIT 1');
            $profileStmt->bind_param('i', $_SESSION['user']['id']);
            $profileStmt->execute();
            $profile = $profileStmt->get_result()->fetch_assoc();
            $profileStmt->close();
            $_SESSION['user']['id_pelanggan'] = !empty($profile['id_pelanggan']) ? (int) $profile['id_pelanggan'] : null;
        }

        $currentScript = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH);
        $allowedCustomerPages = [
            url('login.php'),
            url('logout.php'),
            url('pelanggan/beranda.php'),
            url('pelanggan/riwayat.php'),
            url('pelanggan/detail_transaksi.php')
        ];
        if (!in_array($currentScript, $allowedCustomerPages, true)) {
            redirect('pelanggan/beranda.php');
        }
    }

} catch (mysqli_sql_exception $e) {

    http_response_code(500);

    die(
        "Koneksi database gagal. " .
        "Pastikan MySQL aktif dan database db_tokoko sudah di-import. " .
        e($e->getMessage())
    );
}

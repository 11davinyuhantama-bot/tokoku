<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Menentukan alamat utama aplikasi secara otomatis
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

$folders = [
    'pelanggan',
    'transaksi',
    'cabang',
    'laporan',
    'aktivitas',
    'produk',
    'kasir'
];

$lastFolder = basename($scriptDir);
while (in_array($lastFolder, $folders, true)) {
    $scriptDir = dirname($scriptDir);
    $lastFolder = basename($scriptDir);
}

$basePath = rtrim($scriptDir, '/');

if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $basePath);
}

// Membuat URL aplikasi
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// Mengamankan output
function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format rupiah
function rupiah($value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

// Redirect halaman
function redirect(string $path): never
{
    if (preg_match('~^https?://~i', $path)) {
        header("Location: " . $path);
    } else {
        header("Location: " . url($path));
    }

    exit;
}

// Pesan notifikasi
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Menampilkan notifikasi
function show_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];

        $allowedTypes = [
            'success',
            'danger',
            'warning',
            'info',
            'primary'
        ];

        $type = in_array($flash['type'], $allowedTypes, true)
            ? $flash['type']
            : 'info';

        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show">';
        echo e($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';

        unset($_SESSION['flash']);
    }
}

function csrf_token(string $key = 'csrf_token'): string
{
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$key];
}

function verify_csrf_token(string $submitted, string $key = 'csrf_token'): bool
{
    return isset($_SESSION[$key])
        && $submitted !== ''
        && hash_equals($_SESSION[$key], $submitted);
}

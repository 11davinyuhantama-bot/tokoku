
<?php
require_once 'config/database.php';

$akun = [
    [
        'Administrator',
        'admin',
        'admin123',
        'admin'
    ],
    [
        'Kasir Toko',
        'kasir',
        'kasir123',
        'kasir'
    ],
    [
        'Pelanggan Demo',
        'pelanggan',
        'pelanggan123',
        'pelanggan'
    ]
];

$stmt = $conn->prepare("
    INSERT INTO tbl_user
        (nama, username, password, role)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        nama = VALUES(nama),
        password = VALUES(password),
        role = VALUES(role)
");

foreach ($akun as $data) {

    $nama = $data[0];
    $username = $data[1];
    $password = password_hash($data[2], PASSWORD_DEFAULT);
    $role = $data[3];

    $stmt->bind_param(
        "ssss",
        $nama,
        $username,
        $password,
        $role
    );

    $stmt->execute();
}

$stmt->close();

echo "Tiga akun berhasil dibuat!";

Sistem Informasi Penjualan Toko Berbasis PHP Native
Aplikasi Sistem Informasi Penjualan Toko merupakan aplikasi yang dikembangkan menggunakan PHP Native sebagai backend, HTML, CSS, dan JavaScript sebagai frontend. Aplikasi menggunakan database MySQL/MariaDB dengan nama db_tokoku.
1. Teknologi yang Digunakan
- Backend: PHP Native
- Frontend: HTML, CSS, JavaScript
- Framework CSS: Bootstrap 5 (opsional)
- Database: MySQL / MariaDB
- Local Development: XAMPP
- Code Editor: Visual Studio Code
- Web Browser: Google Chrome / Microsoft Edge
2. Fitur dan Komponen Database
Database db_tokoku memiliki komponen berikut.

A. Tabel
Nama Tabel	Keterangan
tbl_pelanggan	Menyimpan data pelanggan
tbl_transaksi	Menyimpan data transaksi penjualan
tbl_detail_transaksi	Menyimpan detail produk pada transaksi
tbl_cabang	Menyimpan data cabang
log_pelanggan	Mencatat aktivitas penambahan pelanggan
log_perubahan_pelanggan	Mencatat perubahan nama pelanggan

B. View
1. view_pelanggan – Menampilkan data pelanggan.
2. view_transaksi_pelanggan – Menampilkan transaksi beserta data pelanggan.
3. view_total_belanja – Menampilkan jumlah transaksi dan total belanja setiap pelanggan.
C. Stored Procedure
1. TampilPelanggan
2. CariPelanggan
3. TambahPelanggan
4. HitungPelanggan
5. LaporanTransaksi
D. Stored Function
1. HitungDiskon
2. HitungPajak
3. HitungTotal
4. KategoriBelanja
5. KonversiNilai
E. Trigger
1. after_insert_pelanggan – Mencatat penambahan pelanggan ke log_pelanggan.
2. after_update_pelanggan – Mencatat perubahan nama pelanggan ke log_perubahan_pelanggan.
3. before_insert_transaksi – Menolak transaksi jika total_belanja bernilai negatif.
3. Persyaratan Instalasi
Pastikan komputer sudah memiliki:
- PHP versi yang sesuai.
- XAMPP (Apache dan MySQL/MariaDB).
- Visual Studio Code.
- Web Browser.
- Composer (opsional, jika menggunakan library PHP tambahan).
4. Cara Instalasi
Langkah 1. Membuat Folder Project

Buka folder:
C:\xampp\htdocs


Buat folder project dengan nama:
tokoku


Langkah 2. Membuat Database
1. Jalankan XAMPP.
2. Aktifkan Apache dan MySQL.
3. Buka browser.
4. Masuk ke:
http://localhost/phpmyadmin


5. Buat database baru bernama:
db_tokoku


Langkah 3. Import Database
1. Pilih database db_tokoku.
2. Klik menu Import.
3. Pilih file db_tokoku.sql.
4. Klik Import atau Go.
Import tersebut akan membuat tabel, data contoh, View, Stored Procedure, Function, dan Trigger yang tersedia dalam file SQL.

Langkah 4. Membuat Koneksi Database

Buat file koneksi.php.

Simpan di folder:
C:\xampp\htdocs\tokoku


Isi kode:
<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "db_tokoku";

$koneksi = mysqli_connect(
    $host,
    $user,
    $password,
    $database
);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

?>


Sesuaikan username, password, dan port jika konfigurasi MySQL berbeda.

Langkah 5. Membuat File Project

Buat struktur folder seperti berikut.
tokoku/
│
├── koneksi.php
├── index.php
├── login.php
├── logout.php
│
├── dashboard.php
│
├── pelanggan/
│   ├── index.php
│   ├── tambah.php
│   ├── edit.php
│   ├── hapus.php
│
├── transaksi/
│   ├── index.php
│   ├── tambah.php
│   ├── detail.php
│   └── laporan.php
│
├── cabang/
│   ├── index.php
│   ├── tambah.php
│   └── edit.php
│
├── laporan/
│   └── index.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   │
│   ├── js/
│   │   └── script.js
│   │
│   └── img/
│
└── db_tokoku.sql


Langkah 6. Menjalankan Aplikasi
1. Pastikan Apache dan MySQL aktif di XAMPP.
2. Buka Google Chrome.
3. Masukkan alamat:
http://localhost/tokoku/


4. Aplikasi Sistem Informasi Penjualan Toko akan ditampilkan.
5. Fitur Aplikasi
Fitur yang dapat dikembangkan dalam aplikasi:
No.	Fitur	Keterangan
1	Login	Login admin
2	Dashboard	Menampilkan ringkasan penjualan
3	Data Pelanggan	Tambah, tampil, edit, hapus pelanggan
4	Data Transaksi	Mengelola transaksi penjualan
5	Detail Transaksi	Menampilkan detail transaksi
6	Data Cabang	Mengelola data cabang
7	Laporan	Menampilkan laporan penjualan
8	Database	Menggunakan View, Procedure, Function, Trigger

6. Penggunaan Komponen Database
Aplikasi PHP Native akan terhubung dengan database menggunakan MySQLi.

Contoh menampilkan data pelanggan menggunakan PHP:
<?php

include "koneksi.php";

$query = mysqli_query(
    $koneksi,
    "SELECT * FROM view_pelanggan"
);

while ($data = mysqli_fetch_assoc($query)) {

?>

<tr>
    <td><?= htmlspecialchars($data['id_pelanggan']) ?></td>
    <td><?= htmlspecialchars($data['nama_pelanggan']) ?></td>
</tr>

<?php
}
?>


PHP juga dapat menjalankan Stored Procedure menggunakan:
<?php

include "koneksi.php";

$query = mysqli_query(
    $koneksi,
    "CALL TampilPelanggan()"
);

while ($data = mysqli_fetch_assoc($query)) {
    echo $data['nama_pelanggan'] . "<br>";
}

?>


7. Catatan
- Aplikasi menggunakan PHP Native, bukan Laravel.
- Frontend menggunakan HTML, CSS, JavaScript, dan Bootstrap.
- Database tetap menggunakan MySQL/MariaDB.
- Koneksi database menggunakan MySQLi.
- File SQL harus sesuai dengan struktur database project.
- Jangan mengimpor file SQL berulang kali pada database yang sudah berisi data penting.
- Gunakan prepared statement untuk query yang menerima input pengguna.
- Gunakan htmlspecialchars() untuk menampilkan data agar lebih aman.
8. Identitas Pengembang
- Nama: (Nama Pengembang)
- Kelas: (Kelas)
- Jurusan: Rekayasa Perangkat Lunak (RPL)

Perubahan teknologi:
Sebelumnya	Sekarang
Laravel	PHP Native
Vue.js	HTML, CSS, JavaScript
Composer	Tidak wajib
NPM	Tidak wajib
Laravel Artisan	Tidak digunakan
MySQL/MariaDB	MySQL/MariaDB
XAMPP	XAMPP

Catatan penting: Dokumen ini merupakan rancangan konversi teknologi. File SQL dan struktur kolom database tetap harus diperiksa agar seluruh halaman PHP sesuai dengan database yang sebenarnya.

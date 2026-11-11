# 🛒 SISTEM TOKO ELEKTRONIK GRATIS

**Aplikasi Sistem Informasi Penjualan Toko Berbasis PHP Native, MySQL/MariaDB, Bootstrap 5.**

---

## 📋 PERSYARATAN SISTEM

Pastikan perangkat sudah memiliki:

- 🖥️ XAMPP dengan Apache dan MySQL/MariaDB aktif
- 🐘 PHP 8.1 atau lebih baru
- 🌐 Browser modern (Chrome, Edge, Firefox)
- 📡 Koneksi internet untuk memuat Bootstrap CDN dan Bootstrap Icons

## ⚙️ INSTALASI

1. 📂 Ekstrak folder `tokoku_php_bootstrap` ke `C:\xampp\htdocs\`
2. 🚀 Buka XAMPP, aktifkan Apache dan MySQL.
3. 🌐 Buka [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. 🗄️ Buat database bernama `db_tokoku`.
5. 📥 Pilih database `db_tokoku` → Import → pilih file `db_tokoku.sql`.
6. 📁 Salin file SQL ke folder project jika ingin menyimpannya bersama project (tidak wajib).
7. 🔧 Periksa `config/database.php`. Konfigurasi default XAMPP:
   - Host: `127.0.0.1`
   - User: `root`
   - Password: kosong
   - Port: `3306`
8. ✅ Buka aplikasi melalui [http://localhost/tokoku_php_bootstrap/](http://localhost/tokoku_php_bootstrap/).

## ✨ FITUR APLIKASI

### 📊 Dashboard
- Statistik pelanggan.
- Statistik transaksi.
- Statistik cabang.

### 👥 Manajemen Pelanggan
- CRUD pelanggan.
- Tambah pelanggan menggunakan Stored Procedure `TambahPelanggan`.
- Edit pelanggan memicu trigger `after_update_pelanggan`.

### 🧾 Manajemen Transaksi
- Menampilkan data transaksi dari `view_transaksi_pelanggan`.
- Melihat detail produk dari `tbl_detail_transaksi`.

### 🏢 Manajemen Cabang
- Tambah cabang.
- Edit cabang.
- Hapus cabang.

### 📈 Laporan
- Rekap belanja dari `view_total_belanja`.
- Kategori belanja menggunakan Stored Function `KategoriBelanja`.

### 📜 Riwayat Perubahan
- Log pelanggan dari `log_pelanggan`.
- Riwayat perubahan pelanggan dari `log_perubahan_pelanggan`.

## 🔐 HAK AKSES PENGGUNA

Aplikasi menyediakan tiga jenis akun:

| 👤 Role | 🔑 Hak Akses |
|---|---|
| Admin | Mengelola data dan produk |
| Kasir | Melihat katalog produk |
| Pelanggan | Belanja dan melihat riwayat transaksi |

### 🔑 Akun Demo

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Kasir | `kasir` | `kasir123` |
| Pelanggan | `pelanggan` | `pelanggan123` |

## 🛍️ FITUR PELANGGAN

- 📝 Registrasi akun pelanggan.
- 🛒 Melihat katalog produk.
- 🧺 Menambahkan produk ke keranjang.
- 💳 Checkout pembelian.
- 📦 Melihat riwayat belanja pribadi.
- 📉 Stok otomatis berkurang setelah checkout berhasil.

## ⚠️ CATATAN PENTING

1. 🔐 Login tersedia untuk admin, kasir, dan pelanggan. Tabel akun serta akun demo dibuat dari `db_tokoku.sql`.
2. 🗄️ Database yang digunakan aplikasi disebut `db_tokoko` pada dump terbaru. Jika database sudah diimpor, pilih `db_tokoko` di phpMyAdmin lalu impor `setup_login.sql` agar peran admin, kasir, dan pelanggan tersedia.
3. 📦 Untuk menyiapkan katalog, pilih `db_tokoko` lalu impor `setup_produk.sql`.
4. 👨‍💻 Admin dapat menambah, mengubah, dan menghapus produk. Kasir dapat melihat katalog.
5. 🛍️ Pelanggan masuk melalui `pelanggan/beranda.php` untuk melihat katalog, mengisi keranjang, checkout, dan melihat riwayat belanja miliknya.
6. 📝 Registrasi tersedia melalui tautan Daftar di halaman login.
7. 🗂️ Database SQL yang diberikan tidak memiliki tabel produk terpisah. Rincian produk diambil dari `tbl_detail_transaksi`.
8. 🧾 Skema transaksi belum memiliki halaman input transaksi. Menu transaksi saat ini menampilkan transaksi yang sudah ada beserta detailnya.
9. 🚫 Penghapusan pelanggan yang sudah memiliki transaksi dapat ditolak oleh foreign key.
10. 🔧 Jika impor SQL gagal karena `DEFINER`, sesuaikan `DEFINER root@localhost` dengan akun MariaDB lokal.
11. ⚠️ Jangan mengimpor SQL berulang kali ke database yang sudah berisi data penting.

---

## 💻 TEKNOLOGI YANG DIGUNAKAN

- 🐘 PHP Native
- 🗄️ MySQL / MariaDB
- 🎨 Bootstrap 5
- 🖌️ Bootstrap Icons
- 🌐 HTML, CSS, JavaScript
- 🖥️ XAMPP

---

**© 2026 Sistem Toko Elektronik — Aplikasi Penjualan Berbasis Web.**

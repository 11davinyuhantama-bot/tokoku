SISTEM INFORMASI PENJUALAN TOKO - PHP NATIVE + BOOTSTRAP 5

PERSYARATAN
- XAMPP dengan Apache dan MySQL/MariaDB aktif
- PHP 8.1 atau lebih baru disarankan
- Browser modern
- Koneksi internet untuk memuat Bootstrap CDN dan Bootstrap Icons

INSTALASI
1. Ekstrak folder tokoku_php_bootstrap ke C:\xampp\htdocs\
2. Buka XAMPP, aktifkan Apache dan MySQL.
3. Buka http://localhost/phpmyadmin
4. Buat database bernama db_tokoku.
5. Pilih database db_tokoku > Import > pilih file db_tokoku.sql (file SQL dari pengguna).
6. Salin file SQL tersebut ke folder project jika ingin menyimpannya bersama project (tidak wajib).
7. Periksa config/database.php. Default XAMPP:
   host 127.0.0.1, user root, password kosong, port 3306.
8. Buka http://localhost/tokoku_php_bootstrap/

FITUR
- Dashboard statistik dari tbl_pelanggan, tbl_transaksi, tbl_cabang
- CRUD pelanggan. Tambah pelanggan memakai Stored Procedure TambahPelanggan.
- Edit pelanggan memicu trigger after_update_pelanggan.
- Data transaksi dari view_transaksi_pelanggan.
- Detail produk dari tbl_detail_transaksi.
- CRUD cabang.
- Laporan rekap dari view_total_belanja dan Stored Function KategoriBelanja.
- Riwayat trigger dari log_pelanggan dan log_perubahan_pelanggan.

CATATAN PENTING
- Login tersedia untuk admin, kasir, dan pelanggan. Tabel akun serta akun demo dibuat dari db_tokoku.sql.
- Akun demo: admin / admin123, kasir / kasir123, pelanggan / pelanggan123.
- Database yang dipakai aplikasi adalah db_tokoko (sesuai dump terbaru). Jika database sudah terlanjur diimpor, pilih db_tokoko di phpMyAdmin lalu impor setup_login.sql agar peran admin, kasir, dan pelanggan tersedia.
- Untuk menyiapkan katalog, pilih db_tokoko di phpMyAdmin lalu impor setup_produk.sql. Admin dapat menambah, mengubah, dan menghapus produk; kasir dapat melihat katalog.
- Akun pelanggan masuk ke pelanggan/beranda.php untuk melihat katalog, mengisi keranjang, checkout, dan melihat riwayat belanja miliknya. Stok otomatis dikurangi ketika checkout berhasil.
- Registrasi pelanggan tersedia melalui tautan Daftar di halaman login; pendaftaran membuat akun dan profil pelanggan sekaligus.
- Database SQL yang diberikan juga tidak memiliki tabel produk terpisah. Rincian produk diambil dari tbl_detail_transaksi.
- Skema transaksi yang diberikan belum memiliki halaman input transaksi. Menu transaksi saat ini menampilkan transaksi yang sudah ada beserta detailnya.
- Penghapusan pelanggan yang sudah memiliki transaksi dapat ditolak oleh foreign key.
- Jika impor SQL gagal karena DEFINER, buka file SQL dan sesuaikan DEFINER `root`@`localhost` dengan akun MariaDB lokal.
- Jangan impor SQL berulang kali ke database yang sudah berisi data penting.

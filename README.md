Sistem Informasi Penjualan Toko
Aplikasi sistem informasi penjualan toko yang dikembangkan menggunakan
Laravel sebagai backend dan Vue.js sebagai frontend. Aplikasi
menggunakan database MySQL/MariaDB dengan nama db_tokoku.
Teknologi yang Digunakan
- Backend: Laravel
- Frontend: Vue.js
- Database: MySQL / MariaDB
- Local Development: XAMPP
- Dependency Manager: Composer dan NPM
Fitur dan Komponen Database
Database db_tokoku memiliki komponen berikut berdasarkan file SQL:
Tabel
  Nama Tabel                  Keterangan
  tbl_pelanggan             Menyimpan data pelanggan
tbl_transaksi             Menyimpan data transaksi penjualan
tbl_detail_transaksi      Menyimpan detail produk pada transaksi
tbl_cabang                Menyimpan data cabang
log_pelanggan             Mencatat aktivitas penambahan pelanggan
log_perubahan_pelanggan   Mencatat perubahan nama pelanggan
View
- view_pelanggan --- menampilkan data pelanggan.
- view_transaksi_pelanggan --- menampilkan transaksi beserta data
  pelanggan.
- view_total_belanja --- menampilkan jumlah transaksi dan total
  belanja setiap pelanggan.
Stored Procedure
- TampilPelanggan
- CariPelanggan
- TambahPelanggan
- HitungPelanggan
- LaporanTransaksi
Stored Function
- HitungDiskon
- HitungPajak
- HitungTotal
- KategoriBelanja
- KonversiNilai
Trigger
- after_insert_pelanggan --- mencatat penambahan pelanggan ke
  log_pelanggan.
- after_update_pelanggan --- mencatat perubahan nama pelanggan ke
  log_perubahan_pelanggan.
- before_insert_transaksi --- menolak transaksi jika total_belanja
  bernilai negatif.
Persyaratan
Pastikan perangkat sudah memiliki:
- PHP yang sesuai dengan versi Laravel yang digunakan.
- Composer.
- Node.js dan NPM.
- MySQL atau MariaDB (dapat menggunakan XAMPP).
- Git (opsional).
Cara Instalasi
1. Clone atau salin project ke komputer:
   git clone URL_REPOSITORY
   cd nama-folder-project
   Jika project sudah tersedia di komputer, langsung buka folder
   project melalui terminal.
2. Instal dependency Laravel:
   composer install
3. Buat file konfigurasi environment:
   cp .env.example .env
   Pada Windows CMD, jika perintah cp tidak tersedia, gunakan:
   copy .env.example .env
4. Buat application key:
   php artisan key:generate
5. Buat database bernama db_tokoku melalui phpMyAdmin atau MariaDB.
6. Import file SQL database db_tokoku melalui phpMyAdmin:
   - Pilih database db_tokoku.
   - Buka menu Import.
   - Pilih file db_tokoku.sql.
   - Klik Import atau Go.
   Import SQL ini akan membuat struktur tabel, data contoh, View,
   Stored Procedure, Function, dan Trigger yang terdapat di dalam dump.
7. Atur koneksi database pada file .env:
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_tokoku
   DB_USERNAME=root
   DB_PASSWORD=
   Sesuaikan username, password, dan port dengan konfigurasi
   MySQL/MariaDB di komputer.
8. Instal dependency frontend:
   npm install
9. Jalankan aplikasi Laravel:
   php artisan serve
10. Jalankan Vite untuk frontend Vue.js pada terminal lain:
    npm run dev
11. Buka alamat aplikasi yang ditampilkan oleh Laravel, biasanya:
    http://127.0.0.1:8000
Struktur Folder
Struktur folder dapat berbeda sesuai konfigurasi project Laravel dan
Vue.js yang digunakan. Folder utama Laravel umumnya:
nama-folder-project/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
│   ├── js/
│   └── views/
├── routes/
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
└── package.json
Catatan
- Jangan menjalankan import SQL berulang kali pada database yang sudah
  berisi data penting karena dapat menyebabkan konflik tabel atau
  data.
- File SQL yang digunakan harus sesuai dengan database project.
- Jalankan php artisan migrate hanya jika project memang menggunakan
  migration Laravel untuk membuat atau memperbarui struktur database.
  Jika struktur database sudah dibuat melalui file SQL, jangan
  menjalankan migration yang membuat tabel dengan nama yang sama tanpa
  memeriksa isinya terlebih dahulu.
- Pastikan konfigurasi .env sesuai dengan server database lokal.
Pengembang
Isi bagian ini dengan nama pengembang atau anggota kelompok.
- Nama: \(Nama Pengembang\)
- Kelas: \(Kelas\)
- Jurusan: Rekayasa Perangkat Lunak (RPL)
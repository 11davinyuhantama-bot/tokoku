-- Pilih database db_tokoko di phpMyAdmin sebelum menjalankan file ini.
CREATE TABLE IF NOT EXISTS `tbl_produk` (
  `id_produk` int(11) NOT NULL AUTO_INCREMENT,
  `kode_produk` varchar(30) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_produk`),
  UNIQUE KEY `uq_tbl_produk_kode` (`kode_produk`),
  KEY `idx_tbl_produk_nama` (`nama_produk`),
  KEY `idx_tbl_produk_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Jalankan file ini pada database db_tokoko jika database sudah diimpor sebelumnya.
CREATE TABLE IF NOT EXISTS `tbl_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir','pelanggan','pembeli') NOT NULL DEFAULT 'pelanggan',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tbl_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT,
  MODIFY `role` enum('admin','kasir','pelanggan','pembeli') NOT NULL DEFAULT 'pelanggan';

ALTER TABLE `tbl_user`
  ADD COLUMN IF NOT EXISTS `id_pelanggan` int(11) DEFAULT NULL AFTER `role`;

UPDATE `tbl_user` SET `role` = 'pelanggan' WHERE `role` = 'pembeli';

INSERT INTO `tbl_user` (`nama`, `username`, `password`, `role`) VALUES
('Administrator', 'admin', '$2y$10$/F7OOdA66w.RCQ13VRAWiu1Q9ms8zry8PwPGMUIOMsx66I3PqfvKi', 'admin'),
('Kasir Toko', 'kasir', '$2y$10$xPG1AXnVS6siqtzHnOipfummRFIKyxiPSu67uTGMNCCQmVqyLeCQW', 'kasir'),
('Pelanggan Demo', 'pelanggan', '$2y$10$7siV.5qVT8.3Cj3YQQNqZeCG15ctYsS5CO94lWwdOgZ/TUDx.4qDe', 'pelanggan')
ON DUPLICATE KEY UPDATE
  `nama` = VALUES(`nama`), `password` = VALUES(`password`), `role` = VALUES(`role`);

INSERT INTO `tbl_pelanggan` (`nama_pelanggan`, `kota`)
SELECT `u`.`nama`, 'Belum diisi'
FROM `tbl_user` `u`
WHERE `u`.`username` = 'pelanggan'
  AND NOT EXISTS (
    SELECT 1 FROM `tbl_pelanggan` `p` WHERE `p`.`nama_pelanggan` = `u`.`nama`
  );

UPDATE `tbl_user` `u`
JOIN (
  SELECT `nama_pelanggan`, MIN(`id_pelanggan`) AS `id_pelanggan`
  FROM `tbl_pelanggan`
  GROUP BY `nama_pelanggan`
  HAVING COUNT(*) = 1
) `p` ON `p`.`nama_pelanggan` = `u`.`nama`
SET `u`.`id_pelanggan` = `p`.`id_pelanggan`
WHERE `u`.`role` = 'pelanggan' AND `u`.`id_pelanggan` IS NULL;

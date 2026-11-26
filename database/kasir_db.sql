-- =========================================================
-- kasir_db : database aplikasi kasir KasirJayaJayaAtma
-- Impor lewat phpMyAdmin (tab Import) atau:  mysql -u root < kasir_db.sql
-- Data transaksi contoh dibuat relatif terhadap tanggal hari ini.
-- =========================================================

CREATE DATABASE IF NOT EXISTS `kasir_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `kasir_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `tb_detail_penjualan`, `tb_penjualan`, `tb_produk`, `tb_pelanggan`, `tb_user`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `tb_user` (
  `UserID` INT NOT NULL AUTO_INCREMENT,
  `NamaUser` VARCHAR(25) NOT NULL,
  `username` VARCHAR(25) NOT NULL,
  `password` VARCHAR(255) NOT NULL COMMENT 'hash dari password_hash()',
  `role` ENUM('Admin','Petugas') NOT NULL DEFAULT 'Petugas',
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB;

CREATE TABLE `tb_pelanggan` (
  `PelangganID` INT NOT NULL AUTO_INCREMENT,
  `NamaPelanggan` VARCHAR(25) NOT NULL,
  `Alamat` TEXT NOT NULL,
  `NomorTelepon` VARCHAR(15) NOT NULL,
  PRIMARY KEY (`PelangganID`)
) ENGINE=InnoDB;

CREATE TABLE `tb_produk` (
  `ProdukID` INT NOT NULL AUTO_INCREMENT,
  `NamaProduk` VARCHAR(25) NOT NULL,
  `Harga` DECIMAL(10,2) NOT NULL,
  `Stok` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`ProdukID`)
) ENGINE=InnoDB;

-- PelangganID 0 = Pelanggan Umum (non-member), karena itu tidak memakai foreign key.
CREATE TABLE `tb_penjualan` (
  `PenjualanID` VARCHAR(11) NOT NULL COMMENT 'PJ + yymmdd + urutan 3 digit',
  `TanggalPenjualan` DATE NOT NULL,
  `TotalHarga` DECIMAL(10,2) NOT NULL,
  `PelangganID` INT NOT NULL DEFAULT 0,
  `UserID` INT NOT NULL,
  `Status` ENUM('Ongoing','Complete','Cancelled') NOT NULL DEFAULT 'Ongoing',
  PRIMARY KEY (`PenjualanID`),
  KEY `idx_tanggal` (`TanggalPenjualan`)
) ENGINE=InnoDB;

CREATE TABLE `tb_detail_penjualan` (
  `DetailID` INT NOT NULL AUTO_INCREMENT,
  `PenjualanID` VARCHAR(11) NOT NULL,
  `ProdukID` INT NOT NULL,
  `JumlahProduk` INT NOT NULL,
  `SubTotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`DetailID`),
  KEY `idx_penjualan` (`PenjualanID`),
  CONSTRAINT `fk_detail_penjualan` FOREIGN KEY (`PenjualanID`) REFERENCES `tb_penjualan` (`PenjualanID`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_produk` FOREIGN KEY (`ProdukID`) REFERENCES `tb_produk` (`ProdukID`)
) ENGINE=InnoDB;

-- Akun contoh. Password Admin: admin123, Petugas: petugas123
-- (hash SHA-512 crypt; otomatis diganti bcrypt oleh password_hash() saat login pertama)

INSERT INTO `tb_user` (`UserID`, `NamaUser`, `username`, `password`, `role`) VALUES
  (5, 'Marshall', 'RollBy0', '$6$KasirJJA2026adm$jqVxg0KDmCqawCq6SHuxtbOlxwf5rQgvd8lMknG4jmY1ZeitVqCWsTr703tfvY.BVrXa2n2BZvb.aPvLTKfEu0', 'Admin'),
  (6, 'Derren', 'derrenyip', '$6$KasirJJA2026adm$jqVxg0KDmCqawCq6SHuxtbOlxwf5rQgvd8lMknG4jmY1ZeitVqCWsTr703tfvY.BVrXa2n2BZvb.aPvLTKfEu0', 'Admin'),
  (7, 'Ivan', 'Ivan', '$6$KasirJJA2026pet$z/bNoB4XSMHhd9Pfio0dknJCpPAg54HipsAzAQHlsvnb/M5Pm91U.eIb0OUmbBn09PX7Nl906WSRf6L5kIq7z0', 'Petugas');

INSERT INTO `tb_pelanggan` (`PelangganID`, `NamaPelanggan`, `Alamat`, `NomorTelepon`) VALUES
  (1, 'Dimas Pratama', 'Jl. Melati No. 12', '081234567801'),
  (2, 'Siti Rahmawati', 'Jl. Kenanga No. 5', '081298765432'),
  (3, 'Andi Setiawan', 'Perum Griya Asri B-7', '085711223344'),
  (4, 'Rina Lestari', 'Jl. Mawar No. 21', '087788990011'),
  (5, 'Bagus Saputra', 'Jl. Anggrek No. 3', '082233445566');

INSERT INTO `tb_produk` (`ProdukID`, `NamaProduk`, `Harga`, `Stok`) VALUES
  (1, 'Beras Premium 5kg', 72000, 24),
  (2, 'Minyak Goreng 1L', 18500, 4),
  (3, 'Gula Pasir 1kg', 16500, 9),
  (4, 'Telur Ayam 1kg', 29000, 18),
  (5, 'Kopi Instan 10 Sachet', 14500, 7),
  (6, 'Mi Instan Goreng', 3500, 120),
  (7, 'Susu UHT 1L', 19500, 32),
  (8, 'Air Mineral 600ml', 4000, 96),
  (9, 'Sabun Mandi Batang', 5500, 40),
  (10, 'Roti Tawar', 16000, 12);

-- Transaksi contoh 7 hari terakhir
SET @d0 = CURDATE() - INTERVAL 0 DAY;
SET @d1 = CURDATE() - INTERVAL 1 DAY;
SET @d2 = CURDATE() - INTERVAL 2 DAY;
SET @d3 = CURDATE() - INTERVAL 3 DAY;
SET @d4 = CURDATE() - INTERVAL 4 DAY;
SET @d5 = CURDATE() - INTERVAL 5 DAY;
SET @d6 = CURDATE() - INTERVAL 6 DAY;

INSERT INTO `tb_penjualan` (`PenjualanID`, `TanggalPenjualan`, `TotalHarga`, `PelangganID`, `UserID`, `Status`) VALUES
  (CONCAT('PJ', DATE_FORMAT(@d6, '%y%m%d'), '001'), @d6, 107000, 1, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d6, '%y%m%d'), '002'), @d6, 40000, 0, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d5, '%y%m%d'), '001'), @d5, 116500, 2, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d5, '%y%m%d'), '002'), @d5, 29000, 3, 7, 'Cancelled'),
  (CONCAT('PJ', DATE_FORMAT(@d4, '%y%m%d'), '001'), @d4, 106000, 0, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d3, '%y%m%d'), '001'), @d3, 166000, 4, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d3, '%y%m%d'), '002'), @d3, 71000, 1, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d2, '%y%m%d'), '001'), @d2, 76000, 0, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d2, '%y%m%d'), '002'), @d2, 60000, 2, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d1, '%y%m%d'), '001'), @d1, 187000, 3, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d1, '%y%m%d'), '002'), @d1, 32500, 0, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '001'), @d0, 71000, 1, 7, 'Complete'),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '002'), @d0, 101000, 4, 7, 'Ongoing'),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '003'), @d0, 43500, 0, 7, 'Complete');

INSERT INTO `tb_detail_penjualan` (`PenjualanID`, `ProdukID`, `JumlahProduk`, `SubTotal`) VALUES
  (CONCAT('PJ', DATE_FORMAT(@d6, '%y%m%d'), '001'), 1, 1, 72000),
  (CONCAT('PJ', DATE_FORMAT(@d6, '%y%m%d'), '001'), 6, 10, 35000),
  (CONCAT('PJ', DATE_FORMAT(@d6, '%y%m%d'), '002'), 8, 6, 24000),
  (CONCAT('PJ', DATE_FORMAT(@d6, '%y%m%d'), '002'), 10, 1, 16000),
  (CONCAT('PJ', DATE_FORMAT(@d5, '%y%m%d'), '001'), 4, 2, 58000),
  (CONCAT('PJ', DATE_FORMAT(@d5, '%y%m%d'), '001'), 7, 3, 58500),
  (CONCAT('PJ', DATE_FORMAT(@d5, '%y%m%d'), '002'), 5, 2, 29000),
  (CONCAT('PJ', DATE_FORMAT(@d4, '%y%m%d'), '001'), 2, 3, 55500),
  (CONCAT('PJ', DATE_FORMAT(@d4, '%y%m%d'), '001'), 3, 2, 33000),
  (CONCAT('PJ', DATE_FORMAT(@d4, '%y%m%d'), '001'), 6, 5, 17500),
  (CONCAT('PJ', DATE_FORMAT(@d3, '%y%m%d'), '001'), 1, 2, 144000),
  (CONCAT('PJ', DATE_FORMAT(@d3, '%y%m%d'), '001'), 9, 4, 22000),
  (CONCAT('PJ', DATE_FORMAT(@d3, '%y%m%d'), '002'), 7, 2, 39000),
  (CONCAT('PJ', DATE_FORMAT(@d3, '%y%m%d'), '002'), 10, 2, 32000),
  (CONCAT('PJ', DATE_FORMAT(@d2, '%y%m%d'), '001'), 8, 12, 48000),
  (CONCAT('PJ', DATE_FORMAT(@d2, '%y%m%d'), '001'), 6, 8, 28000),
  (CONCAT('PJ', DATE_FORMAT(@d2, '%y%m%d'), '002'), 4, 1, 29000),
  (CONCAT('PJ', DATE_FORMAT(@d2, '%y%m%d'), '002'), 3, 1, 16500),
  (CONCAT('PJ', DATE_FORMAT(@d2, '%y%m%d'), '002'), 5, 1, 14500),
  (CONCAT('PJ', DATE_FORMAT(@d1, '%y%m%d'), '001'), 1, 1, 72000),
  (CONCAT('PJ', DATE_FORMAT(@d1, '%y%m%d'), '001'), 2, 2, 37000),
  (CONCAT('PJ', DATE_FORMAT(@d1, '%y%m%d'), '001'), 7, 4, 78000),
  (CONCAT('PJ', DATE_FORMAT(@d1, '%y%m%d'), '002'), 9, 3, 16500),
  (CONCAT('PJ', DATE_FORMAT(@d1, '%y%m%d'), '002'), 8, 4, 16000),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '001'), 6, 12, 42000),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '001'), 5, 2, 29000),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '002'), 1, 1, 72000),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '002'), 4, 1, 29000),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '003'), 10, 1, 16000),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '003'), 7, 1, 19500),
  (CONCAT('PJ', DATE_FORMAT(@d0, '%y%m%d'), '003'), 8, 2, 8000);

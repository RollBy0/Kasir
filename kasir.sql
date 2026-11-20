SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `kasir`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_detail_penjualan`
--

CREATE TABLE `tb_detail_penjualan` (
  `DetailID` int(11) NOT NULL,
  `PenjualanID` varchar(11) NOT NULL,
  `ProdukID` int(11) NOT NULL,
  `JumlahProduk` int(11) NOT NULL,
  `SubTotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- data untuk tabel `tb_detail_penjualan`
--

INSERT INTO `tb_detail_penjualan` (`DetailID`, `PenjualanID`, `ProdukID`, `JumlahProduk`, `SubTotal`) VALUES
(1, '2405120001', 2, 3, '45000.00'),
(2, '2405120001', 3, 1, '20000.00'),
(9, '2405160001', 2, 2, '30000.00'),
(10, '2405160002', 3, 2, '40000.00');

-- --------------------------------------------------------

--
-- Struktur tabel `tb_pelanggan`
--

CREATE TABLE `tb_pelanggan` (
  `PelangganID` int(11) NOT NULL,
  `NamaPelanggan` varchar(25) NOT NULL,
  `Alamat` text NOT NULL,
  `NomorTelepon` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- data untuk tabel `tb_pelanggan`
--

INSERT INTO `tb_pelanggan` (`PelangganID`, `NamaPelanggan`, `Alamat`, `NomorTelepon`) VALUES
(1, 'Pengguna1', 'Jl bumi no 22', '0849282456'),
(2, 'Pengguna2', 'Jl wakanda no 11', '12435678');

-- --------------------------------------------------------

--
-- Struktur  tabel `tb_penjualan`
--

CREATE TABLE `tb_penjualan` (
  `PenjualanID` varchar(11) NOT NULL,
  `TanggalPenjualan` date NOT NULL,
  `TotalHarga` decimal(10,2) NOT NULL,
  `PelangganID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- data untuk tabel `tb_penjualan`
--

INSERT INTO `tb_penjualan` (`PenjualanID`, `TanggalPenjualan`, `TotalHarga`, `PelangganID`, `UserID`) VALUES
('2405160001', '2024-05-16', '30000.00', 1, 1),
('2405160002', '2024-05-16', '40000.00', 2, 1);

-- --------------------------------------------------------

--
-- Struktur tabel `tb_produk`
--

CREATE TABLE `tb_produk` (
  `ProdukID` int(11) NOT NULL,
  `NamaProduk` varchar(25) NOT NULL,
  `Harga` decimal(10,2) NOT NULL,
  `Stok` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- data untuk tabel `tb_produk`
--

INSERT INTO `tb_produk` (`ProdukID`, `NamaProduk`, `Harga`, `Stok`) VALUES
(2, 'Kertas ', '15000.00', 10),
(3, 'Gunting', '20000.00', 7);

-- --------------------------------------------------------

--
-- Struktur tabel `tb_user`
--

CREATE TABLE `tb_user` (
  `UserID` int(11) NOT NULL,
  `NamaUser` varchar(25) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Petugas') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- data untuk tabel `tb_user`
--

INSERT INTO `tb_user` (`UserID`, `NamaUser`, `username`, `password`, `role`) VALUES
(1, 'Test', 'test', '098f6bcd4621d373cade4e832627b4f6', 'Admin'),
(3, 'petugas1', 'petugas1', 'b53fe7751b37e40ff34d012c7774d65f', 'Petugas');

--
-- Indeks tabel `tb_detail_penjualan`
--

ALTER TABLE `tb_detail_penjualan`
  ADD PRIMARY KEY (`DetailID`);

--
-- Indeks tabel `tb_pelanggan`
--

ALTER TABLE `tb_pelanggan`
  ADD PRIMARY KEY (`PelangganID`);

--
-- Indeks tabel `tb_penjualan`
--

ALTER TABLE `tb_penjualan`
  ADD PRIMARY KEY (`PenjualanID`);

--
-- Indeks tabel `tb_produk`
--

ALTER TABLE `tb_produk`
  ADD PRIMARY KEY (`ProdukID`);

--
-- Indeks tabel `tb_user`
--

ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`UserID`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT tabel `tb_detail_penjualan`
--
ALTER TABLE `tb_detail_penjualan`
  MODIFY `DetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT tabel `tb_pelanggan`
--
ALTER TABLE `tb_pelanggan`
  MODIFY `PelangganID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT tabel `tb_produk`
--
ALTER TABLE `tb_produk`
  MODIFY `ProdukID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT tabel `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
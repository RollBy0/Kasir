-- Migrasi untuk kasir_db: menambahkan kolom status transaksi.
-- Ongoing   = transaksi belum selesai
-- Complete  = transaksi selesai (dihitung sebagai pendapatan)
-- Cancelled = void / retur yang disetujui Admin (final, stok dikembalikan)

ALTER TABLE `tb_penjualan`
  ADD COLUMN `Status` ENUM('Ongoing','Complete','Cancelled') NOT NULL DEFAULT 'Ongoing' AFTER `UserID`;

-- Transaksi lama dianggap selesai.
UPDATE `tb_penjualan` SET `Status` = 'Complete';

-- Contoh query UPDATE khusus Admin (void / retur), pastikan $_SESSION['role'] === 'Admin' di PHP:
-- UPDATE tb_penjualan SET Status = 'Cancelled' WHERE PenjualanID = ? AND Status <> 'Cancelled';
-- UPDATE tb_produk p JOIN tb_detail_penjualan d ON d.ProdukID = p.ProdukID
--   SET p.Stok = p.Stok + d.JumlahProduk WHERE d.PenjualanID = ?;

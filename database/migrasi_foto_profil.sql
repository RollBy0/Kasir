-- Jalankan satu kali pada database kasir_db yang sudah ada.
ALTER TABLE `tb_user`
  ADD COLUMN `foto` VARCHAR(255) NULL DEFAULT NULL AFTER `role`;

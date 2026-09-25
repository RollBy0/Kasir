<?php
include "../config/koneksi.php";

//Menangkap Data ID yang dikirim
$ProdukID = $_GET['ProdukID'];
$PenjualanID = $_GET['PenjualanID'];

//menghitung Stok Baru
$jml_stok = mysqli_query($koneksi,"SELECT JumlahProduk from tb_detail_penjualan where ProdukID= '$ProdukID' 
AND PenjualanID='$PenjualanID'");
$jml = mysqli_fetch_assoc($jml_stok);
$stok = mysqli_query($koneksi,"SELECT Stok from tb_produk where ProdukID='$ProdukID'");
$s = mysqli_fetch_assoc($stok);

//Penjumlahan
$up_stok = implode($s) + implode($jml);

//Update Stok
mysqli_query($koneksi,"Update tb_produk SET Stok = '$up_stok' where ProdukID = '$ProdukID'");

//Menghapus Data dari tb_detail_penjualan
mysqli_query($koneksi,"DELETE FROM tb_detail_penjualan where ProdukID='$ProdukID' AND 
PenjualanID='$PenjualanID'");

header("location:transaksi_tambah.php");
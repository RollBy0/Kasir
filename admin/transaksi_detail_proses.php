<?php
include "../config/koneksi.php";


$notrans = $_POST['no_trans'];
$ProdukID = $_POST['produk'];
$JumlahProduk = $_POST['jumlah'];

//Menghitung Sub Total
$st = mysqli_query($koneksi,"SELECT Harga FROM tb_produk where ProdukID = '$ProdukID'");
$harga = mysqli_fetch_assoc($st);
$sub_total = implode($harga) * $JumlahProduk;

//Insert data ke tb_detail_penjualan
mysqli_query($koneksi,"INSERT INTO tb_detail_penjualan values ('', '$notrans', '$ProdukID', '$JumlahProduk',
'$sub_total')");

//Menghitung Stok Terbaru
$stok = mysqli_query($koneksi,"SELECT Stok from tb_produk where ProdukID = '$ProdukID'");
$s = mysqli_fetch_assoc($stok);
$update = implode($s) - $JumlahProduk;

//Update Data Stok
mysqli_query($koneksi,"UPDATE tb_produk set Stok='$update' where ProdukID = '$ProdukID'");

header("location:transaksi_tambah.php");
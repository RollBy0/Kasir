<?php
include "../config/koneksi.php";

//Mengambil Data yang dikirim dari Edit Modal Data Produk
$ProdukID = $_POST['id_produk'];
$NamaProduk = $_POST['nm_produk'];
$Harga = $_POST['harga'];
$Stok = $_POST['stok'];

//Update Data Produk
mysqli_query($koneksi, "UPDATE tb_produk set NamaProduk = '$NamaProduk', Harga = '$Harga', 
Stok = '$Stok' where ProdukID = '$ProdukID'");

header("location:produk_data.php");
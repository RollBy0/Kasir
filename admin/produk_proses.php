<?php
include "../config/koneksi.php";

//Mengambil Data yang dikirim dari Modal Tambah Data produk
$NamaProduk = $_POST['nm_produk'];
$Harga = $_POST['harga'];
$Stok = $_POST['stok'];

mysqli_query($koneksi, "INSERT INTO tb_produk values('','$NamaProduk','$Harga','$Stok')");

header("location:produk_data.php");
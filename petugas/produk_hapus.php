<?php
include "../config/koneksi.php";

//Mengambil ProdukID ari URL
$ProdukID = $_GET['ProdukID'];

//Query
mysqli_query($koneksi,"DELETE from tb_produk where ProdukID = '$ProdukID'");

header("location:produk_data.php");
<?php
include "../config/koneksi.php";

//Mengambil ProdukID ari URL
$UserID = $_GET['UserID'];

//Query
mysqli_query($koneksi,"DELETE from tb_user where UserID = '$UserID'");

header("location:kasir_data.php");
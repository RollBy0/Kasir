<?php
include "../config/koneksi.php";

//Mengambil PelangganID ari URL
$PelangganID = $_GET['PelangganID'];

//Query
mysqli_query($koneksi,"DELETE from tb_pelanggan where PelangganID = '$PelangganID'");

header("location:pelanggan_data.php");
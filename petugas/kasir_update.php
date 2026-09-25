<?php
include "../config/koneksi.php";

//Mengambil Data yang dikirim dari Edit Modal Data Kasir
$UserID = $_POST['id_user'];
$NamaUser = $_POST['nm_user'];
$Username = $_POST['username'];
$Password = md5($_POST['password']);
$Role = $_POST['role'];

//Update Data Kasir
mysqli_query($koneksi, "UPDATE tb_user set NamaUser = '$NamaUser', username = '$Username', 
password = '$Password', role = '$Role' where UserID = '$UserID'");

header("location:kasir_data.php");
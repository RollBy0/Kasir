<?php 
//Memulai sesi
session_start();

//Menghancurkan sesi
session_destroy();

header("location:../index.php");
?>
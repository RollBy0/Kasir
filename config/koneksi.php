<?php
$koneksi = mysqli_connect('localhost', 'root', '', 'kasir_db');

if(mysqli_connect_errno()) {
    echo "koneksi gagal :" . mysqli_connect_errno();
}
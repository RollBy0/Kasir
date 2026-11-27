<?php
/** Dashboard: ringkasan aktivitas toko hari ini. */
require __DIR__ . '/includes/init.php';

$user = require_login();
require_access($user, 'dashboard');

$today = date('Y-m-d');

$todayRevenue = (float) db_value("SELECT COALESCE(SUM(TotalHarga), 0) FROM tb_penjualan WHERE Status = 'Complete' AND TanggalPenjualan = ?", [$today]);
$todaySales = (int) db_value('SELECT COUNT(*) FROM tb_penjualan WHERE TanggalPenjualan = ?', [$today]);
$ongoingToday = (int) db_value("SELECT COUNT(*) FROM tb_penjualan WHERE Status = 'Ongoing' AND TanggalPenjualan = ?", [$today]);
$itemsToday = (int) db_value(
    "SELECT COALESCE(SUM(d.JumlahProduk), 0) FROM tb_detail_penjualan d
     JOIN tb_penjualan p ON p.PenjualanID = d.PenjualanID
     WHERE p.Status = 'Complete' AND p.TanggalPenjualan = ?",
    [$today]
);
$productCount = (int) db_value('SELECT COUNT(*) FROM tb_produk');
$memberCount = (int) db_value('SELECT COUNT(*) FROM tb_pelanggan');
$weekTotal = (float) db_value(
    "SELECT COALESCE(SUM(TotalHarga), 0) FROM tb_penjualan WHERE Status = 'Complete' AND TanggalPenjualan BETWEEN ? AND ?",
    [days_ago(6), $today]
);
$weekCount = (int) db_value(
    "SELECT COUNT(*) FROM tb_penjualan WHERE Status = 'Complete' AND TanggalPenjualan BETWEEN ? AND ?",
    [days_ago(6), $today]
);
$lowStock = db_all('SELECT ProdukID, NamaProduk, Harga, Stok FROM tb_produk WHERE Stok <= ? ORDER BY Stok ASC, ProdukID ASC LIMIT 4', [LOW_STOCK]);
$latest = db_all(
    'SELECT p.PenjualanID, p.TanggalPenjualan, p.TotalHarga, p.PelangganID, p.Status, pl.NamaPelanggan
     FROM tb_penjualan p LEFT JOIN tb_pelanggan pl ON pl.PelangganID = p.PelangganID
     ORDER BY p.TanggalPenjualan DESC, p.PenjualanID DESC LIMIT 5'
);

render('dashboard', [
    'user' => $user,
    'activeModule' => 'dashboard',
    'todayRevenue' => $todayRevenue,
    'todaySales' => $todaySales,
    'ongoingToday' => $ongoingToday,
    'itemsToday' => $itemsToday,
    'productCount' => $productCount,
    'memberCount' => $memberCount,
    'weekTotal' => $weekTotal,
    'weekCount' => $weekCount,
    'lowStock' => $lowStock,
    'latest' => $latest,
]);

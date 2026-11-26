<?php
/** Laporan: rekap pendapatan dan penjualan per periode (baca saja). */
require __DIR__ . '/includes/init.php';

$user = require_login();
require_access($user, 'laporan');

$isDate = fn (string $v) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
$from = $isDate(get('dari')) ? get('dari') : days_ago(6);
$to = $isDate(get('sampai')) ? get('sampai') : days_ago(0);
if ($from > $to) {
    [$from, $to] = [$to, $from];
}
$range = [$from, $to];

$summary = [];
foreach (db_all('SELECT Status, COUNT(*) AS jumlah, COALESCE(SUM(TotalHarga), 0) AS total FROM tb_penjualan WHERE TanggalPenjualan BETWEEN ? AND ? GROUP BY Status', $range) as $row) {
    $summary[$row['Status']] = ['jumlah' => (int) $row['jumlah'], 'total' => (float) $row['total']];
}
$completed = $summary['Complete'] ?? ['jumlah' => 0, 'total' => 0.0];
$ongoing = $summary['Ongoing'] ?? ['jumlah' => 0, 'total' => 0.0];
$cancelled = $summary['Cancelled'] ?? ['jumlah' => 0, 'total' => 0.0];

$itemsSold = (int) db_value(
    "SELECT COALESCE(SUM(d.JumlahProduk), 0) FROM tb_detail_penjualan d JOIN tb_penjualan p ON p.PenjualanID = d.PenjualanID
     WHERE p.Status = 'Complete' AND p.TanggalPenjualan BETWEEN ? AND ?",
    $range
);
$daily = db_all(
    "SELECT TanggalPenjualan AS tanggal, COUNT(*) AS jumlah, SUM(TotalHarga) AS pendapatan FROM tb_penjualan
     WHERE Status = 'Complete' AND TanggalPenjualan BETWEEN ? AND ?
     GROUP BY TanggalPenjualan ORDER BY TanggalPenjualan DESC",
    $range
);
$topProducts = db_all(
    "SELECT d.ProdukID, COALESCE(pr.NamaProduk, CONCAT('Produk #', d.ProdukID)) AS NamaProduk, SUM(d.JumlahProduk) AS qty, SUM(d.SubTotal) AS total
     FROM tb_detail_penjualan d
     JOIN tb_penjualan p ON p.PenjualanID = d.PenjualanID
     LEFT JOIN tb_produk pr ON pr.ProdukID = d.ProdukID
     WHERE p.Status = 'Complete' AND p.TanggalPenjualan BETWEEN ? AND ?
     GROUP BY d.ProdukID, pr.NamaProduk ORDER BY qty DESC LIMIT 5",
    $range
);

render('laporan', compact('user', 'from', 'to', 'completed', 'ongoing', 'cancelled', 'itemsSold', 'daily', 'topProducts') + ['activeModule' => 'laporan']);

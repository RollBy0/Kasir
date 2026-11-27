<?php
/** Transaksi: daftar penjualan, detail, ubah status (Admin), input transaksi & struk (Petugas). */
require __DIR__ . '/includes/init.php';

$user = require_login();
require_access($user, 'transaksi');
$role = $user['role'];
$canCreate = can($role, 'transaksi', 'create');
$canUpdate = can($role, 'transaksi', 'update');
$statuses = ['Ongoing', 'Complete', 'Cancelled'];

if (is_post()) {
    verify_csrf();
    $aksi = post('aksi');
    $pdo = db();

    if ($aksi === 'simpan' && $canCreate) {
        // Gabungkan baris keranjang: produk_id[] & jumlah[]
        $lines = [];
        foreach ((array) ($_POST['produk_id'] ?? []) as $i => $rawId) {
            $id = (int) $rawId;
            $qty = (int) ($_POST['jumlah'][$i] ?? 0);
            if ($id > 0 && $qty > 0) {
                $lines[$id] = ($lines[$id] ?? 0) + $qty;
            }
        }
        $pelangganId = (int) post('pelanggan_id');

        try {
            if (!$lines) {
                throw new RuntimeException('Tambahkan minimal satu produk.');
            }
            if ($pelangganId !== UMUM_ID && !db_value('SELECT 1 FROM tb_pelanggan WHERE PelangganID = ?', [$pelangganId])) {
                throw new RuntimeException('Member tidak ditemukan.');
            }

            $pdo->beginTransaction();
            $placeholders = implode(',', array_fill(0, count($lines), '?'));
            $st = $pdo->prepare("SELECT ProdukID, NamaProduk, Harga, Stok FROM tb_produk WHERE ProdukID IN ($placeholders) FOR UPDATE");
            $st->execute(array_keys($lines));
            $products = [];
            foreach ($st->fetchAll() as $row) {
                $products[(int) $row['ProdukID']] = $row;
            }

            $total = 0.0;
            foreach ($lines as $id => $qty) {
                if (!isset($products[$id])) {
                    throw new RuntimeException("Produk #{$id} tidak ditemukan.");
                }
                if ($qty > (int) $products[$id]['Stok']) {
                    throw new RuntimeException("Stok {$products[$id]['NamaProduk']} hanya tersisa {$products[$id]['Stok']}.");
                }
                $total += (float) $products[$id]['Harga'] * $qty;
            }

            // PenjualanID: PJ + yymmdd + nomor urut 3 digit
            $tanggal = date('Y-m-d');
            $prefix = 'PJ' . date('ymd');
            $last = db_value('SELECT PenjualanID FROM tb_penjualan WHERE PenjualanID LIKE ? ORDER BY PenjualanID DESC LIMIT 1 FOR UPDATE', [$prefix . '%']);
            $penjualanId = $prefix . str_pad((string) ($last ? (int) substr($last, 8) + 1 : 1), 3, '0', STR_PAD_LEFT);

            db_exec(
                "INSERT INTO tb_penjualan (PenjualanID, TanggalPenjualan, TotalHarga, PelangganID, UserID, Status) VALUES (?, ?, ?, ?, ?, 'Complete')",
                [$penjualanId, $tanggal, $total, $pelangganId, $user['UserID']]
            );
            foreach ($lines as $id => $qty) {
                db_exec(
                    'INSERT INTO tb_detail_penjualan (PenjualanID, ProdukID, JumlahProduk, SubTotal) VALUES (?, ?, ?, ?)',
                    [$penjualanId, $id, $qty, (float) $products[$id]['Harga'] * $qty]
                );
                db_exec('UPDATE tb_produk SET Stok = Stok - ? WHERE ProdukID = ?', [$qty, $id]);
            }
            $pdo->commit();

            flash("Transaksi {$penjualanId} berhasil disimpan.");
            redirect(url('transaksi.php', ['struk' => $penjualanId]));
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash($ex instanceof PDOException ? 'Terjadi kesalahan database, transaksi tidak disimpan.' : $ex->getMessage(), 'error');
            redirect('transaksi.php?baru=1');
        }
    }

    if ($aksi === 'status' && $canUpdate) {
        $id = post('id');
        $status = post('status');
        if (!in_array($status, $statuses, true)) {
            flash('Status tidak valid.', 'error');
            redirect('transaksi.php');
        }

        $pdo->beginTransaction();
        $current = db_value('SELECT Status FROM tb_penjualan WHERE PenjualanID = ? FOR UPDATE', [$id]);
        if ($current === null || $current === 'Cancelled') {
            $pdo->rollBack();
            flash('Transaksi tidak ditemukan atau sudah dibatalkan.', 'error');
            redirect('transaksi.php');
        }
        if ($status === 'Cancelled') {
            // Void / retur: kembalikan stok seluruh barang pada transaksi.
            db_exec(
                'UPDATE tb_produk p JOIN (
                    SELECT ProdukID, SUM(JumlahProduk) AS qty FROM tb_detail_penjualan WHERE PenjualanID = ? GROUP BY ProdukID
                 ) d ON d.ProdukID = p.ProdukID
                 SET p.Stok = p.Stok + d.qty',
                [$id]
            );
        }
        db_exec('UPDATE tb_penjualan SET Status = ? WHERE PenjualanID = ?', [$status, $id]);
        $pdo->commit();

        flash($status === 'Cancelled' ? "Transaksi {$id} dibatalkan, stok dikembalikan." : "Status {$id} diubah menjadi {$status}.");
        redirect('transaksi.php');
    }

    flash('Aksi tidak diizinkan untuk role Anda.', 'error');
    redirect('transaksi.php');
}

/* ---------- Data tampilan ---------- */

$q = get('q');
$filter = in_array(get('status'), $statuses, true) ? get('status') : 'Semua';

$counts = ['Semua' => 0, 'Ongoing' => 0, 'Complete' => 0, 'Cancelled' => 0];
foreach (db_all('SELECT Status, COUNT(*) AS jumlah FROM tb_penjualan GROUP BY Status') as $row) {
    $counts[$row['Status']] = (int) $row['jumlah'];
    $counts['Semua'] += (int) $row['jumlah'];
}

$sql = "SELECT p.*, pl.NamaPelanggan, u.NamaUser
        FROM tb_penjualan p
        LEFT JOIN tb_pelanggan pl ON pl.PelangganID = p.PelangganID
        LEFT JOIN tb_user u ON u.UserID = p.UserID
        WHERE 1 = 1";
$params = [];
if ($filter !== 'Semua') {
    $sql .= ' AND p.Status = ?';
    $params[] = $filter;
}
if ($q !== '') {
    $sql .= " AND (p.PenjualanID LIKE ? OR COALESCE(pl.NamaPelanggan, IF(p.PelangganID = 0, 'Pelanggan Umum', '')) LIKE ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
$sql .= ' ORDER BY p.TanggalPenjualan DESC, p.PenjualanID DESC';
$sales = db_all($sql, $params);

/** Ambil satu transaksi lengkap dengan barangnya (untuk detail & struk). */
function load_sale(string $id): ?array
{
    $sale = db_row(
        'SELECT p.*, pl.NamaPelanggan, u.NamaUser FROM tb_penjualan p
         LEFT JOIN tb_pelanggan pl ON pl.PelangganID = p.PelangganID
         LEFT JOIN tb_user u ON u.UserID = p.UserID
         WHERE p.PenjualanID = ?',
        [$id]
    );
    if ($sale !== null) {
        $sale['items'] = db_all(
            'SELECT d.*, COALESCE(pr.NamaProduk, CONCAT(\'Produk #\', d.ProdukID)) AS NamaProduk
             FROM tb_detail_penjualan d LEFT JOIN tb_produk pr ON pr.ProdukID = d.ProdukID
             WHERE d.PenjualanID = ? ORDER BY d.DetailID',
            [$id]
        );
    }
    return $sale;
}

$detailSale = get('detail') !== '' ? load_sale(get('detail')) : null;
$receiptSale = ($canCreate && get('struk') !== '') ? load_sale(get('struk')) : null;
$posOpen = $canCreate && get('baru') === '1';

render('transaksi', [
    'user' => $user,
    'activeModule' => 'transaksi',
    'canCreate' => $canCreate,
    'canUpdate' => $canUpdate,
    'q' => $q,
    'filter' => $filter,
    'counts' => $counts,
    'sales' => $sales,
    'detailSale' => $detailSale,
    'receiptSale' => $receiptSale,
    'posOpen' => $posOpen,
    'produk' => $canCreate ? db_all('SELECT ProdukID, NamaProduk, Harga, Stok FROM tb_produk ORDER BY NamaProduk') : [],
    'pelanggan' => $canCreate ? db_all('SELECT PelangganID, NamaPelanggan, NomorTelepon FROM tb_pelanggan ORDER BY NamaPelanggan') : [],
]);

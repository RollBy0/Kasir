<?php
/** Produk: katalog, harga, dan stok (CRUD Admin, baca saja untuk Petugas). */
require __DIR__ . '/includes/init.php';

$user = require_login();
require_access($user, 'produk');
$role = $user['role'];
$canCreate = can($role, 'produk', 'create');
$canUpdate = can($role, 'produk', 'update');
$canDelete = can($role, 'produk', 'delete');

$q = get('q');
$lowOnly = get('menipis') === '1';
$backUrl = url('produk.php', ['q' => $q, 'menipis' => $lowOnly ? '1' : '']);

$form = ['ProdukID' => 0, 'NamaProduk' => '', 'Harga' => '', 'Stok' => ''];
$formOpen = false;
$formError = '';

if (is_post()) {
    verify_csrf();
    $aksi = post('aksi');

    if ($aksi === 'simpan') {
        $form = ['ProdukID' => (int) post('ProdukID'), 'NamaProduk' => post('NamaProduk'), 'Harga' => post('Harga'), 'Stok' => post('Stok')];
        $editing = $form['ProdukID'] > 0;
        if (!($editing ? $canUpdate : $canCreate)) {
            flash('Aksi tidak diizinkan untuk role Anda.', 'error');
            redirect($backUrl);
        }

        if ($form['NamaProduk'] === '') {
            $formError = 'Nama produk wajib diisi.';
        } elseif (mb_strlen($form['NamaProduk']) > 25) {
            $formError = 'Nama produk maksimal 25 karakter.';
        } elseif (!is_numeric($form['Harga']) || (float) $form['Harga'] <= 0) {
            $formError = 'Harga harus lebih dari 0.';
        } elseif (!ctype_digit($form['Stok'])) {
            $formError = 'Stok harus berupa bilangan bulat ≥ 0.';
        }

        if ($formError === '') {
            if ($editing) {
                db_exec('UPDATE tb_produk SET NamaProduk = ?, Harga = ?, Stok = ? WHERE ProdukID = ?', [$form['NamaProduk'], (float) $form['Harga'], (int) $form['Stok'], $form['ProdukID']]);
                flash("Produk {$form['NamaProduk']} diperbarui.");
            } else {
                db_exec('INSERT INTO tb_produk (NamaProduk, Harga, Stok) VALUES (?, ?, ?)', [$form['NamaProduk'], (float) $form['Harga'], (int) $form['Stok']]);
                flash("Produk {$form['NamaProduk']} ditambahkan.");
            }
            redirect($backUrl);
        }
        $formOpen = true; // tampilkan lagi form beserta pesan kesalahan
    } elseif ($aksi === 'hapus' && $canDelete) {
        $id = (int) post('ProdukID');
        $nama = db_value('SELECT NamaProduk FROM tb_produk WHERE ProdukID = ?', [$id]);
        if ($nama === null) {
            flash('Produk tidak ditemukan.', 'error');
        } elseif (db_value('SELECT COUNT(*) FROM tb_detail_penjualan WHERE ProdukID = ?', [$id]) > 0) {
            flash('Produk tercatat pada detail penjualan sehingga tidak dapat dihapus.', 'error');
        } else {
            db_exec('DELETE FROM tb_produk WHERE ProdukID = ?', [$id]);
            flash("Produk {$nama} dihapus.");
        }
        redirect($backUrl);
    } else {
        flash('Aksi tidak diizinkan untuk role Anda.', 'error');
        redirect($backUrl);
    }
}

if (!$formOpen && $canUpdate && get('edit') !== '') {
    $row = db_row('SELECT ProdukID, NamaProduk, Harga, Stok FROM tb_produk WHERE ProdukID = ?', [(int) get('edit')]);
    if ($row !== null) {
        $form = ['ProdukID' => (int) $row['ProdukID'], 'NamaProduk' => $row['NamaProduk'], 'Harga' => (string) (float) $row['Harga'], 'Stok' => (string) $row['Stok']];
        $formOpen = true;
    }
}
if (!$formOpen && $canCreate && get('baru') === '1') {
    $formOpen = true;
}

$sql = 'SELECT p.*, (SELECT COUNT(*) FROM tb_detail_penjualan d WHERE d.ProdukID = p.ProdukID) AS dipakai FROM tb_produk p WHERE p.NamaProduk LIKE ?';
$params = ["%{$q}%"];
if ($lowOnly) {
    $sql .= ' AND p.Stok <= ?';
    $params[] = LOW_STOCK;
}
$produk = db_all($sql . ' ORDER BY p.ProdukID', $params);

render('produk', compact('user', 'canCreate', 'canUpdate', 'canDelete', 'q', 'lowOnly', 'backUrl', 'form', 'formOpen', 'formError', 'produk') + ['activeModule' => 'produk']);

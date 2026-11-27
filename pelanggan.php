<?php
/** Member: data tb_pelanggan. Petugas boleh mendaftarkan & mengubah alamat/telepon saja. */
require __DIR__ . '/includes/init.php';

$user = require_login();
require_access($user, 'pelanggan');
$role = $user['role'];
$canCreate = can($role, 'pelanggan', 'create');
$canUpdate = can($role, 'pelanggan', 'update');
$canDelete = can($role, 'pelanggan', 'delete');
$editable = EDITABLE_PELANGGAN[$role];

$q = get('q');
$backUrl = url('pelanggan.php', ['q' => $q]);

$form = ['PelangganID' => 0, 'NamaPelanggan' => '', 'Alamat' => '', 'NomorTelepon' => ''];
$formOpen = false;
$formError = '';

if (is_post()) {
    verify_csrf();
    $aksi = post('aksi');

    if ($aksi === 'simpan') {
        $id = (int) post('PelangganID');
        $existing = $id > 0 ? db_row('SELECT * FROM tb_pelanggan WHERE PelangganID = ?', [$id]) : null;
        if (!($id > 0 ? $canUpdate && $existing !== null : $canCreate)) {
            flash('Aksi tidak diizinkan atau data tidak ditemukan.', 'error');
            redirect($backUrl);
        }

        $form = ['PelangganID' => $id, 'NamaPelanggan' => post('NamaPelanggan'), 'Alamat' => post('Alamat'), 'NomorTelepon' => post('NomorTelepon')];
        if ($existing !== null && !in_array('NamaPelanggan', $editable, true)) {
            $form['NamaPelanggan'] = $existing['NamaPelanggan']; // kolom terkunci untuk role ini
        }

        if ($form['NamaPelanggan'] === '' || $form['Alamat'] === '' || $form['NomorTelepon'] === '') {
            $formError = 'Semua kolom wajib diisi.';
        } elseif (mb_strlen($form['NamaPelanggan']) > 25) {
            $formError = 'Nama pelanggan maksimal 25 karakter.';
        } elseif (!preg_match('/^[0-9+]{8,15}$/', $form['NomorTelepon'])) {
            $formError = 'Nomor telepon harus 8–15 digit angka.';
        }

        if ($formError === '') {
            if ($existing !== null) {
                db_exec('UPDATE tb_pelanggan SET NamaPelanggan = ?, Alamat = ?, NomorTelepon = ? WHERE PelangganID = ?', [$form['NamaPelanggan'], $form['Alamat'], $form['NomorTelepon'], $id]);
                flash("Data member {$form['NamaPelanggan']} diperbarui.");
            } else {
                db_exec('INSERT INTO tb_pelanggan (NamaPelanggan, Alamat, NomorTelepon) VALUES (?, ?, ?)', [$form['NamaPelanggan'], $form['Alamat'], $form['NomorTelepon']]);
                flash("Member {$form['NamaPelanggan']} berhasil didaftarkan.");
            }
            redirect($backUrl);
        }
        $formOpen = true;
    } elseif ($aksi === 'hapus' && $canDelete) {
        $id = (int) post('PelangganID');
        $nama = db_value('SELECT NamaPelanggan FROM tb_pelanggan WHERE PelangganID = ?', [$id]);
        if ($nama === null) {
            flash('Member tidak ditemukan.', 'error');
        } elseif (db_value('SELECT COUNT(*) FROM tb_penjualan WHERE PelangganID = ?', [$id]) > 0) {
            flash('Member memiliki riwayat transaksi sehingga tidak dapat dihapus.', 'error');
        } else {
            db_exec('DELETE FROM tb_pelanggan WHERE PelangganID = ?', [$id]);
            flash("Member {$nama} dihapus.");
        }
        redirect($backUrl);
    } else {
        flash('Aksi tidak diizinkan untuk role Anda.', 'error');
        redirect($backUrl);
    }
}

if (!$formOpen && $canUpdate && get('edit') !== '') {
    $row = db_row('SELECT * FROM tb_pelanggan WHERE PelangganID = ?', [(int) get('edit')]);
    if ($row !== null) {
        $form = $row;
        $form['PelangganID'] = (int) $row['PelangganID'];
        $formOpen = true;
    }
}
if (!$formOpen && $canCreate && get('baru') === '1') {
    $formOpen = true;
}

$like = "%{$q}%";
$pelanggan = db_all(
    'SELECT pl.*, (SELECT COUNT(*) FROM tb_penjualan p WHERE p.PelangganID = pl.PelangganID) AS jumlah_transaksi
     FROM tb_pelanggan pl
     WHERE pl.NamaPelanggan LIKE ? OR pl.NomorTelepon LIKE ? OR pl.Alamat LIKE ?
     ORDER BY pl.PelangganID',
    [$like, $like, $like]
);
$total = (int) db_value('SELECT COUNT(*) FROM tb_pelanggan');

render('pelanggan', compact('user', 'canCreate', 'canUpdate', 'canDelete', 'editable', 'q', 'backUrl', 'form', 'formOpen', 'formError', 'pelanggan', 'total') + ['activeModule' => 'pelanggan']);

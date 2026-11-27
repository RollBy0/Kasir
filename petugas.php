<?php
/** Data Petugas: kelola akun tb_user (khusus Admin). */
require __DIR__ . '/includes/init.php';

$user = require_login();
require_access($user, 'petugas');

$q = get('q');
$roleFilter = in_array(get('role'), ['Admin', 'Petugas'], true) ? get('role') : 'Semua';
$backUrl = url('petugas.php', ['q' => $q, 'role' => $roleFilter === 'Semua' ? '' : $roleFilter]);

$form = ['UserID' => 0, 'NamaUser' => '', 'username' => '', 'role' => 'Petugas'];
$formOpen = false;
$formError = '';

if (is_post()) {
    verify_csrf();
    $aksi = post('aksi');

    if ($aksi === 'simpan') {
        $id = (int) post('UserID');
        $isSelf = $id === (int) $user['UserID'];
        $form = ['UserID' => $id, 'NamaUser' => post('NamaUser'), 'username' => post('username'), 'role' => post('role')];
        $password = (string) ($_POST['password'] ?? '');
        $existing = $id > 0 ? db_row('SELECT UserID, role FROM tb_user WHERE UserID = ?', [$id]) : null;

        if ($id > 0 && $existing === null) {
            flash('Akun tidak ditemukan.', 'error');
            redirect($backUrl);
        }
        if ($isSelf) {
            $form['role'] = $existing['role']; // role akun sendiri tidak dapat diubah
        }

        if ($form['NamaUser'] === '' || $form['username'] === '') {
            $formError = 'Nama dan username wajib diisi.';
        } elseif (mb_strlen($form['NamaUser']) > 25 || mb_strlen($form['username']) > 25) {
            $formError = 'Nama dan username maksimal 25 karakter.';
        } elseif (!in_array($form['role'], ['Admin', 'Petugas'], true)) {
            $formError = 'Role tidak valid.';
        } elseif (db_value('SELECT COUNT(*) FROM tb_user WHERE username = ? AND UserID <> ?', [$form['username'], $id]) > 0) {
            $formError = 'Username sudah digunakan akun lain.';
        } elseif ($id === 0 && strlen($password) < 4) {
            $formError = 'Password minimal 4 karakter.';
        } elseif ($id > 0 && $password !== '' && strlen($password) < 4) {
            $formError = 'Password minimal 4 karakter.';
        }

        if ($formError === '') {
            if ($id > 0) {
                db_exec('UPDATE tb_user SET NamaUser = ?, username = ?, role = ? WHERE UserID = ?', [$form['NamaUser'], $form['username'], $form['role'], $id]);
                if ($password !== '') {
                    db_exec('UPDATE tb_user SET password = ? WHERE UserID = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
                }
                flash("Akun {$form['NamaUser']} berhasil diperbarui.");
            } else {
                db_exec('INSERT INTO tb_user (NamaUser, username, password, role) VALUES (?, ?, ?, ?)', [$form['NamaUser'], $form['username'], password_hash($password, PASSWORD_DEFAULT), $form['role']]);
                flash("Akun {$form['NamaUser']} berhasil ditambahkan.");
            }
            redirect($backUrl);
        }
        $formOpen = true;
    } elseif ($aksi === 'hapus') {
        $id = (int) post('UserID');
        $nama = db_value('SELECT NamaUser FROM tb_user WHERE UserID = ?', [$id]);
        if ($id === (int) $user['UserID']) {
            flash('Tidak dapat menghapus akun sendiri.', 'error');
        } elseif ($nama === null) {
            flash('Akun tidak ditemukan.', 'error');
        } else {
            db_exec('DELETE FROM tb_user WHERE UserID = ?', [$id]);
            flash("Akun {$nama} dihapus.");
        }
        redirect($backUrl);
    } else {
        redirect($backUrl);
    }
}

if (!$formOpen && get('edit') !== '') {
    $row = db_row('SELECT UserID, NamaUser, username, role FROM tb_user WHERE UserID = ?', [(int) get('edit')]);
    if ($row !== null) {
        $form = $row;
        $form['UserID'] = (int) $row['UserID'];
        $formOpen = true;
    }
}
if (!$formOpen && get('baru') === '1') {
    $formOpen = true;
}

$sql = 'SELECT UserID, NamaUser, username, role FROM tb_user WHERE (NamaUser LIKE ? OR username LIKE ?)';
$params = ["%{$q}%", "%{$q}%"];
if ($roleFilter !== 'Semua') {
    $sql .= ' AND role = ?';
    $params[] = $roleFilter;
}
$users = db_all($sql . ' ORDER BY UserID', $params);

render('petugas', compact('user', 'q', 'roleFilter', 'backUrl', 'form', 'formOpen', 'formError', 'users') + ['activeModule' => 'petugas']);

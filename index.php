<?php
/** Halaman login. */
require __DIR__ . '/includes/init.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if (is_post()) {
    verify_csrf();
    $username = post('username');
    $password = (string) ($_POST['password'] ?? '');

    $row = db_row('SELECT UserID, password FROM tb_user WHERE username = ?', [$username]);
    $valid = $row !== null && password_verify($password, $row['password']);

    // Kompatibilitas data lama: password tersimpan polos atau MD5 tetap bisa login sekali, lalu di-hash ulang.
    if (!$valid && $row !== null && $password !== '' && empty(password_get_info($row['password'])['algo'])) {
        $valid = hash_equals($row['password'], $password) || hash_equals($row['password'], md5($password));
    }

    if ($valid) {
        if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
            db_exec('UPDATE tb_user SET password = ? WHERE UserID = ?', [password_hash($password, PASSWORD_DEFAULT), $row['UserID']]);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['UserID'];
        redirect('dashboard.php');
    }
    $error = 'Username atau password salah.';
}

render('login', ['error' => $error, 'username' => $username], 'none');

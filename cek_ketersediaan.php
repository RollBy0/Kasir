<?php
/** Cek username saat Admin mengisi form akun. */
require __DIR__ . '/includes/init.php';

header('Content-Type: application/json; charset=utf-8');

function reply(int $status, bool $available, string $message): void
{
    http_response_code($status);
    echo json_encode(['available' => $available, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    reply(405, false, 'Metode tidak diizinkan.');
}
if (current_user() === null) {
    reply(401, false, 'Silakan masuk terlebih dahulu.');
}
$user = require_login();
if (!can($user['role'], 'petugas', 'read')) {
    reply(403, false, 'Akses ditolak.');
}
require_access($user, 'petugas');

$field = get('field');
$except = get('except_id');
if ($field !== 'username' || ($except !== '' && (!ctype_digit($except) || (int) $except < 0))) {
    reply(400, false, 'Parameter tidak valid.');
}
$value = get('value');
if ($value === '') {
    reply(200, false, 'Username wajib diisi.');
}
if (mb_strlen($value) > 25) {
    reply(200, false, 'Username maksimal 25 karakter.');
}

$exists = (int) db_value(
    'SELECT COUNT(*) FROM tb_user WHERE LOWER(TRIM(username)) = LOWER(?) AND UserID <> ?',
    [$value, $except === '' ? 0 : (int) $except]
);
reply(200, $exists === 0, $exists ? 'Username sudah dipakai' : 'Username tersedia');

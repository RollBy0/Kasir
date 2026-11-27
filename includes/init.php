<?php
/**
 * Bootstrap aplikasi: sesi, koneksi, hak akses, dan fungsi bantu.
 * Di-include oleh setiap halaman PHP.
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/komponen.php';

const STORE_NAME = 'KasirJayaJayaAtma';
const STORE_TAGLINE = 'KasirNo1seIndonesia';
const LOW_STOCK = 10;   // batas stok menipis
const UMUM_ID = 0;      // PelangganID 0 = pembeli non-member
const VIEW_DIR = __DIR__ . '/../views';

const CRUD = ['create', 'read', 'update', 'delete'];

/** Matriks hak akses per role. Dicek di setiap halaman dan setiap aksi POST. */
const ACCESS = [
    'Admin' => [
        'dashboard' => ['read'],
        'transaksi' => ['read', 'update'],          // update khusus status: void / retur
        'produk'    => CRUD,
        'pelanggan' => CRUD,
        'petugas'   => CRUD,
        'laporan'   => ['read'],
    ],
    'Petugas' => [
        'dashboard' => ['read'],
        'transaksi' => ['read', 'create'],          // input transaksi kasir & cetak struk
        'produk'    => ['read'],
        'pelanggan' => ['create', 'read', 'update'],
        'petugas'   => [],
        'laporan'   => ['read'],
    ],
];

/** Kolom tb_pelanggan yang boleh diubah tiap role. */
const EDITABLE_PELANGGAN = [
    'Admin'   => ['NamaPelanggan', 'Alamat', 'NomorTelepon'],
    'Petugas' => ['Alamat', 'NomorTelepon'],
];

const MODULES = [
    'dashboard' => ['label' => 'Dashboard',    'description' => 'Ringkasan aktivitas toko',            'icon' => 'dashboard', 'file' => 'dashboard.php'],
    'transaksi' => ['label' => 'Transaksi',    'description' => 'tb_penjualan & tb_detail_penjualan', 'icon' => 'cart',      'file' => 'transaksi.php'],
    'produk'    => ['label' => 'Produk',       'description' => 'tb_produk',                          'icon' => 'box',       'file' => 'produk.php'],
    'pelanggan' => ['label' => 'Member',       'description' => 'tb_pelanggan',                       'icon' => 'users',     'file' => 'pelanggan.php'],
    'laporan'   => ['label' => 'Laporan',      'description' => 'Rekap pendapatan dan penjualan',     'icon' => 'report',    'file' => 'laporan.php'],
    'petugas'   => ['label' => 'Data Petugas', 'description' => 'tb_user',                            'icon' => 'shield',    'file' => 'petugas.php'],
];

/* ---------- Bantuan umum ---------- */

/** Escape untuk output XHTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XHTML, 'UTF-8');
}

function rupiah($value): string
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

/** 2025-03-15 -> "15 Mar 2025" */
function tanggal(string $iso, bool $withYear = true): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $parts = explode('-', substr($iso, 0, 10));
    if (count($parts) !== 3) {
        return $iso;
    }
    return (int) $parts[2] . ' ' . $bulan[(int) $parts[1]] . ($withYear ? ' ' . $parts[0] : '');
}

/** Contoh: "Kamis, 26 November 2026" */
function tanggal_panjang(): string
{
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $hari[(int) date('w')] . ', ' . (int) date('j') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
}

function days_ago(int $days): string
{
    return date('Y-m-d', strtotime("-{$days} day"));
}

function initials(string $name): string
{
    $letters = '';
    foreach (preg_split('/\s+/', trim($name)) as $word) {
        if ($word !== '') {
            $letters .= mb_substr($word, 0, 1);
        }
    }
    return mb_strtoupper(mb_substr($letters, 0, 2));
}

function nama_pelanggan(int $id, ?string $nama): string
{
    if ($id === UMUM_ID) {
        return 'Pelanggan Umum';
    }
    return $nama ?? "Member #{$id}";
}

/** Bangun URL dengan query string. Selalu tampilkan lewat e() agar & menjadi &amp;. */
function url(string $path, array $query = []): string
{
    $query = array_filter($query, fn ($v) => $v !== null && $v !== '');
    return $query ? $path . '?' . http_build_query($query) : $path;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function post(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function get(string $key): string
{
    return trim((string) ($_GET[$key] ?? ''));
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ---------- Query singkat ---------- */

function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_row(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $value = $st->fetchColumn();
    return $value === false ? null : $value;
}

function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/* ---------- Flash message (toast) ---------- */

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '" />';
}

function verify_csrf(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------- Autentikasi & hak akses ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (isset($_SESSION['user_id'])) {
            $user = db_row('SELECT UserID, NamaUser, username, role FROM tb_user WHERE UserID = ?', [$_SESSION['user_id']]);
            if ($user === null) {
                unset($_SESSION['user_id']); // akun dihapus: keluar dari sesi
            }
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('index.php');
    }
    return $user;
}

function can(string $role, string $module, string $action): bool
{
    return in_array($action, ACCESS[$role][$module] ?? [], true);
}

/** Hentikan request dengan halaman "Akses ditolak" bila role tidak punya izin. */
function require_access(array $user, string $module, string $action = 'read'): void
{
    if (!can($user['role'], $module, $action)) {
        http_response_code(403);
        render('akses_ditolak', ['user' => $user, 'activeModule' => $module]);
        exit;
    }
}

/* ---------- View ---------- */

/**
 * Tampilkan template views/{$view}.xhtml.
 * $layout 'app' membungkus halaman dengan sidebar + navbar, 'none' untuk halaman mandiri (login).
 */
function render(string $view, array $data = [], string $layout = 'app'): void
{
    header('Content-Type: text/html; charset=utf-8');
    $flash = take_flash();
    extract($data, EXTR_SKIP);
    if ($layout === 'app') {
        require VIEW_DIR . '/partials/layout_atas.xhtml';
        require VIEW_DIR . '/' . $view . '.xhtml';
        require VIEW_DIR . '/partials/layout_bawah.xhtml';
    } else {
        require VIEW_DIR . '/' . $view . '.xhtml';
    }
}

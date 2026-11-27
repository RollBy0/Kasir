<?php
/**
 * Komponen tampilan yang dipakai berulang di template .xhtml.
 * Semua fungsi mengembalikan string XHTML yang sudah di-escape.
 */

const ICONS = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="2" /><rect x="14" y="3" width="7" height="7" rx="2" /><rect x="3" y="14" width="7" height="7" rx="2" /><rect x="14" y="14" width="7" height="7" rx="2" />',
    'cart'      => '<circle cx="9" cy="20" r="1" /><circle cx="19" cy="20" r="1" /><path d="M3 3h2l2.4 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 7H6" />',
    'box'       => '<path d="m21 8-9-5-9 5 9 5 9-5Z" /><path d="m3 8 9 5 9-5v8l-9 5-9-5Z" /><path d="M12 13v8" />',
    'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" />',
    'receipt'   => '<path d="M4 2v20l3-2 3 2 2-2 3 2 2-2 3 2V2l-3 2-3-2-2 2-3-2-2 2Z" /><path d="M8 9h8M8 13h6" />',
    'report'    => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2" />',
    'logout'    => '<path d="M10 17l5-5-5-5M15 12H3" /><path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" />',
    'search'    => '<circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" />',
    'menu'      => '<path d="M4 7h16M4 12h16M4 17h16" />',
    'chevron'   => '<path d="m9 18 6-6-6-6" />',
    'close'     => '<path d="M6 6l12 12M18 6 6 18" />',
    'plus'      => '<path d="M12 5v14M5 12h14" />',
    'minus'     => '<path d="M5 12h14" />',
    'edit'      => '<path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />',
    'trash'     => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6" />',
    'print'     => '<path d="M6 9V2h12v7" /><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" /><path d="M6 14h12v8H6Z" />',
    'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />',
    'lock'      => '<rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" />',
    'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" /><path d="m9 12 2 2 4-4" />',
    'alert'     => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /><path d="M12 9v4M12 17h.01" />',
    'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />',
];

function icon(string $name, int $size = 20): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . (ICONS[$name] ?? '') . '</svg>';
}

/** Avatar foto atau inisial dengan markup XHTML yang aman. */
function avatar_html(array $row, string $size = ''): string
{
    $class = 'avatar' . ($size !== '' ? ' ' . e($size) : '');
    $foto = basename((string) ($row['foto'] ?? ''));
    if ($foto !== '' && preg_match('/^u[0-9]+_[a-f0-9]{16}\.(jpg|png|webp)$/', $foto)) {
        return '<span class="' . $class . '"><img src="uploads/profil/' . e($foto) . '" alt="Foto profil ' . e($row['NamaUser']) . '" /></span>';
    }
    return '<span class="' . $class . '">' . e(initials($row['NamaUser'])) . '</span>';
}

/** Judul halaman. $actions berisi XHTML tombol (opsional). */
function page_header(string $title, string $description, string $actions = ''): string
{
    $html = '<section class="page-heading"><div><h1>' . e($title) . '</h1><span>' . e($description) . '</span></div>';
    if ($actions !== '') {
        $html .= '<div class="page-actions">' . $actions . '</div>';
    }
    return $html . '</section>';
}

/** Menampilkan hak CRUD yang dimiliki role saat ini pada modul aktif. */
function permission_bar(array $allowed, string $note = ''): string
{
    $labels = ['create' => 'Create', 'read' => 'Read', 'update' => 'Update', 'delete' => 'Delete'];
    $html = '<div class="permission-bar"><span class="permission-title">' . icon('shield', 16) . ' Hak akses Anda</span><div class="permission-chips">';
    foreach ($labels as $action => $label) {
        $on = in_array($action, $allowed, true);
        $html .= '<span class="perm-chip ' . ($on ? 'on' : 'off') . '">' . ($on ? '' : icon('lock', 11)) . $label . '</span>';
    }
    $html .= '</div>';
    if ($note !== '') {
        $html .= '<small>' . e($note) . '</small>';
    }
    return $html . '</div>';
}

function status_badge(string $status): string
{
    return '<span class="status ' . e(strtolower($status)) . '">' . e($status) . '</span>';
}

function empty_row(int $colspan, string $message = 'Data tidak ditemukan.'): string
{
    return '<tr><td colspan="' . $colspan . '" class="empty-cell">' . e($message) . '</td></tr>';
}

/** Kolom pencarian (form GET). $keep = parameter lain yang ikut dipertahankan. */
function search_form(string $value, string $placeholder, array $keep = []): string
{
    $html = '<form class="search" method="get" action="">' . icon('search', 17);
    foreach ($keep as $name => $val) {
        if ($val !== '' && $val !== null) {
            $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($val) . '" />';
        }
    }
    return $html . '<input type="search" name="q" value="' . e($value) . '" placeholder="' . e($placeholder) . '" aria-label="' . e($placeholder) . '" /></form>';
}

/** Atribut selected/checked/disabled versi XHTML. */
function attr_if(bool $condition, string $attribute): string
{
    return $condition ? ' ' . $attribute . '="' . $attribute . '"' : '';
}

# KasirJayaJayaAtma — versi PHP

Aplikasi kasir (Point of Sale) dengan PHP + MySQL. Tampilan dan fiturnya sama dengan prototipe di Figma Make:
login, dashboard, transaksi (kasir & struk), produk, member, laporan, dan data petugas dengan hak akses Admin / Petugas.

## Struktur folder

```
kasir-php/
├── index.php            # halaman login
├── logout.php
├── dashboard.php        # controller tiap halaman (logika + query)
├── transaksi.php
├── produk.php
├── pelanggan.php
├── petugas.php
├── profil.php          # profil dan foto pengguna sendiri
├── cek_ketersediaan.php # cek username untuk form akun Admin
├── laporan.php
├── config/koneksi.php   # pengaturan database
├── includes/
│   ├── init.php         # sesi, hak akses, fungsi bantu
│   └── komponen.php     # ikon & komponen tampilan
├── views/               # template XHTML (*.xhtml) untuk setiap halaman
│   └── partials/        # head, sidebar + navbar, toast
├── assets/
│   ├── css/style.css
│   ├── js/app.js        # sidebar, modal, konfirmasi hapus, keranjang kasir
│   └── img/             # aset dari desain Figma
├── uploads/profil/       # foto profil; folder dibuat otomatis bila perlu
└── database/
    ├── kasir_db.sql                 # skema + data contoh
    ├── migrasi_status_penjualan.sql # hanya untuk database lama
    └── migrasi_foto_profil.sql      # kolom foto untuk database lama
```

Template di `views/` ditulis sebagai XHTML (XML yang well-formed: tag ditutup, atribut berkutip, `selected="selected"`, dll.)
dan di-include oleh file PHP. Di VS Code, file `.xhtml` sudah diatur agar dibuka dengan mode PHP (lihat `.vscode/settings.json`).

## Cara menjalankan

Kebutuhan: PHP 7.4 atau lebih baru (ekstensi `pdo_mysql` dan `mbstring`) dan MySQL/MariaDB. Paling mudah memakai **XAMPP** atau **Laragon**.

1. Buka folder `kasir-php` di VS Code (**File → Open Folder**).
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Impor database: buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/kasir_db.sql` → **Go**.
   (Atau lewat terminal: `mysql -u root < database/kasir_db.sql`)
4. Sesuaikan `config/koneksi.php` bila user/password MySQL Anda berbeda (default XAMPP: `root` tanpa password).
5. Jalankan aplikasi, pilih salah satu:
   - Salin folder `kasir-php` ke `C:\xampp\htdocs\`, lalu buka `http://localhost/kasir-php/`
   - Atau dari terminal VS Code di folder ini: `php -S localhost:8000`, lalu buka `http://localhost:8000`

## Akun contoh

| Nama     | Username    | Password     | Role    |
|----------|-------------|--------------|---------|
| Marshall | `RollBy0`   | `admin123`   | Admin   |
| Derren   | `derrenyip` | `admin123`   | Admin   |
| Ivan     | `Ivan`      | `petugas123` | Petugas |

Password disimpan sebagai hash dan otomatis diperbarui ke `password_hash()` (bcrypt) saat login pertama.
Segera ganti password akun contoh lewat menu **Data Petugas**.

## Memakai database kasir_db yang sudah ada

Jika Anda sudah punya `kasir_db` sendiri, jangan impor `kasir_db.sql` (file itu menghapus tabel lama). Cukup jalankan:

```sql
-- kolom status transaksi
SOURCE database/migrasi_status_penjualan.sql;
-- kolom foto profil (jalankan satu kali)
SOURCE database/migrasi_foto_profil.sql;
-- kolom password harus cukup panjang untuk hash
ALTER TABLE tb_user MODIFY password VARCHAR(255) NOT NULL;
```

Password lama yang masih tersimpan polos atau MD5 tetap bisa dipakai login sekali, lalu otomatis di-hash ulang.

Untuk database yang sudah memiliki kolom status, jalankan hanya migrasi foto profil. Jangan impor ulang `kasir_db.sql` pada database aktif karena file tersebut menghapus tabel lama.

Foto profil disimpan di `uploads/profil/`; pastikan proses PHP dapat menulis ke folder `uploads/`. Foto tidak ikut Git melalui `uploads/.gitignore`. Apache membaca `uploads/profil/.htaccess` untuk mencegah eksekusi skrip; jika memakai Nginx, atur server agar tidak mengeksekusi skrip dari folder upload. Dengan ekstensi GD, foto diproses ulang dan dikecilkan hingga maksimal 400×400 piksel. Jika GD tidak tersedia, gambar valid disimpan langsung setelah pemeriksaan MIME, ukuran, dan struktur gambar; sebaiknya aktifkan GD di server.

## Hak akses

| Modul        | Admin                         | Petugas                                   |
|--------------|-------------------------------|-------------------------------------------|
| Transaksi    | Read, Update (void / retur)   | Read, Create (input transaksi, cetak struk) |
| Produk       | Create, Read, Update, Delete  | Read                                      |
| Member       | Create, Read, Update, Delete  | Create, Read, Update (alamat & telepon)   |
| Data Petugas | Create, Read, Update, Delete  | —                                         |
| Laporan      | Read                          | Read                                      |

Aturan ini dicek di server (`includes/init.php` → `ACCESS`) pada setiap halaman dan setiap aksi form, bukan hanya di tampilan.

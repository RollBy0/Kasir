# KasirJayaJayaAtma - PANDUAN

## Akun Default di Database

| Nama     | Username    | Password     | Role    |
|----------|-------------|--------------|---------|
| Marshall | `RollBy0`   | `admin123`   | Admin   |
| Derren   | `derrenyip` | `admin123`   | Admin   |
| Ivan     | `Ivan`      | `petugas123` | Petugas |

Password disimpan sebagai hash dan otomatis diperbarui ke `password_hash()` (bcrypt).

## Hak akses

| Modul        | Admin                         | Petugas                                    |
|--------------|-------------------------------|------------------------------------------- |
| Transaksi    | Read, Update (void / retur)   | Read, Create (input transaksi, cetak struk)|
| Produk       | Create, Read, Update, Delete  | Read                                       |
| Member       | Create, Read, Update, Delete  | Create, Read, Update (alamat & telepon)    |
| Data Petugas | Create, Read, Update, Delete  | —                                          |
| Laporan      | Read                          | Read                                       |

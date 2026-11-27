<?php
/**
 * Koneksi database (PDO + MySQL).
 * Sesuaikan nilai di bawah dengan pengaturan MySQL Anda (XAMPP/Laragon default: root tanpa password).
 */
const DB_HOST = 'localhost';
const DB_NAME = 'kasir_db';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Koneksi database gagal. Periksa config/koneksi.php dan pastikan database kasir_db sudah diimpor.');
        }
    }
    return $pdo;
}

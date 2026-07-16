<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pencatatan_keuangan');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    try {
        $pdo->query("SELECT role FROM users LIMIT 1");
    } catch (PDOException $e) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user' AFTER name");
            $pdo->exec("UPDATE users SET role = 'admin' WHERE username = 'admin'");
        } catch (PDOException $ex) {
            // Silence migration failure if table doesn't exist yet (e.g. before initial sql import)
        }
    }
} catch (PDOException $e) {
    // Stop execution and show a user-friendly error page if database connection fails
    die("Koneksi database gagal: " . $e->getMessage());
}
?>

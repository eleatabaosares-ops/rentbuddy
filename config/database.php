<?php
/**
 * RentBuddy - Database Configuration
 * Location: config/database.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rentbuddy');

// Corrected BASE_URL to match your local server folder structure
define('BASE_URL', 'http://localhost/website/Rentbuddy/');

define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
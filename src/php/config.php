<?php
/**
 * MediBook - Database Configuration
 * 
 * Reads database credentials from environment variables (Docker)
 * with fallback to default values for local XAMPP development.
 */

// Database credentials from environment variables
$host = getenv('MYSQL_HOST') ?: 'localhost';
$db   = getenv('MYSQL_DATABASE') ?: 'hospital_db';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';
$port = getenv('MYSQL_PORT') ?: '3306';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    // In production, log the error instead of displaying it
    if (getenv('APP_ENV') === 'production') {
        error_log("Database connection failed: " . $e->getMessage());
        die("Service temporarily unavailable. Please try again later.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

// Secure session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    
    if (getenv('APP_ENV') === 'production') {
        ini_set('session.cookie_secure', 1);
    }
}
?>
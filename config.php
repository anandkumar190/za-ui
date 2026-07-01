<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'za-user');
define('DB_PASS', 'Anand@123!');
define('DB_NAME', 'zaviora_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Database connection failed.");
}
<?php
// /adet/api/db.php
// Extremely robust database connection for ByetHost

// LOCAL DEVELOPMENT - Using local XAMPP MySQL
$host = 'localhost';
$db   = 'transit_system';
$user = 'root';
$pass = '';

// BYETHOST PRODUCTION - Uncomment below to use remote server
// $host = 'sql100.byethost33.com';
// $db   = 'b33_41637866_TRANSITGO';
// $user = 'b33_41637866';
// $pass = 'ADMIN123';

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset;timeout=5";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // If we can't connect, return a JSON error immediately
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Database Connection Failed: " . $e->getMessage());
    echo json_encode([
        "authenticated" => false,
        "error" => "Database Connection Failed: " . $e->getMessage()
    ]);
    exit;
}

// Global helper to send consistent JSON responses
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($status, $data) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
?>
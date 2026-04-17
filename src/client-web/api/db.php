<?php
// src/client-web/api/db.php
// Database connection with environment variable support

// Load environment variables from .env file
// Check multiple locations for .env (portable deployment)
$possiblePaths = [
    __DIR__ . '/.env',                          // Local: src/client-web/api/.env
    __DIR__ . '/../../.env',                    // Parent: src/client-web/.env
    __DIR__ . '/../../../.env',                 // Root: src/.env (if src/ downloaded standalone)
    realpath(__DIR__ . '/../../..') . '/.env'   // Project root: ADET/.env (full deployment)
];

$envFile = null;
$env = [];

// Find the first .env file that exists
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $envFile = $path;
        $env = parse_ini_file($envFile);
        break;
    }
}

// Get database credentials from environment or use defaults
$host = $env['DB_HOST'] ?? 'localhost';
$db   = $env['DB_NAME'] ?? 'transit_system';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';

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
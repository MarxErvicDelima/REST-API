<?php
// /adet/api/db.php
// Database connection with environment variable support

// Load environment variables from .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
} else {
    // Fallback to defaults if .env doesn't exist
    $env = [];
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
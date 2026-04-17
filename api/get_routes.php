<?php
/**
 * ========================================================
 * GET_ROUTES.PHP - Unified Schema Version
 * ========================================================
 * Fetches all available route cities from scheduled_trips
 * Works with new unified architecture
 * 
 * Returns: Array of unique origins and destinations
 * ========================================================
 */

require_once 'db.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(405, ["error" => "Method Not Allowed. Use GET."]);
}

try {
    // Query distinct origins from scheduled_trips
    $originStmt = $pdo->query("SELECT DISTINCT origin FROM scheduled_trips ORDER BY origin ASC");
    $origins = $originStmt->fetchAll(PDO::FETCH_COLUMN);

    // Query distinct destinations from scheduled_trips
    $destStmt = $pdo->query("SELECT DISTINCT destination FROM scheduled_trips ORDER BY destination ASC");
    $destinations = $destStmt->fetchAll(PDO::FETCH_COLUMN);

    // Success response
    sendJsonResponse(200, [
        "status" => "success",
        "data" => [
            "origins" => $origins,
            "destinations" => $destinations
        ]
    ]);

} catch (PDOException $e) {
    sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
}
?>


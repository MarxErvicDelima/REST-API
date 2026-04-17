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
<?php
// /adet/api/get_routes.php
// GET endpoint to fetch unique origins and destinations from the database

require_once 'db.php';

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(405, ["error" => "Method Not Allowed. Use GET."]);
}

try {
    // Fetch unique origins
    $originStmt = $pdo->query("SELECT DISTINCT origin FROM routes ORDER BY origin ASC");
    $origins = $originStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch unique destinations
    $destStmt = $pdo->query("SELECT DISTINCT destination FROM routes ORDER BY destination ASC");
    $destinations = $destStmt->fetchAll(PDO::FETCH_COLUMN);

    // Success response with unique route values
    sendJsonResponse(200, [
        "status" => "success",
        "data" => [
            "origins" => $origins,
            "destinations" => $destinations
        ]
    ]);

} catch (PDOException $e) {
    sendJsonResponse(500, ["error" => "Internal Server Error: " . $e->getMessage()]);
}
?>

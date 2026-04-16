<?php
// /adet/api/get_schedules.php
// GET endpoint to fetch bus schedules with filtering

require_once 'db.php';

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(405, ["error" => "Method Not Allowed. Use GET."]);
}

// Get filter parameters from the URL (e.g., ?origin=Manila&destination=Tabaco)
$origin = isset($_GET['origin']) ? $_GET['origin'] : null;
$destination = isset($_GET['destination']) ? $_GET['destination'] : null;

// Validation: Both origin and destination are required for this search
if (!$origin || !$destination) {
    sendJsonResponse(400, ["error" => "Missing required parameters: origin and destination."]);
}

try {
    // SQL Query to join schedules with bus_routes, routes, and buses
    // Note: Requires schedules table to have bus_route_id foreign key
    $sql = "SELECT 
                s.id, 
                s.departure_time, 
                s.arrival_time, 
                s.fare, 
                r.origin, 
                r.destination, 
                r.distance_km,
                b.bus_number, 
                b.bus_type, 
                br.capacity,
                (SELECT COUNT(*) FROM tickets t WHERE t.schedule_id = s.id) as booked_seats
            FROM schedules s
            INNER JOIN bus_routes br ON s.bus_route_id = br.id
            INNER JOIN routes r ON br.route_id = r.id
            INNER JOIN buses b ON br.bus_id = b.id
            WHERE r.origin = :origin 
              AND r.destination = :destination
              AND br.is_active = TRUE
            ORDER BY s.departure_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['origin' => $origin, 'destination' => $destination]);
    $schedules = $stmt->fetchAll();

    // If no schedules found for the route
    if (empty($schedules)) {
        sendJsonResponse(404, ["error" => "No buses are scheduled for that specific route."]);
    }

    // Success response with data
    sendJsonResponse(200, [
        "status" => "success",
        "count" => count($schedules),
        "data" => $schedules
    ]);

} catch (PDOException $e) {
    // Enhanced error message for debugging
    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, 'bus_route_id') !== false || strpos($errorMsg, '1054') !== false) {
        sendJsonResponse(500, [
            "error" => "Database schema mismatch. Missing 'bus_route_id' in schedules table.",
            "hint" => "Execute schema.sql or visit /api/setup.php for diagnostics",
            "details" => $errorMsg
        ]);
    } else {
        sendJsonResponse(500, ["error" => "Internal Server Error: " . $errorMsg]);
    }
}
?>

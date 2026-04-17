<?php
/**
 * ========================================================
 * GET_SCHEDULES.PHP - Unified Schema Version
 * ========================================================
 * Fetches available bus schedules for a given route
 * Works with new unified scheduled_trips table
 * 
 * Parameters:
 *   origin (required) - Departure city
 *   destination (required) - Arrival city
 * 
 * Returns: Array of available schedules with trip info
 * ========================================================
 */

require_once 'db.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(405, ["error" => "Method Not Allowed. Use GET."]);
}

// Get filter parameters
$origin = isset($_GET['origin']) ? trim($_GET['origin']) : null;
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : null;

// Validation: Both origin and destination required
if (!$origin || !$destination) {
    sendJsonResponse(400, ["error" => "Missing required parameters: origin and destination"]);
}

try {
    // Query: Get schedules from unified scheduled_trips table
    // NEW SCHEMA: scheduled_trips contains all trip info + bus info
    $sql = "SELECT 
                s.id as schedule_id,
                st.id as trip_id,
                st.origin,
                st.destination,
                st.distance_km,
                st.bus_code,
                st.bus_type,
                st.capacity,
                st.departure_time,
                st.fare,
                s.available_seats,
                (SELECT COUNT(*) FROM tickets t WHERE t.schedule_id = s.id) as booked_seats,
                (st.capacity - (SELECT COUNT(*) FROM tickets t WHERE t.schedule_id = s.id)) as seats_remaining
            FROM scheduled_trips st
            INNER JOIN schedules s ON st.id = s.scheduled_trip_id
            WHERE st.origin = :origin 
              AND st.destination = :destination
            ORDER BY st.departure_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'origin' => $origin,
        'destination' => $destination
    ]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // No schedules found
    if (empty($schedules)) {
        sendJsonResponse(404, ["error" => "No buses scheduled for that route"]);
    }

    // Success response
    sendJsonResponse(200, [
        "status" => "success",
        "count" => count($schedules),
        "data" => $schedules
    ]);

} catch (PDOException $e) {
    sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
}
?>

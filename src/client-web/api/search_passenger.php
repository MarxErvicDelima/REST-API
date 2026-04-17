<?php
/**
 * ========================================================
 * SEARCH_PASSENGER.PHP - Unified Schema Version
 * ========================================================
 * Searches for passenger bookings by email, name, phone, or trip code
 * Works with new unified scheduled_trips table
 * 
 * Parameters:
 *   q (required) - Email, name, phone, or trip code to search for
 * ========================================================
 */

require_once 'db.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    sendJsonResponse(400, ['status' => 'error', 'error' => 'Search query is required']);
}

try {
    // Query: Search for bookings by email, name, phone, or trip code
    // NEW SCHEMA: scheduled_trips contains all trip info
    $sql = "
        SELECT 
            t.id as ticket_id,
            p.id as passenger_id,
            p.name as passenger_name,
            p.email as passenger_email,
            p.phone as passenger_phone,
            st.origin,
            st.destination,
            st.bus_code,
            st.bus_type,
            st.departure_time,
            st.fare,
            t.seat_number,
            t.trip_code,
            t.booking_time
        FROM tickets t
        JOIN passengers p ON t.passenger_id = p.id
        JOIN schedules s ON t.schedule_id = s.id
        JOIN scheduled_trips st ON s.scheduled_trip_id = st.id
        WHERE p.email LIKE ?
           OR p.name LIKE ?
           OR p.phone LIKE ?
           OR t.trip_code = ?
        ORDER BY t.booking_time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $searchParam = '%' . $query . '%';
    $stmt->execute([
        $searchParam,  // email LIKE
        $searchParam,  // name LIKE
        $searchParam,  // phone LIKE
        $query         // trip_code =
    ]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($bookings) === 0) {
        sendJsonResponse(404, ['status' => 'error', 'error' => 'No bookings found']);
    } else {
        sendJsonResponse(200, [
            'status' => 'success',
            'data' => $bookings
        ]);
    }
} catch (Exception $e) {
    sendJsonResponse(500, ['status' => 'error', 'error' => $e->getMessage()]);
}
?>

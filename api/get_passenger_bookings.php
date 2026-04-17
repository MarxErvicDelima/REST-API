<?php
/**
 * ========================================================
 * GET_PASSENGER_BOOKINGS.PHP - Unified Schema Version
 * ========================================================
 * Fetches all passenger bookings with full details
 * Works with new unified scheduled_trips table
 * ========================================================
 */

require_once 'db.php';

try {
    // Query: Get all bookings from unified schema
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
            st.arrival_time,
            st.fare,
            t.seat_number,
            t.trip_code,
            t.booking_time
        FROM tickets t
        JOIN passengers p ON t.passenger_id = p.id
        JOIN schedules s ON t.schedule_id = s.id
        JOIN scheduled_trips st ON s.scheduled_trip_id = st.id
        ORDER BY t.booking_time DESC
        LIMIT 500
    ";
    
    $result = $pdo->query($sql);
    $bookings = $result->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(200, [
        'status' => 'success',
        'data' => $bookings,
        'count' => count($bookings)
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(500, ['status' => 'error', 'error' => $e->getMessage()]);
}
?>

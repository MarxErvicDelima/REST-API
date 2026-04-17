<?php
/**
 * ========================================================
 * GET_MY_BOOKINGS.PHP - Passenger Bookings by Email
 * ========================================================
 * Fetches all bookings for a specific passenger using email
 * Works with the unified scheduled_trips table
 * ========================================================
 */

require_once 'db.php';

try {
    // Get email from query parameter or POST
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    
    if (empty($email)) {
        sendJsonResponse(400, [
            'status' => 'error', 
            'error' => 'Email parameter is required'
        ]);
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(400, [
            'status' => 'error', 
            'error' => 'Invalid email format'
        ]);
    }
    
    // Query: Get all bookings for a passenger
    $sql = "
        SELECT 
            t.id as ticket_id,
            p.id as passenger_id,
            p.name as passenger_name,
            p.email as passenger_email,
            p.phone as passenger_phone,
            st.id as trip_id,
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
        WHERE p.email = :email
        ORDER BY t.booking_time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($bookings) === 0) {
        sendJsonResponse(200, [
            'status' => 'success',
            'data' => [],
            'count' => 0,
            'message' => 'No bookings found for this email'
        ]);
    } else {
        sendJsonResponse(200, [
            'status' => 'success',
            'data' => $bookings,
            'count' => count($bookings)
        ]);
    }
    
} catch (Exception $e) {
    sendJsonResponse(500, [
        'status' => 'error', 
        'error' => $e->getMessage()
    ]);
}
?>

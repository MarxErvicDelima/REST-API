<?php
/**
 * ========================================================
 * GET_PASSENGER_BOOKINGS.PHP - Unified Schema Version
 * ========================================================
 * Fetches passenger bookings with optional search functionality
 * Works with new unified scheduled_trips table
 * Supports search by trip code via ?q=trip_code parameter
 * ========================================================
 */

require_once 'db.php';

try {
    // Get optional search query
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    // Base query: Get bookings from unified schema
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
    ";
    
    // Add WHERE clause if search query is provided
    if (!empty($query)) {
        $sql .= " WHERE t.trip_code = :trip_code";
    }
    
    $sql .= " ORDER BY t.booking_time DESC";
    
    // Add limit only if not searching (search results might be fewer)
    if (empty($query)) {
        $sql .= " LIMIT 500";
    }
    
    // Execute query
    if (!empty($query)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['trip_code' => $query]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($bookings) === 0) {
            sendJsonResponse(404, ['status' => 'error', 'error' => 'No booking found for this trip code']);
        } else {
            sendJsonResponse(200, [
                'status' => 'success',
                'data' => $bookings,
                'count' => count($bookings),
                'search_query' => $query
            ]);
        }
    } else {
        $result = $pdo->query($sql);
        $bookings = $result->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse(200, [
            'status' => 'success',
            'data' => $bookings,
            'count' => count($bookings)
        ]);
    }
    
} catch (Exception $e) {
    sendJsonResponse(500, ['status' => 'error', 'error' => $e->getMessage()]);
}
?>

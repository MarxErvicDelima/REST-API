<?php
require 'db.php';

try {
    // Get all passenger bookings with details
    $sql = "
        SELECT 
            t.id as ticket_id,
            p.id as passenger_id,
            p.name as passenger_name,
            p.email as passenger_email,
            p.phone as passenger_phone,
            r.origin,
            r.destination,
            b.bus_number,
            b.bus_type,
            s.departure_time,
            s.arrival_time,
            s.fare,
            t.seat_number,
            t.trip_code,
            t.booking_time
        FROM tickets t
        JOIN passengers p ON t.passenger_id = p.id
        JOIN schedules s ON t.schedule_id = s.id
        JOIN bus_routes br ON s.bus_route_id = br.id
        JOIN buses b ON br.bus_id = b.id
        JOIN routes r ON br.route_id = r.id
        ORDER BY t.booking_time DESC
        LIMIT 500
    ";
    
    $result = $pdo->query($sql);
    $bookings = $result->fetchAll();
    
    sendJsonResponse(200, [
        'status' => 'success',
        'data' => $bookings,
        'count' => count($bookings)
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(500, ['status' => 'error', 'error' => $e->getMessage()]);
}
?>

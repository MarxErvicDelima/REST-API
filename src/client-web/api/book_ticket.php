<?php
// src/client-web/api/book_ticket.php
// POST endpoint to book a ticket for a passenger

require_once 'db.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(405, ["error" => "Method Not Allowed. Use POST."]);
}

// Get the raw POST data (JSON payload)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Extract parameters from JSON payload
$passenger_id = isset($data['passenger_id']) ? (int)$data['passenger_id'] : null;
$schedule_id = isset($data['schedule_id']) ? (int)$data['schedule_id'] : null;
$seat_number = isset($data['seat_number']) ? (int)$data['seat_number'] : null;

// Validation: All fields are required
if (!$passenger_id || !$schedule_id || !$seat_number) {
    sendJsonResponse(400, ["error" => "Missing required parameters: passenger_id, schedule_id, and seat_number."]);
}

try {
    // Check if the seat is already taken for the given schedule
    $check_sql = "SELECT id FROM tickets WHERE schedule_id = :schedule_id AND seat_number = :seat_number";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute(['schedule_id' => $schedule_id, 'seat_number' => $seat_number]);
    $existing_ticket = $check_stmt->fetch();

    if ($existing_ticket) {
        // Seat Collision Detection: Conflict (409) if the seat is already booked
        sendJsonResponse(409, ["error" => "Conflict: The requested seat was just taken by someone else."]);
    }

    // Seat Collision Detection: Atomic Insert operation to prevent double-booking
    // Generate simple unique trip code (4 chars: mix of letters and numbers, e.g., AB12)
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $trip_code = '';
    for ($i = 0; $i < 4; $i++) {
        $trip_code .= $characters[mt_rand(0, strlen($characters) - 1)];
    }
    
    $sql = "INSERT INTO tickets (passenger_id, schedule_id, seat_number, trip_code) VALUES (:passenger_id, :schedule_id, :seat_number, :trip_code)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'passenger_id' => $passenger_id,
        'schedule_id' => $schedule_id,
        'seat_number' => $seat_number,
        'trip_code' => $trip_code
    ]);

    // Return 201 Created with ticket summary
    sendJsonResponse(201, [
        "status" => "success",
        "message" => "Booking successful",
        "ticket" => [
            "id" => $pdo->lastInsertId(),
            "passenger_id" => $passenger_id,
            "schedule_id" => $schedule_id,
            "seat_number" => $seat_number,
            "trip_code" => $trip_code
        ]
    ]);

} catch (PDOException $e) {
    // If the error code is 23000 (Integrity constraint violation), it's likely a double-booking race condition
    if ($e->getCode() == '23000') {
        sendJsonResponse(409, ["error" => "The requested seat was just taken by someone else (DB constraint)."]);
    }
    sendJsonResponse(500, ["error" => "Internal Server Error: " . $e->getMessage()]);
}
?>

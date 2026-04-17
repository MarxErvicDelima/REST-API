<?php
// /adet/api/delete_ticket.php
// DELETE endpoint for trip cancellations

require_once 'db.php';

// Check if the request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendJsonResponse(405, ["error" => "Method Not Allowed. Use DELETE."]);
}

// Get the ticket_id from the URL (e.g., ?ticket_id=104)
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null;

// Validation: ticket_id is required
if (!$ticket_id) {
    sendJsonResponse(400, ["error" => "Missing required parameter: ticket_id."]);
}

try {
    // Check if the ticket exists
    $check_sql = "SELECT id FROM tickets WHERE id = :ticket_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute(['ticket_id' => $ticket_id]);
    $ticket = $check_stmt->fetch();

    if (!$ticket) {
        sendJsonResponse(404, ["error" => "Ticket with ID $ticket_id not found."]);
    }

    // Delete the ticket record from the database
    $sql = "DELETE FROM tickets WHERE id = :ticket_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ticket_id' => $ticket_id]);

    // Success response
    sendJsonResponse(200, [
        "status" => "success",
        "message" => "Ticket cancellation successful",
        "ticket_id" => $ticket_id
    ]);

} catch (PDOException $e) {
    sendJsonResponse(500, ["error" => "Internal Server Error: " . $e->getMessage()]);
}
?>

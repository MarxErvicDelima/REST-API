<?php
// /adet/api/create_passenger.php
// POST endpoint to create or get a passenger

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(405, ["error" => "Method Not Allowed. Use POST."]);
}

$data = json_decode(file_get_contents('php://input'), true);

$name = isset($data['name']) ? trim($data['name']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;
$phone = isset($data['phone']) ? trim($data['phone']) : null;

if (!$name) {
    sendJsonResponse(400, ["error" => "Name is required"]);
}

try {
    // Check if passenger exists by email (if provided)
    if ($email) {
        $checkStmt = $pdo->prepare("SELECT id FROM passengers WHERE email = ?");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Update existing passenger
            $updateStmt = $pdo->prepare("UPDATE passengers SET name = ?, phone = ? WHERE id = ?");
            $updateStmt->execute([$name, $phone, $existing['id']]);
            sendJsonResponse(200, ["id" => $existing['id'], "created" => false]);
        }
    }

    // Create new passenger
    $stmt = $pdo->prepare("INSERT INTO passengers (name, email, phone) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $phone]);
    
    sendJsonResponse(201, [
        "status" => "success",
        "id" => $pdo->lastInsertId(),
        "created" => true
    ]);

} catch (PDOException $e) {
    // Handle duplicate email
    if ($e->getCode() == '23000') {
        sendJsonResponse(400, ["error" => "Email already registered"]);
    }
    sendJsonResponse(500, ["error" => "Internal Server Error: " . $e->getMessage()]);
}
?>

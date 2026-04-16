<?php
// /adet/api/passenger_auth.php
// Simplified Passenger Authentication

session_start();
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch($action) {
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if (!$name || !$email) {
            sendJsonResponse(400, ['authenticated' => false, 'error' => 'Missing required fields']);
        }

        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM passengers WHERE email = ?");
            $stmt->execute([$email]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // User exists, just return their info
                $_SESSION['passenger_id'] = $existing['id'];
                $_SESSION['passenger_name'] = $name;
                sendJsonResponse(200, [
                    'authenticated' => true,
                    'user' => ['id' => $existing['id'], 'name' => $name, 'email' => $email]
                ]);
            } else {
                // Insert new passenger
                $stmt = $pdo->prepare("INSERT INTO passengers (name, email, phone) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $phone]);
                
                $passenger_id = $pdo->lastInsertId();
                $_SESSION['passenger_id'] = $passenger_id;
                $_SESSION['passenger_name'] = $name;
                
                sendJsonResponse(201, [
                    'authenticated' => true,
                    'user' => ['id' => $passenger_id, 'name' => $name, 'email' => $email]
                ]);
            }
        } catch (Exception $e) {
            sendJsonResponse(500, ['authenticated' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');

        if (!$email) {
            sendJsonResponse(400, ['authenticated' => false, 'error' => 'Email is required']);
        }

        try {
            $stmt = $pdo->prepare("SELECT id, name, email FROM passengers WHERE email = ?");
            $stmt->execute([$email]);
            $passenger = $stmt->fetch();

            if ($passenger) {
                $_SESSION['passenger_id'] = $passenger['id'];
                $_SESSION['passenger_name'] = $passenger['name'];
                sendJsonResponse(200, [
                    'authenticated' => true,
                    'user' => ['id' => $passenger['id'], 'name' => $passenger['name'], 'email' => $passenger['email']]
                ]);
            } else {
                sendJsonResponse(401, ['authenticated' => false, 'error' => 'Passenger not found. Please register first.']);
            }
        } catch (Exception $e) {
            sendJsonResponse(500, ['authenticated' => false, 'error' => $e->getMessage()]);
        }
        break;
    
    case 'logout':
        session_destroy();
        sendJsonResponse(200, ['authenticated' => false]);
        break;
    
    case 'check':
        if (isset($_SESSION['passenger_id'])) {
            sendJsonResponse(200, [
                'authenticated' => true,
                'user' => ['id' => $_SESSION['passenger_id'], 'name' => $_SESSION['passenger_name']]
            ]);
        } else {
            sendJsonResponse(200, ['authenticated' => false]);
        }
        break;
    
    default:
        sendJsonResponse(400, ['error' => 'Invalid action']);
}
?>
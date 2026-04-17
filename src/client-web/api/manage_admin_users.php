<?php
// src/client-web/api/manage_admin_users.php
// Admin user management (create, update, delete)

session_start();
require_once 'db.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    sendJsonResponse(401, ['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $fullname = $data['fullname'] ?? '';
        
        if (!$username || !$email || !$password) {
            sendJsonResponse(400, ['error' => 'Missing required fields']);
            break;
        }
        
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                sendJsonResponse(409, ['error' => 'Username or email already exists']);
                break;
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("
                INSERT INTO admin_users (username, email, password_hash, full_name, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$username, $email, $passwordHash, $fullname]);
            
            sendJsonResponse(201, ['success' => true, 'message' => 'Admin user created']);
        } catch (Exception $e) {
            sendJsonResponse(500, ['error' => $e->getMessage()]);
        }
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $email = $data['email'] ?? '';
        $fullname = $data['fullname'] ?? '';
        $status = $data['status'] ?? 'active';
        $password = $data['password'] ?? '';
        
        if (!$id) {
            sendJsonResponse(400, ['error' => 'Missing admin ID']);
            break;
        }
        
        try {
            if ($password) {
                // Update with new password
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    UPDATE admin_users 
                    SET email = ?, full_name = ?, status = ?, password_hash = ?
                    WHERE id = ?
                ");
                $stmt->execute([$email, $fullname, $status, $passwordHash, $id]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("
                    UPDATE admin_users 
                    SET email = ?, full_name = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$email, $fullname, $status, $id]);
            }
            
            sendJsonResponse(200, ['success' => true, 'message' => 'Admin user updated']);
        } catch (Exception $e) {
            sendJsonResponse(500, ['error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            sendJsonResponse(400, ['error' => 'Missing admin ID']);
            break;
        }
        
        // Prevent deleting self
        if ($id == $_SESSION['admin_id']) {
            sendJsonResponse(403, ['error' => 'Cannot delete your own account']);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$id]);
            
            sendJsonResponse(200, ['success' => true, 'message' => 'Admin user deleted']);
        } catch (Exception $e) {
            sendJsonResponse(500, ['error' => $e->getMessage()]);
        }
        break;

    default:
        sendJsonResponse(400, ['error' => 'Invalid action']);
}
?>

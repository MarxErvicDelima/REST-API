<?php
// /adet/api/admin_auth.php
// Simplified Admin Authentication

session_start();
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch($action) {
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (!$username || !$password) {
            sendJsonResponse(400, ['authenticated' => false, 'error' => 'Missing username or password']);
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin) {
                // Support both plain-text (legacy) and hashed passwords for backward compatibility
                $passwordValid = false;
                
                // Check if password is bcrypt hash (starts with $2)
                if (strpos($admin['password_hash'], '$2') === 0) {
                    $passwordValid = password_verify($password, $admin['password_hash']);
                } else {
                    // Fallback to plain text for legacy data
                    $passwordValid = ($password === $admin['password_hash']);
                }
                
                if ($passwordValid) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    sendJsonResponse(200, ['authenticated' => true, 'user' => $admin]);
                } else {
                    sendJsonResponse(401, ['authenticated' => false, 'error' => 'Invalid credentials']);
                }
            } else {
                sendJsonResponse(401, ['authenticated' => false, 'error' => 'Invalid credentials']);
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
        if (isset($_SESSION['admin_id'])) {
            sendJsonResponse(200, [
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['admin_id'],
                    'username' => $_SESSION['admin_username'],
                    'full_name' => $_SESSION['admin_name']
                ]
            ]);
        } else {
            sendJsonResponse(200, ['authenticated' => false]);
        }
        break;

    default:
        sendJsonResponse(400, ['error' => 'Invalid action']);
}
?>
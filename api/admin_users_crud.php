<?php
// /adet/api/admin_users_crud.php
// Admin User Management CRUD operations

require_once 'db.php';

// Get action from query parameters
$action = $_GET['action'] ?? '';

// Only allow authenticated admins
session_start();
$adminId = $_SESSION['admin_id'] ?? null;

if (!$adminId && $action !== 'create' && $action !== 'read') {
    sendJsonResponse(401, ['error' => 'Unauthorized']);
}

switch($action) {
    case 'read':
        // Get all admin users
        try {
            $sql = "SELECT id, username, email, full_name, status, last_login, created_at 
                    FROM admin_users 
                    ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll();

            sendJsonResponse(200, [
                'status' => 'success',
                'users' => $users
            ]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ['error' => 'Failed to fetch admin users']);
        }
        break;

    case 'create':
        // Create new admin user
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJsonResponse(405, ['error' => 'Method Not Allowed']);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $full_name = trim($data['full_name'] ?? '');
        $status = $data['status'] ?? 'active';

        if (!$username || !$email || !$password) {
            sendJsonResponse(400, ['error' => 'Missing required fields: username, email, password']);
        }

        try {
            // Check if username or email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
            $check_stmt->execute([$username, $email]);
            if ($check_stmt->fetch()) {
                sendJsonResponse(409, ['error' => 'Username or email already exists']);
            }

            // For now, store password as-is (in production: use bcrypt)
            $sql = "INSERT INTO admin_users (username, email, password_hash, full_name, status) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email, $password, $full_name, $status]);

            sendJsonResponse(201, [
                'status' => 'success',
                'id' => $pdo->lastInsertId(),
                'message' => 'Admin user created successfully'
            ]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ['error' => 'Failed to create admin user']);
        }
        break;

    case 'update':
        // Update admin user
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
            sendJsonResponse(405, ['error' => 'Method Not Allowed']);
        }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(400, ['error' => 'Missing user ID']);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $full_name = trim($data['full_name'] ?? '');
        $status = $data['status'] ?? 'active';
        $password = trim($data['password'] ?? '');

        try {
            if ($password) {
                // Update with new password
                $sql = "UPDATE admin_users SET email = ?, full_name = ?, status = ?, password_hash = ?, updated_at = NOW() 
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$email, $full_name, $status, $password, $id]);
            } else {
                // Update without changing password
                $sql = "UPDATE admin_users SET email = ?, full_name = ?, status = ?, updated_at = NOW() 
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$email, $full_name, $status, $id]);
            }

            sendJsonResponse(200, [
                'status' => 'success',
                'message' => 'Admin user updated successfully'
            ]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ['error' => 'Failed to update admin user']);
        }
        break;

    case 'delete':
        // Delete admin user
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJsonResponse(405, ['error' => 'Method Not Allowed']);
        }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(400, ['error' => 'Missing user ID']);
        }

        // Prevent deleting the last admin
        try {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_users");
            $count_stmt->execute();
            $result = $count_stmt->fetch();
            
            if ($result['count'] <= 1) {
                sendJsonResponse(409, ['error' => 'Cannot delete the last admin user']);
            }

            $sql = "DELETE FROM admin_users WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            sendJsonResponse(200, [
                'status' => 'success',
                'message' => 'Admin user deleted successfully'
            ]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ['error' => 'Failed to delete admin user']);
        }
        break;

    default:
        sendJsonResponse(400, ['error' => 'Invalid action']);
}
?>

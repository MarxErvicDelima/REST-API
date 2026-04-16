<?php
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

try {
    switch ($action) {
        case 'read':
            $stmt = $pdo->query("SELECT id, username, email, full_name, status, last_login FROM admin_users ORDER BY id DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendJsonResponse(200, ['users' => $users]);
            break;

        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
                sendJsonResponse(400, ['error' => 'Missing required fields: username, password, email']);
                return;
            }

            if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
                sendJsonResponse(400, ['error' => 'Username, password, and email cannot be empty']);
                return;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email, full_name, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data['username'],
                    $data['password'],
                    $data['email'],
                    $data['full_name'] ?? '',
                    $data['status'] ?? 'active'
                ]);

                sendJsonResponse(201, ['id' => $pdo->lastInsertId(), 'status' => 'success']);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), '1062') !== false) {
                    sendJsonResponse(400, ['error' => 'Username or email already exists']);
                } else {
                    sendJsonResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
                }
                return;
            }
            break;

        case 'update':
            if (!$id) {
                sendJsonResponse(400, ['error' => 'Missing ID']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $updates = [];
            $params = [];
            
            if (isset($data['username'])) {
                $updates[] = 'username = ?';
                $params[] = $data['username'];
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $updates[] = 'password_hash = ?';
                $params[] = $data['password'];
            }
            if (isset($data['email'])) {
                $updates[] = 'email = ?';
                $params[] = $data['email'];
            }
            if (isset($data['full_name'])) {
                $updates[] = 'full_name = ?';
                $params[] = $data['full_name'];
            }
            if (isset($data['status'])) {
                $updates[] = 'status = ?';
                $params[] = $data['status'];
            }

            if (empty($updates)) {
                sendJsonResponse(400, ['error' => 'No fields to update']);
                return;
            }

            try {
                $params[] = $id;
                $query = "UPDATE admin_users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);

                sendJsonResponse(200, ['updated' => true, 'status' => 'success']);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), '1062') !== false) {
                    sendJsonResponse(400, ['error' => 'Username or email already exists']);
                } else {
                    sendJsonResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
                }
                return;
            }
            break;

        case 'delete':
            if (!$id) {
                sendJsonResponse(400, ['error' => 'Missing ID']);
                return;
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
                $stmt->execute([$id]);

                sendJsonResponse(200, ['deleted' => true, 'status' => 'success']);
            } catch (PDOException $e) {
                sendJsonResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
                return;
            }
            break;

        default:
            sendJsonResponse(400, ['error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Admin CRUD PDO Error: " . $e->getMessage());
    sendJsonResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Admin CRUD Error: " . $e->getMessage());
    sendJsonResponse(500, ['error' => 'Server error: ' . $e->getMessage()]);
}
?>


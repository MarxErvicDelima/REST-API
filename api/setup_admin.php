<?php
/**
 * Setup Admin Credentials
 * This script ensures the admin user exists with username: admin, password: admin123
 */

require_once 'db.php';

try {
    // Check if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo json_encode([
            'status' => 'error',
            'message' => 'admin_users table does not exist. Please import schema_fixed.sql first.'
        ]);
        exit;
    }

    // Insert or update admin user credentials
    $username = 'admin';
    $password = 'admin123';
    $email = 'admin@transitgo.com';
    $fullName = 'System Administrator';

    // Try to update if exists
    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ?, status = 'active' WHERE username = ?");
    $stmt->execute([$password, $username]);

    if ($stmt->rowCount() == 0) {
        // Insert if doesn't exist
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$username, $email, $password, $fullName]);
    }

    // Verify the admin user exists and has correct credentials
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && $admin['password_hash'] === $password) {
        sendJsonResponse(200, [
            'status' => 'success',
            'message' => 'Admin user setup complete',
            'user' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email'],
                'full_name' => $admin['full_name'],
                'status' => $admin['status']
            ]
        ]);
    } else {
        sendJsonResponse(400, [
            'status' => 'error',
            'message' => 'Failed to verify admin credentials'
        ]);
    }

} catch (Exception $e) {
    sendJsonResponse(500, [
        'status' => 'error',
        'message' => 'Setup failed: ' . $e->getMessage()
    ]);
}
?>

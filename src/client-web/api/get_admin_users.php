<?php
// /adet/api/get_admin_users.php
// Fetch all admin users for dashboard display

session_start();
require_once 'db.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    sendJsonResponse(401, ['error' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            username, 
            email, 
            full_name, 
            status, 
            last_login, 
            created_at 
        FROM admin_users 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(200, $admins);
} catch (Exception $e) {
    sendJsonResponse(500, ['error' => $e->getMessage()]);
}
?>

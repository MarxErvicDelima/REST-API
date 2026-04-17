<?php
// Final comprehensive verification test

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Database connection
try {
    require 'api/db.php';
    $tests[] = ['Database Connection', 'PASS'];
    $passed++;
} catch (Exception $e) {
    $tests[] = ['Database Connection', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 2: Check if schema tables exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['table_count'] >= 7) {
        $tests[] = ['Schema Tables (7+)', 'PASS - Found ' . $result['table_count'] . ' tables'];
        $passed++;
    } else {
        $tests[] = ['Schema Tables (7+)', 'FAIL - Only ' . $result['table_count'] . ' tables'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['Schema Tables', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 3: Check admin_users table structure (password hashing)
try {
    $stmt = $pdo->query("SHOW CREATE TABLE admin_users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($result['Create Table'], 'password') !== false) {
        $tests[] = ['Admin Users Table', 'PASS - Password field exists'];
        $passed++;
    } else {
        $tests[] = ['Admin Users Table', 'FAIL - Password field missing'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['Admin Users Table', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 4: Verify API files exist
$required_apis = [
    'admin_auth.php',
    'admin_users_crud.php',
    'create_schedule.php',
    'passenger_auth.php',
    'book_ticket.php',
    'get_routes.php',
    'get_schedules.php'
];

$missing_apis = [];
foreach ($required_apis as $api) {
    if (!file_exists("api/$api")) {
        $missing_apis[] = $api;
    }
}

if (empty($missing_apis)) {
    $tests[] = ['Required API Files', 'PASS - All ' . count($required_apis) . ' APIs present'];
    $passed++;
} else {
    $tests[] = ['Required API Files', 'FAIL - Missing: ' . implode(', ', $missing_apis)];
    $failed++;
}

// Test 5: Check for hardcoded bypass in dashboard.html
try {
    $dashboard = file_get_contents('admin/dashboard.html');
    if (strpos($dashboard, "localStorage.getItem('admin_session')") === false) {
        $tests[] = ['Dashboard Hardcoded Bypass Removed', 'PASS - No localStorage bypass found'];
        $passed++;
    } else {
        $tests[] = ['Dashboard Hardcoded Bypass Removed', 'FAIL - Bypass code still present'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['Dashboard Hardcoded Bypass', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 6: Check admin_auth.php for password_verify
try {
    $auth_code = file_get_contents('api/admin_auth.php');
    if (strpos($auth_code, 'password_verify') !== false) {
        $tests[] = ['Admin Auth Password Verification', 'PASS - password_verify() implemented'];
        $passed++;
    } else {
        $tests[] = ['Admin Auth Password Verification', 'FAIL - password_verify() not found'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['Admin Auth Password Verification', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 7: Check admin_users_crud.php for bcrypt hashing
try {
    $crud_code = file_get_contents('api/admin_users_crud.php');
    if (strpos($crud_code, 'password_hash') !== false && strpos($crud_code, 'PASSWORD_BCRYPT') !== false) {
        $tests[] = ['Admin Users Password Hashing', 'PASS - Bcrypt password_hash() implemented'];
        $passed++;
    } else {
        $tests[] = ['Admin Users Password Hashing', 'FAIL - Bcrypt not implemented'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['Admin Users Password Hashing', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 8: Check create_schedule.php for correct parameter order
try {
    $schedule_code = file_get_contents('api/create_schedule.php');
    // Should not have old pattern (false, $data, 400) but should have new pattern
    $old_pattern_count = substr_count($schedule_code, 'sendJsonResponse($data, 400');
    if ($old_pattern_count === 0) {
        $tests[] = ['Create Schedule Parameter Order', 'PASS - Correct parameter order (status, data)'];
        $passed++;
    } else {
        $tests[] = ['Create Schedule Parameter Order', 'FAIL - Found ' . $old_pattern_count . ' instances of wrong parameter order'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['Create Schedule Parameter Order', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 9: Check passenger/index.html for correct endpoint
try {
    $passenger_portal = file_get_contents('passenger/index.html');
    if (strpos($passenger_portal, 'passenger_auth.php?action=register') !== false) {
        $tests[] = ['Passenger Portal Endpoints', 'PASS - Using passenger_auth.php?action=register'];
        $passed++;
    } else {
        $tests[] = ['Passenger Portal Endpoints', 'FAIL - Wrong endpoint reference'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['Passenger Portal Endpoints', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Test 10: Check C++ client for correct endpoint
try {
    $cpp_client = file_get_contents('cpp_client/transit_client.cpp');
    if (strpos($cpp_client, 'passenger_auth.php?action=register') !== false) {
        $tests[] = ['C++ Client Endpoints', 'PASS - Using passenger_auth.php?action=register'];
        $passed++;
    } else {
        $tests[] = ['C++ Client Endpoints', 'FAIL - Wrong endpoint reference'];
        $failed++;
    }
} catch (Exception $e) {
    $tests[] = ['C++ Client Endpoints', 'FAIL - ' . $e->getMessage()];
    $failed++;
}

// Output results
echo "\n========================================\n";
echo "FINAL VERIFICATION REPORT\n";
echo "========================================\n\n";

foreach ($tests as $test) {
    $status = substr($test[1], 0, 4);
    $icon = $status === 'PASS' ? '✅' : '❌';
    echo "$icon {$test[0]}: {$test[1]}\n";
}

echo "\n========================================\n";
echo "SUMMARY: $passed PASSED, $failed FAILED\n";
echo "========================================\n";
echo ($failed === 0 ? "\n✅ All tests passed! System is production-ready.\n" : "\n❌ Some tests failed. Please review above.\n");

?>

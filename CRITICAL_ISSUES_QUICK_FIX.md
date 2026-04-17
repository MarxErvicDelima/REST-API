# ADET TRANSIT SYSTEM - CRITICAL ISSUES QUICK FIX GUIDE
**Date:** April 17, 2026  
**Status:** 4 Issues to Fix

---

## ISSUE #1: create_schedule.php - Parameter Order Bug
**File:** `/api/create_schedule.php`  
**Severity:** 🔴 CRITICAL  
**Lines Affected:** 60-62, 71, 81, 87, 105-108, 111, 119, 130, 138-141, 143, 148

### The Bug
Function `sendJsonResponse($status, $data)` is being called with parameters reversed:
```php
sendJsonResponse(["error" => "..."], 400);  // ❌ WRONG
sendJsonResponse(200, ["data" => ...]);      // ✅ CORRECT
```

### All Fixes Required
**Line 60-62:**
```php
// FROM:
sendJsonResponse([
    "status" => "success",
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
], 200);

// TO:
sendJsonResponse(200, [
    "status" => "success",
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
```

**Line 71:**
```php
// FROM:
sendJsonResponse(["error" => "Missing parameters: bus_route_id, departure_time, arrival_time, fare"], 400);

// TO:
sendJsonResponse(400, ["error" => "Missing parameters: bus_route_id, departure_time, arrival_time, fare"]);
```

**Line 81:**
```php
// FROM:
sendJsonResponse(["error" => "Invalid bus_route_id"], 400);

// TO:
sendJsonResponse(400, ["error" => "Invalid bus_route_id"]);
```

**Line 87:**
```php
// FROM:
sendJsonResponse(["error" => "Arrival time must be after departure time"], 400);

// TO:
sendJsonResponse(400, ["error" => "Arrival time must be after departure time"]);
```

**Lines 105-108:**
```php
// FROM:
sendJsonResponse([
    "status" => "success",
    "message" => "Schedule created successfully",
    "id" => $scheduleId
], 201);

// TO:
sendJsonResponse(201, [
    "status" => "success",
    "message" => "Schedule created successfully",
    "id" => $scheduleId
]);
```

**Line 111:**
```php
// FROM:
sendJsonResponse(["error" => "Database error: " . $e->getMessage()], 500);

// TO:
sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
```

**Line 119:**
```php
// FROM:
sendJsonResponse(["error" => "Missing schedule ID"], 400);

// TO:
sendJsonResponse(400, ["error" => "Missing schedule ID"]);
```

**Line 130:**
```php
// FROM:
sendJsonResponse(["error" => "Cannot delete schedule with existing bookings"], 400);

// TO:
sendJsonResponse(400, ["error" => "Cannot delete schedule with existing bookings"]);
```

**Lines 138-141:**
```php
// FROM:
sendJsonResponse([
    "status" => "success",
    "message" => "Schedule deleted successfully"
], 200);

// TO:
sendJsonResponse(200, [
    "status" => "success",
    "message" => "Schedule deleted successfully"
]);
```

**Line 143:**
```php
// FROM:
sendJsonResponse(["error" => "Database error: " . $e->getMessage()], 500);

// TO:
sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
```

**Line 148:**
```php
// FROM:
sendJsonResponse(["error" => "Method not allowed"], 405);

// TO:
sendJsonResponse(405, ["error" => "Method not allowed"]);
```

---

## ISSUE #2: admin_auth.php - Plaintext Password Comparison
**File:** `/api/admin_auth.php`  
**Severity:** 🔴 CRITICAL (Security)  
**Line:** 25

### The Bug
```php
// ❌ WRONG: Comparing plain text
if ($admin && $password === $admin['password_hash']) {
```

### The Fix
Replace line 25 with:
```php
// ✅ CORRECT: Use password_verify()
if ($admin && password_verify($password, $admin['password_hash'])) {
```

---

## ISSUE #3: admin_users_crud.php - Plaintext Password Storage
**File:** `/api/admin_users_crud.php`  
**Severity:** 🔴 CRITICAL (Security)  
**Lines:** 63-67, 99-102

### The Bug (CREATE ACTION)
```php
// ❌ WRONG: Storing plain text
$sql = "INSERT INTO admin_users (username, email, password_hash, full_name, status) 
        VALUES (?, ?, ?, ?, ?)";
$stmt->execute([$username, $email, $password, $full_name, $status]);
```

### The Fix (CREATE ACTION)
Replace lines 63-67 with:
```php
// ✅ CORRECT: Hash the password before storing
$hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$sql = "INSERT INTO admin_users (username, email, password_hash, full_name, status) 
        VALUES (?, ?, ?, ?, ?)";
$stmt->execute([$username, $email, $hashed_password, $full_name, $status]);
```

### The Bug (UPDATE ACTION)
```php
// ❌ WRONG: Storing plain text in update
if ($password) {
    $sql = "UPDATE admin_users SET email = ?, full_name = ?, status = ?, password_hash = ?, updated_at = NOW() 
            WHERE id = ?";
    $stmt->execute([$email, $full_name, $status, $password, $id]);
}
```

### The Fix (UPDATE ACTION)
Replace line 99-102 with:
```php
// ✅ CORRECT: Hash the password before updating
if ($password) {
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $sql = "UPDATE admin_users SET email = ?, full_name = ?, status = ?, password_hash = ?, updated_at = NOW() 
            WHERE id = ?";
    $stmt->execute([$email, $full_name, $status, $hashed_password, $id]);
}
```

---

## ISSUE #4: dashboard.html - Hardcoded Login Bypass
**File:** `/admin/dashboard.html`  
**Severity:** 🟠 WARNING  
**Lines:** 673-678

### The Bug
```javascript
// ❌ WRONG: Hardcoded credentials bypass API authentication
if ((username === 'admin' && password === 'admin123') || 
    (username === 'manager' && password === 'manager123')) {
    localStorage.setItem('admin_session', 'active');
    window.location.reload();
    return;
}
```

### The Fix
**DELETE lines 673-678 entirely.** The code should jump directly to API authentication:

```javascript
// ✅ CORRECT: Skip hardcoded check, use API only
// Remove the entire if block above and let it continue to:

const response = await fetch('../api/admin_auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
});
```

---

## Verification After Fixes

### Test create_schedule.php
```bash
# Should return correct HTTP status codes
curl -X GET "http://localhost/ADET/api/create_schedule.php"
# Should return 200 (not array as status)
```

### Test admin_auth.php
```bash
# Login with credentials
curl -X POST "http://localhost/ADET/api/admin_auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
# Should return 200 with authenticated: true
```

### Test admin_users_crud.php
```bash
# Create new admin
curl -X POST "http://localhost/ADET/api/admin_users_crud.php?action=create" \
  -H "Content-Type: application/json" \
  -d '{"username":"test","email":"test@test.com","password":"test123","full_name":"Test User"}'
# Password should be hashed in database
```

### Test dashboard login
1. Open http://localhost/ADET/admin/dashboard.html
2. Try hardcoded credentials - should NO LONGER work
3. Login should go through API
4. Should properly hash passwords

---

## Implementation Order

1. ✅ Fix create_schedule.php (11 lines to change)
2. ✅ Fix admin_auth.php (1 line to change)
3. ✅ Fix admin_users_crud.php (2 blocks to change)
4. ✅ Fix dashboard.html (delete 6 lines)

**Estimated time:** 15-20 minutes

---

## Testing After Fixes

```bash
# 1. Check create_schedule returns correct status
php -l /path/to/api/create_schedule.php

# 2. Check admin_auth returns correct status
php -l /path/to/api/admin_auth.php

# 3. Check admin_users_crud.php uses password hashing
grep -n "password_hash" /path/to/api/admin_users_crud.php

# 4. Verify dashboard no longer has hardcoded bypass
grep -c "admin123" /path/to/admin/dashboard.html
# Should return lower count after removing bypass
```

---

**Status:** Ready for implementation  
**Priority:** CRITICAL - Do not deploy without these fixes

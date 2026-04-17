# ADET Transit System - Database Setup Instructions

## Quick Start (Clean Installation)

### Option 1: Fresh Database (Recommended)
If you don't have existing data:

1. **phpMyAdmin Method:**
   - Open phpMyAdmin
   - Click "New" → Create new database: `transit_system`
   - Select the database
   - Click "Import" tab
   - Choose `schema_unified.sql`
   - Click "Go"
   - ✅ Done!

2. **MySQL Command Line:**
   ```bash
   mysql -u root -p < schema_unified.sql
   ```

3. **ByetHost Method:**
   - Login to phpMyAdmin
   - Select database: `b33_41637866_TRANSITGO`
   - Click "Import" tab
   - Choose `schema_unified.sql`
   - Click "Go"
   - ✅ Done!

---

## Option 2: Existing Database (Has Old Schema)

If you already have a database with old schema and getting this error:
```
#1451 - Cannot delete or update a parent row: a foreign key constraint fails
```

**Follow these steps:**

### Step 1: Backup Your Data (IMPORTANT!)
```bash
mysqldump -u root -p transit_system > backup_before_cleanup.sql
```

### Step 2: Clean Up Old Tables
- Open phpMyAdmin
- Select your database
- Click "SQL" tab
- Copy all SQL from `CLEANUP_OLD_SCHEMA.sql`
- Paste into SQL editor
- Click "Go"
- Wait for completion ✅

### Step 3: Verify Database is Clean
After cleanup, you should see:
```
remaining_tables = 0
```

If not 0, something went wrong. Restore your backup and try again.

### Step 4: Import New Schema
- Click "Import" tab
- Choose `schema_unified.sql`
- Click "Go"
- ✅ Database ready!

---

## Verification

After importing `schema_unified.sql`, verify the setup:

### Check Tables Created
```sql
SELECT TABLE_NAME FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'transit_system'
ORDER BY TABLE_NAME;
```

**Expected result (5 tables):**
```
admin_users
passengers
schedules
scheduled_trips
tickets
```

### Check Admin Users
```sql
SELECT username, email, status FROM admin_users;
```

**Expected result (4 admins):**
```
admin          admin@transitgo.com              active
manager        manager@transitgo.com            active
marx           ervicmarx51504@gmail.com         active
arbie          ancheta@gmail.com                active
```

### Check Sample Data
```sql
SELECT * FROM passengers;
```

**Expected result (3 passengers):**
```
MARX ERVIC CHANLIONGCO DELIMA
Kenneth Borjal
Toni Fowler
```

### Check Routes
```sql
SELECT COUNT(*) as total_trips FROM scheduled_trips;
```

**Expected result:** 28 trips

---

## Troubleshooting

### Error: "Cannot delete or update a parent row"
**Solution:** Run `CLEANUP_OLD_SCHEMA.sql` first to remove old tables

### Error: "Access denied for user"
**Solution:** Make sure you're using correct MySQL credentials

### Error: "Database doesn't exist"
**Solution:** Create database first or use `CREATE DATABASE transit_system;` in SQL

### Error: "Duplicate entry for key"
**Solution:** You may have imported twice. Run `CLEANUP_OLD_SCHEMA.sql` then import again

### Tables won't drop
**Solution:** Make sure foreign key checks are disabled:
```sql
SET FOREIGN_KEY_CHECKS = 0;
```

---

## Database Configuration

Update `/api/db.php` with your credentials:

```php
$host = 'localhost';        // Your MySQL host
$db   = 'transit_system';   // Your database name
$user = 'root';             // Your MySQL username
$pass = '';                 // Your MySQL password
```

Or create `.env` file in `/api/` folder:
```
DB_HOST=localhost
DB_NAME=transit_system
DB_USER=root
DB_PASS=
```

---

## Testing APIs After Setup

### Test Route Discovery
```bash
curl http://localhost/ADET/api/get_routes.php
```

### Test Admin Login
```bash
curl -X POST http://localhost/ADET/api/admin_auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### Test Passenger Registration
```bash
curl -X POST http://localhost/ADET/api/passenger_auth.php?action=register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","phone":"09123456789"}'
```

---

## Default Admin Credentials

```
Username: admin
Password: admin123
Email: admin@transitgo.com
```

⚠️ **Important:** Change these in production!

---

## Support

**Files Reference:**
- `schema_unified.sql` - Main database schema
- `CLEANUP_OLD_SCHEMA.sql` - Cleanup for old databases
- `API_DOCUMENTATION.md` - API reference
- `PROJECT_STRUCTURE.md` - Project overview

**Common Issues:**
- Foreign key errors? → Run CLEANUP_OLD_SCHEMA.sql first
- Database not creating? → Check MySQL service is running
- Tables not appearing? → Verify database name in schema file

---

**Status:** ✅ Ready for deployment

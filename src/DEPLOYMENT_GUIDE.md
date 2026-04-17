# TransitGo - Source Code Deployment Guide

## 📁 Directory Structure

```
src/
├── client-web/
│   ├── admin/
│   │   └── dashboard.html
│   ├── passenger/
│   │   └── index.html
│   └── api/
│       ├── .env.example
│       ├── db.php
│       ├── passenger_auth.php
│       ├── book_ticket.php
│       ├── get_schedules.php
│       └── ... (other API files)
└── client-cpp/
    ├── CMakeLists.txt
    ├── transit_client.cpp
    └── build/
        └── transit_client (compiled binary)
```

---

## 🚀 Quick Start Guide

### **Option 1: Web Clients Only (Recommended for beginners)**

#### Step 1: Setup Database Configuration
```bash
cd src/client-web/api/

# Copy the example .env file
cp .env.example .env

# Edit .env with your database credentials
nano .env
# Update: DB_HOST, DB_USER, DB_PASS, DB_NAME
```

#### Step 2: Deploy to Web Server
```bash
# Copy to your web server's document root
cp -r src/ /var/www/html/transit-go/
# or for XAMPP: cp -r src/ /Applications/XAMPP/xamppfiles/htdocs/transit-go/
```

#### Step 3: Access Web Clients
- **Admin Dashboard**: `http://localhost/transit-go/client-web/admin/dashboard.html`
- **Passenger Portal**: `http://localhost/transit-go/client-web/passenger/index.html`

---

### **Option 2: Complete Setup (Web + C++)**

#### Step 1: Database Configuration
```bash
# Create .env file in your src/ root directory
cd src/
cat > .env << 'EOF'
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=transit_system
EOF
```

#### Step 2: Deploy Web Clients
```bash
# Copy src/client-web/ to your web server
cp -r src/client-web /var/www/html/transit-go/
```

#### Step 3: Build & Configure C++ Client
```bash
cd src/client-cpp/

# Configure the API URL (IMPORTANT!)
# Edit transit_client.cpp, line 12
# const string API_URL = "http://localhost/transit-go/client-web/api";

# Build the project
mkdir -p build
cd build
cmake ..
make

# Run the client
./transit_client
```

---

## ⚙️ Database Configuration

### .env File Locations (checked in order)
1. **src/client-web/api/.env** (local, highest priority)
2. **src/.env** (if src/ is standalone)
3. **Project root/.env** (if full project deployed)

### Example .env File
```ini
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=transit_system
```

### Supported Databases
- MySQL 5.7+
- MariaDB 10.3+
- Percona Server 5.7+

---

## 🔧 Configuring C++ Client

The C++ client requires manual configuration for different deployments:

### Default Configuration
```cpp
const string API_URL = "http://localhost/ADET/src/client-web/api";
```

### Change for Different Deployments

**Local testing on different port:**
```cpp
const string API_URL = "http://localhost:8080/src/client-web/api";
```

**Different directory name:**
```cpp
const string API_URL = "http://localhost/my-transit-app/src/client-web/api";
```

**Remote server:**
```cpp
const string API_URL = "http://transit.example.com/src/client-web/api";
```

### After Changing API URL
```bash
cd src/client-cpp/
cmake --build build/
```

---

## 🗄️ Database Setup

### Create Database Schema
```bash
# Using MySQL CLI
mysql -u root -p < database/schema_unified.sql

# Or if in your web server's phpmyadmin
# 1. Create new database: transit_system
# 2. Import: database/schema_unified.sql
```

### Verify Database Connection
```bash
# Test PHP API connection
curl http://localhost/transit-go/client-web/api/get_routes.php

# If you see JSON response, database is connected ✅
# If you see error, check .env file and database credentials ❌
```

---

## 🧪 Testing

### Web Clients
1. Open Admin Dashboard: `http://localhost/transit-go/client-web/admin/dashboard.html`
2. Try login with test credentials
3. Create a route and schedule
4. Open Passenger Portal: `http://localhost/transit-go/client-web/passenger/index.html`
5. Register and search for buses

### C++ Client
```bash
./src/client-cpp/build/transit_client

# Follow the menu:
# 1. Register as passenger
# 2. Search available buses
# 3. Book a ticket
# 4. View bookings
```

---

## 🔗 API Endpoints

All API endpoints are located in `src/client-web/api/`:

### Authentication
- `passenger_auth.php?action=register` - Register passenger
- `passenger_auth.php?action=login` - Login passenger
- `admin_auth.php?action=login` - Admin login

### Data Access
- `get_routes.php` - Get all available routes
- `get_schedules.php?origin=X&destination=Y` - Search schedules
- `get_my_bookings.php?email=X` - Get passenger bookings

### Booking
- `book_ticket.php` - Create booking
- `delete_ticket.php?ticket_id=X` - Cancel booking

See [API_DOCUMENTATION.md](../API_DOCUMENTATION.md) for complete documentation.

---

## 🐛 Troubleshooting

### Web Client Shows Error: "Failed to fetch"
**Cause**: API is not responding  
**Solution**: 
1. Check if web server is running
2. Verify database connection: `curl http://localhost/transit-go/client-web/api/get_routes.php`
3. Check `.env` file exists and has correct database credentials

### C++ Client: "Connection refused"
**Cause**: API URL is incorrect  
**Solution**:
1. Check if API URL matches your deployment location
2. Edit `src/client-cpp/transit_client.cpp`, line 12
3. Rebuild with `cmake --build src/client-cpp/build/`

### Database Connection Error
**Cause**: .env file not found or invalid credentials  
**Solution**:
1. Verify `.env` exists in one of: `src/client-web/api/`, `src/`, or project root
2. Check credentials: `mysql -u root -p -e "USE transit_system;"`
3. Ensure database and tables exist: `mysql < database/schema_unified.sql`

### Port Already in Use
**Cause**: Another service using port 80 or 3306  
**Solution**:
1. Stop other services: `sudo lsof -i :80` or `:3306`
2. Or use different port in C++ client configuration

---

## 📦 Deployment Checklist

- [ ] .env file created with correct database credentials
- [ ] Database imported: `schema_unified.sql`
- [ ] Web files copied to web server document root
- [ ] Web clients accessible: http://localhost/transit-go/client-web/admin/
- [ ] API responding: curl http://localhost/transit-go/client-web/api/get_routes.php
- [ ] C++ client API URL configured (if building C++)
- [ ] C++ client compiled successfully
- [ ] C++ client connects and communicates with API

---

## 📝 Environment Variables

The `.env` file supports these variables:

| Variable | Default | Description |
|----------|---------|-------------|
| DB_HOST | localhost | MySQL server host |
| DB_USER | root | MySQL username |
| DB_PASS | (empty) | MySQL password |
| DB_NAME | transit_system | Database name |

---

## 🔐 Security Notes

- **Never** commit `.env` file to version control (only `.env.example`)
- **Never** hardcode database credentials in code
- Use strong passwords for database users
- Restrict database access to localhost in production
- Keep PHP and MySQL updated to latest versions
- Consider using HTTPS for production deployments

---

## 📚 Additional Resources

- [API Documentation](../API_DOCUMENTATION.md)
- [Database Setup Guide](../DATABASE_SETUP.md)
- [Requirements Verification](../REQUIREMENTS_VERIFICATION.md)
- [Path Communication Guide](../PATH_COMMUNICATION_VERIFICATION.md)

---

## ✅ Verification

To verify everything is working:

```bash
# 1. Check web server running
sudo service apache2 status  # or nginx, or XAMPP

# 2. Check database running
mysql -u root -e "SELECT 1;"

# 3. Test API endpoint
curl -s http://localhost/transit-go/client-web/api/get_routes.php | jq .

# 4. Build C++ client
cd src/client-cpp/build/
cmake .. && make

# 5. Run C++ client
./transit_client
```

---

**Last Updated**: April 17, 2026  
**Version**: 2.0 (Portable Deployment)  
**Status**: ✅ Production Ready

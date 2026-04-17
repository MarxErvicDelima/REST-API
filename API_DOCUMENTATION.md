# ADET Transit System - API Documentation (Unified Schema v2.0)

## System Overview

**Architecture**: Unified Single-Entry Design
- Database: `scheduled_trips` table combines routes + buses + schedules
- All related data (origin, destination, bus, capacity, times) in one row
- Simplified queries without junction tables

---

## Core System Files (2)

### 1. **db.php** - Database Connection & Helper
**Purpose**: Central database connection and utility functions
- PDO MySQL connection with environment variable support
- Global `sendJsonResponse()` helper for consistent JSON responses
- Connection pooling and error handling

**Key Functions**:
```php
sendJsonResponse($status, $data)  // Send standardized JSON response
```

### 2. **manage_routes_consolidated.php** - Unified Route Management
**Purpose**: Complete CRUD for routes + schedules + buses as single entry
**Methods**: GET, POST, PUT, DELETE
**Query Parameter**: `?action=create|read|update|delete`

**Actions**:
- `POST ?action=create` - Create new route with schedule
- `GET ?action=read` - Get all routes
- `GET ?action=read_one?id=X` - Get single route
- `PUT ?action=update?id=X` - Update route
- `DELETE ?action=delete?id=X` - Delete route

**Request Body (POST)**:
```json
{
  "origin": "Manila",
  "destination": "Tabaco",
  "distance_km": 450.5,
  "bus_code": "BUS-101",
  "bus_type": "Elite",
  "capacity": 40,
  "departure_date": "2026-05-01",
  "departure_time": "08:00:00",
  "arrival_time": "18:00:00",
  "fare": 1200.00
}
```

---

## Passenger Portal APIs (4)

### 3. **passenger_auth.php** - Passenger Authentication
**Purpose**: Register, login, and manage passenger sessions
**Query Parameter**: `?action=register|login|logout|check`

**Actions**:
- `POST ?action=register` - Create new passenger or get existing by email
- `POST ?action=login` - Login passenger by email
- `GET ?action=check` - Verify session status
- `GET ?action=logout` - Destroy session

**Register Request**:
```json
{
  "name": "John Doe",
  "email": "john@email.com",
  "phone": "09123456789"
}
```

**Response**:
```json
{
  "authenticated": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@email.com"
  }
}
```

### 4. **get_routes.php** - Route Discovery
**Purpose**: Get all available origins and destinations
**Method**: GET
**Parameters**: None required

**Response**:
```json
{
  "status": "success",
  "data": {
    "origins": ["Manila", "Tabaco", "Legazpi"],
    "destinations": ["Tabaco", "Manila", "Naga"]
  }
}
```

### 5. **get_schedules.php** - Schedule Search
**Purpose**: Find available schedules for a route
**Method**: GET
**Parameters**: 
- `origin` (required)
- `destination` (required)

**Response**:
```json
{
  "status": "success",
  "count": 2,
  "data": [
    {
      "schedule_id": 1,
      "trip_id": 1,
      "origin": "Manila",
      "destination": "Tabaco",
      "bus_code": "BUS-101",
      "bus_type": "Elite",
      "capacity": 40,
      "departure_time": "2026-05-01 08:00:00",
      "arrival_time": "2026-05-01 18:00:00",
      "fare": 1200.00,
      "available_seats": 40,
      "booked_seats": 0,
      "seats_remaining": 40
    }
  ]
}
```

### 6. **book_ticket.php** - Booking Confirmation
**Purpose**: Create ticket booking for passenger
**Method**: POST

**Request**:
```json
{
  "passenger_id": 1,
  "schedule_id": 1,
  "seat_number": 5
}
```

**Response**:
```json
{
  "status": "success",
  "message": "Booking successful",
  "ticket": {
    "id": 42,
    "passenger_id": 1,
    "schedule_id": 1,
    "seat_number": 5,
    "trip_code": "AB12"
  }
}
```

**Error Responses**:
- `409 Conflict` - Seat already taken
- `400 Bad Request` - Missing/invalid parameters

---

## Admin Portal APIs (3)

### 7. **admin_auth.php** - Admin Authentication
**Purpose**: Admin login, logout, and session management
**Query Parameter**: `?action=login|logout|check`

**Login Request**:
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Response**:
```json
{
  "authenticated": true,
  "user": {
    "id": 1,
    "username": "admin",
    "full_name": "System Administrator"
  }
}
```

### 8. **get_passenger_bookings.php** - All Bookings View
**Purpose**: Fetch all passenger bookings with full details
**Method**: GET
**Parameters**: None
**Limit**: 500 records

**Response**:
```json
{
  "status": "success",
  "count": 42,
  "data": [
    {
      "ticket_id": 1,
      "passenger_id": 1,
      "passenger_name": "John Doe",
      "passenger_email": "john@email.com",
      "passenger_phone": "09123456789",
      "origin": "Manila",
      "destination": "Tabaco",
      "bus_code": "BUS-101",
      "bus_type": "Elite",
      "departure_time": "2026-05-01 08:00:00",
      "arrival_time": "2026-05-01 18:00:00",
      "fare": 1200.00,
      "seat_number": 5,
      "trip_code": "AB12",
      "booking_time": "2026-04-15 10:30:00"
    }
  ]
}
```

### 9. **search_passenger.php** - Booking Search
**Purpose**: Find bookings by trip code
**Method**: GET
**Parameters**: `q` (trip code, required)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "ticket_id": 1,
      "passenger_id": 1,
      "passenger_name": "John Doe",
      "trip_code": "AB12",
      ...
    }
  ]
}
```

---

## Utility APIs (1)

### 10. **delete_ticket.php** - Booking Cancellation
**Purpose**: Cancel/delete a passenger booking
**Method**: DELETE
**Parameters**: `ticket_id` (required)

**Response**:
```json
{
  "status": "success",
  "message": "Ticket cancellation successful",
  "ticket_id": 42
}
```

---

## Error Responses (Standard)

All APIs return consistent error format:

```json
{
  "status": "error",
  "error": "Description of error"
}
```

**Common HTTP Status Codes**:
- `200` - OK (GET/PUT successful)
- `201` - Created (POST successful)
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (auth failed)
- `404` - Not Found (resource missing)
- `405` - Method Not Allowed (wrong HTTP verb)
- `409` - Conflict (seat double-booked)
- `500` - Internal Server Error

---

## Complete Booking Flow

### Passenger Side
```
1. GET /api/get_routes.php
   ↓ Get available origins/destinations
2. GET /api/get_schedules.php?origin=X&destination=Y
   ↓ Get available schedules for route
3. POST /api/passenger_auth.php?action=register
   ↓ Create passenger or login
4. POST /api/book_ticket.php
   ↓ Create booking with seat
5. Response: ticket_id + trip_code
```

### Admin Side
```
1. POST /api/admin_auth.php?action=login
   ↓ Admin login
2. GET /api/get_passenger_bookings.php
   ↓ View all bookings
3. GET /api/search_passenger.php?q=TRIP_CODE
   ↓ Search specific booking
4. DELETE /api/delete_ticket.php?ticket_id=X
   ↓ Cancel booking if needed
5. POST /api/manage_routes_consolidated.php?action=create
   ↓ Add new route/schedule
```

---

## Database Tables (Unified Schema)

### scheduled_trips
```
id (PK)
origin, destination, distance_km
bus_code (UNIQUE), bus_type, capacity
departure_time, arrival_time, fare
created_at
```

### schedules
```
id (PK)
scheduled_trip_id (FK → scheduled_trips)
available_seats
created_at
```

### tickets
```
id (PK)
passenger_id (FK → passengers)
schedule_id (FK → schedules)
seat_number
trip_code (UNIQUE)
booking_time
```

### passengers
```
id (PK)
name, email (UNIQUE), phone
created_at
```

### admin_users
```
id (PK)
username (UNIQUE), email (UNIQUE)
password_hash
full_name, status
last_login, created_at, updated_at
```

---

## Summary

**Total API Files**: 10 (consolidated from 16)
- 2 Core system files
- 4 Passenger portal APIs
- 3 Admin portal APIs
- 1 Utility API

**Key Features**:
- ✅ Unified single-entry database architecture
- ✅ No junction tables (simplified queries)
- ✅ Atomic booking with collision detection
- ✅ Consistent JSON responses
- ✅ Full CRUD on routes/schedules
- ✅ Email-based passenger identification
- ✅ Trip code tracking for bookings

**Status**: Production Ready

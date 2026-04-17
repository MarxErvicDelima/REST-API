# ADET Transit System - Test Data Reference

## Database Seed Data Overview

### Current Expansion (April 17, 2026)
- **Total Scheduled Trips**: 44 routes
- **Unique Routes**: 14 route pairs (bidirectional)
- **Bus Fleet**: 8 buses (BUS-101 through BUS-108)
- **Test Passengers**: 3 sample passengers
- **Sample Bookings**: 1 existing booking

---

## Route Network Map

### Core Routes (Manila Hub)
```
Manila ←→ Tabaco    (450.5 km, 4 schedules, ₱1200)
Manila ←→ Legazpi   (400 km,   4 schedules, ₱1000)
Manila ←→ Daet      (350 km,   4 schedules, ₱900)
Manila ←→ Naga      (380 km,   4 schedules, ₱950)
```

### Regional Connectors
```
Tabaco ←→ Legazpi   (55 km,    4 schedules, ₱400)
Tabaco ←→ Naga      (120 km,   4 schedules, ₱650)    [NEW]
Tabaco ←→ Daet      (175 km,   4 schedules, ₱850)    [NEW]

Legazpi ←→ Naga     (65 km,    6 schedules, ₱450)    [EXPANDED]
Legazpi ←→ Daet     (85 km,    4 schedules, ₱500)    [NEW]

Daet ←→ Naga        (45 km,    4 schedules, ₱350)
```

---

## Bus Fleet Details

| Bus Code | Type | Capacity | Routes Used |
|----------|------|----------|------------|
| BUS-101 | Elite | 40 | Manila-Tabaco, Tabaco-Naga |
| BUS-102 | Economy | 45 | Manila-Legazpi, Tabaco-Legazpi, Daet-Legazpi |
| BUS-103 | Sleeper | 30 | Manila-Daet, Legazpi-Naga, Tabaco-Daet |
| BUS-104 | Aircon | 50 | Manila-Naga, Daet-Naga, Legazpi-Naga |
| BUS-105 | Economy | 35 | Manila-Naga, Daet-Naga, Naga-Daet |
| BUS-106 | Royal | 48 | Manila-Tabaco, Tabaco-Naga, Legazpi-Naga, Tabaco-Daet |
| BUS-107 | Sleeper | 32 | Manila-Legazpi, Tabaco-Legazpi, Tabaco-Naga |
| BUS-108 | Coach | 42 | Manila-Daet, Legazpi-Naga, Daet-Legazpi |

---

## Schedule Time Distribution

### Early Morning (5:00-8:00)
- Tabaco → Daet (05:00)
- Daet → Legazpi (07:30)
- Tabaco → Naga (06:00)
- Manila → Daet (07:00)

### Mid-Morning (8:00-12:00)
- Manila → Legazpi (09:30)
- Manila → Naga (10:00)
- Tabaco → Legazpi (10:00)
- Legazpi → Naga (08:00)

### Afternoon (12:00-18:00)
- Manila → Tabaco (14:00)
- Daet → Naga (12:00)
- Legazpi → Daet (10:00)
- Daet → Legazpi (14:30)

### Evening/Night (18:00-02:00)
- Manila → Legazpi (15:30)
- Manila → Naga (18:00)
- Tabaco → Daet (20:00)
- Legazpi → Daet (17:00)

---

## Pricing by Distance

| Distance | Example Route | Price |
|----------|---------------|-------|
| 45 km | Daet ↔ Naga | ₱350 |
| 55 km | Tabaco ↔ Legazpi | ₱400 |
| 65 km | Legazpi ↔ Naga | ₱450-480 |
| 85 km | Daet ↔ Legazpi | ₱500 |
| 120 km | Tabaco ↔ Naga | ₱650 |
| 175 km | Tabaco ↔ Daet | ₱850 |
| 350 km | Manila ↔ Daet | ₱900 |
| 380 km | Manila ↔ Naga | ₱950 |
| 400 km | Manila ↔ Legazpi | ₱1000 |
| 450.5 km | Manila ↔ Tabaco | ₱1200 |

---

## Sample Passengers

### Existing Passengers
```
1. MARX ERVIC CHANLIONGCO DELIMA
   Email: ervicmarx51504@gmail.com
   Phone: 09161771157
   Bookings: 1 (Ticket ID 1, Trip Code: OE55)

2. Kenneth Borjal
   Email: ken@email.com
   Phone: 12345678910
   Bookings: 0

3. Toni Fowler
   Email: toni@gmail.com
   Phone: 0934567890
   Bookings: 0
```

---

## Verification Queries

### Get Total Statistics
```sql
SELECT 
  (SELECT COUNT(*) FROM scheduled_trips) as total_trips,
  (SELECT COUNT(DISTINCT origin) FROM scheduled_trips) as unique_cities,
  (SELECT COUNT(*) FROM passengers) as total_passengers,
  (SELECT COUNT(*) FROM tickets) as total_bookings,
  (SELECT SUM(capacity) FROM scheduled_trips) as total_capacity;
```

**Expected Result:**
```
total_trips: 44
unique_cities: 5
total_passengers: 3
total_bookings: 1
total_capacity: 1994
```

### List All Available Routes
```sql
SELECT DISTINCT CONCAT(origin, ' → ', destination) as route, 
       COUNT(*) as schedules,
       MIN(fare) as min_fare,
       MAX(capacity) as max_capacity
FROM scheduled_trips
GROUP BY origin, destination
ORDER BY origin, destination;
```

### Routes from Manila
```sql
SELECT origin, destination, distance_km, bus_code, bus_type, 
       capacity, departure_time, fare
FROM scheduled_trips
WHERE origin = 'Manila'
ORDER BY departure_time;
```

### Current Bookings
```sql
SELECT t.trip_code, p.name, p.email, st.origin, st.destination, 
       st.bus_code, t.seat_number, t.booking_time
FROM tickets t
JOIN passengers p ON t.passenger_id = p.id
JOIN schedules s ON t.schedule_id = s.id
JOIN scheduled_trips st ON s.scheduled_trip_id = st.id;
```

**Expected Result:**
```
trip_code: OE55
name: Kenneth Borjal
email: ken@email.com
origin: Manila
destination: Tabaco
bus_code: BUS-101
seat_number: 15
booking_time: 2026-04-15 10:30:00
```

### Available Seats Status
```sql
SELECT st.origin, st.destination, st.bus_code, st.capacity,
       COUNT(t.id) as booked_seats,
       (st.capacity - COUNT(t.id)) as available_seats
FROM scheduled_trips st
LEFT JOIN schedules s ON st.id = s.scheduled_trip_id
LEFT JOIN tickets t ON s.id = t.schedule_id
GROUP BY st.id
ORDER BY st.origin, st.destination;
```

---

## Testing Scenarios

### Scenario 1: Basic Route Search
1. Search for Manila → Tabaco
2. Expected: 2 schedules (08:00 BUS-101, 14:00 BUS-106)
3. Verify: Both show capacity and fare

### Scenario 2: Book Ticket
1. Select Manila → Tabaco, BUS-101, 08:00 schedule
2. Register passenger: test@example.com
3. Book seat #5
4. Expected: Trip code generated (4-char code)

### Scenario 3: Check Booking
1. Search bookings by trip code
2. Should show: passenger info, route, bus, seat, time

### Scenario 4: Test Multiple Bookings
1. Book multiple seats on same trip (verify no double-booking)
2. Book on different routes
3. Verify seat collision detection

### Scenario 5: Complex Routes
1. Search Daet → Legazpi (new connector route)
2. Book on this new route
3. Verify all trip details display correctly

---

## Admin Testing

### Verify All Admins
```sql
SELECT username, email, full_name, status FROM admin_users;
```

### Create New Route
Use dashboard to create new route with all 44 existing + new ones

### View All Bookings
Admin panel should show:
- 1 existing booking (OE55)
- All future passenger bookings

### Search Functionality
Search by trip code: OE55
Should find Kenneth Borjal's booking

---

## Performance Testing

### Total Records to Load
```sql
-- Calculate approximate data volume
SELECT 
  (SELECT COUNT(*) FROM scheduled_trips) * 44 as trip_records,
  (SELECT COUNT(*) FROM schedules) * 44 as schedule_records,
  (SELECT COUNT(*) FROM tickets) * 100 as potential_bookings;
```

### Query Performance
All queries should execute in < 100ms with proper indexes

### Concurrent Users
Test with 10+ simultaneous bookings on same trip
Verify no race conditions or double-bookings

---

## Maintenance Notes

- Seed data uses dates in May 2026 (future dates for testing)
- All buses have unique codes (BUS-101 to BUS-108)
- All sample trips have seats available (except as booked)
- Prices calculated based on 3-5 pesos per km

---

**Last Updated**: April 17, 2026
**Version**: 2.0 (Expanded Test Data)
**Status**: Ready for Testing ✅

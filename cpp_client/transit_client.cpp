#include <iostream>
#include <curl/curl.h>
#include <string>
#include <sstream>
#include <vector>
#include <nlohmann/json.hpp>

using json = nlohmann::json;
using namespace std;

// ===== GLOBAL VARIABLES =====
const string API_URL = "http://localhost/ADET/api";
string currentPassengerId = "";
string currentPassengerName = "";
string currentAdminUsername = "";
bool isAdminLoggedIn = false;

// ===== CALLBACK FUNCTION TO CAPTURE HTTP RESPONSE =====
size_t WriteCallback(void* contents, size_t size, size_t nmemb, string* userp) {
    userp->append((char*)contents, size * nmemb);
    return size * nmemb;
}

// ===== MAKE HTTP REQUEST TO API =====
json makeRequest(string endpoint, string method = "GET", json body = nullptr) {
    CURL* curl = curl_easy_init();
    string readBuffer;
    string postData = "";  // Persist the JSON string until after curl_easy_perform
    
    if (!curl) {
        cout << "❌ Failed to initialize CURL" << endl;
        return json::object();
    }

    string url = API_URL + endpoint;
    curl_easy_setopt(curl, CURLOPT_URL, url.c_str());
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);

    // Set headers
    struct curl_slist* headers = nullptr;
    headers = curl_slist_append(headers, "Content-Type: application/json");
    curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);

    // Set method and body
    if (method == "POST") {
        curl_easy_setopt(curl, CURLOPT_POST, 1L);
        if (!body.is_null()) {
            postData = body.dump();
            curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
        }
    } else if (method == "PUT") {
        curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "PUT");
        if (!body.is_null()) {
            postData = body.dump();
            curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
        }
    } else if (method == "DELETE") {
        curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    // Perform request
    CURLcode res = curl_easy_perform(curl);
    if (res != CURLE_OK) {
        cout << "❌ CURL Error: " << curl_easy_strerror(res) << endl;
        curl_slist_free_all(headers);
        curl_easy_cleanup(curl);
        return json::object();
    }

    // Cleanup
    curl_slist_free_all(headers);
    curl_easy_cleanup(curl);

    // Parse and return JSON response
    try {
        return json::parse(readBuffer);
    } catch (exception& e) {
        cout << "❌ JSON Parse Error: " << e.what() << endl;
        cout << "   Response: " << readBuffer << endl;
        return json::object();
    }
}

// ===== PASSENGER REGISTRATION / LOGIN =====
void registerOrLoginPassenger() {
    string name, email, phone;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Register / Get Passenger Account     ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Full Name: ";
    getline(cin, name);
    
    cout << "Enter Email: ";
    getline(cin, email);
    
    cout << "Enter Phone Number: ";
    getline(cin, phone);

    // Create JSON payload
    json payload = {
        {"name", name},
        {"email", email},
        {"phone", phone}
    };

    // Call API
    json response = makeRequest("/create_passenger.php", "POST", payload);

    if (!response.is_null() && response.contains("id")) {
        if (response["id"].is_string()) {
            currentPassengerId = response["id"].get<string>();
        } else {
            currentPassengerId = to_string(response["id"].get<int>());
        }
        currentPassengerName = name;
        cout << "\n✅ Success! Passenger ID: " << currentPassengerId << endl;
    } else {
        cout << "\n❌ Error: " << response.value("error", "Unknown error") << endl;
    }
}

// ===== SEARCH AVAILABLE BUSES =====
void searchBuses() {
    string origin, destination;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Search Available Buses & Schedules   ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Origin City: ";
    getline(cin, origin);
    
    cout << "Enter Destination City: ";
    getline(cin, destination);

    // Call API
    json response = makeRequest("/get_schedules.php?origin=" + origin + "&destination=" + destination, "GET");

    if (!response.is_null() && response.contains("data")) {
        auto schedules = response["data"];
        
        if (schedules.empty()) {
            cout << "\n❌ No buses found for this route" << endl;
            return;
        }

        cout << "\n┌─────────────────────────────────────────────────────────────┐" << endl;
        cout << "│ Available Schedules                                         │" << endl;
        cout << "├─────────────────────────────────────────────────────────────┤" << endl;

        for (size_t i = 0; i < schedules.size(); i++) {
            auto schedule = schedules[i];
            // Calculate available seats: capacity - booked_seats
            int capacity = schedule["capacity"].get<int>();
            int booked = schedule["booked_seats"].get<int>();
            int available = capacity - booked;
            
            cout << "Schedule #" << i + 1 << ":" << endl;
            cout << "  Schedule ID: " << schedule["id"] << endl;
            cout << "  Bus: " << schedule["bus_number"] << " (" << schedule["bus_type"] << ")" << endl;
            cout << "  Departure: " << schedule["departure_time"] << endl;
            cout << "  Arrival: " << schedule["arrival_time"] << endl;
            cout << "  Fare: ₱" << schedule["fare"] << endl;
            cout << "  Available Seats: " << available << "/" << capacity << endl;
            cout << "─────────────────────────────────────────────────────────────" << endl;
        }
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to fetch schedules") << endl;
    }
}

// ===== BOOK TICKET =====
void bookTicket() {
    if (currentPassengerId.empty()) {
        cout << "\n❌ Please register first!" << endl;
        return;
    }

    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Book a Ticket                        ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    int scheduleId, seatNumber;
    
    cout << "Enter Schedule ID: ";
    cin >> scheduleId;
    
    cout << "Enter Seat Number: ";
    cin >> seatNumber;
    cin.ignore(); // Clear input buffer

    // Prepare JSON payload
    json payload = {
        {"passenger_id", stoi(currentPassengerId)},
        {"schedule_id", scheduleId},
        {"seat_number", seatNumber}
    };

    // Call API
    json response = makeRequest("/book_ticket.php", "POST", payload);

    if (!response.is_null() && response.contains("ticket")) {
        auto ticket = response["ticket"];
        cout << "\n╔════════════════════════════════════════╗" << endl;
        cout << "║  ✅ BOOKING SUCCESSFUL!               ║" << endl;
        cout << "╠════════════════════════════════════════╣" << endl;
        cout << "║ Ticket ID: " << ticket["id"] << endl;
        cout << "║ Trip Code: " << ticket["trip_code"] << endl;
        cout << "║ Passenger: " << currentPassengerName << endl;
        cout << "║ Seat #" << seatNumber << endl;
        cout << "╚════════════════════════════════════════╝" << endl;
    } else if (response.contains("error")) {
        string errorMsg = response["error"];
        if (errorMsg.find("409") != string::npos || errorMsg.find("already taken") != string::npos) {
            cout << "\n❌ Seat Already Taken!" << endl;
        } else {
            cout << "\n❌ Booking Error: " << errorMsg << endl;
        }
    }
}

// ===== GET PASSENGER BOOKINGS =====
void viewMyBookings() {
    if (currentPassengerId.empty()) {
        cout << "\n❌ Please register first!" << endl;
        return;
    }

    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Your Bookings                        ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;

    string tripCode;
    cout << "Enter Your Trip Code (from booking confirmation): ";
    getline(cin, tripCode);

    if (tripCode.empty()) {
        cout << "\n❌ Trip code is required" << endl;
        return;
    }

    // Call API to search by trip code
    json response = makeRequest("/search_passenger.php?q=" + tripCode, "GET");

    if (!response.is_null() && response.contains("data")) {
        auto bookings = response["data"];
        
        if (bookings.empty()) {
            cout << "\n📭 No bookings found with that trip code" << endl;
            return;
        }

        cout << "\n┌─────────────────────────────────────────────────────────────┐" << endl;
        cout << "│ Your Trip Bookings                                          │" << endl;
        cout << "├─────────────────────────────────────────────────────────────┤" << endl;

        for (auto& booking : bookings) {
            cout << "Trip Code: " << booking["trip_code"] << endl;
            cout << "  Route: " << booking["origin"] << " → " << booking["destination"] << endl;
            cout << "  Bus: " << booking["bus_number"] << " (" << booking["bus_type"] << ")" << endl;
            cout << "  Seat: #" << booking["seat_number"] << endl;
            cout << "  Departure: " << booking["departure_time"] << endl;
            cout << "  Arrival: " << booking["arrival_time"] << endl;
            cout << "  Fare: ₱" << booking["fare"] << endl;
            cout << "  Booked: " << booking["booking_time"] << endl;
            cout << "─────────────────────────────────────────────────────────────" << endl;
        }
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to fetch bookings") << endl;
    }
}

// ===== ADMIN LOGIN =====
void adminLogin() {
    string username, password;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Admin Login                          ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Username: ";
    getline(cin, username);
    
    cout << "Enter Password: ";
    getline(cin, password);

    // Create JSON payload
    json payload = {
        {"username", username},
        {"password", password}
    };

    // Call API with action parameter
    json response = makeRequest("/admin_auth.php?action=login", "POST", payload);

    if (!response.is_null() && response.contains("authenticated") && response["authenticated"]) {
        currentAdminUsername = username;
        isAdminLoggedIn = true;
        cout << "\n✅ Admin login successful!" << endl;
    } else {
        cout << "\n❌ Error: " << response.value("error", "Invalid credentials") << endl;
    }
}

// ===== VIEW ALL BUSES =====
void viewAllBuses() {
    json response = makeRequest("/manage_fleet.php", "GET");

    if (!response.is_null() && response.contains("data")) {
        auto buses = response["data"];
        
        if (buses.empty()) {
            cout << "\n❌ No buses found" << endl;
            return;
        }

        cout << "\n┌─────────────────────────────────────────────────────────────┐" << endl;
        cout << "│ Fleet Management - All Buses                                │" << endl;
        cout << "├─────────────────────────────────────────────────────────────┤" << endl;

        for (size_t i = 0; i < buses.size(); i++) {
            auto bus = buses[i];
            cout << "Bus #" << i + 1 << ":" << endl;
            cout << "  ID: " << bus["id"] << endl;
            cout << "  Bus Number: " << bus["bus_number"] << endl;
            cout << "  Type: " << bus["type"] << endl;
            cout << "  Capacity: " << bus["capacity"] << " seats" << endl;
            cout << "  Status: " << bus["status"] << endl;
            cout << "─────────────────────────────────────────────────────────────" << endl;
        }
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to fetch buses") << endl;
    }
}

// ===== ADD NEW BUS =====
void addNewBus() {
    string busNumber, busType;
    int capacity;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Add New Bus                          ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Bus Number (e.g., BUS-101): ";
    getline(cin, busNumber);
    
    cout << "Enter Bus Type:" << endl;
    cout << "  1. Economy" << endl;
    cout << "  2. Aircon" << endl;
    cout << "  3. Sleeper" << endl;
    cout << "  4. Coach" << endl;
    cout << "  5. Minibus" << endl;
    cout << "  6. Van" << endl;
    cout << "  7. Luxury" << endl;
    cout << "  8. Express" << endl;
    cout << "Enter choice (1-8): ";
    int typeChoice;
    cin >> typeChoice;
    cin.ignore();
    
    vector<string> busTypes = {"Economy", "Aircon", "Sleeper", "Coach", "Minibus", "Van", "Luxury", "Express"};
    if (typeChoice < 1 || typeChoice > 8) {
        cout << "\n❌ Invalid bus type selection" << endl;
        return;
    }
    busType = busTypes[typeChoice - 1];
    
    cout << "Enter Capacity (number of seats): ";
    cin >> capacity;
    cin.ignore();

    // Create JSON payload
    json payload = {
        {"bus_number", busNumber},
        {"bus_type", busType},
        {"capacity", capacity}
    };

    // Call API
    json response = makeRequest("/manage_fleet.php", "POST", payload);

    if (!response.is_null() && response.contains("id")) {
        cout << "\n✅ Bus added successfully! Bus ID: " << response["id"] << endl;
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to add bus") << endl;
    }
}

// ===== DELETE BUS =====
void deleteBus() {
    int busId;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Delete Bus                           ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Bus ID to delete: ";
    cin >> busId;
    cin.ignore();

    // Call API
    json response = makeRequest("/manage_fleet.php?id=" + to_string(busId), "DELETE");

    if (!response.is_null() && response.contains("status")) {
        cout << "\n✅ Bus deleted successfully!" << endl;
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to delete bus") << endl;
    }
}

// ===== VIEW ALL ROUTES =====
void viewAllRoutes() {
    json response = makeRequest("/manage_routes.php", "GET");

    if (!response.is_null() && response.contains("data")) {
        auto routes = response["data"];
        
        if (routes.empty()) {
            cout << "\n❌ No routes found" << endl;
            return;
        }

        cout << "\n┌─────────────────────────────────────────────────────────────┐" << endl;
        cout << "│ Route Management - All Routes                               │" << endl;
        cout << "├─────────────────────────────────────────────────────────────┤" << endl;

        for (size_t i = 0; i < routes.size(); i++) {
            auto route = routes[i];
            cout << "Route #" << i + 1 << ":" << endl;
            cout << "  ID: " << route["id"] << endl;
            cout << "  Origin: " << route["origin"] << endl;
            cout << "  Destination: " << route["destination"] << endl;
            cout << "  Distance: " << route["distance"] << " km" << endl;
            cout << "─────────────────────────────────────────────────────────────" << endl;
        }
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to fetch routes") << endl;
    }
}

// ===== ADD NEW ROUTE =====
void addNewRoute() {
    string origin, destination;
    double distance;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Add New Route                        ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Origin City: ";
    getline(cin, origin);
    
    cout << "Enter Destination City: ";
    getline(cin, destination);
    
    cout << "Enter Distance (in km): ";
    cin >> distance;
    cin.ignore();

    // Create JSON payload
    json payload = {
        {"origin", origin},
        {"destination", destination},
        {"distance_km", distance}
    };

    // Call API
    json response = makeRequest("/manage_routes.php", "POST", payload);

    if (!response.is_null() && response.contains("id")) {
        cout << "\n✅ Route added successfully! Route ID: " << response["id"] << endl;
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to add route") << endl;
    }
}

// ===== DELETE ROUTE =====
void deleteRoute() {
    int routeId;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Delete Route                         ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Route ID to delete: ";
    cin >> routeId;
    cin.ignore();

    // Call API
    json response = makeRequest("/manage_routes.php?id=" + to_string(routeId), "DELETE");

    if (!response.is_null() && response.contains("status")) {
        cout << "\n✅ Route deleted successfully!" << endl;
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to delete route") << endl;
    }
}

// ===== VIEW PASSENGER BOOKINGS =====
void viewAllBookings() {
    json response = makeRequest("/get_passenger_bookings.php", "GET");

    if (!response.is_null() && response.contains("data")) {
        auto bookings = response["data"];
        
        if (bookings.empty()) {
            cout << "\n❌ No bookings found" << endl;
            return;
        }

        cout << "\n┌────────────────────────────────────────────────────────────────────┐" << endl;
        cout << "│ All Passenger Bookings                                             │" << endl;
        cout << "├────────────────────────────────────────────────────────────────────┤" << endl;

        for (size_t i = 0; i < bookings.size() && i < 50; i++) {
            auto booking = bookings[i];
            cout << "Booking #" << i + 1 << ":" << endl;
            cout << "  Ticket ID: " << booking["ticket_id"] << endl;
            cout << "  Trip Code: " << booking.value("trip_code", "N/A") << endl;
            cout << "  Passenger: " << booking.value("passenger_name", "N/A") << endl;
            cout << "  Email: " << booking.value("passenger_email", "N/A") << endl;
            cout << "  Phone: " << booking.value("passenger_phone", "N/A") << endl;
            cout << "  Route: " << booking.value("origin", "N/A") << " → " << booking.value("destination", "N/A") << endl;
            cout << "  Bus: " << booking.value("bus_number", "N/A") << " (" << booking.value("bus_type", "N/A") << ")" << endl;
            cout << "  Seat: " << booking["seat_number"] << endl;
            cout << "  Departure: " << booking.value("departure_time", "N/A") << endl;
            cout << "  Fare: ₱" << booking.value("fare", "0") << endl;
            cout << "  Booked: " << booking.value("booking_time", "N/A") << endl;
            cout << "─────────────────────────────────────────────────────────────────" << endl;
        }
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to fetch bookings") << endl;
    }
}

// ===== SEARCH PASSENGER BY TRIP CODE =====
void searchPassenger() {
    string tripCode;
    
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Search Passenger by Trip Code        ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    cout << "Enter Trip Code: ";
    getline(cin, tripCode);

    if (tripCode.empty()) {
        cout << "\n❌ Trip code cannot be empty" << endl;
        return;
    }

    // Call API
    json response = makeRequest("/search_passenger.php?q=" + tripCode, "GET");

    if (!response.is_null() && response.contains("data")) {
        auto bookings = response["data"];
        
        if (bookings.empty()) {
            cout << "\n❌ No bookings found for trip code: " << tripCode << endl;
            return;
        }

        cout << "\n┌────────────────────────────────────────────────────────────────────┐" << endl;
        cout << "│ Search Results for Trip Code: " << tripCode << endl;
        cout << "├────────────────────────────────────────────────────────────────────┤" << endl;

        for (size_t i = 0; i < bookings.size(); i++) {
            auto booking = bookings[i];
            cout << "\nBooking #" << i + 1 << ":" << endl;
            cout << "  Ticket ID: " << booking["ticket_id"] << endl;
            cout << "  Trip Code: " << booking.value("trip_code", "N/A") << endl;
            cout << "  ───────── PASSENGER INFO ─────────" << endl;
            cout << "  Name: " << booking.value("passenger_name", "N/A") << endl;
            cout << "  Email: " << booking.value("passenger_email", "N/A") << endl;
            cout << "  Phone: " << booking.value("passenger_phone", "N/A") << endl;
            cout << "  ───────── JOURNEY INFO ──────────" << endl;
            cout << "  Route: " << booking.value("origin", "N/A") << " → " << booking.value("destination", "N/A") << endl;
            cout << "  Bus: " << booking.value("bus_number", "N/A") << " (" << booking.value("bus_type", "N/A") << ")" << endl;
            cout << "  Seat: " << booking["seat_number"] << endl;
            cout << "  Departure: " << booking.value("departure_time", "N/A") << endl;
            cout << "  Arrival: " << booking.value("arrival_time", "N/A") << endl;
            cout << "  Fare: ₱" << booking.value("fare", "0") << endl;
            cout << "  Booked: " << booking.value("booking_time", "N/A") << endl;
            cout << "─────────────────────────────────────────────────────────────────" << endl;
        }
    } else if (response.contains("error")) {
        cout << "\n❌ Error: " << response.value("error", "Failed to search passenger") << endl;
    } else {
        cout << "\n❌ Error: Could not search passenger" << endl;
    }
}

// ===== DISPLAY ADMIN MENU =====
void displayAdminMenu() {
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  🛡️  ADMIN PANEL - Transit Management  ║" << endl;
    cout << "╠════════════════════════════════════════╣" << endl;
    cout << "║ Logged in as: " << currentAdminUsername << endl;
    cout << "╠════════════════════════════════════════╣" << endl;
    cout << "║ PASSENGER MANAGEMENT:                  ║" << endl;
    cout << "║ 1. Search Passenger by Trip Code      ║" << endl;
    cout << "║ 2. View All Bookings                  ║" << endl;
    cout << "║                                        ║" << endl;
    cout << "║ FLEET MANAGEMENT:                      ║" << endl;
    cout << "║ 3. View All Buses                     ║" << endl;
    cout << "║ 4. Add New Bus                        ║" << endl;
    cout << "║ 5. Delete Bus                         ║" << endl;
    cout << "║                                        ║" << endl;
    cout << "║ ROUTE MANAGEMENT:                     ║" << endl;
    cout << "║ 6. View All Routes                    ║" << endl;
    cout << "║ 7. Add New Route                      ║" << endl;
    cout << "║ 8. Delete Route                       ║" << endl;
    cout << "║                                        ║" << endl;
    cout << "║ 9. Logout                             ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
}

// ===== ADMIN MODE =====
void adminMode() {
    int choice;
    bool adminRunning = true;

    while (adminRunning) {
        displayAdminMenu();
        
        cout << "Enter your choice (1-9): ";
        cin >> choice;
        cin.ignore();

        switch (choice) {
            case 1:
                searchPassenger();
                break;
            case 2:
                viewAllBookings();
                break;
            case 3:
                viewAllBuses();
                break;
            case 4:
                addNewBus();
                break;
            case 5:
                deleteBus();
                break;
            case 6:
                viewAllRoutes();
                break;
            case 7:
                addNewRoute();
                break;
            case 8:
                deleteRoute();
                break;
            case 9:
                isAdminLoggedIn = false;
                currentAdminUsername = "";
                cout << "\n👋 Admin logout successful!" << endl;
                adminRunning = false;
                break;
            default:
                cout << "\n❌ Invalid choice. Please try again." << endl;
        }
    }
}

// ===== CANCEL BOOKING =====
void cancelBooking() {
    if (currentPassengerId.empty()) {
        cout << "\n❌ Please register first!" << endl;
        return;
    }

    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  Cancel a Booking                     ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
    
    int ticketId;
    cout << "Enter Ticket ID to Cancel: ";
    cin >> ticketId;
    cin.ignore();

    // Call API
    json response = makeRequest("/delete_ticket.php?ticket_id=" + to_string(ticketId), "DELETE");

    if (!response.is_null() && response.contains("status")) {
        cout << "\n✅ Booking cancelled successfully!" << endl;
    } else {
        cout << "\n❌ Error: " << response.value("error", "Failed to cancel") << endl;
    }
}

// ===== DISPLAY MENU =====
void displayMenu() {
    cout << "\n╔════════════════════════════════════════╗" << endl;
    cout << "║  🚌 TRANSITGO - Ticket Booking System ║" << endl;
    cout << "╠════════════════════════════════════════╣" << endl;
    cout << "║ Logged in as: " << (currentPassengerName.empty() ? "Guest" : currentPassengerName) << endl;
    cout << "╠════════════════════════════════════════╣" << endl;
    cout << "║ PASSENGER:                             ║" << endl;
    cout << "║ 1. Register / Login Passenger         ║" << endl;
    cout << "║ 2. Search Buses                       ║" << endl;
    cout << "║ 3. Book Ticket                        ║" << endl;
    cout << "║ 4. View My Bookings                   ║" << endl;
    cout << "║ 5. Cancel Booking                     ║" << endl;
    cout << "║                                        ║" << endl;
    cout << "║ ADMIN:                                ║" << endl;
    cout << "║ 6. Admin Login                        ║" << endl;
    cout << "║                                        ║" << endl;
    cout << "║ 7. Exit                               ║" << endl;
    cout << "╚════════════════════════════════════════╝" << endl;
}

// ===== MAIN PROGRAM =====
int main() {
    int choice;
    bool running = true;

    cout << "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" << endl;
    cout << "  Welcome to TransitGo Console Client" << endl;
    cout << "  C++ REST API Consumer" << endl;
    cout << "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" << endl;

    while (running) {
        displayMenu();
        
        cout << "Enter your choice (1-7): ";
        cin >> choice;
        cin.ignore(); // Clear input buffer after reading integer

        switch (choice) {
            case 1:
                registerOrLoginPassenger();
                break;
            case 2:
                searchBuses();
                break;
            case 3:
                bookTicket();
                break;
            case 4:
                viewMyBookings();
                break;
            case 5:
                cancelBooking();
                break;
            case 6:
                adminLogin();
                if (isAdminLoggedIn) {
                    adminMode();
                }
                break;
            case 7:
                cout << "\n👋 Thank you for using TransitGo! Goodbye!" << endl;
                running = false;
                break;
            default:
                cout << "\n❌ Invalid choice. Please try again." << endl;
        }
    }

    return 0;
}

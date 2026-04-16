cmake_minimum_required(VERSION 3.10)
project(TransitGoClient)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

# Find required packages
find_package(CURL REQUIRED)
find_package(nlohmann_json 3.2.0 QUIET)

# If nlohmann_json not found via find_package, try header-only approach
if(NOT nlohmann_json_FOUND)
    # For header-only library, just ensure include path
    include_directories(/usr/local/include)
endif()

# Include directories
include_directories(${CURL_INCLUDE_DIR})

# Add the executable
add_executable(transit_client transit_client.cpp)

# Link libraries
target_link_libraries(transit_client ${CURL_LIBRARIES})
if(nlohmann_json_FOUND)
    target_link_libraries(transit_client nlohmann_json::nlohmann_json)
endif()

# Print compilation info
message(STATUS "TransitGo C++ Client - Build Configuration")
message(STATUS "CURL Found: ${CURL_FOUND}")
message(STATUS "CURL Version: ${CURL_VERSION_STRING}")
message(STATUS "C++ Standard: ${CMAKE_CXX_STANDARD}")

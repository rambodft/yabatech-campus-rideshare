<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->passenger_id) || empty($data->pickup_latitude)) {
    Response::error("Required fields missing", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Check wallet balance
$check = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
$check->execute([$data->passenger_id]);
$user = $check->fetch(PDO::FETCH_ASSOC);

if ($user['wallet_balance'] < $data->estimated_fare) {
    Response::error("Insufficient wallet balance", 400);
}

// Find nearby drivers (using Haversine formula)
$drivers_query = "SELECT 
    dl.driver_id,
    u.first_name,
    u.last_name,
    v.vehicle_make,
    v.vehicle_model,
    v.plate_number,
    dl.latitude,
    dl.longitude,
    (6371 * acos(cos(radians(?)) * cos(radians(dl.latitude)) * 
     cos(radians(dl.longitude) - radians(?)) + 
     sin(radians(?)) * sin(radians(dl.latitude)))) AS distance
FROM driver_locations dl
INNER JOIN users u ON dl.driver_id = u.user_id
INNER JOIN vehicles v ON u.user_id = v.user_id
WHERE dl.is_available = 1
AND v.verification_status = 'verified'
AND v.is_active = 1
HAVING distance <= 5
ORDER BY distance
LIMIT 10";

$drivers_stmt = $conn->prepare($drivers_query);
$drivers_stmt->execute([
    $data->pickup_latitude,
    $data->pickup_longitude,
    $data->pickup_latitude
]);

$nearby_drivers = $drivers_stmt->fetchAll(PDO::FETCH_ASSOC);

Response::success([
    'nearby_drivers' => $nearby_drivers,
    'drivers_found' => count($nearby_drivers)
], "Nearby drivers found");
?>
```

---

## 🧪 TEST THE APIs WITH POSTMAN

### **Test 1: Login**
```
POST http://localhost/yabatech_rideshare/api/auth/login.php

Body (raw JSON):
{
    "email": "chidi.okafor@student.yabatech.edu.ng",
    "password": "Password123"
}
```

### **Test 2: Get Balance**
```
GET http://localhost/yabatech_rideshare/api/wallet/get_balance.php?user_id=1
```

### **Test 3: Create Ride Request**
```
POST http://localhost/yabatech_rideshare/api/rides/create_request.php

Body (raw JSON):
{
    "passenger_id": 1,
    "pickup_latitude": 6.5150,
    "pickup_longitude": 3.3889,
    "dropoff_latitude": 6.5244,
    "dropoff_longitude": 3.3792,
    "estimated_distance": 2.5,
    "estimated_fare": 225.00
}
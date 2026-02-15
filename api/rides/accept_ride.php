<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->driver_id) || empty($data->passenger_id) || empty($data->vehicle_id)) {
    Response::error("Required fields missing", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Check if driver is available
$check = $conn->prepare("SELECT is_available FROM driver_locations WHERE driver_id = ?");
$check->execute([$data->driver_id]);
$driver = $check->fetch(PDO::FETCH_ASSOC);

if (!$driver || !$driver['is_available']) {
    Response::error("Driver not available", 400);
}

// Create ride
$query = "INSERT INTO rides (driver_id, passenger_id, vehicle_id, pickup_location_name, 
          pickup_latitude, pickup_longitude, dropoff_location_name, dropoff_latitude, 
          dropoff_longitude, estimated_distance, fare_amount, ride_status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'accepted')";

$stmt = $conn->prepare($query);
$stmt->execute([
    $data->driver_id,
    $data->passenger_id,
    $data->vehicle_id,
    $data->pickup_location_name,
    $data->pickup_latitude,
    $data->pickup_longitude,
    $data->dropoff_location_name,
    $data->dropoff_latitude,
    $data->dropoff_longitude,
    $data->estimated_distance,
    $data->fare_amount
]);

$ride_id = $conn->lastInsertId();

// Update driver availability
$update = $conn->prepare("UPDATE driver_locations SET is_available = 0 WHERE driver_id = ?");
$update->execute([$data->driver_id]);

Response::success([
    'ride_id' => $ride_id,
    'status' => 'accepted'
], "Ride accepted successfully", 201);
?>
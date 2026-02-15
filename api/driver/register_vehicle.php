<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->user_id) || empty($data->vehicle_make) || empty($data->plate_number)) {
    Response::error("Required fields missing", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Check if plate number already exists
$check = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE plate_number = ?");
$check->execute([$data->plate_number]);
if ($check->rowCount() > 0) {
    Response::error("Plate number already registered", 400);
}

$query = "INSERT INTO vehicles (user_id, vehicle_make, vehicle_model, vehicle_year, 
          vehicle_color, plate_number, vehicle_type, seating_capacity) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->execute([
    $data->user_id,
    $data->vehicle_make,
    $data->vehicle_model ?? '',
    $data->vehicle_year ?? date('Y'),
    $data->vehicle_color ?? '',
    $data->plate_number,
    $data->vehicle_type ?? 'sedan',
    $data->seating_capacity ?? 4
]);

$vehicle_id = $conn->lastInsertId();

// Update user as driver
$update = $conn->prepare("UPDATE users SET is_driver = 1 WHERE user_id = ?");
$update->execute([$data->user_id]);

Response::success([
    'vehicle_id' => $vehicle_id,
    'status' => 'pending'
], "Vehicle registered successfully", 201);
?>
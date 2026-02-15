<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->driver_id) || empty($data->latitude) || empty($data->longitude)) {
    Response::error("Driver ID and coordinates required", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Check if driver location exists
$check = $conn->prepare("SELECT driver_id FROM driver_locations WHERE driver_id = ?");
$check->execute([$data->driver_id]);

if ($check->rowCount() > 0) {
    // Update existing location
    $query = "UPDATE driver_locations SET latitude = ?, longitude = ?, updated_at = NOW() WHERE driver_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$data->latitude, $data->longitude, $data->driver_id]);
} else {
    // Insert new location
    $query = "INSERT INTO driver_locations (driver_id, latitude, longitude) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$data->driver_id, $data->latitude, $data->longitude]);
}

Response::success([
    'driver_id' => $data->driver_id,
    'latitude' => $data->latitude,
    'longitude' => $data->longitude
], "Location updated");
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ride_id) || empty($data->cancelled_by)) {
    Response::error("Ride ID and cancelled_by required", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Get ride details
$ride_query = "SELECT driver_id FROM rides WHERE ride_id = ?";
$ride_stmt = $conn->prepare($ride_query);
$ride_stmt->execute([$data->ride_id]);
$ride = $ride_stmt->fetch(PDO::FETCH_ASSOC);

if (!$ride) {
    Response::error("Ride not found", 404);
}

// Update ride status
$query = "UPDATE rides SET ride_status = 'cancelled' WHERE ride_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$data->ride_id]);

// Make driver available again
$avail = $conn->prepare("UPDATE driver_locations SET is_available = 1 WHERE driver_id = ?");
$avail->execute([$ride['driver_id']]);

Response::success([
    'ride_id' => $data->ride_id,
    'cancelled_by' => $data->cancelled_by
], "Ride cancelled successfully");
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ride_id) || empty($data->status)) {
    Response::error("Ride ID and status required", 400);
}

$allowed_statuses = ['accepted', 'in_progress', 'completed', 'cancelled'];
if (!in_array($data->status, $allowed_statuses)) {
    Response::error("Invalid status", 400);
}

$db = new Database();
$conn = $db->getConnection();

$query = "UPDATE rides SET ride_status = ? WHERE ride_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$data->status, $data->ride_id]);

Response::success([
    'ride_id' => $data->ride_id,
    'new_status' => $data->status
], "Ride status updated");
?>
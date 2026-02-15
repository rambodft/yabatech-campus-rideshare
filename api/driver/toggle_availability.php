<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->driver_id)) {
    Response::error("Driver ID required", 400);
}

$db = new Database();
$conn = $db->getConnection();

$new_status = $data->is_available ? 1 : 0;

$query = "UPDATE driver_locations SET is_available = ? WHERE driver_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$new_status, $data->driver_id]);

Response::success([
    'driver_id' => $data->driver_id,
    'is_available' => (bool)$new_status
], "Availability updated");
?>
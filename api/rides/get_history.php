<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

if (empty($_GET['user_id'])) {
    Response::error("User ID required", 400);
}

$db = new Database();
$conn = $db->getConnection();

$query = "SELECT 
    r.*,
    d.first_name as driver_first_name,
    d.last_name as driver_last_name,
    p.first_name as passenger_first_name,
    p.last_name as passenger_last_name,
    v.vehicle_make,
    v.vehicle_model,
    v.plate_number
FROM rides r
INNER JOIN users d ON r.driver_id = d.user_id
INNER JOIN users p ON r.passenger_id = p.user_id
INNER JOIN vehicles v ON r.vehicle_id = v.vehicle_id
WHERE r.driver_id = ? OR r.passenger_id = ?
ORDER BY r.created_at DESC
LIMIT 50";

$stmt = $conn->prepare($query);
$stmt->execute([$_GET['user_id'], $_GET['user_id']]);
$rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

Response::success([
    'rides' => $rides,
    'total' => count($rides)
], "Ride history retrieved");
?>
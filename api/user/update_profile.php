<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->user_id)) {
    Response::error("User ID required", 400);
}

$db = new Database();
$conn = $db->getConnection();

$updates = [];
$params = [];

if (isset($data->first_name)) {
    $updates[] = "first_name = ?";
    $params[] = $data->first_name;
}
if (isset($data->last_name)) {
    $updates[] = "last_name = ?";
    $params[] = $data->last_name;
}
if (isset($data->phone_number)) {
    $updates[] = "phone_number = ?";
    $params[] = $data->phone_number;
}
if (isset($data->department)) {
    $updates[] = "department = ?";
    $params[] = $data->department;
}

if (empty($updates)) {
    Response::error("No fields to update", 400);
}

$params[] = $data->user_id;
$query = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute($params);

Response::success([
    'user_id' => $data->user_id,
    'updated_fields' => count($updates)
], "Profile updated successfully");
?>
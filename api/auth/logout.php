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

$query = "UPDATE users SET auth_token = NULL WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$data->user_id]);

Response::success(null, "Logged out successfully");
?>
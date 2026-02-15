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

$query = "SELECT user_id, wallet_balance, first_name, last_name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['user_id']]);

if ($stmt->rowCount() === 0) {
    Response::error("User not found", 404);
}

$result = $stmt->fetch(PDO::FETCH_ASSOC);

Response::success($result, "Balance retrieved");
?>
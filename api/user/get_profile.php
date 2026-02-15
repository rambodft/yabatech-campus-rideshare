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

$query = "SELECT user_id, matric_number, staff_id, first_name, last_name, email, 
          phone_number, user_type, is_driver, profile_picture, department, faculty, 
          verification_status, account_status, wallet_balance, average_rating, 
          total_ratings, created_at, last_login 
          FROM users WHERE user_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$_GET['user_id']]);

if ($stmt->rowCount() === 0) {
    Response::error("User not found", 404);
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get vehicles if driver
if ($user['is_driver']) {
    $vehicles_query = "SELECT * FROM vehicles WHERE user_id = ?";
    $vehicles_stmt = $conn->prepare($vehicles_query);
    $vehicles_stmt->execute([$_GET['user_id']]);
    $user['vehicles'] = $vehicles_stmt->fetchAll(PDO::FETCH_ASSOC);
}

Response::success($user, "Profile retrieved");
?>
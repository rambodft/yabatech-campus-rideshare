<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password)) {
    Response::error("Email and password required", 400);
}

$db = new Database();
$conn = $db->getConnection();

$query = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$data->email]);

if ($stmt->rowCount() === 0) {
    Response::error("Invalid credentials", 401);
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($data->password, $user['password_hash'])) {
    Response::error("Invalid credentials", 401);
}

if ($user['account_status'] !== 'active') {
    Response::error("Account is " . $user['account_status'], 403);
}

$auth_token = bin2hex(random_bytes(32));

$update = $conn->prepare("UPDATE users SET auth_token = ?, last_login = NOW() WHERE user_id = ?");
$update->execute([$auth_token, $user['user_id']]);

unset($user['password_hash']);

Response::success([
    'user' => $user,
    'auth_token' => $auth_token
], "Login successful");
?>
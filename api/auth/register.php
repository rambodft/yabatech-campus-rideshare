<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password) || empty($data->first_name)) {
    Response::error("All fields required", 400);
}

if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    Response::error("Invalid email", 400);
}

$db = new Database();
$conn = $db->getConnection();

$check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$check->execute([$data->email]);
if ($check->rowCount() > 0) {
    Response::error("Email already registered", 400);
}

$password_hash = password_hash($data->password, PASSWORD_BCRYPT);

$query = "INSERT INTO users (matric_number, staff_id, first_name, last_name, email, phone_number, password_hash, user_type) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->execute([
    $data->matric_number ?? null,
    $data->staff_id ?? null,
    $data->first_name,
    $data->last_name ?? '',
    $data->email,
    $data->phone_number ?? '',
    $password_hash,
    $data->user_type ?? 'student'
]);

Response::success([
    'user_id' => $conn->lastInsertId(),
    'email' => $data->email
], "Registration successful", 201);
?>
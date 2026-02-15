<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->user_id) || empty($data->amount)) {
    Response::error("User ID and amount required", 400);
}

if ($data->amount < 100) {
    Response::error("Minimum top-up is ₦100", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Get current balance
$balance_query = "SELECT wallet_balance FROM users WHERE user_id = ?";
$balance_stmt = $conn->prepare($balance_query);
$balance_stmt->execute([$data->user_id]);
$current_balance = $balance_stmt->fetch(PDO::FETCH_ASSOC)['wallet_balance'];

// Update wallet
$new_balance = $current_balance + $data->amount;
$update = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE user_id = ?");
$update->execute([$new_balance, $data->user_id]);

// Record transaction
$trans_query = "INSERT INTO wallet_transactions (user_id, transaction_type, amount, 
                transaction_category, balance_before, balance_after, status) 
                VALUES (?, 'credit', ?, 'top_up', ?, ?, 'successful')";
$trans_stmt = $conn->prepare($trans_query);
$trans_stmt->execute([$data->user_id, $data->amount, $current_balance, $new_balance]);

Response::success([
    'user_id' => $data->user_id,
    'amount_added' => $data->amount,
    'old_balance' => $current_balance,
    'new_balance' => $new_balance,
    'transaction_id' => $conn->lastInsertId()
], "Wallet topped up successfully");
?>
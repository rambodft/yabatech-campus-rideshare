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

$query = "SELECT * FROM wallet_transactions 
          WHERE user_id = ? 
          ORDER BY created_at DESC 
          LIMIT 50";

$stmt = $conn->prepare($query);
$stmt->execute([$_GET['user_id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

Response::success([
    'transactions' => $transactions,
    'total' => count($transactions)
], "Transactions retrieved");
?>
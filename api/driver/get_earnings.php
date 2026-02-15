<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

if (empty($_GET['driver_id'])) {
    Response::error("Driver ID required", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Get total rides
$rides_query = "SELECT COUNT(*) as total_rides, SUM(fare_amount) as total_fares 
                FROM rides 
                WHERE driver_id = ? AND ride_status = 'completed'";
$rides_stmt = $conn->prepare($rides_query);
$rides_stmt->execute([$_GET['driver_id']]);
$ride_stats = $rides_stmt->fetch(PDO::FETCH_ASSOC);

// Get total earnings from transactions
$earnings_query = "SELECT SUM(amount) as total_earnings 
                   FROM wallet_transactions 
                   WHERE user_id = ? AND transaction_category = 'ride_earning' AND status = 'successful'";
$earnings_stmt = $conn->prepare($earnings_query);
$earnings_stmt->execute([$_GET['driver_id']]);
$earnings = $earnings_stmt->fetch(PDO::FETCH_ASSOC);

// Get current wallet balance
$balance_query = "SELECT wallet_balance FROM users WHERE user_id = ?";
$balance_stmt = $conn->prepare($balance_query);
$balance_stmt->execute([$_GET['driver_id']]);
$balance = $balance_stmt->fetch(PDO::FETCH_ASSOC);

Response::success([
    'total_rides' => $ride_stats['total_rides'] ?? 0,
    'total_fares' => $ride_stats['total_fares'] ?? 0,
    'total_earnings' => $earnings['total_earnings'] ?? 0,
    'current_balance' => $balance['wallet_balance'] ?? 0,
    'average_per_ride' => $ride_stats['total_rides'] > 0 ? 
        round(($earnings['total_earnings'] ?? 0) / $ride_stats['total_rides'], 2) : 0
], "Earnings retrieved");
?>
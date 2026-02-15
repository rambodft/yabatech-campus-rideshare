<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';
require_once '../../config/response.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->ride_id)) {
    Response::error("Ride ID required", 400);
}

$db = new Database();
$conn = $db->getConnection();

// Get ride details
$ride_query = "SELECT * FROM rides WHERE ride_id = ?";
$ride_stmt = $conn->prepare($ride_query);
$ride_stmt->execute([$data->ride_id]);
$ride = $ride_stmt->fetch(PDO::FETCH_ASSOC);

if (!$ride) {
    Response::error("Ride not found", 404);
}

// Calculate amounts
$fare = $ride['fare_amount'];
$commission = $fare * 0.15; // 15% commission
$driver_earnings = $fare - $commission;

// Start transaction
$conn->beginTransaction();

try {
    // Update ride status
    $update_ride = $conn->prepare("UPDATE rides SET ride_status = 'completed', payment_status = 'completed' WHERE ride_id = ?");
    $update_ride->execute([$data->ride_id]);
    
    // Deduct from passenger wallet
    $deduct = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
    $deduct->execute([$fare, $ride['passenger_id']]);
    
    // Add to driver wallet
    $credit = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
    $credit->execute([$driver_earnings, $ride['driver_id']]);
    
    // Get balances for transaction records
    $passenger_balance = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
    $passenger_balance->execute([$ride['passenger_id']]);
    $passenger_new_balance = $passenger_balance->fetch(PDO::FETCH_ASSOC)['wallet_balance'];
    
    $driver_balance = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
    $driver_balance->execute([$ride['driver_id']]);
    $driver_new_balance = $driver_balance->fetch(PDO::FETCH_ASSOC)['wallet_balance'];
    
    // Record transactions
    $trans_query = "INSERT INTO wallet_transactions (user_id, transaction_type, amount, transaction_category, balance_before, balance_after, status) VALUES (?, ?, ?, ?, ?, ?, 'successful')";
    
    // Passenger transaction
    $trans_stmt = $conn->prepare($trans_query);
    $trans_stmt->execute([$ride['passenger_id'], 'debit', $fare, 'ride_payment', $passenger_new_balance + $fare, $passenger_new_balance]);
    
    // Driver transaction
    $trans_stmt->execute([$ride['driver_id'], 'credit', $driver_earnings, 'ride_earning', $driver_new_balance - $driver_earnings, $driver_new_balance]);
    
    // Make driver available again
    $avail = $conn->prepare("UPDATE driver_locations SET is_available = 1 WHERE driver_id = ?");
    $avail->execute([$ride['driver_id']]);
    
    $conn->commit();
    
    Response::success([
        'ride_id' => $data->ride_id,
        'fare' => $fare,
        'commission' => $commission,
        'driver_earnings' => $driver_earnings,
        'passenger_new_balance' => $passenger_new_balance,
        'driver_new_balance' => $driver_new_balance
    ], "Ride completed successfully");
    
} catch (Exception $e) {
    $conn->rollBack();
    Response::error("Transaction failed: " . $e->getMessage(), 500);
}
?>
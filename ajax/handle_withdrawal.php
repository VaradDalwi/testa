<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$amount = (float)$_POST['amount'];
$payment_method = $_POST['payment_method'];
$payment_details = $_POST['payment_details'] ?? '';

// Validate available balance
$stmt = $conn->prepare("SELECT SUM(amount) as balance 
    FROM instructor_earnings 
    WHERE instructor_id = ? AND status = 'cleared'");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$balance = $stmt->get_result()->fetch_assoc()['balance'];

if ($amount > $balance) {
    echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
    exit;
}

// Create withdrawal request
$stmt = $conn->prepare("INSERT INTO withdrawal_requests 
    (instructor_id, amount, payment_method, payment_details) 
    VALUES (?, ?, ?, ?)");
$stmt->bind_param("idss", $instructor_id, $amount, $payment_method, $payment_details);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Withdrawal request submitted']);
?>
